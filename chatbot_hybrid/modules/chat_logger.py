#!/usr/bin/env python3
"""
Chat Logging System for Academy Chatbot
"""

import json
import sqlite3
import os
from datetime import datetime, timedelta
from typing import Dict, List, Optional
import uuid

class ChatLogger:
    def __init__(self, db_path="chat_logs.db"):
        self.db_path = db_path
        self.init_database()
    
    def init_database(self):
        """Initialize SQLite database for chat logs"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        # Create chat logs table
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS chat_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_id TEXT NOT NULL,
                message_id TEXT UNIQUE NOT NULL,
                user_message TEXT NOT NULL,
                bot_response TEXT NOT NULL,
                intent TEXT,
                confidence REAL,
                source TEXT,
                language TEXT DEFAULT 'id',
                handoff BOOLEAN DEFAULT FALSE,
                whatsapp_url TEXT,
                response_time REAL,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                user_ip TEXT,
                user_agent TEXT,
                feedback_rating INTEGER,
                feedback_comment TEXT,
                resolved BOOLEAN DEFAULT FALSE
            )
        ''')
        
        # Create session summary table
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS session_summary (
                session_id TEXT PRIMARY KEY,
                start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                end_time DATETIME,
                message_count INTEGER DEFAULT 0,
                avg_confidence REAL DEFAULT 0.0,
                intents_used TEXT,
                languages_used TEXT,
                handoff_count INTEGER DEFAULT 0,
                resolved BOOLEAN DEFAULT FALSE,
                satisfaction_score REAL
            )
        ''')
        
        # Create daily statistics table
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS daily_stats (
                date DATE PRIMARY KEY,
                total_messages INTEGER DEFAULT 0,
                unique_sessions INTEGER DEFAULT 0,
                avg_response_time REAL DEFAULT 0.0,
                top_intents TEXT,
                language_distribution TEXT,
                handoff_rate REAL DEFAULT 0.0,
                satisfaction_avg REAL DEFAULT 0.0
            )
        ''')
        
        # Create indexes for better performance
        cursor.execute('CREATE INDEX IF NOT EXISTS idx_session_id ON chat_logs(session_id)')
        cursor.execute('CREATE INDEX IF NOT EXISTS idx_timestamp ON chat_logs(timestamp)')
        cursor.execute('CREATE INDEX IF NOT EXISTS idx_intent ON chat_logs(intent)')
        
        conn.commit()
        conn.close()
    
    def save_chat_log(self, 
                      user_message: str, 
                      bot_response: str, 
                      intent: str = None, 
                      confidence: float = 0.0, 
                      source: str = "unknown",
                      language: str = "id",
                      handoff: bool = False,
                      whatsapp_url: str = None,
                      response_time: float = 0.0,
                      session_id: str = None,
                      user_ip: str = None,
                      user_agent: str = None) -> str:
        """Save a single chat interaction"""
        
        # Generate unique message ID
        message_id = str(uuid.uuid4())
        
        # Generate session ID if not provided
        if not session_id:
            session_id = str(uuid.uuid4())
        
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        try:
            # Insert chat log
            cursor.execute('''
                INSERT INTO chat_logs 
                (session_id, message_id, user_message, bot_response, intent, confidence, source, 
                 language, handoff, whatsapp_url, response_time, user_ip, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ''', (session_id, message_id, user_message, bot_response, intent, confidence, source,
                  language, handoff, whatsapp_url, response_time, user_ip, user_agent))
            
            # Update session summary
            self._update_session_summary(cursor, session_id, intent, confidence, language, handoff)
            
            # Update daily statistics
            self._update_daily_stats(cursor, intent, language, handoff, response_time)
            
            conn.commit()
            
            return message_id
            
        except Exception as e:
            conn.rollback()
            raise e
        finally:
            conn.close()
    
    def _update_session_summary(self, cursor, session_id: str, intent: str, confidence: float, language: str, handoff: bool):
        """Update session summary statistics"""
        # Get current session data
        cursor.execute('''
            SELECT message_count, avg_confidence, intents_used, languages_used, handoff_count
            FROM session_summary 
            WHERE session_id = ?
        ''', (session_id,))
        
        result = cursor.fetchone()
        
        if result:
            # Update existing session
            message_count, avg_confidence, intents_used, languages_used, handoff_count = result
            
            # Update averages
            new_message_count = message_count + 1
            new_avg_confidence = ((avg_confidence * message_count) + confidence) / new_message_count
            
            # Update intents and languages lists
            intents_list = json.loads(intents_used) if intents_used else []
            languages_list = json.loads(languages_used) if languages_used else []
            
            if intent and intent not in intents_list:
                intents_list.append(intent)
            if language not in languages_list:
                languages_list.append(language)
            
            new_handoff_count = handoff_count + (1 if handoff else 0)
            
            cursor.execute('''
                UPDATE session_summary 
                SET message_count = ?, avg_confidence = ?, intents_used = ?, 
                    languages_used = ?, handoff_count = ?, end_time = CURRENT_TIMESTAMP
                WHERE session_id = ?
            ''', (new_message_count, new_avg_confidence, json.dumps(intents_list),
                  json.dumps(languages_list), new_handoff_count, session_id))
        else:
            # Create new session
            intents_list = [intent] if intent else []
            languages_list = [language]
            
            cursor.execute('''
                INSERT INTO session_summary 
                (session_id, message_count, avg_confidence, intents_used, languages_used, handoff_count)
                VALUES (?, 1, ?, ?, ?, ?)
            ''', (session_id, confidence, json.dumps(intents_list), json.dumps(languages_list), 1 if handoff else 0))
    
    def _update_daily_stats(self, cursor, intent: str, language: str, handoff: bool, response_time: float):
        """Update daily statistics"""
        today = datetime.now().date()
        
        # Get current daily stats
        cursor.execute('''
            SELECT total_messages, avg_response_time, top_intents, language_distribution, handoff_rate
            FROM daily_stats 
            WHERE date = ?
        ''', (today,))
        
        result = cursor.fetchone()
        
        if result:
            total_messages, avg_response_time, top_intents, language_distribution, handoff_rate = result
            
            # Update averages
            new_total_messages = total_messages + 1
            new_avg_response_time = ((avg_response_time * total_messages) + response_time) / new_total_messages
            
            # Update intents distribution
            intents_dict = json.loads(top_intents) if top_intents else {}
            if intent:
                intents_dict[intent] = intents_dict.get(intent, 0) + 1
            
            # Update language distribution
            languages_dict = json.loads(language_distribution) if language_distribution else {}
            languages_dict[language] = languages_dict.get(language, 0) + 1
            
            # Update handoff rate
            new_handoff_count = handoff_rate * total_messages + (1 if handoff else 0)
            new_handoff_rate = new_handoff_count / new_total_messages
            
            cursor.execute('''
                UPDATE daily_stats 
                SET total_messages = ?, avg_response_time = ?, top_intents = ?, 
                    language_distribution = ?, handoff_rate = ?
                WHERE date = ?
            ''', (new_total_messages, new_avg_response_time, json.dumps(intents_dict),
                  json.dumps(languages_dict), new_handoff_rate, today))
        else:
            # Create new daily stats
            intents_dict = {intent: 1} if intent else {}
            languages_dict = {language: 1}
            
            cursor.execute('''
                INSERT INTO daily_stats 
                (date, total_messages, avg_response_time, top_intents, language_distribution, handoff_rate)
                VALUES (?, 1, ?, ?, ?, ?)
            ''', (today, response_time, json.dumps(intents_dict), json.dumps(languages_dict), 1 if handoff else 0))
    
    def get_session_logs(self, session_id: str) -> List[Dict]:
        """Get all logs for a specific session"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            SELECT * FROM chat_logs 
            WHERE session_id = ? 
            ORDER BY timestamp ASC
        ''', (session_id,))
        
        columns = [description[0] for description in cursor.description]
        logs = [dict(zip(columns, row)) for row in cursor.fetchall()]
        
        conn.close()
        return logs
    
    def get_session_summary(self, session_id: str) -> Optional[Dict]:
        """Get session summary"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            SELECT * FROM session_summary 
            WHERE session_id = ?
        ''', (session_id,))
        
        result = cursor.fetchone()
        conn.close()
        
        if result:
            columns = [description[0] for description in cursor.description]
            return dict(zip(columns, result))
        return None
    
    def get_daily_stats(self, days: int = 7) -> List[Dict]:
        """Get daily statistics for the last N days"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        start_date = datetime.now() - timedelta(days=days)
        
        cursor.execute('''
            SELECT * FROM daily_stats 
            WHERE date >= ?
            ORDER BY date DESC
        ''', (start_date,))
        
        columns = [description[0] for description in cursor.description]
        stats = [dict(zip(columns, row)) for row in cursor.fetchall()]
        
        conn.close()
        return stats
    
    def get_failed_queries(self, days: int = 7) -> List[Dict]:
        """Get failed queries (fallback/ai_fallback) from the last N days"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute(''' 
            SELECT user_message, intent, confidence, timestamp, session_id
            FROM chat_logs
            WHERE intent IN ('ai_fallback', 'safe_fallback', 'model_not_found')
            AND timestamp >= datetime('now', '-{} days')
            ORDER BY timestamp DESC
        '''.format(days))
        
        failed_queries = []
        for row in cursor.fetchall():
            failed_queries.append({
                'user_message': row[0],
                'intent': row[1],
                'confidence': row[2],
                'timestamp': row[3],
                'session_id': row[4]
            })
        
        conn.close()
        return failed_queries
    
    def log_failed_query(self, user_message: str, intent: str, confidence: float, 
                        session_id: str, user_ip: str, user_agent: str, 
                        error_reason: str = "Unknown"):
        """Log a failed query with additional error information"""
        return self.save_chat_log(
            user_message=user_message,
            bot_response=f"[FAILED] {error_reason}",
            intent=intent,
            confidence=confidence,
            source="failed_query",
            language="id",
            handoff=False,
            response_time=0.0,
            session_id=session_id,
            user_ip=user_ip,
            user_agent=user_agent
        )
    
    def add_feedback(self, message_id: str, rating: int, comment: str = None):
        """Add user feedback to a chat message"""
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute('''
            UPDATE chat_logs 
            SET feedback_rating = ?, feedback_comment = ?
            WHERE message_id = ?
        ''', (rating, comment, message_id))
        
        # Update session satisfaction if available
        cursor.execute('''
            SELECT session_id FROM chat_logs WHERE message_id = ?
        ''', (message_id,))
        
        session_id = cursor.fetchone()
        if session_id:
            session_id = session_id[0]
            self._update_session_satisfaction(cursor, session_id)
        
        conn.commit()
        conn.close()
    
    def _update_session_satisfaction(self, cursor, session_id: str):
        """Update session satisfaction score"""
        cursor.execute('''
            SELECT AVG(feedback_rating) FROM chat_logs 
            WHERE session_id = ? AND feedback_rating IS NOT NULL
        ''', (session_id,))
        
        result = cursor.fetchone()
        if result and result[0]:
            avg_satisfaction = result[0]
            cursor.execute('''
                UPDATE session_summary 
                SET satisfaction_score = ?
                WHERE session_id = ?
            ''', (avg_satisfaction, session_id))
    
    def get_analytics_data(self, days: int = 30) -> Dict:
        """Get comprehensive analytics data"""
        stats = self.get_daily_stats(days)
        
        if not stats:
            return {
                "total_messages": 0,
                "total_sessions": 0,
                "avg_response_time": 0,
                "top_intents": {},
                "language_distribution": {},
                "handoff_rate": 0,
                "avg_satisfaction": 0
            }
        
        # Aggregate statistics
        total_messages = sum(stat["total_messages"] for stat in stats)
        total_sessions = len(set(stat.get("unique_sessions", 0) for stat in stats))
        avg_response_time = sum(stat["avg_response_time"] for stat in stats) / len(stats)
        
        # Aggregate intents and languages
        all_intents = {}
        all_languages = {}
        
        for stat in stats:
            intents = json.loads(stat.get("top_intents", "{}"))
            languages = json.loads(stat.get("language_distribution", "{}"))
            
            for intent, count in intents.items():
                all_intents[intent] = all_intents.get(intent, 0) + count
            
            for lang, count in languages.items():
                all_languages[lang] = all_languages.get(lang, 0) + count
        
        # Calculate handoff rate
        total_handoffs = sum(stat["handoff_rate"] * stat["total_messages"] for stat in stats)
        handoff_rate = total_handoffs / total_messages if total_messages > 0 else 0
        
        return {
            "total_messages": total_messages,
            "total_sessions": total_sessions,
            "avg_response_time": avg_response_time,
            "top_intents": all_intents,
            "language_distribution": all_languages,
            "handoff_rate": handoff_rate,
            "avg_satisfaction": sum(stat.get("satisfaction_avg", 0) for stat in stats) / len(stats)
        }

# Global instance
CHAT_LOGGER = ChatLogger()

def save_chat_log(user_message: str, 
                  bot_response: str, 
                  intent: str = None, 
                  confidence: float = 0.0, 
                  source: str = "unknown",
                  language: str = "id",
                  handoff: bool = False,
                  whatsapp_url: str = None,
                  response_time: float = 0.0,
                  session_id: str = None,
                  user_ip: str = None,
                  user_agent: str = None) -> str:
    """Global function to save chat log"""
    return CHAT_LOGGER.save_chat_log(
        user_message, bot_response, intent, confidence, source, language,
        handoff, whatsapp_url, response_time, session_id, user_ip, user_agent
    )

if __name__ == "__main__":
    # Test chat logging
    logger = ChatLogger()
    
    # Test saving a chat log
    message_id = logger.save_chat_log(
        user_message="apa itu academy",
        bot_response="Academy Next Top Model adalah academy modeling...",
        intent="about_academy",
        confidence=0.9,
        source="academy_dataset",
        language="id",
        response_time=0.5
    )
    
    print(f"Saved chat log with message ID: {message_id}")
    
    # Test analytics
    analytics = logger.get_analytics_data(7)
    print("Analytics data:")
    print(json.dumps(analytics, indent=2))
