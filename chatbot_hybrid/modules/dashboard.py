#!/usr/bin/env python3
"""
Chatbot Analytics Dashboard
Simple web dashboard to view chatbot statistics and analytics
"""

from flask import Flask, render_template, jsonify
from chat_logger import CHAT_LOGGER
from datetime import datetime, timedelta
import json

app = Flask(__name__)

@app.route('/')
def dashboard():
    """Main dashboard page"""
    return render_template('dashboard.html')

@app.route('/api/stats')
def get_stats():
    """API endpoint for statistics"""
    try:
        # Get analytics data for last 7 days
        analytics = CHAT_LOGGER.get_analytics_data(7)
        
        # Get failed queries
        failed_queries = CHAT_LOGGER.get_failed_queries(7)
        
        # Get daily stats
        daily_stats = CHAT_LOGGER.get_daily_stats(7)
        
        return jsonify({
            'success': True,
            'analytics': analytics,
            'failed_queries': failed_queries[:10],  # Last 10 failed queries
            'daily_stats': daily_stats,
            'last_updated': datetime.now().isoformat()
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        })

@app.route('/api/failed-queries')
def get_failed_queries():
    """Get detailed failed queries"""
    try:
        failed_queries = CHAT_LOGGER.get_failed_queries(30)
        return jsonify({
            'success': True,
            'failed_queries': failed_queries
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        })

if __name__ == '__main__':
    print("Starting Chatbot Analytics Dashboard...")
    print("Dashboard will be available at: http://127.0.0.1:5001")
    app.run(host='127.0.0.1', port=5001, debug=True)
