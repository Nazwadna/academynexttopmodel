#!/usr/bin/env python3
"""
Dataset Updater for Academy Chatbot
Analyzes chat logs to identify patterns and update training dataset
"""

import json
import sqlite3
import os
from datetime import datetime, timedelta
from typing import Dict, List, Tuple
from chat_logger import CHAT_LOGGER

class DatasetUpdater:
    def __init__(self, log_db_path="chat_logs.db", dataset_path="academy_enhanced.json"):
        self.log_db_path = log_db_path
        self.dataset_path = dataset_path
        self.min_confidence = 0.7
        self.min_occurrences = 1
    
    def analyze_fallback_patterns(self) -> Dict:
        """Analyze fallback patterns from chat logs"""
        conn = sqlite3.connect(self.log_db_path)
        cursor = conn.cursor()
        
        # Get all fallback queries
        cursor.execute('''
            SELECT user_message, intent, confidence, COUNT(*) as count
            FROM chat_logs 
            WHERE intent IN ('ai_fallback', 'safe_fallback')
            GROUP BY user_message, intent, confidence
            HAVING count >= ?
            ORDER BY count DESC, confidence DESC
        ''', (self.min_occurrences,))
        
        fallback_patterns = []
        for row in cursor.fetchall():
            user_message, intent, confidence, count = row
            fallback_patterns.append({
                'question': user_message,
                'intent': intent,
                'confidence': confidence,
                'count': count,
                'suggested_category': self._categorize_question(user_message)
            })
        
        conn.close()
        return {
            'patterns': fallback_patterns,
            'total_fallbacks': len(fallback_patterns),
            'analysis_date': datetime.now().isoformat()
        }
    
    def _categorize_question(self, question: str) -> str:
        """Categorize question based on keywords"""
        question_lower = question.lower()
        
        # Academy information
        if any(word in question_lower for word in ['academy', 'tentang', 'apa itu', 'program', 'pelatihan']):
            return 'about_academy'
        
        # Registration
        if any(word in question_lower for word in ['daftar', 'pendaftaran', 'register', 'gabung']):
            return 'registration_steps'
        
        # Schedule
        if any(word in question_lower for word in ['jadwal', 'kapan', 'jam', 'hari', 'waktu']):
            return 'schedule_info'
        
        # Model information
        if any(word in question_lower for word in ['model', 'talent', 'nama model']):
            return 'model_info'
        
        # Contact
        if any(word in question_lower for word in ['kontak', 'hubungi', 'nomor', 'alamat', 'email']):
            return 'contact_admin'
        
        # General
        return 'general'
    
    def generate_dataset_suggestions(self) -> Dict:
        """Generate suggestions for dataset updates"""
        patterns = self.analyze_fallback_patterns()
        
        suggestions = {
            'new_keywords': {},
            'new_responses': {},
            'intent_improvements': {},
            'priority_updates': []
        }
        
        # Group by category
        categorized_patterns = {}
        for pattern in patterns['patterns']:
            category = pattern['suggested_category']
            if category not in categorized_patterns:
                categorized_patterns[category] = []
            categorized_patterns[category].append(pattern)
        
        # Generate suggestions for each category
        for category, category_patterns in categorized_patterns.items():
            if category == 'about_academy':
                suggestions['new_keywords'][category] = self._extract_about_keywords(category_patterns)
                suggestions['new_responses'][category] = self._generate_about_responses(category_patterns)
            
            elif category == 'registration_steps':
                suggestions['new_keywords'][category] = self._extract_registration_keywords(category_patterns)
                suggestions['new_responses'][category] = self._generate_registration_responses(category_patterns)
            
            elif category == 'schedule_info':
                suggestions['new_keywords'][category] = self._extract_schedule_keywords(category_patterns)
                suggestions['new_responses'][category] = self._generate_schedule_responses(category_patterns)
            
            elif category == 'model_info':
                suggestions['new_keywords'][category] = self._extract_model_keywords(category_patterns)
                suggestions['new_responses'][category] = self._generate_model_responses(category_patterns)
        
        # Add priority updates (most frequent patterns)
        priority_patterns = sorted(patterns['patterns'], key=lambda x: x['count'], reverse=True)[:5]
        suggestions['priority_updates'] = [
            {
                'question': p['question'],
                'count': p['count'],
                'category': p['suggested_category'],
                'suggested_response': self._generate_suggested_response(p)
            }
            for p in priority_patterns
        ]
        
        return suggestions
    
    def _extract_about_keywords(self, patterns: List[Dict]) -> List[str]:
        """Extract keywords for about academy category"""
        keywords = set()
        for pattern in patterns:
            question = pattern['question'].lower()
            # Extract potential keywords
            if 'academy' in question:
                keywords.add(question)
            elif 'tentang' in question:
                keywords.add(question)
            elif 'apa itu' in question:
                keywords.add(question)
        return list(keywords)
    
    def _extract_registration_keywords(self, patterns: List[Dict]) -> List[str]:
        """Extract keywords for registration category"""
        keywords = set()
        for pattern in patterns:
            question = pattern['question'].lower()
            if 'daftar' in question:
                keywords.add(question)
            elif 'pendaftaran' in question:
                keywords.add(question)
            elif 'register' in question:
                keywords.add(question)
        return list(keywords)
    
    def _extract_schedule_keywords(self, patterns: List[Dict]) -> List[str]:
        """Extract keywords for schedule category"""
        keywords = set()
        for pattern in patterns:
            question = pattern['question'].lower()
            if 'jadwal' in question:
                keywords.add(question)
            elif 'kapan' in question:
                keywords.add(question)
            elif 'jam' in question:
                keywords.add(question)
        return list(keywords)
    
    def _extract_model_keywords(self, patterns: List[Dict]) -> List[str]:
        """Extract keywords for model category"""
        keywords = set()
        for pattern in patterns:
            question = pattern['question'].lower()
            if 'model' in question:
                keywords.add(question)
            elif 'talent' in question:
                keywords.add(question)
        return list(keywords)
    
    def _generate_about_responses(self, patterns: List[Dict]) -> List[str]:
        """Generate responses for about academy category"""
        responses = [
            "Academy Next Top Model adalah academy modeling yang berfokus pada pengembangan talent, pelatihan modeling, dan portofolio digital.",
            "Kami adalah academy modeling profesional yang menyediakan program pelatihan lengkap untuk calon model.",
            "Academy Next Top Model didirikan untuk mengembangkan talent modeling di Indonesia dengan kurikulum internasional."
        ]
        return responses
    
    def _generate_registration_responses(self, patterns: List[Dict]) -> List[str]:
        """Generate responses for registration category"""
        responses = [
            "Untuk mendaftar di Academy Next Top Model, Anda dapat mengisi formulir pendaftaran online di website kami.",
            "Proses pendaftaran academy meliputi pengisian formulir, wawancara, dan tes modeling dasar.",
            "Anda bisa mendaftar secara online atau langsung ke office kami di Trans Studio Mall Cibubur, GF Floor Second Atrium."
        ]
        return responses
    
    def _generate_schedule_responses(self, patterns: List[Dict]) -> List[str]:
        """Generate responses for schedule category"""
        responses = [
            "Kelas Academy Next Top Model berlangsung setiap Sabtu pukul 11.00-15.00 di Mall TSM Cibubur.",
            "Jadwal pelatihan regular adalah setiap hari Sabtu, dengan sesi pagi dan siang.",
            "Untuk jadwal private class, Anda bisa menghubungi admin untuk penjadwalan yang fleksibel."
        ]
        return responses
    
    def _generate_model_responses(self, patterns: List[Dict]) -> List[str]:
        """Generate responses for model category"""
        responses = [
            "Saya dapat membantu memberikan informasi model yang tersedia. Silakan sebutkan nama model yang ingin Anda cari.",
            "Untuk informasi model dan status mereka, Anda bisa mengecek langsung di website academy.",
            "Data model kami update secara berkala. Hubungi admin untuk informasi model terbaru."
        ]
        return responses
    
    def _generate_suggested_response(self, pattern: Dict) -> str:
        """Generate suggested response for a specific pattern"""
        category = pattern['suggested_category']
        question = pattern['question']
        
        if category == 'about_academy':
            return "Academy Next Top Model adalah academy modeling profesional yang berfokus pada pengembangan talent dan pelatihan modeling."
        
        elif category == 'registration_steps':
            return "Untuk informasi pendaftaran, silakan hubungi admin kami melalui WhatsApp atau kunjungi website academy."
        
        elif category == 'schedule_info':
            return "Kelas academy berlangsung setiap Sabtu pukul 11.00-15.00 di Mall TSM Cibubur. Hubungi admin untuk jadwal detail."
        
        elif category == 'model_info':
            return "Untuk informasi model, silakan sebutkan nama model yang ingin Anda ketahui statusnya."
        
        else:
            return "Saya adalah asisten Academy Next Top Model. Silakan ajukan pertanyaan tentang academy, program, atau modeling."
    
    def update_dataset(self, auto_apply: bool = False) -> Dict:
        """Update dataset with new patterns"""
        if not os.path.exists(self.dataset_path):
            return {"error": "Dataset file not found"}
        
        suggestions = self.generate_dataset_suggestions()
        
        if auto_apply:
            try:
                # Load current dataset
                with open(self.dataset_path, 'r', encoding='utf-8') as f:
                    dataset = json.load(f)
                
                # Apply suggestions to dataset
                updated_dataset = self._apply_suggestions_to_dataset(dataset, suggestions)
                
                # Save updated dataset
                backup_path = f"{self.dataset_path}.backup_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
                os.rename(self.dataset_path, backup_path)
                
                with open(self.dataset_path, 'w', encoding='utf-8') as f:
                    json.dump(updated_dataset, f, indent=2, ensure_ascii=False)
                
                return {
                    "success": True,
                    "backup_created": backup_path,
                    "updates_applied": len(suggestions['priority_updates']),
                    "suggestions": suggestions
                }
                
            except Exception as e:
                return {"error": f"Failed to update dataset: {str(e)}"}
        
        return {
            "success": True,
            "suggestions": suggestions,
            "auto_apply_disabled": True
        }
    
    def _apply_suggestions_to_dataset(self, dataset: Dict, suggestions: Dict) -> Dict:
        """Apply suggestions to dataset"""
        # Add new keywords
        for category, keywords in suggestions['new_keywords'].items():
            if category in dataset.get('keywords', {}):
                dataset['keywords'][category]['id'].extend(keywords)
                # Remove duplicates
                dataset['keywords'][category]['id'] = list(set(dataset['keywords'][category]['id']))
        
        # Add new responses
        for category, responses in suggestions['new_responses'].items():
            if category in dataset.get('responses', {}):
                dataset['responses'][category]['id'].extend(responses)
                # Remove duplicates
                dataset['responses'][category]['id'] = list(set(dataset['responses'][category]['id']))
        
        return dataset
    
    def export_fallback_report(self, output_path: str = "fallback_analysis.json") -> str:
        """Export fallback analysis report"""
        analysis = self.analyze_fallback_patterns()
        suggestions = self.generate_dataset_suggestions()
        
        report = {
            "analysis_date": datetime.now().isoformat(),
            "fallback_patterns": analysis,
            "dataset_suggestions": suggestions,
            "summary": {
                "total_fallback_patterns": analysis['total_fallbacks'],
                "categories_detected": len(set(p['suggested_category'] for p in analysis['patterns'])),
                "high_priority_updates": len(suggestions['priority_updates'])
            }
        }
        
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(report, f, indent=2, ensure_ascii=False)
        
        return output_path

if __name__ == "__main__":
    # Test dataset updater
    updater = DatasetUpdater()
    
    # Analyze fallback patterns
    print("Analyzing fallback patterns...")
    analysis = updater.analyze_fallback_patterns()
    print(f"Found {analysis['total_fallbacks']} fallback patterns")
    
    # Generate suggestions
    print("Generating dataset suggestions...")
    suggestions = updater.generate_dataset_suggestions()
    print(f"Generated {len(suggestions['priority_updates'])} priority updates")
    
    # Export report
    report_path = updater.export_fallback_report()
    print(f"Report exported to: {report_path}")
    
    # Show sample suggestions
    print("\nSample priority updates:")
    for i, update in enumerate(suggestions['priority_updates'][:3], 1):
        print(f"{i}. {update['question']} (count: {update['count']})")
        print(f"   Category: {update['category']}")
        print(f"   Suggested: {update['suggested_response']}")
        print()
