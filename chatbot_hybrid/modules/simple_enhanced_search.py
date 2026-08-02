def enhanced_academy_search(message: str, language="id"):
    """Enhanced academy search with multilingual support"""
    message_lower = message.lower()
    
    # Check if ACADEMY_DATA is available
    if not ACADEMY_DATA:
        return None
    
    responses = ACADEMY_DATA.get("responses", {})
    keywords = ACADEMY_DATA.get("keywords", {})
    intents = ACADEMY_DATA.get("intents", {})
    
    # Detect language if not specified
    if language not in ["id", "en"]:
        language = detect_language(message)
    
    # Enhanced keyword matching with multilingual support
    intent_checks = [
        # Emergency
        ("emergency", keywords.get("emergency", []), "emergency"),
        
        # Greeting
        ("greeting", keywords.get("greeting", []), "greeting"),
        
        # About Academy
        ("about_academy", keywords.get("about_academy", []), "about_academy"),
        
        # Benefits
        ("benefits_joining", keywords.get("benefits_joining", []), "benefits_joining"),
        
        # Programs
        ("programs_info", keywords.get("programs_info", []), "programs_info"),
        
        # Registration Steps
        ("registration_steps", keywords.get("registration_steps", []), "registration_steps"),
        
        # Registration Requirements
        ("registration_requirements", keywords.get("registration_requirements", []), "registration_requirements"),
        
        # Schedule
        ("schedule_info", keywords.get("schedule_info", []), "schedule_info"),
        
        # Pricing (general)
        ("pricing_info", keywords.get("pricing_info", []), "pricing_info"),
        
        # Login Help
        ("login_help", keywords.get("login_help", []), "login_help"),
        
        # Register Help
        ("register_help", keywords.get("register_help", []), "register_help"),
        
        # OTP Help
        ("otp_help", keywords.get("otp_help", []), "otp_help"),
        
        # Model Categories
        ("model_categories", keywords.get("model_categories", []), "model_categories"),
        
        # Client Search Model
        ("client_search_model", keywords.get("client_search_model", []), "client_search_model"),
        
        # Portfolio
        ("portfolio_info", keywords.get("portfolio_info", []), "portfolio_info"),
        
        # Career Highlights
        ("career_highlights_info", keywords.get("career_highlights_info", []), "career_highlights_info"),
        
        # Contact Admin
        ("contact_admin", keywords.get("contact_admin", []), "contact_admin"),
        
        # Booking Info
        ("booking_info", keywords.get("booking_info", []), "booking_info"),
        
        # Technical Support
        ("technical_support", keywords.get("technical", []), "technical_support")
    ]
    
    # Check each intent with keywords
    for intent_name, keyword_list, intent_key in intent_checks:
        if keyword_list and any(keyword in message_lower for keyword in keyword_list):
            response_data = responses.get(intent_name, {})
            
            # Get response for detected language or fallback to Indonesian
            if language in response_data:
                response_list = response_data[language]
            else:
                response_list = response_data.get("id", [])
            
            if response_list:
                import random
                response = random.choice(response_list)
                
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
    sensitive_keywords = ACADEMY_DATA.get("sensitive_keywords", [])
    if isinstance(sensitive_keywords, dict):
        sensitive_list = sensitive_keywords.get(language, sensitive_keywords.get("id", []))
    else:
        sensitive_list = sensitive_keywords
    
    if any(keyword in message_lower for keyword in sensitive_list):
        response_data = responses.get("pricing_sensitive", {})
        
        # Get response for detected language or fallback to Indonesian
        if language in response_data:
            response_list = response_data[language]
        else:
            response_list = response_data.get("id", [])
        
        if response_list:
            import random
            response = random.choice(response_list)
            
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
