#!/usr/bin/env python3
"""
User Feedback System for Academy Chatbot
"""

import json
import sqlite3
from datetime import datetime
from collections import defaultdict
import os

class FeedbackSystem:
    def __init__(self, db_path="chatbot_feedback.db"):
        self.db_path = db_path
        self.init_database()
    
    def init_database(self):
        """Initialize SQLite database for feedback"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        # Create feedback table
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS feedback (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                interaction_id TEXT,
                user_message TEXT NOT NULL,
                bot_response TEXT NOT NULL,
                intent TEXT,
                confidence REAL,
                rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
                comment TEXT,
                category TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                session_id TEXT,
                language TEXT
            )
        ''')
        
        # Create feedback summary table
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS feedback_summary (
                date DATE PRIMARY KEY,
                total_feedback INTEGER DEFAULT 0,
                avg_rating REAL DEFAULT 0.0,
                rating_distribution TEXT,
                common_issues TEXT,
                improvement_suggestions TEXT
            )
        ''')
        
        # Create improvement suggestions table
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS improvement_suggestions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                intent TEXT,
                issue_type TEXT,
                suggestion TEXT,
                priority TEXT,
                status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                resolved_at DATETIME
            )
        ''')
        
        conn.commit()
        conn.close()
    
    def log_feedback(self, interaction_id, user_message, bot_response, intent, 
                      confidence, rating, comment=None, category=None, session_id=None, language="id"):
        """Log user feedback"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            INSERT INTO feedback 
            (interaction_id, user_message, bot_response, intent, confidence, rating, comment, category, session_id, language)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ''', (interaction_id, user_message, bot_response, intent, confidence, rating, comment, category, session_id, language))
        
        conn.commit()
        conn.close()
        
        # Update summary
        self.update_feedback_summary()
    
    def update_feedback_summary(self):
        """Update daily feedback summary"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        today = datetime.now().date()
        
        # Get today's feedback
        cursor.execute('''
            SELECT COUNT(*) as total, AVG(rating) as avg_rating
            FROM feedback 
            WHERE DATE(timestamp) = ?
        ''', (today,))
        
        result = cursor.fetchone()
        if result and result[0] > 0:
            total_feedback, avg_rating = result
            
            # Get rating distribution
            cursor.execute('''
                SELECT rating, COUNT(*) as count
                FROM feedback 
                WHERE DATE(timestamp) = ?
                GROUP BY rating
                ORDER BY rating
            ''', (today,))
            
            distribution = {str(row[0]): row[1] for row in cursor.fetchall()}
            
            # Get common issues (low ratings)
            cursor.execute('''
                SELECT comment, COUNT(*) as count
                FROM feedback 
                WHERE DATE(timestamp) = ? AND rating <= 2 AND comment IS NOT NULL
                GROUP BY comment
                ORDER BY count DESC
                LIMIT 5
            ''', (today,))
            
            common_issues = [f"{row[0]} ({row[1]}x)" for row in cursor.fetchall()]
            
            # Update summary
            cursor.execute('''
                INSERT OR REPLACE INTO feedback_summary 
                (date, total_feedback, avg_rating, rating_distribution, common_issues)
                VALUES (?, ?, ?, ?, ?)
            ''', (today, total_feedback, avg_rating, json.dumps(distribution), json.dumps(common_issues)))
        
        conn.commit()
        conn.close()
    
    def get_feedback_report(self, days=7):
        """Get comprehensive feedback report"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        start_date = datetime.now() - datetime.timedelta(days=days)
        
        # Overall statistics
        cursor.execute('''
            SELECT COUNT(*) as total_feedback, AVG(rating) as avg_rating
            FROM feedback 
            WHERE timestamp >= ?
        ''', (start_date,))
        
        overall = cursor.fetchone()
        
        # Rating distribution
        cursor.execute('''
            SELECT rating, COUNT(*) as count
            FROM feedback 
            WHERE timestamp >= ?
            GROUP BY rating
            ORDER BY rating
        ''', (start_date,))
        
        rating_dist = cursor.fetchall()
        
        # Issues by category
        cursor.execute('''
            SELECT category, COUNT(*) as count, AVG(rating) as avg_rating
            FROM feedback 
            WHERE timestamp >= ? AND category IS NOT NULL
            GROUP BY category
            ORDER BY avg_rating ASC
        ''', (start_date,))
        
        category_issues = cursor.fetchall()
        
        # Low rated feedback with comments
        cursor.execute('''
            SELECT intent, rating, comment, timestamp
            FROM feedback 
            WHERE timestamp >= ? AND rating <= 2
            ORDER BY timestamp DESC
            LIMIT 20
        ''', (start_date,))
        
        low_rated = cursor.fetchall()
        
        # Improvement suggestions
        cursor.execute('''
            SELECT intent, issue_type, suggestion, priority, status
            FROM improvement_suggestions
            ORDER BY priority, created_at DESC
        ''')
        
        suggestions = cursor.fetchall()
        
        conn.close()
        
        return {
            "overall": {
                "total_feedback": overall[0] if overall else 0,
                "avg_rating": round(overall[1] if overall else 0, 2),
                "period_days": days
            },
            "rating_distribution": rating_dist,
            "category_issues": category_issues,
            "low_rated_feedback": low_rated,
            "improvement_suggestions": suggestions
        }
    
    def generate_improvement_suggestions(self):
        """Generate improvement suggestions based on feedback"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        # Get intents with low ratings
        cursor.execute('''
            SELECT intent, AVG(rating) as avg_rating, COUNT(*) as count
            FROM feedback 
            WHERE rating <= 3
            GROUP BY intent
            HAVING count >= 3
            ORDER BY avg_rating ASC
        ''')
        
        problem_intents = cursor.fetchall()
        
        for intent, avg_rating, count in problem_intents:
            # Determine issue type
            if avg_rating <= 2:
                issue_type = "critical"
                priority = "high"
            elif avg_rating <= 2.5:
                issue_type = "major"
                priority = "medium"
            else:
                issue_type = "minor"
                priority = "low"
            
            # Generate suggestion
            suggestion = self.generate_suggestion_for_intent(intent, avg_rating, count)
            
            # Insert suggestion
            cursor.execute('''
                INSERT INTO improvement_suggestions 
                (intent, issue_type, suggestion, priority, status)
                VALUES (?, ?, ?, ?, ?)
            ''', (intent, issue_type, suggestion, priority, "pending"))
        
        conn.commit()
        conn.close()
    
    def generate_suggestion_for_intent(self, intent, avg_rating, count):
        """Generate specific suggestion for intent"""
        suggestions = {
            "about_academy": "Consider adding more detailed information about academy history, mission, and values. Expand responses to include success stories and testimonials.",
            "benefits_joining": "Enhance responses with specific examples of success stories, networking opportunities, and career progression paths. Add quantifiable benefits.",
            "programs_info": "Provide more detailed program descriptions including curriculum, duration, prerequisites, and expected outcomes. Add success metrics and alumni testimonials.",
            "registration_steps": "Create step-by-step visual guides or video tutorials. Add troubleshooting tips for common registration issues.",
            "schedule_info": "Include more detailed location information, transportation options, and class preparation tips. Add calendar integration suggestions.",
            "pricing_info": "Provide transparent pricing breakdown, payment options, and value proposition. Add comparison with competitors if available.",
            "login_help": "Create visual login guides, password reset tutorials, and account recovery options. Add security best practices.",
            "technical_support": "Develop comprehensive troubleshooting guides, FAQ for common issues, and escalation procedures.",
            "greeting": "Personalize greetings based on time of day and returning users. Add contextual help suggestions."
        }
        
        base_suggestion = suggestions.get(intent, "Review and enhance response accuracy and completeness for this intent.")
        
        return f"{base_suggestion} Current average rating: {avg_rating:.2f} from {count} reviews."
    
    def get_top_issues(self, days=7):
        """Get top issues from feedback"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        start_date = datetime.now() - datetime.timedelta(days=days)
        
        cursor.execute('''
            SELECT comment, COUNT(*) as count, AVG(rating) as avg_rating
            FROM feedback 
            WHERE timestamp >= ? AND rating <= 3 AND comment IS NOT NULL
            GROUP BY comment
            ORDER BY count DESC, avg_rating ASC
            LIMIT 10
        ''', (start_date,))
        
        issues = cursor.fetchall()
        conn.close()
        
        return issues
    
    def mark_suggestion_resolved(self, suggestion_id):
        """Mark improvement suggestion as resolved"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            UPDATE improvement_suggestions 
            SET status = 'resolved', resolved_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ''', (suggestion_id,))
        
        conn.commit()
        conn.close()

# API endpoints for feedback system
def create_feedback_routes(app, feedback_system):
    """Create Flask routes for feedback system"""
    
    @app.route('/feedback', methods=['POST'])
    def submit_feedback():
        """Submit user feedback"""
        try:
            data = request.get_json()
            
            feedback_system.log_feedback(
                interaction_id=data.get('interaction_id'),
                user_message=data.get('user_message'),
                bot_response=data.get('bot_response'),
                intent=data.get('intent'),
                confidence=data.get('confidence'),
                rating=data.get('rating'),
                comment=data.get('comment'),
                category=data.get('category'),
                session_id=data.get('session_id'),
                language=data.get('language', 'id')
            )
            
            return jsonify({"status": "success", "message": "Feedback submitted successfully"})
            
        except Exception as e:
            return jsonify({"status": "error", "message": str(e)}), 500
    
    @app.route('/feedback/report')
    def feedback_report():
        """Get feedback report"""
        try:
            days = request.args.get('days', 7, type=int)
            report = feedback_system.get_feedback_report(days)
            return jsonify(report)
        except Exception as e:
            return jsonify({"status": "error", "message": str(e)}), 500
    
    @app.route('/feedback/suggestions', methods=['POST'])
    def generate_suggestions():
        """Generate improvement suggestions"""
        try:
            feedback_system.generate_improvement_suggestions()
            return jsonify({"status": "success", "message": "Suggestions generated successfully"})
        except Exception as e:
            return jsonify({"status": "error", "message": str(e)}), 500
    
    @app.route('/feedback/issues')
    def top_issues():
        """Get top issues from feedback"""
        try:
            days = request.args.get('days', 7, type=int)
            issues = feedback_system.get_top_issues(days)
            return jsonify({"issues": issues})
        except Exception as e:
            return jsonify({"status": "error", "message": str(e)}), 500

if __name__ == "__main__":
    # Test the feedback system
    feedback = FeedbackSystem()
    
    # Sample feedback
    feedback.log_feedback(
        "test_123",
        "apa itu academy",
        "Academy Next Top Model adalah academy modeling...",
        "about_academy",
        0.9,
        4,
        "Good response but could be more detailed",
        "content_quality",
        "session_456"
    )
    
    # Get report
    report = feedback.get_feedback_report(7)
    print("Feedback Report:")
    print(json.dumps(report, indent=2))
