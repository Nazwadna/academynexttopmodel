#!/usr/bin/env python3
"""
WhatsApp Handoff System for Academy Chatbot
"""

import json
import time
from urllib.parse import quote
from datetime import datetime

class WhatsAppHandoff:
    def __init__(self):
        self.admin_whatsapp = "+6281214237742"
        self.academy_name = "Academy Next Top Model"
        
        # WhatsApp message templates for different sensitive intents
        self.message_templates = {
            "pricing_sensitive": {
                "id": "Halo Admin Academy Next Top Model, saya ingin bertanya tentang biaya dan harga program. Pertanyaan saya: {message}",
                "en": "Hello Admin Academy Next Top Model, I'd like to ask about program fees and pricing. My question: {message}"
            },
            "payment_sensitive": {
                "id": "Halo Admin Academy Next Top Model, saya ingin informasi tentang metode pembayaran. Pertanyaan: {message}",
                "en": "Hello Admin Academy Next Top Model, I need information about payment methods. Question: {message}"
            },
            "contract_sensitive": {
                "id": "Halo Admin Academy Next Top Model, saya ingin konsultasi tentang kontrak model. Pertanyaan: {message}",
                "en": "Hello Admin Academy Next Top Model, I'd like to consult about model contracts. Question: {message}"
            },
            "booking_sensitive": {
                "id": "Halo Admin Academy Next Top Model, saya ingin informasi tentang booking model. Pertanyaan: {message}",
                "en": "Hello Admin Academy Next Top Model, I need information about model booking. Question: {message}"
            },
            "specific_model_sensitive": {
                "id": "Halo Admin Academy Next Top Model, saya ingin bertanya tentang fee/rate model. Pertanyaan: {message}",
                "en": "Hello Admin Academy Next Top Model, I'd like to ask about model rates/fees. Question: {message}"
            }
        }
        
        # Response templates
        self.response_templates = {
            "pricing_sensitive": {
                "id": "Untuk informasi biaya dan harga yang akurat sesuai kebutuhan Anda, silakan hubungi admin kami melalui WhatsApp. Admin akan memberikan penawaran terbaik dan penjelasan lengkap.",
                "en": "For accurate pricing information tailored to your needs, please contact our admin via WhatsApp. Our admin will provide the best offer and complete explanation."
            },
            "payment_sensitive": {
                "id": "Untuk informasi metode pembayaran yang aman dan lengkap, silakan hubungi admin kami melalui WhatsApp. Admin akan membantu proses pembayaran Anda.",
                "en": "For secure and complete payment method information, please contact our admin via WhatsApp. Our admin will assist with your payment process."
            },
            "contract_sensitive": {
                "id": "Untuk konsultasi kontrak dan perjanjian model, silakan hubungi admin kami melalui WhatsApp. Admin akan memberikan penjelasan hukum dan detail kontrak.",
                "en": "For contract and model agreement consultation, please contact our admin via WhatsApp. Our admin will provide legal explanation and contract details."
            },
            "booking_sensitive": {
                "id": "Untuk informasi ketersediaan dan detail booking, silakan hubungi admin kami melalui WhatsApp. Admin akan cek jadwal real-time untuk Anda.",
                "en": "For availability and booking details, please contact our admin via WhatsApp. Our admin will check real-time schedules for you."
            },
            "specific_model_sensitive": {
                "id": "Untuk informasi fee dan rate model yang spesifik, silakan hubungi admin kami melalui WhatsApp. Admin akan memberikan penawaran sesuai kebutuhan Anda.",
                "en": "For specific model rates and fees, please contact our admin via WhatsApp. Our admin will provide offers tailored to your needs."
            }
        }
    
    def generate_whatsapp_url(self, message: str, intent: str, language="id"):
        """Generate WhatsApp URL with pre-filled message"""
        # Get message template
        template = self.message_templates.get(intent, self.message_templates["pricing_sensitive"])
        whatsapp_message = template.get(language, template["id"]).format(message=message)
        
        # Create WhatsApp URL
        encoded_message = quote(whatsapp_message)
        whatsapp_url = f"https://wa.me/{self.admin_whatsapp.replace('+', '')}?text={encoded_message}"
        
        return whatsapp_url, whatsapp_message
    
    def generate_handoff_response(self, intent: str, language="id"):
        """Generate response for WhatsApp handoff"""
        template = self.response_templates.get(intent, self.response_templates["pricing_sensitive"])
        response = template.get(language, template["id"])
        
        return response
    
    def create_handoff_data(self, message: str, intent: str, language="id", session_id=None):
        """Create complete handoff data"""
        whatsapp_url, whatsapp_message = self.generate_whatsapp_url(message, intent, language)
        response = self.generate_handoff_response(intent, language)
        
        return {
            "intent": intent,
            "response": response,
            "handoff": True,
            "whatsapp_url": whatsapp_url,
            "whatsapp_message": whatsapp_message,
            "admin_number": self.admin_whatsapp,
            "language": language,
            "session_id": session_id,
            "timestamp": datetime.now().isoformat(),
            "confidence": 1.0,
            "source": "whatsapp_handoff"
        }
    
    def log_handoff(self, message: str, intent: str, language="id", session_id=None):
        """Log handoff for analytics"""
        handoff_data = self.create_handoff_data(message, intent, language, session_id)
        
        # Log to analytics if available
        try:
            from app import ANALYTICS_ENABLED, ANALYTICS
            if ANALYTICS_ENABLED and ANALYTICS:
                ANALYTICS.log_interaction(
                    message, 
                    handoff_data["response"], 
                    intent, 
                    1.0, 
                    "whatsapp_handoff", 
                    0.0, 
                    language, 
                    session_id
                )
        except:
            pass  # Analytics not available
        
        return handoff_data

# Global instance
WHATSAPP_HANDOFF = WhatsAppHandoff()

def create_whatsapp_handoff(message: str, intent: str, language="id", session_id=None):
    """Global function to create WhatsApp handoff"""
    return WHATSAPP_HANDOFF.create_handoff_data(message, intent, language, session_id)

if __name__ == "__main__":
    # Test WhatsApp handoff
    handoff = WhatsAppHandoff()
    
    test_cases = [
        ("berapa biaya detail photoshoot", "pricing_sensitive", "id"),
        ("cara bayar dengan kartu kredit", "payment_sensitive", "id"),
        ("apa syarat kontrak model", "contract_sensitive", "id"),
        ("bagaimana booking model", "booking_sensitive", "id"),
        ("berapa fee model endorsement", "specific_model_sensitive", "id"),
        ("what is the model rate", "specific_model_sensitive", "en")
    ]
    
    print("WHATSAPP HANDOFF TEST")
    print("=" * 50)
    
    for message, intent, lang in test_cases:
        handoff_data = handoff.create_handoff_data(message, intent, lang)
        
        print(f"Message: {message}")
        print(f"Intent: {intent}")
        print(f"Language: {lang}")
        print(f"Response: {handoff_data['response']}")
        print(f"WhatsApp URL: {handoff_data['whatsapp_url']}")
        print("-" * 50)
