def enhanced_academy_search(message: str, language="id"):
    """Enhanced academy search with multilingual support and analytics"""
    message_lower = message.lower()
    responses = ACADEMY_DATA.get("responses", {})
    keywords = ACADEMY_DATA.get("keywords", {})
    intents = ACADEMY_DATA.get("intents", {})
    
    # Detect language if not specified
    if language not in ["id", "en"]:
        language = detect_language(message)
    
    # Enhanced keyword matching with multilingual support
    intent_checks = [
        # Emergency
        ("emergency", keywords.get("emergency", {}).get(language, []), "emergency"),
        
        # Greeting
        ("greeting", keywords.get("greeting", {}).get(language, []), "greeting"),
        
        # Benefits (more specific than about)
        ("benefits_joining", keywords.get("benefits_joining", {}).get(language, []), "benefits_joining"),
        
        # About Academy
        ("about_academy", keywords.get("about_academy", {}).get(language, []), "about_academy"),
        
        # Programs
        ("programs_info", keywords.get("programs_info", {}).get(language, []), "programs_info"),
        
        # Registration Steps
        ("registration_steps", keywords.get("registration_steps", {}).get(language, []), "registration_steps"),
        
        # Registration Requirements
        ("registration_requirements", keywords.get("registration_requirements", {}).get(language, []), "registration_requirements"),
        
        # Schedule
        ("schedule_info", keywords.get("schedule_info", {}).get(language, []), "schedule_info"),
        
        # Pricing (general)
        ("pricing_info", keywords.get("pricing_info", {}).get(language, []), "pricing_info"),
        
        # Login Help
        ("login_help", keywords.get("login_help", {}).get(language, []), "login_help"),
        
        # Register Help
        ("register_help", keywords.get("register_help", {}).get(language, []), "register_help"),
        
        # OTP Help
        ("otp_help", keywords.get("otp_help", {}).get(language, []), "otp_help"),
        
        # Model Categories
        ("model_categories", keywords.get("model_categories", {}).get(language, []), "model_categories"),
        
        # Client Search Model
        ("client_search_model", keywords.get("client_search_model", {}).get(language, []), "client_search_model"),
        
        # Portfolio
        ("portfolio_info", keywords.get("portfolio_info", {}).get(language, []), "portfolio_info"),
        
        # Career Highlights
        ("career_highlights_info", keywords.get("career_highlights_info", {}).get(language, []), "career_highlights_info"),
        
        # Contact Admin
        ("contact_admin", keywords.get("contact_admin", {}).get(language, []), "contact_admin"),
        
        # Booking Info
        ("booking_info", keywords.get("booking_info", {}).get(language, []), "booking_info"),
        
        # Technical Support
        ("technical_support", keywords.get("technical", {}).get(language, []), "technical_support")
    ]
    
    # Check each intent with keywords
    for intent_name, keyword_list, intent_key in intent_checks:
        if keyword_list and any(keyword in message_lower for keyword in keyword_list):
            response_data = responses.get(intent_name, {}).get(language, responses.get(intent_name, {}).get("id", ""))
            
            if response_data:
                import random
                response = random.choice(response_data) if isinstance(response_data, list) else response_data
                
                result = {
                    "answer": response,
                    "intent": intents[intent_key],
                    "confidence": 0.90,
                    "source": f"academy_{intent_name}",
                    "language": language
                }
                
                # Log to analytics if enabled
                if ANALYTICS_ENABLED:
                    ANALYTICS.log_interaction(
                        message, response, intents[intent_key], 0.90, 
                        f"academy_{intent_name}", 0.0, language
                    )
                
                return result
    
    # Check for sensitive keywords (auto handoff to WhatsApp)
    sensitive_keywords = ACADEMY_DATA.get("sensitive_keywords", {}).get(language, [])
    if any(keyword in message_lower for keyword in sensitive_keywords):
        response_data = responses.get("pricing_sensitive", {}).get(language, responses["pricing_sensitive"]["id"])
        response = random.choice(response_data) if isinstance(response_data, list) else response_data
        
        result = {
            "answer": response,
            "intent": intents["pricing_sensitive"],
            "confidence": 1.0,
            "source": "academy_sensitive",
            "handoff": True,
            "whatsapp_url": f"https://wa.me/6281214237742?text=Halo%20Admin%20Academy%20Next%20Top%20Model%2C%20saya%20ingin%20bertanya%3A%20{quote(message)}",
            "language": language
        }
        
        # Log to analytics if enabled
        if ANALYTICS_ENABLED:
            ANALYTICS.log_interaction(
                message, response, intents["pricing_sensitive"], 1.0, 
                "academy_sensitive", 0.0, language
            )
        
        return result
    
    return None

# Replace the original academy_search with enhanced version
academy_search = enhanced_academy_search
