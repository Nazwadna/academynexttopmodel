#!/usr/bin/env python3
"""
Analytics and Learning System for Academy Chatbot
"""

import json
import sqlite3
import time
from datetime import datetime, timedelta
from collections import defaultdict, Counter
import os

class ChatbotAnalytics:
    def __init__(self, db_path="chatbot_analytics.db"):
        self.db_path = db_path
        self.init_database()
    
    def init_database(self):
        """Initialize SQLite database for analytics"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        # Create tables
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS interactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                user_message TEXT NOT NULL,
                bot_response TEXT NOT NULL,
                intent TEXT,
                confidence REAL,
                source TEXT,
                response_time REAL,
                user_feedback INTEGER,
                language TEXT,
                session_id TEXT
            )
        ''')
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS intent_performance (
                intent TEXT PRIMARY KEY,
                total_queries INTEGER DEFAULT 0,
                successful_queries INTEGER DEFAULT 0,
                avg_confidence REAL DEFAULT 0.0,
                last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ''')
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS keyword_performance (
                keyword TEXT PRIMARY KEY,
                hit_count INTEGER DEFAULT 0,
                success_rate REAL DEFAULT 0.0,
                last_used DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ''')
        
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS user_sessions (
                session_id TEXT PRIMARY KEY,
                start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                end_time DATETIME,
                message_count INTEGER DEFAULT 0,
                avg_satisfaction REAL DEFAULT 0.0
            )
        ''')
        
        conn.commit()
        conn.close()
    
    def log_interaction(self, user_message, bot_response, intent, confidence, 
                        source, response_time, language="id", session_id=None):
        """Log user interaction for analytics"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            INSERT INTO interactions 
            (user_message, bot_response, intent, confidence, source, response_time, language, session_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ''', (user_message, bot_response, intent, confidence, source, response_time, language, session_id))
        
        # Update intent performance
        if intent:
            cursor.execute('''
                INSERT OR REPLACE INTO intent_performance 
                (intent, total_queries, successful_queries, avg_confidence, last_updated)
                VALUES (
                    COALESCE((SELECT total_queries FROM intent_performance WHERE intent = ?), 0) + 1,
                    COALESCE((SELECT successful_queries FROM intent_performance WHERE intent = ?), 0) + 1,
                    ?,
                    CURRENT_TIMESTAMP
                )
            ''', (intent, intent, confidence))
        
        conn.commit()
        conn.close()
    
    def log_user_feedback(self, interaction_id, feedback_score):
        """Log user feedback (1-5 scale)"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            UPDATE interactions SET user_feedback = ? WHERE id = ?
        ''', (feedback_score, interaction_id))
        
        conn.commit()
        conn.close()
    
    def get_analytics_dashboard(self, days=7):
        """Get comprehensive analytics data"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        # Date filter
        start_date = datetime.now() - timedelta(days=days)
        
        # Total interactions
        cursor.execute('''
            SELECT COUNT(*) FROM interactions 
            WHERE timestamp >= ?
        ''', (start_date,))
        total_interactions = cursor.fetchone()[0]
        
        # Average response time
        cursor.execute('''
            SELECT AVG(response_time) FROM interactions 
            WHERE timestamp >= ?
        ''', (start_date,))
        avg_response_time = cursor.fetchone()[0] or 0
        
        # Top intents
        cursor.execute('''
            SELECT intent, COUNT(*) as count 
            FROM interactions 
            WHERE timestamp >= ?
            GROUP BY intent 
            ORDER BY count DESC 
            LIMIT 10
        ''', (start_date,))
        top_intents = cursor.fetchall()
        
        # Language distribution
        cursor.execute('''
            SELECT language, COUNT(*) as count 
            FROM interactions 
            WHERE timestamp >= ?
            GROUP BY language
        ''', (start_date,))
        language_dist = cursor.fetchall()
        
        # Average satisfaction
        cursor.execute('''
            SELECT AVG(user_feedback) FROM interactions 
            WHERE timestamp >= ? AND user_feedback IS NOT NULL
        ''', (start_date,))
        avg_satisfaction = cursor.fetchone()[0] or 0
        
        # Daily activity
        cursor.execute('''
            SELECT DATE(timestamp) as date, COUNT(*) as count
            FROM interactions 
            WHERE timestamp >= ?
            GROUP BY DATE(timestamp)
            ORDER BY date
        ''', (start_date,))
        daily_activity = cursor.fetchall()
        
        conn.close()
        
        return {
            "total_interactions": total_interactions,
            "avg_response_time": round(avg_response_time, 3),
            "top_intents": top_intents,
            "language_distribution": language_dist,
            "avg_satisfaction": round(avg_satisfaction, 2),
            "daily_activity": daily_activity,
            "period_days": days
        }
    
    def get_intent_performance(self):
        """Get performance metrics by intent"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            SELECT intent, total_queries, successful_queries, avg_confidence
            FROM intent_performance
            ORDER BY avg_confidence DESC
        ''')
        
        results = cursor.fetchall()
        conn.close()
        
        return [
            {
                "intent": row[0],
                "total_queries": row[1],
                "success_rate": round((row[2] / row[1]) * 100, 2) if row[1] > 0 else 0,
                "avg_confidence": round(row[3], 3)
            }
            for row in results
        ]
    
    def export_analytics(self, format="json"):
        """Export analytics data"""
        if format == "json":
            return json.dumps(self.get_analytics_dashboard(30), indent=2)
        else:
            return self.get_analytics_dashboard(30)

class ChatbotLearning:
    def __init__(self, analytics_db="chatbot_analytics.db", knowledge_base_path="academy_enhanced.json"):
        self.analytics = ChatbotAnalytics(analytics_db)
        self.knowledge_base_path = knowledge_base_path
        self.load_knowledge_base()
    
    def load_knowledge_base(self):
        """Load knowledge base from JSON file"""
        try:
            with open(self.knowledge_base_path, 'r', encoding='utf-8') as f:
                self.knowledge_base = json.load(f)
        except FileNotFoundError:
            self.knowledge_base = {"responses": {}, "keywords": {}, "intents": {}}
    
    def save_knowledge_base(self):
        """Save knowledge base to JSON file"""
        with open(self.knowledge_base_path, 'w', encoding='utf-8') as f:
            json.dump(self.knowledge_base, f, indent=2, ensure_ascii=False)
    
    def learn_from_feedback(self, threshold=0.7):
        """Learn from user feedback to improve responses"""
        # Get low-performing intents
        intent_performance = self.analytics.get_intent_performance()
        
        for intent_data in intent_performance:
            if intent_data["success_rate"] < threshold and intent_data["avg_confidence"] < threshold:
                intent = intent_data["intent"]
                print(f"Learning: Improving intent '{intent}' (success rate: {intent_data['success_rate']}%)")
                
                # Suggest improvements
                self.suggest_intent_improvements(intent)
    
    def suggest_intent_improvements(self, intent):
        """Suggest improvements for low-performing intent"""
        if intent in self.knowledge_base.get("keywords", {}):
            keywords = self.knowledge_base["keywords"][intent]
            print(f"Keywords for '{intent}': {keywords}")
            
            # Suggest adding more specific keywords
            suggestions = []
            if isinstance(keywords, dict):
                for lang, kw_list in keywords.items():
                    if len(kw_list) < 5:
                        suggestions.append(f"Add more {lang} keywords for '{intent}'")
            
            if suggestions:
                print("Suggestions:", suggestions)
    
    def detect_keyword_conflicts(self):
        """Detect and report keyword conflicts"""
        all_keywords = {}
        conflicts = []
        
        for intent, keywords in self.knowledge_base.get("keywords", {}).items():
            if isinstance(keywords, dict):
                for lang, kw_list in keywords.items():
                    for keyword in kw_list:
                        keyword_lower = keyword.lower()
                        if keyword_lower in all_keywords:
                            conflicts.append({
                                "keyword": keyword,
                                "intent1": all_keywords[keyword_lower],
                                "intent2": intent,
                                "language": lang
                            })
                        else:
                            all_keywords[keyword_lower] = intent
        
        return conflicts
    
    def auto_update_keywords(self, user_queries):
        """Automatically update keywords based on user queries"""
        # This would analyze successful queries and suggest new keywords
        pass
    
    def generate_performance_report(self):
        """Generate comprehensive performance report"""
        analytics = self.analytics.get_analytics_dashboard(30)
        intent_performance = self.analytics.get_intent_performance()
        conflicts = self.detect_keyword_conflicts()
        
        report = {
            "summary": analytics,
            "intent_performance": intent_performance,
            "keyword_conflicts": conflicts,
            "recommendations": self.generate_recommendations(analytics, intent_performance)
        }
        
        return report
    
    def generate_recommendations(self, analytics, intent_performance):
        """Generate actionable recommendations"""
        recommendations = []
        
        # Low satisfaction recommendations
        if analytics["avg_satisfaction"] < 4.0:
            recommendations.append({
                "type": "satisfaction",
                "priority": "high",
                "message": "Average user satisfaction is below 4.0. Review response quality and add more specific answers."
            })
        
        # Slow response recommendations
        if analytics["avg_response_time"] > 2.0:
            recommendations.append({
                "type": "performance",
                "priority": "medium", 
                "message": "Average response time is above 2 seconds. Consider optimizing response generation."
            })
        
        # Low performing intents
        low_performing = [i for i in intent_performance if i["success_rate"] < 70]
        if low_performing:
            recommendations.append({
                "type": "accuracy",
                "priority": "high",
                "message": f"{len(low_performing)} intents have success rate below 70%. Review and improve keyword matching."
            })
        
        return recommendations

# API endpoints for analytics
def create_analytics_routes(app, analytics, learning):
    """Create Flask routes for analytics"""
    
    @app.route('/analytics/dashboard')
    def analytics_dashboard():
        """Get analytics dashboard data"""
        days = request.args.get('days', 7, type=int)
        return jsonify(analytics.get_analytics_dashboard(days))
    
    @app.route('/analytics/performance')
    def analytics_performance():
        """Get intent performance data"""
        return jsonify(analytics.get_intent_performance())
    
    @app.route('/analytics/feedback', methods=['POST'])
    def analytics_feedback():
        """Log user feedback"""
        data = request.get_json()
        interaction_id = data.get('interaction_id')
        feedback_score = data.get('feedback_score')
        
        analytics.log_user_feedback(interaction_id, feedback_score)
        return jsonify({"status": "success"})
    
    @app.route('/learning/report')
    def learning_report():
        """Get learning and performance report"""
        return jsonify(learning.generate_performance_report())
    
    @app.route('/learning/update', methods=['POST'])
    def learning_update():
        """Trigger learning process"""
        learning.learn_from_feedback()
        return jsonify({"status": "learning_completed"})

if __name__ == "__main__":
    # Test the analytics system
    analytics = ChatbotAnalytics()
    learning = ChatbotLearning()
    
    # Sample data
    analytics.log_interaction(
        "apa itu academy", 
        "Academy Next Top Model adalah academy modeling...",
        "about_academy", 
        0.9, 
        "academy_about", 
        0.5, 
        "id", 
        "session_123"
    )
    
    # Get dashboard
    dashboard = analytics.get_analytics_dashboard(7)
    print("Analytics Dashboard:")
    print(json.dumps(dashboard, indent=2))
    
    # Performance report
    report = learning.generate_performance_report()
    print("\nPerformance Report:")
    print(json.dumps(report, indent=2))
