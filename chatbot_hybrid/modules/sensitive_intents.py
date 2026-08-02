#!/usr/bin/env python3
"""
Sensitive Intent Detection for Academy Chatbot
"""

class SensitiveIntentDetector:
    def __init__(self):
        self.sensitive_intents = {
            "pricing_sensitive": {
                "keywords": {
                    "id": [
                        "biaya detail", "harga spesial", "diskon detail", "paket harga", 
                        "cicilan", "angsuran", "promo spesial", "harga per bulan",
                        "biaya per sesi", "harga per kelas", "diskon khusus",
                        "pembayaran cicilan", "harga paket", "biaya lengkap"
                    ],
                    "en": [
                        "detailed pricing", "special price", "discount details", "package price",
                        "installment", "monthly payment", "special promo", "per session price",
                        "per class price", "special discount", "payment installment", 
                        "package cost", "complete pricing"
                    ]
                },
                "confidence": 1.0,
                "handoff": True,
                "reason": "Pricing details require personalized consultation"
            },
            
            "payment_sensitive": {
                "keywords": {
                    "id": [
                        "cara bayar", "metode pembayaran", "transfer bank", "kartu kredit",
                        "e-wallet", "gopay", "ovo", "dana", "link aja", "shopeepay",
                        "virtual account", "payment gateway", "midtrans", "stripe",
                        "bca", "mandiri", "bni", "bri", "permata", "cimb niaga"
                    ],
                    "en": [
                        "how to pay", "payment method", "bank transfer", "credit card",
                        "e-wallet", "digital payment", "payment gateway", "midtrans", "stripe",
                        "paypal", "venmo", "zelle", "cash app", "apple pay", "google pay"
                    ]
                },
                "confidence": 1.0,
                "handoff": True,
                "reason": "Payment methods require secure handling"
            },
            
            "contract_sensitive": {
                "keywords": {
                    "id": [
                        "kontrak model", "perjanjian", "syarat kontrak", "durasi kontrak",
                        "terminasi kontrak", "biaya kontrak", "fee kontrak", "legal contract",
                        "surat perjanjian", "binding agreement", "exclusive contract",
                        "non-exclusive", "kontrak eksklusif", "periode kontrak"
                    ],
                    "en": [
                        "model contract", "agreement", "contract terms", "contract duration",
                        "contract termination", "contract fee", "legal contract", "binding agreement",
                        "exclusive contract", "non-exclusive", "contract period", "model agreement"
                    ]
                },
                "confidence": 1.0,
                "handoff": True,
                "reason": "Contract details require legal consultation"
            },
            
            "booking_sensitive": {
                "keywords": {
                    "id": [
                        "booking detail", "reservasi", "jadwal booking", "dp booking",
                        "uang muka", "deposit", "cancel booking", "refund booking",
                        "ubah jadwal", "reschedule", "biaya cancel", "kebijakan cancel",
                        "booking fee", "service charge", "availability check"
                    ],
                    "en": [
                        "booking details", "reservation", "booking schedule", "booking deposit",
                        "down payment", "cancel booking", "refund booking", "reschedule",
                        "cancellation fee", "booking policy", "service charge", "availability check"
                    ]
                },
                "confidence": 1.0,
                "handoff": True,
                "reason": "Booking requires real-time availability check"
            },
            
            "specific_model_sensitive": {
                "keywords": {
                    "id": [
                        "fee model", "tarif model", "harga per jam", "harga per hari",
                        "rate model", "biaya photoshoot", "harga runway", "fee endorsement",
                        "iklan model", "casting fee", "audition fee", "model rates",
                        "biaya talent", "harga talent", "tarif endorsement", "rate card"
                    ],
                    "en": [
                        "model fee", "model rates", "hourly rate", "daily rate", "photoshoot cost",
                        "runway fee", "endorsement fee", "model pricing", "casting fee", "audition fee",
                        "talent fee", "endorsement rates", "rate card", "model compensation"
                    ]
                },
                "confidence": 1.0,
                "handoff": True,
                "reason": "Model rates require individual negotiation"
            }
        }
    
    def detect_sensitive_intent(self, message: str, language="id"):
        """Detect if message contains sensitive intent"""
        message_lower = message.lower()
        
        if language not in ["id", "en"]:
            language = "id"  # Default to Indonesian
        
        for intent_name, intent_data in self.sensitive_intents.items():
            keywords = intent_data["keywords"].get(language, intent_data["keywords"].get("id", []))
            
            if any(keyword in message_lower for keyword in keywords):
                return {
                    "intent": intent_name,
                    "confidence": intent_data["confidence"],
                    "handoff": intent_data["handoff"],
                    "reason": intent_data["reason"],
                    "detected_keywords": [kw for kw in keywords if kw in message_lower]
                }
        
        return None
    
    def get_all_sensitive_keywords(self, language="id"):
        """Get all sensitive keywords for a language"""
        all_keywords = {}
        
        for intent_name, intent_data in self.sensitive_intents.items():
            keywords = intent_data["keywords"].get(language, intent_data["keywords"].get("id", []))
            all_keywords[intent_name] = keywords
        
        return all_keywords
    
    def is_sensitive_message(self, message: str, language="id"):
        """Quick check if message is sensitive"""
        return self.detect_sensitive_intent(message, language) is not None

# Global instance
SENSITIVE_DETECTOR = SensitiveIntentDetector()

def detect_sensitive_intent(message: str, language="id"):
    """Global function to detect sensitive intent"""
    return SENSITIVE_DETECTOR.detect_sensitive_intent(message, language)

if __name__ == "__main__":
    # Test sensitive intent detection
    detector = SensitiveIntentDetector()
    
    test_messages = [
        ("berapa biaya detail untuk photoshoot", "id"),
        ("cara bayar dengan kartu kredit", "id"),
        ("apa syarat kontrak model", "id"),
        ("bagaimana cara booking model", "id"),
        ("berapa fee model untuk endorsement", "id"),
        ("what is the model rate per hour", "en"),
        ("how to pay with credit card", "en"),
        ("contract terms for models", "en")
    ]
    
    print("SENSITIVE INTENT DETECTION TEST")
    print("=" * 50)
    
    for message, lang in test_messages:
        result = detector.detect_sensitive_intent(message, lang)
        if result:
            print(f"{message:40} -> {result['intent']} (conf: {result['confidence']})")
            print(f"  Keywords: {', '.join(result['detected_keywords'])}")
        else:
            print(f"{message:40} -> NOT SENSITIVE")
        print()
