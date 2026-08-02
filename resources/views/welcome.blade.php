<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{{ __('messages.title') }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Fondamento&family=Newsreader:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Nama di kartu model — seragam di Face of Models / New Models / Hire Models */
    .model-card-name {
      font-family: 'Newsreader', Georgia, serif !important;
      font-weight: 700 !important;
      font-size: 22px;
      line-height: 1;
      color: #000 !important;
      letter-spacing: 0.02em;
      text-shadow: 0 0 4px #fff, 0 0 2px #fff;
    }
    @media (min-width: 640px) {
      .model-card-name {
        font-size: 22px;
      }
    }
    @media (max-width: 640px) {
      .model-card-name {
        font-size: 16px;
      }
    }
    body {
      font-family: "Open Sans", sans-serif;
    }
    h1.main {
      font-family: "Fondamento", cursive;
    }
    .font-fondamento { font-family: 'Fondamento', cursive; }
    
    /* Modern Animation Styles */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes fadeInLeft {
      from {
        opacity: 0;
        transform: translateX(-30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
    
    @keyframes fadeInRight {
      from {
        opacity: 0;
        transform: translateX(30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
    
    @keyframes scaleIn {
      from {
        opacity: 0;
        transform: scale(0.8);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }
    
    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-50px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.05);
      }
    }
    
    @keyframes float {
      0%, 100% {
        transform: translateY(0px);
      }
      50% {
        transform: translateY(-10px);
      }
    }
    
    @keyframes shimmer {
      0% {
        background-position: -200% 0;
      }
      100% {
        background-position: 200% 0;
      }
    }
    
    @keyframes ripple {
      0% {
        transform: scale(0);
        opacity: 1;
      }
      100% {
        transform: scale(4);
        opacity: 0;
      }
    }
    
    /* Enhanced ripple animation for talent cards */
    @keyframes talentRipple {
      0% {
        transform: scale(0);
        opacity: 0.8;
      }
      50% {
        opacity: 0.4;
      }
      100% {
        transform: scale(2);
        opacity: 0;
      }
    }
    
    .talent-ripple {
      animation: talentRipple 0.8s ease-out;
    }
    
    /* Animation Classes */
    .animate-fade-in-up {
      animation: fadeInUp 0.8s ease-out forwards;
    }
    
    .animate-fade-in-left {
      animation: fadeInLeft 0.8s ease-out forwards;
    }
    
    .animate-fade-in-right {
      animation: fadeInRight 0.8s ease-out forwards;
    }
    
    .animate-scale-in {
      animation: scaleIn 0.6s ease-out forwards;
    }
    
    .animate-slide-in-up {
      animation: slideInUp 0.8s ease-out forwards;
    }
    
    .animate-slide-in-down {
      animation: slideInDown 0.8s ease-out forwards;
    }
    
    .animate-pulse-slow {
      animation: pulse 3s ease-in-out infinite;
    }
    
    .animate-float {
      animation: float 3s ease-in-out infinite;
    }
    
    .animate-shimmer {
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
      background-size: 200% 100%;
      animation: shimmer 2s infinite;
    }
    
    /* Hover Effects */
    .hover-lift {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .hover-lift:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    
    /* Talent Section Card Hover Effects */
    .talent-card-link {
      transition: all 0.3s ease;
    }
    
    .talent-card-link:hover {
      transform: scale(1.05);
      filter: brightness(1.1);
    }
    
    /* Enhanced text styling for talent card labels */
    .text-stroke-white {
      color: black !important;
      font-weight: 700;
      display: inline-block;
      max-width: 200px;
      line-height: 1.3;
      white-space: normal;
      overflow: visible;
      text-overflow: clip;
      z-index: 10;
      position: relative;
      box-sizing: border-box;
      margin: 0;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .talent-card-link:hover .hover-lift {
      transform: translateY(-8px);
      box-shadow: 0 15px 35px rgba(255,255,255,0.3);
    }
    

    
    /* Hover effect for text-stroke-white */
    .talent-card-link:hover .text-stroke-white {
      transform: translateY(-2px);
      color: black !important;
    }
    
    /* Enhanced image hover effect */
    .talent-card-link:hover img {
      transform: scale(1.05);
      filter: brightness(1.1);
    }
    
    .talent-card-link img {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      filter: contrast(1.05) saturate(1.1);
    }
    

    
    @media (max-width: 768px) {
      .talent-card-link:hover img {
        transform: scale(1.02);
        filter: brightness(1.05);
      }
      
      /* Mobile responsive text styling */
      .text-stroke-white {
        font-size: 14px;
        max-width: 180px;
        line-height: 1.2;
        white-space: normal;
        overflow: visible;
        color: black !important;
      }
      
      /* Enhanced mobile talent card styling */
      .talent-card-link .font-['Newsreader'] {
        font-size: 16px !important;
        line-height: 1.1;
      }
    }
    
    @media (max-width: 480px) {
      /* Small mobile responsive text styling */
      .text-stroke-white {
        font-size: 12px;
        max-width: 150px;
        line-height: 1.1;
        white-space: normal;
        overflow: visible;
        color: black !important;
      }
      
      /* Enhanced small mobile talent card styling */
      .talent-card-link .font-['Newsreader'] {
        font-size: 14px !important;
        line-height: 1.0;
      }
    }
    

    

    
    /* Loading animation for arrow */
    .talent-card-link.loading .bg-white.rounded-full {
      background-color: #FFB800 !important;
      color: white !important;
      animation: loadingSpin 1s linear infinite;
    }
    
    @keyframes loadingSpin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    
    /* Enhanced scroll behavior for talent section */
    .talent-card-link[href^="#"] {
      scroll-behavior: smooth;
    }
    

    

        -ms-backdrop-filter: none;
        -webkit-background-color: transparent;
        -moz-background-color: transparent;
        -ms-background-color: transparent;
        -webkit-background-image: none;
        -moz-background-image: none;
        -ms-background-image: none;
        -webkit-background-repeat: no-repeat;
        -moz-background-repeat: no-repeat;
        -ms-background-repeat: no-repeat;
        -webkit-background-position: center;
        -moz-background-position: center;
        -ms-background-position: center;
        -webkit-background-size: auto;
        -moz-background-size: auto;
        -ms-background-size: auto;
        -webkit-background-attachment: scroll;
        -moz-background-attachment: scroll;
        -ms-background-attachment: scroll;
        -webkit-background-clip: border-box;
        -moz-background-clip: border-box;
        -ms-background-clip: border-box;
        -webkit-background-origin: border-box;
        -moz-background-origin: border-box;
        -ms-background-origin: border-box;
        -webkit-background-blend-mode: normal;
        -moz-background-blend-mode: normal;
        -ms-background-blend-mode: normal;
        -webkit-background-composite: source-over;
        -moz-background-composite: source-over;
        -ms-background-composite: source-over;
        -webkit-background-mask: none;
        -moz-background-mask: none;
        -ms-background-mask: none;
        -webkit-background-mask-image: none;
        -moz-background-mask-image: none;
        -ms-background-mask-image: none;
        -webkit-background-mask-repeat: no-repeat;
        -moz-background-mask-repeat: no-repeat;
        -ms-background-mask-repeat: no-repeat;
        -webkit-background-mask-position: center;
        -moz-background-mask-position: center;
        -ms-background-mask-position: center;
        -webkit-background-mask-size: auto;
        -moz-background-mask-size: auto;
        -ms-background-mask-size: auto;
        -webkit-background-mask-clip: border-box;
        -moz-background-mask-clip: border-box;
        -ms-background-mask-clip: border-box;
        -webkit-background-mask-origin: border-box;
        -moz-background-mask-origin: border-box;
        -ms-background-mask-origin: border-box;
        -webkit-background-mask-blend-mode: normal;
        -moz-background-mask-blend-mode: normal;
        -ms-background-mask-blend-mode: normal;
      }
    
    /* Arrow animation on hover */
    .talent-card-link:hover .bg-white.rounded-full {
      transform: scale(1.1) rotate(5deg);
      background-color: #30B0C7 !important;
      color: white !important;
      box-shadow: 0 4px 12px rgba(48, 176, 199, 0.4);
    }
    
    /* Enhanced arrow transition */
    .talent-card-link .bg-white.rounded-full {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Ripple effect on click */
    .talent-card-link:active {
      transform: scale(0.98);
    }
    
    /* Active state for text-stroke-white */
    .talent-card-link:active .text-stroke-white {
      transform: translateY(0px);
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
      color: black !important;
    }
    
    /* Enhanced mobile responsiveness */
    @media (max-width: 768px) {
      .talent-card-link:hover {
        transform: scale(1.02);
        filter: brightness(1.05);
      }
      
      .talent-card-link:hover .hover-lift {
        transform: translateY(-4px);
      }
      
      .talent-card-link:active {
        transform: scale(0.98);
      }
      
      .talent-card-link:hover .bg-white.bg-opacity-90 {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      }
      
      .talent-card-link:hover .bg-white.rounded-full {
        transform: scale(1.05) rotate(3deg);
        background-color: #30B0C7 !important;
        color: white !important;
        box-shadow: 0 2px 8px rgba(48, 176, 199, 0.4);
      }
      

      
      .talent-card-link:hover .hover-lift {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255,255,255,0.2);
      }
      
      .talent-card-link:focus {
        outline: 2px solid #30B0C7;
        outline-offset: 2px;
        border-radius: 20px;
        transform: scale(1.01);
      }
      
      .talent-card-link:focus-visible {
        outline: 2px solid #30B0C7;
        outline-offset: 2px;
        border-radius: 20px;
        transform: scale(1.01);
      }
      
      /* Enhanced keyboard navigation for mobile */
      .talent-card-link:focus .bg-white.rounded-full {
        background-color: #30B0C7 !important;
        color: white !important;
        transform: scale(1.05);
      }
      
      /* Enhanced text focus for mobile */
      .talent-card-link:focus .bg-white.bg-opacity-90 {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      }
      

      
      /* Enhanced card focus for mobile */
      .talent-card-link:focus .hover-lift {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255,255,255,0.2);
      }
      
      /* Enhanced image focus for mobile */
      .talent-card-link:focus img {
        transform: scale(1.02);
        filter: brightness(1.05);
      }
      
      /* Enhanced touch feedback for mobile */
      .talent-card-link:active {
        transform: scale(0.98);
        filter: brightness(0.95);
      }
      
      .talent-card-link:active .bg-white.rounded-full {
        transform: scale(0.95);
        background-color: #30B0C7 !important;
        color: white !important;
      }
      
      .talent-card-link:active .bg-white.bg-opacity-90 {
        transform: translateY(0px);
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
      }
      

      
      .talent-card-link:active .hover-lift {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(255,255,255,0.15);
      }
      
      .talent-card-link:active img {
        transform: scale(0.98);
        filter: brightness(0.95);
      }
      
      /* Enhanced touch feedback for mobile */
      @media (hover: none) and (pointer: coarse) {
        .talent-card-link:active {
          transform: scale(0.95);
          filter: brightness(0.95);
        }
        
        .talent-card-link:active .bg-white.rounded-full {
          transform: scale(0.9);
          background-color: #30B0C7 !important;
          color: white !important;
        }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
        
        .talent-card-link:active .hover-lift {
          transform: translateY(-1px);
        }
        
        .talent-card-link:active img {
          transform: scale(0.98);
        }
        
        /* Enhanced touch feedback for mobile */
        .talent-card-link:active {
          filter: brightness(0.95);
        }
        
        .talent-card-link:active .bg-white.rounded-full {
          background-color: #30B0C7 !important;
          color: white !important;
        }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
        
        .talent-card-link:active .hover-lift {
          transform: translateY(-1px);
        }
        
        .talent-card-link:active img {
          transform: scale(0.98);
        }
        
        /* Enhanced touch feedback for mobile */
        .talent-card-link:active {
          filter: brightness(0.95);
        }
        
        .talent-card-link:active .bg-white.rounded-full {
          background-color: #30B0C7 !important;
          color: white !important;
        }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
        
        .talent-card-link:active .hover-lift {
          transform: translateY(-1px);
        }
        
        .talent-card-link:active img {
          transform: scale(0.98);
        }
        
        /* Enhanced touch feedback for mobile */
        .talent-card-link:active {
          filter: brightness(0.95);
        }
        
        .talent-card-link:active .bg-white.rounded-full {
          background-color: #30B0C7 !important;
          color: white !important;
        }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
        
        .talent-card-link:active .hover-lift {
          transform: translateY(-1px);
        }
        
        .talent-card-link:active img {
          transform: scale(0.98);
        }
        
        /* Enhanced touch feedback for mobile */
        .talent-card-link:active {
          filter: brightness(0.95);
        }
        
        .talent-card-link:active .bg-white.rounded-full {
          background-color: #30B0C7 !important;
          color: white !important;
        }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
        
        .talent-card-link:active .hover-lift {
          transform: translateY(-1px);
        }
        
        .talent-card-link:active img {
          transform: scale(0.98);
        }
        
        /* Enhanced touch feedback for mobile */
        .talent-card-link:active {
          filter: brightness(0.95);
        }
        
        .talent-card-link:active .bg-white.rounded-full {
          background-color: #30B0C7 !important;
          color: white !important;
        }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
        
        .talent-card-link:active .hover-lift {
          transform: translateY(-1px);
        }
        
        .talent-card-link:active img {
          transform: scale(0.98);
        }
        
        /* Enhanced touch feedback for mobile */
        .talent-card-link:active {
          filter: brightness(0.95);
        }
        
        .talent-card-link:active .bg-white.rounded-full {
          background-color: #30B0C7 !important;
          color: white !important;
        }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
        
        .talent-card-link:active .hover-lift {
          transform: translateY(-1px);
        }
        
        .talent-card-link:active img {
          transform: scale(0.98);
        }
        
        /* Enhanced touch feedback for mobile */
        .talent-card-link:active {
          filter: brightness(0.95);
        }
        
        .talent-card-link:active .bg-white.rounded-full {
          background-color: #30B0C7 !important;
          color: white !important;
        }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
        
        .talent-card-link:active .hover-lift {
          transform: translateY(-1px);
        }
        
        .talent-card-link:active img {
          transform: scale(0.98);
        }
        
        /* Enhanced touch feedback for mobile */
        .talent-card-link:active {
          filter: brightness(0.95);
        }
        
        .talent-card-link:active .bg-white.rounded-full {
          background-color: #30B0C7 !important;
          color: white !important;
        }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
        
        .talent-card-link:active .hover-lift {
          transform: translateY(-1px);
        }
        
        .talent-card-link:active img {
          transform: scale(0.98);
        }
        
        /* Enhanced touch feedback for mobile */
        .talent-card-link:active {
          filter: brightness(0.95);
        }
        
        .talent-card-link:active .bg-white.rounded-full {
          background-color: #30B0C7 !important;
          color: white !important;
        }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
        
        .talent-card-link:active .hover-lift {
          transform: translateY(-1px);
        }
        
        .talent-card-link:active img {
          transform: scale(0.98);
        }
        
        /* Enhanced touch feedback for mobile */
        .talent-card-link:active {
          filter: brightness(0.95);
        }
        
        .talent-card-link:active .bg-white.rounded-full {
          background-color: #30B0C7 !important;
          color: white !important;
        }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
        
        .talent-card-link:active .hover-lift {
          transform: translateY(-1px);
        }
        
        .talent-card-link:active img {
          transform: scale(0.98);
        }
      }
        
        .talent-card-link:active .bg-white.bg-opacity-90 {
          background-color: rgba(255, 255, 255, 0.98) !important;
        }
        
        .talent-card-link:active .border-white {
          border-color: #30B0C7;
        }
      }
    }
    
    /* Success state for internal links */
    .talent-card-link .bg-white.rounded-full.success {
      background-color: #10B981 !important;
      color: white !important;
      animation: successPulse 0.5s ease-out;
    }
    
    @keyframes successPulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.2); }
      100% { transform: scale(1); }
    }
    
    .hover-glow {
      transition: all 0.3s ease;
    }
    
    .hover-glow:hover {
      box-shadow: 0 0 20px rgba(255,255,255,0.3);
    }
    
    /* Performance Optimizations */
    .will-change-transform {
      will-change: transform;
    }
    
    .backface-hidden {
      backface-visibility: hidden;
    }
    
    /* Smooth Scrolling */
    html {
      scroll-behavior: smooth;
    }
    
    /* Scroll offset for fixed navbar */
    html {
      scroll-padding-top: 100px; /* Adjust based on navbar height */
    }
    
    /* Ensure sections are properly positioned */
    section[id] {
      scroll-margin-top: 100px; /* Adjust based on navbar height */
    }
    
    /* Additional styling for text-stroke-white */
    .text-stroke-white {
      color: black !important;
      font-weight: 700 !important;
    }
    
    /* Enhanced talent card text styling for better readability */
    .talent-card-link .font-['Newsreader'] {
      font-weight: 600;
      letter-spacing: 0.02em;
    }
    
    /* Improved text contrast for talent section titles */
    .talent-section-title {
      text-shadow: 
        0 2px 4px rgba(0,0,0,0.8),
        0 4px 8px rgba(0,0,0,0.6);
    }
    
    /* Mobile touch feedback for text-stroke-white */
    @media (hover: none) and (pointer: coarse) {
      .talent-card-link:active .text-stroke-white {
        transform: scale(0.95);
        color: black !important;
        transition: all 0.1s ease;
      }
      
      .talent-card-link:active {
        transform: scale(0.98);
      }
    }
    

    
    /* Highlight animation for scrolled sections */
    section[id] {
      transition: all 0.3s ease;
    }
    
    /* Active section indicator */
    .section-active {
      transform: scale(1.02);
      box-shadow: 0 0 30px rgba(255,255,255,0.3);
    }
    
    /* Smooth transition for all interactive elements */
    * {
      transition: all 0.3s ease;
    }
    
    /* Enhanced scroll behavior */
    body {
      scroll-behavior: smooth;
    }
    
    /* WhatsApp link animations */
    .whatsapp-link {
      transition: all 0.3s ease;
    }
    
    .whatsapp-link:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 25px rgba(48, 176, 199, 0.4);
    }
    
    .whatsapp-link:hover svg {
      animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.1);
      }
    }
    
    /* WhatsApp button specific styles */
    .whatsapp-link {
      position: relative;
      overflow: hidden;
    }
    
    .whatsapp-link::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }
    
    .whatsapp-link:hover::before {
      left: 100%;
    }
    

    
    /* Input field animations */
    input[placeholder="No. WhatsApp"] {
      transition: all 0.3s ease;
    }
    
    input[placeholder="No. WhatsApp"]:focus {
      transform: scale(1.02);
      box-shadow: 0 0 0 3px rgba(48, 176, 199, 0.3);
    }
    
    input[placeholder="No. WhatsApp"]:not(:placeholder-shown) {
      border-color: #30B0C7;
      background-color: rgba(48, 176, 199, 0.1);
    }
    
    /* Notification animations */
    .notification-enter {
      animation: slideInRight 0.3s ease-out;
    }
    
    .notification-exit {
      animation: slideOutRight 0.3s ease-in;
    }
    
    @keyframes slideInRight {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    @keyframes slideOutRight {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(100%);
        opacity: 0;
      }
    }
    
    /* Instagram link animations */
    .instagram-link {
      transition: all 0.3s ease;
    }
    
    .instagram-link:hover {
      transform: translateY(-2px);
      text-shadow: 0 2px 4px rgba(48, 176, 199, 0.3);
    }
    
    .instagram-link:hover svg {
      filter: drop-shadow(0 2px 4px rgba(48, 176, 199, 0.5));
    }
    
    /* Instagram gradient effect */
    .instagram-link::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
      border-radius: 4px;
      z-index: -1;
    }
    
    .instagram-link:hover::before {
      opacity: 0.1;
    }
    
    /* Instagram specific hover effects */
    .instagram-link:hover {
      background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: instagramGlow 2s ease-in-out infinite alternate;
    }
    
    @keyframes instagramGlow {
      from {
        filter: drop-shadow(0 0 5px rgba(220, 39, 67, 0.3));
      }
      to {
        filter: drop-shadow(0 0 15px rgba(220, 39, 67, 0.6));
      }
    }
    
    /* Instagram icon animation */
    .instagram-link:hover svg {
      animation: instagramIconPulse 1s ease-in-out infinite;
    }
    
    @keyframes instagramIconPulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.2);
      }
    }
    
    /* Tooltip styles */
    .tooltip {
      position: relative;
    }
    
    .tooltip::after {
      content: '';
      position: absolute;
      top: 100%;
      left: 50%;
      transform: translateX(-50%);
      border: 5px solid transparent;
      border-top-color: black;
    }
    
    /* Loading State */
    body:not(.loaded) {
      overflow: hidden;
    }
    
    body:not(.loaded)::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: black;
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    body:not(.loaded)::after {
      content: 'Loading...';
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: white;
      font-size: 24px;
      z-index: 10000;
    }
    
    /* Reduce motion for users who prefer it */
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }
    }
    
    /* Performance optimizations */
    .lazy {
      opacity: 0;
      transition: opacity 0.3s;
    }
    
    .lazy.loaded {
      opacity: 1;
    }
    
    /* GPU acceleration for animations */
    .animate-float,
    .animate-pulse-slow,
    .animate-scale-in,
    .animate-fade-in-up,
    .animate-fade-in-left,
    .animate-fade-in-right,
    .animate-slide-in-up,
    .animate-slide-in-down {
      transform: translateZ(0);
      backface-visibility: hidden;
      perspective: 1000px;
    }
    
    /* Optimize for low-end devices */
    @media (max-width: 768px) {
      .animate-float {
        animation-duration: 4s !important;
      }
      
      .animate-pulse-slow {
        animation-duration: 4s !important;
      }
    }

    /* Scroll to Top Button Styles */
    .scroll-to-top-btn {
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }

    .scroll-to-top-btn:hover {
      box-shadow: 0 8px 25px rgb(0, 255, 85);
    }

    .scroll-to-top-btn:focus {
      outline: 2px solidrgb(255, 255, 255);
      outline-offset: 2px;
    }

    /* Mobile responsive adjustments */
    @media (max-width: 768px) {
      .scroll-to-top-btn {
        bottom: 4;
        right: 4;
        width: 48px;
        height: 48px;
      }
      
      .scroll-to-top-btn svg {
        width: 20px;
        height: 20px;
      }
    }

    /* Animation for scroll to top button */
    @keyframes scrollToTopPulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.1);
      }
    }

    .scroll-to-top-btn:hover {
      animation: scrollToTopPulse 1s ease-in-out infinite;
    }

    /* Enhanced accessibility */
    .scroll-to-top-btn:focus-visible {
      outline: 2px solid #30B0C7;
      outline-offset: 2px;
      transform: scale(1.05);
    }

    /* Touch feedback for mobile */
    @media (hover: none) and (pointer: coarse) {
      .scroll-to-top-btn:active {
        transform: scale(0.95);
        background-color: #2A9DB3;
      }
    }

  </style>
</head>

  <!-- Enhanced Animation System -->
<script>
  document.addEventListener("DOMContentLoaded", function () {
    // Performance optimization: Use requestAnimationFrame for smooth animations
    const animateElement = (element, animationClass, delay = 0) => {
      setTimeout(() => {
        element.classList.add(animationClass);
        element.style.opacity = '1';
      }, delay);
    };

    // Enhanced Intersection Observer for better performance
    const animationObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
          const element = entry.target;
          const animationType = element.dataset.animation || 'fade-in-up';
          const delay = parseInt(element.dataset.delay) || 0;
          
          // Add will-change for GPU acceleration
          element.classList.add('will-change-transform', 'backface-hidden');
          
          // Apply animation based on data attribute
          switch(animationType) {
            case 'fade-in-left':
              animateElement(element, 'animate-fade-in-left', delay);
              break;
            case 'fade-in-right':
              animateElement(element, 'animate-fade-in-right', delay);
              break;
            case 'scale-in':
              animateElement(element, 'animate-scale-in', delay);
              break;
            case 'slide-in-up':
              animateElement(element, 'animate-slide-in-up', delay);
              break;
            case 'slide-in-down':
              animateElement(element, 'animate-slide-in-down', delay);
              break;
            default:
              animateElement(element, 'animate-fade-in-up', delay);
          }
          
          // Remove observer after animation
          animationObserver.unobserve(element);
        }
      });
    }, { 
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    });

    // Enhanced lazy loading with staggered animations
    const lazySections = document.querySelectorAll('.lazy-section');
    const lazyObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          const element = entry.target;
          const delay = index * 100; // Staggered delay
          
          element.classList.add('opacity-100', 'translate-y-0');
          element.classList.remove('opacity-0', 'translate-y-10');
          
          // Add floating animation to images
          const images = element.querySelectorAll('img');
          images.forEach((img, imgIndex) => {
            setTimeout(() => {
              img.classList.add('animate-float');
            }, delay + (imgIndex * 200));
          });
          
          // Add hover effects to cards
          const cards = element.querySelectorAll('.relative');
          cards.forEach(card => {
            card.classList.add('hover-lift');
          });
          
          // Add shimmer effect to buttons
          const buttons = element.querySelectorAll('button, a[href]');
          buttons.forEach(button => {
            button.classList.add('hover-glow');
          });
      }
    });
  }, { threshold: 0.2 });

    // Observe all sections
  lazySections.forEach(section => {
      lazyObserver.observe(section);
    });

    // Enhanced scroll-triggered animations
    const scrollElements = document.querySelectorAll('[data-scroll-animation]');
    scrollElements.forEach((element, index) => {
      element.style.opacity = '0';
      animationObserver.observe(element);
    });

    // Parallax effect for hero section
    const heroImage = document.querySelector('.hero-image');
    if (heroImage) {
      window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        heroImage.style.transform = `translateY(${rate}px)`;
      });
    }

    // Smooth reveal animations for text elements
    const textElements = document.querySelectorAll('h1, h2, p, span');
    textElements.forEach((element, index) => {
      if (!element.closest('.lazy-section')) {
        element.style.opacity = '0';
        setTimeout(() => {
          element.style.opacity = '1';
          element.classList.add('animate-fade-in-up');
        }, index * 50);
      }
    });

    // Performance optimization: Debounce scroll events
    let ticking = false;
    function updateAnimations() {
      ticking = false;
      // Additional scroll-based animations can be added here
    }

    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(updateAnimations);
        ticking = true;
      }
    });

    // Add loading animation for images
    const images = document.querySelectorAll('img');
    images.forEach(img => {
      img.addEventListener('load', () => {
        img.classList.add('animate-scale-in');
      });
    });

    // Add interactive hover effects
    const interactiveElements = document.querySelectorAll('a, button, .card');
    interactiveElements.forEach(element => {
      element.addEventListener('mouseenter', () => {
        element.style.transform = 'scale(1.02)';
      });
      
      element.addEventListener('mouseleave', () => {
        element.style.transform = 'scale(1)';
      });
    });

    // Performance optimization: Preload critical images
    const criticalImages = [
      '{{ asset("img/home.jpg") }}',
      '{{ asset("img/becomeamodel.png") }}',
      '{{ asset("img/contact-model.png") }}'
    ];
    
    criticalImages.forEach(src => {
      const img = new Image();
      img.src = src;
    });

    // Add smooth scroll behavior for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        const target = document.querySelector(href);
        if (target) {
          // Calculate offset for navbar height
          const navbarHeight = document.querySelector('header')?.offsetHeight || 0;
          const targetPosition = target.offsetTop - navbarHeight - 20; // 20px extra padding
          
          // Add click animation
          this.style.transform = 'scale(0.95)';
          setTimeout(() => {
            this.style.transform = '';
          }, 150);
          
          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });
        }
      });
    });
    
    // Add click animation for talent section cards
    document.querySelectorAll('.talent-card-link').forEach(link => {
      link.addEventListener('click', function(e) {
        // Add click animation
        this.classList.add('clicked');
        setTimeout(() => {
          this.classList.remove('clicked');
        }, 300);
        
        // Add ripple effect
        const ripple = document.createElement('div');
        ripple.className = 'absolute inset-0 bg-white bg-opacity-20 rounded-[20px] pointer-events-none talent-ripple';
        this.querySelector('.relative').appendChild(ripple);
        
        setTimeout(() => {
          if (ripple.parentNode) {
            ripple.parentNode.removeChild(ripple);
          }
        }, 800);
        
        // Add success feedback for external links
        const href = this.getAttribute('href');
        if (href && !href.startsWith('#')) {
          // Show loading state for external links
          this.classList.add('loading');
          
          // Add loading animation to arrow
          const arrow = this.querySelector('.bg-white.rounded-full');
          if (arrow) {
            arrow.innerHTML = '⏳';
          }
          
          setTimeout(() => {
            this.classList.remove('loading');
            if (arrow) {
              arrow.innerHTML = '↗';
              arrow.style.animation = '';
            }
          }, 1000);
        }
        
        // Add smooth scroll for internal links
        if (href && href.startsWith('#')) {
          e.preventDefault();
          const targetId = href.substring(1);
          const target = document.getElementById(targetId);
          
          if (target) {
            const navbarHeight = document.querySelector('header')?.offsetHeight || 0;
            const targetPosition = target.offsetTop - navbarHeight - 20;
            
            window.scrollTo({
              top: targetPosition,
              behavior: 'smooth'
            });
            
            // Add highlight animation to target section
            setTimeout(() => {
              target.style.transition = 'all 0.3s ease';
              target.style.transform = 'scale(1.02)';
              target.style.boxShadow = '0 0 30px rgba(255,255,255,0.3)';
              
              setTimeout(() => {
                target.style.transform = 'scale(1)';
                target.style.boxShadow = 'none';
              }, 300);
            }, 500);
            
            // Add success feedback for internal links
            const arrow = this.querySelector('.bg-white.rounded-full');
            if (arrow) {
              arrow.innerHTML = '✓';
              arrow.classList.add('success');
              
              setTimeout(() => {
                arrow.innerHTML = '↗';
                arrow.classList.remove('success');
              }, 1000);
            }
          }
        }
      });
    });
    
    // Add hover sound effect (optional)
    document.querySelectorAll('.talent-card-link').forEach(link => {
      link.addEventListener('mouseenter', function() {
        // Add subtle glow effect
        this.style.filter = 'brightness(1.1)';
      });
      
      link.addEventListener('mouseleave', function() {
        this.style.filter = '';
      });
    });
    
    // Add keyboard navigation support
    document.querySelectorAll('.talent-card-link').forEach(link => {
      link.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.click();
        }
      });
      
      // Add focus management
      link.addEventListener('focus', function() {
        this.style.transform = 'scale(1.02)';
      });
      
      link.addEventListener('blur', function() {
        this.style.transform = '';
      });
    });
    
    // Add loading state for external links
    document.querySelectorAll('.talent-card-link[href^="http"], .talent-card-link[href^="/"]').forEach(link => {
      link.addEventListener('click', function() {
        if (!this.href.includes('#')) {
          this.classList.add('loading');
          setTimeout(() => {
            this.classList.remove('loading');
          }, 1000);
        }
      });
    });
    
    // Add success feedback for internal links
    document.querySelectorAll('.talent-card-link[href^="#"]').forEach(link => {
      link.addEventListener('click', function() {
        // Add success feedback
        const successIndicator = document.createElement('div');
        successIndicator.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
        successIndicator.innerHTML = 'Navigating to section...';
        document.body.appendChild(successIndicator);
        
        setTimeout(() => {
          if (document.body.contains(successIndicator)) {
            document.body.removeChild(successIndicator);
          }
        }, 2000);
      });
    });

    // Add loading states for better UX
    window.addEventListener('load', () => {
      document.body.classList.add('loaded');
    });

    // Optimize for mobile performance
    if ('ontouchstart' in window) {
      // Reduce animation complexity on touch devices
      document.querySelectorAll('.animate-float').forEach(el => {
        el.style.animationDuration = '4s';
      });
    }

    // Performance optimization: Lazy load non-critical images
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src || img.src;
          img.classList.remove('lazy');
          observer.unobserve(img);
        }
      });
    });

    lazyImages.forEach(img => imageObserver.observe(img));

    // Optimize scroll performance
    let scrollTimeout;
    window.addEventListener('scroll', () => {
      if (scrollTimeout) {
        clearTimeout(scrollTimeout);
      }
      scrollTimeout = setTimeout(() => {
        // Throttled scroll handling
      }, 16); // ~60fps
    });

    // Add error handling for animations
    window.addEventListener('error', (e) => {
      if (e.target.tagName === 'IMG') {
        e.target.style.display = 'none';
      }
    });

    // Memory cleanup
    window.addEventListener('beforeunload', () => {
      // Clean up observers and event listeners
      if (animationObserver) animationObserver.disconnect();
      if (lazyObserver) lazyObserver.disconnect();
      if (imageObserver) imageObserver.disconnect();
    });

    // Add active class to sections in viewport
    const sectionObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          // Remove active class from all sections
          document.querySelectorAll('section[id]').forEach(section => {
            section.classList.remove('section-active');
          });
          
          // Add active class to current section
          entry.target.classList.add('section-active');
        }
      });
    }, { threshold: 0.3 });

    // Observe all sections with IDs
    document.querySelectorAll('section[id]').forEach(section => {
      sectionObserver.observe(section);
    });

    // WhatsApp link click animation
    const whatsappLink = document.querySelector('.whatsapp-link');
    const whatsappInput = document.querySelector('input[placeholder="No. WhatsApp"]');
    
    // Update button text based on input
    if (whatsappInput) {
      whatsappInput.addEventListener('input', function() {
        const inputValue = this.value.trim();
        if (inputValue) {
          whatsappLink.innerHTML = `
            ${whatsappLink.querySelector('svg').outerHTML}
            Chat with ${inputValue}
          `;
        } else {
          whatsappLink.innerHTML = `
            <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
            </svg>
            {{ __('messages.lets_collaborate') }}
          `;
        }
      });
    }
    
    if (whatsappLink) {
      whatsappLink.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Check if user entered a custom number
        let phoneNumber = '6281214237742'; // Default number
        if (whatsappInput && whatsappInput.value.trim()) {
          const inputValue = whatsappInput.value.trim();
          // Clean the number (remove spaces, dashes, etc.)
          const cleanNumber = inputValue.replace(/[\s\-\(\)]/g, '');
          // Add country code if not present
          if (cleanNumber.startsWith('0')) {
            phoneNumber = '62' + cleanNumber.substring(1);
          } else if (cleanNumber.startsWith('62')) {
            phoneNumber = cleanNumber;
          } else if (cleanNumber.startsWith('+62')) {
            phoneNumber = cleanNumber.substring(1);
          } else {
            phoneNumber = '62' + cleanNumber;
          }
        }
        
        // Add click animation
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
          this.style.transform = 'scale(1.05)';
          setTimeout(() => {
            this.style.transform = '';
          }, 150);
        }, 100);
        
        // Add success feedback
        const originalHTML = this.innerHTML;
        
        this.innerHTML = `
          <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Opening WhatsApp...
        `;
        
        // Open WhatsApp with the phone number
        setTimeout(() => {
          window.open(`https://wa.me/${phoneNumber}`, '_blank');
          this.innerHTML = originalHTML;
        }, 1000);
        
        // Show success message
        const successMessage = document.createElement('div');
        successMessage.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 notification-enter';
        successMessage.innerHTML = `
          <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Opening WhatsApp...
          </div>
        `;
        document.body.appendChild(successMessage);
        
        // Remove after 3 seconds
        setTimeout(() => {
          successMessage.classList.remove('notification-enter');
          successMessage.classList.add('notification-exit');
          setTimeout(() => {
            if (document.body.contains(successMessage)) {
              document.body.removeChild(successMessage);
            }
          }, 300);
                }, 3000);
      });
    }
    
    // Instagram link click animation
    const instagramLink = document.querySelector('.instagram-link');
    if (instagramLink) {
      // Add tooltip on hover
      instagramLink.addEventListener('mouseenter', function() {
        const tooltip = document.createElement('div');
        tooltip.className = 'absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 bg-black text-white text-xs rounded-lg opacity-0 transition-opacity duration-300 whitespace-nowrap z-50';
        tooltip.textContent = 'Follow us on Instagram!';
        tooltip.style.pointerEvents = 'none';
        
        this.appendChild(tooltip);
        
        setTimeout(() => {
          tooltip.style.opacity = '1';
        }, 100);
      });
      
      instagramLink.addEventListener('mouseleave', function() {
        const tooltip = this.querySelector('div');
        if (tooltip) {
          tooltip.style.opacity = '0';
          setTimeout(() => {
            if (tooltip.parentNode) {
              tooltip.parentNode.removeChild(tooltip);
            }
          }, 300);
        }
      });
      
      instagramLink.addEventListener('click', function(e) {
        // Add click animation
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
          this.style.transform = 'scale(1.05)';
          setTimeout(() => {
            this.style.transform = '';
          }, 150);
        }, 100);
        
        // Add success feedback
        const originalHTML = this.innerHTML;
        
        this.innerHTML = `
          <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-300" fill="currentColor" viewBox="0 0 24 24">
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Opening Instagram...
        `;
        
        // Show success message
        const successMessage = document.createElement('div');
        successMessage.className = 'fixed top-4 right-4 bg-gradient-to-r from-purple-500 to-pink-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 notification-enter';
        successMessage.innerHTML = `
          <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
            </svg>
            Opening Instagram...
          </div>
        `;
        document.body.appendChild(successMessage);
        
        // Remove after 3 seconds
        setTimeout(() => {
          successMessage.classList.remove('notification-enter');
          successMessage.classList.add('notification-exit');
          setTimeout(() => {
            if (document.body.contains(successMessage)) {
              document.body.removeChild(successMessage);
            }
          }, 300);
        }, 3000);
        
        // Restore original content
        setTimeout(() => {
          this.innerHTML = originalHTML;
        }, 2000);
      });
    }
});
</script>

  <!-- Welcome Start -->
<body class="bg-black text-white min-h-screen relative overflow-x-hidden">
  @include('navbar')

<!-- Hero Section -->
<div class="relative pt-10 lazy-section opacity-0 translate-y-10 transition duration-700 ease-in-out" data-scroll-animation="fade-in-up">
    <div class="hidden md:block relative pt-10 lazy-section opacity-0 translate-y-10 transition duration-700 ease-in-out">
      <div class="absolute top-[20px] left-1/2 transform -translate-x-1/2 z-20">
          <img src="{{ asset('img/home.jpg') }}" alt="Model" class="w-[550px] h-auto relative hero-image animate-float" data-scroll-animation="scale-in">
        <!-- Teks Founder -->
          <div class="absolute bottom-[30%] right-[-80px] text-right z-30 max-w-[220px] slide-in-right" data-scroll-animation="fade-in-right" data-delay="300">
          <p class="text-white text-[16px] leading-tight font-light">
            {{ __('messages.ceo_founder_coach') }}<br>
            <span class="font-semibold">{{ __('messages.olivarie') }}</span><br>
            <span class="opacity-80 text-[15px]">{{ __('messages.miss_inter') }}</span>
          </p>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-[200px] bg-gradient-to-b from-transparent via-black/30 to-black"></div>
      </div>
      <h1 class="main text-[78px] md:text-[78px] sm:text-[60px] xs:text-[40px] text-center relative z-10 mt-[-50px] mb-5 animate-pulse-slow" data-scroll-animation="slide-in-down" data-delay="200">ACADEMY NEXT TOP MODEL</h1>
      <section class="flex flex-col md:flex-row justify-between items-start px-4 md:px-[70px] mt-[-80px] relative z-10">
        <div class="flex-1 pt-8 md:pt-14 md:text-left text-center" data-scroll-animation="fade-in-left" data-delay="400">
          <p class="text-[20px] sm:text-[24px] md:text-[28px] leading-snug opacity-80 font-light">
            {{ __('messages.hero_slogan_1') }}<br>{{ __('messages.hero_slogan_2') }}<br>{{ __('messages.hero_slogan_3') }}
          </p>
          @php
              $pesan1 = "Halo, saya tertarik untuk mengetahui lebih banyak tentang Academy Next Top Model. Mohon infonya ya, terima kasih!";
          @endphp

          <a href="https://api.whatsapp.com/send?phone=6281214237742&text={{ urlencode($pesan1) }}" target="_blank"
            class="inline-flex items-center px-4 py-2 border border-white rounded-full text-white text-[16px] mt-6 md:mt-10
                    hover:bg-white hover:text-black hover:border-black transition animate-shimmer">
              {{ __('messages.lets_collaborate') }} <span class="ml-2">↗</span>
          </a>

        </div>
        <div class="flex-1 md:text-right text-center md:mt-0 mt-8 pt-8 md:pt-14" data-scroll-animation="fade-in-right" data-delay="600">
          <p class="text-[18px] sm:text-[20px] md:text-[24px] leading-snug opacity-80 font-light">
            {{ __('messages.hero_slogan_4') }}
          </p>
        </div>
      </section>
    </div>

    <!-- Hero Section Tab/Tablet -->
    <div class="hidden sm:block md:hidden relative pt-10 lazy-section opacity-0 translate-y-10 transition duration-700 ease-in-out" data-scroll-animation="fade-in-up">
      <div class="absolute top-[10px] left-1/2 transform -translate-x-1/2 z-20">
        <img src="{{ asset('img/home.jpg') }}" alt="Model" class="w-[340px] h-auto relative hero-image animate-float" data-scroll-animation="scale-in">
        <!-- Teks Founder -->
        <div class="absolute bottom-[20%] right-[-40px] text-right z-30 max-w-[140px] slide-in-right" data-scroll-animation="fade-in-right" data-delay="300">
          <p class="text-white text-[12px] leading-tight font-light">
            {{ __('messages.ceo_founder_coach') }}<br>
            <span class="font-semibold">{{ __('messages.olivarie') }}</span><br>
            <span class="opacity-80 text-[11px]">{{ __('messages.miss_inter') }}</span>
          </p>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-[100px] bg-gradient-to-b from-transparent via-black/30 to-black"></div>
      </div>
      <h1 class="main text-[38px] text-center relative z-10 mt-[-30px] mb-3 animate-pulse-slow" data-scroll-animation="slide-in-down" data-delay="200">ACADEMY NEXT TOP MODEL</h1>
      <section class="flex flex-col sm:flex-row justify-between items-start px-2 sm:px-8 mt-[-40px] relative z-10">
        <div class="flex-1 pt-4 sm:pt-8 sm:text-left text-center" data-scroll-animation="fade-in-left" data-delay="400">
          <p class="text-[15px] sm:text-[18px] leading-snug opacity-80 font-light">
            {{ __('messages.hero_slogan_1') }}<br>{{ __('messages.hero_slogan_2') }}<br>{{ __('messages.hero_slogan_3') }}
          </p>
          
          @php
              $pesan1 = "Halo, saya tertarik untuk mengetahui lebih banyak tentang Academy Next Top Model. Mohon infonya ya, terima kasih!";
          @endphp

          <a href="https://api.whatsapp.com/send?phone=6281214237742&text={{ urlencode($pesan1) }}" target="_blank"
            class="inline-flex items-center px-3 py-1.5 border border-white rounded-full text-white text-[13px] mt-4 sm:mt-6
                    hover:bg-white hover:text-black hover:border-black transition animate-shimmer">
            {{ __('messages.lets_collaborate') }} <span class="ml-2">↗</span>
          </a>
        </div>
        <div class="flex-1 sm:text-right text-center sm:mt-0 mt-6 pt-4 sm:pt-8" data-scroll-animation="fade-in-right" data-delay="600">
          <p class="text-[13px] sm:text-[15px] leading-snug opacity-80 font-light">
            {{ __('messages.hero_slogan_4') }}
          </p>
        </div>
      </section>
    </div>

    <!-- Hero Section Mobile -->
    <div class="flex flex-col items-center sm:hidden px-4 pt-6" data-scroll-animation="fade-in-up">
      <h1 class="main text-[38px] text-center mb-4 animate-pulse-slow" data-scroll-animation="slide-in-down" data-delay="200">{{ __('messages.title') }}</h1>
      <p class="text-[16px] leading-snug opacity-80 font-light text-center mb-4" data-scroll-animation="fade-in-up" data-delay="400">
        {{ __('messages.hero_slogan_4') }}
      </p>
      <div class="relative w-full max-w-[300px] h-auto mb-4" data-scroll-animation="scale-in" data-delay="600">
        <img src="{{ asset('img/home.jpg') }}" alt="Model" class="w-full h-auto rounded-lg shadow-lg animate-float" loading="lazy">
        <!-- Teks Founder Mobile -->
        <div class="absolute top-[60%] right-[-20px] pr-2 text-right z-10 max-w-[120px] slide-in-right" data-scroll-animation="fade-in-right" data-delay="800">
          <p class="text-white text-[12px] leading-tight font-light">
            {{ __('messages.ceo_founder_coach') }}<br>
            <span class="font-semibold">{{ __('messages.olivarie') }}</span><br>
            <span class="opacity-80 text-[11px]">{{ __('messages.miss_inter') }}</span>
          </p>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-12 bg-gradient-to-b from-transparent via-black/30 to-black pointer-events-none"></div>
      </div>
      <p class="text-[18px] leading-snug opacity-80 font-light text-center mb-4" data-scroll-animation="fade-in-up" data-delay="1000">
        {{ __('messages.hero_slogan_1') }}<br>{{ __('messages.hero_slogan_2') }}<br>{{ __('messages.hero_slogan_3') }}
      </p>
      @php
              $pesan1 = "Halo, saya tertarik untuk mengetahui lebih banyak tentang Academy Next Top Model. Mohon infonya ya, terima kasih!";
          @endphp

          <a href="https://api.whatsapp.com/send?phone=6281214237742&text={{ urlencode($pesan1) }}" target="_blank"
        class="inline-flex items-center px-4 py-2 border border-white rounded-full text-white text-[14px] mb-3
                hover:bg-white hover:text-black hover:border-black transition animate-shimmer" data-scroll-animation="scale-in" data-delay="1200">
        {{ __('messages.lets_collaborate') }} <span class="ml-2">↗</span>
      </a>
    </div>
  </div>

<!-- About Us Section -->
<section id="aboutus" class="flex flex-col md:flex-row mt-[100px] md:mt-[250px] min-h-screen md:h-screen lazy-section opacity-0 -translate-y-10 transition duration-1000 ease-in-out" data-scroll-animation="fade-in-up">

  
  <!-- Left -->
  <div class="w-full md:w-1/2 h-[300px] md:h-full overflow-hidden" data-scroll-animation="fade-in-left" data-delay="200">
    <img src="{{ asset('img/becomeamodel.png') }}" alt="Model Image" class="w-full h-full object-cover animate-float" loading="lazy">
  </div>

  <!-- Right -->
  <div class="w-full md:w-1/2 px-6 md:px-[60px] py-10 md:py-[80px] flex flex-col justify-center bg-black" data-scroll-animation="fade-in-right" data-delay="400">
    <h1 class="font-['Fondamento'] text-[36px] md:text-[60px] leading-tight mb-[20px] md:mb-[30px] animate-pulse-slow">
      {{ __('messages.about_us2') }}
    </h1>
    <p class="text-[14px] md:text-[15px] mb-[16px] md:mb-[20px] leading-relaxed" data-scroll-animation="fade-in-up" data-delay="600">
      {{ __('messages.about_us_desc_1') }}
    </p>
    <p class="text-[14px] md:text-[15px] mb-[16px] md:mb-[20px] leading-relaxed" data-scroll-animation="fade-in-up" data-delay="800">
      {{ __('messages.about_us_desc_2') }}
    </p>
    @php
              $pesan1 = "Halo, saya tertarik untuk mengetahui lebih banyak tentang Academy Next Top Model. Mohon infonya ya, terima kasih!";
          @endphp

          <a href="https://api.whatsapp.com/send?phone=6281214237742&text={{ urlencode($pesan1) }}" target="_blank" class="flex items-center border border-white rounded-full pl-6 md:pl-8 pr-2 py-2 mt-4 md:mt-5 w-full max-w-[250px] h-[52px] md:h-[60px] group transition-colors duration-300 bg-black animate-shimmer" data-scroll-animation="scale-in" data-delay="1000">
      <span class="text-white text-[16px] tracking-wide flex-1">{{ __('messages.more_info') }}</span>
      <span class="bg-white rounded-full w-10 md:w-12 h-10 md:h-12 flex items-center justify-center transition group-hover:bg-black ml-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 md:w-7 h-6 md:h-7 text-black group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 16l7-7m0 0v6m0-6H9" transform="rotate(45 12 12)" />
        </svg>
      </span>
    </a>
  </div>
</section>

@php
  $talenSectionLinksDesktop = [
    __('messages.talent_meet') => '#meet-our-talent',
    __('messages.talent_new') => '#new-models',
    __('messages.talent_faces') => '#face-of-models',
    __('messages.talent_explore') => url('/models'),
    __('messages.talent_popular') => '#popular-talent',
  ];
@endphp


  <!-- Talent Section -->
  <section id="meet-our-talent" class="mt-[100px] md:mt-[150px] bg-black lazy-section opacity-0 -translate-y-10 transition duration-1000 ease-in-out talent-section" data-scroll-animation="fade-in-up">
  <div class="max-w-[1280px] mx-auto px-4">
    <div class="flex flex-col lg:flex-row justify-center items-center lg:gap-[120px] gap-[60px]">
      
      <!-- Left Column -->
      <div class="grid grid-cols-2 gap-4 lg:flex lg:flex-col" data-scroll-animation="fade-in-left" data-delay="200">
        @php
          $leftCards = [
            ['img' => 'img/talent/model1.png', 'title' => __('messages.talent_meet'), 'link' => '#meet-our-talents-section'],
            ['img' => 'img/talent/model2.png', 'title' => __('messages.talent_faces'), 'link' => '#face-of-models'],
          ];
        @endphp

        @foreach($leftCards as $index => $card)
        <a href="{{ $card['link'] }}" class="talent-card-link block w-full max-w-[280px] transition-all duration-300 hover:scale-105">
          <div class="relative w-full h-[350px] rounded-[20px] overflow-hidden hover-lift" data-scroll-animation="scale-in" data-delay="{{ 300 + ($index * 100) }}">
            <img src="{{ asset($card['img']) }}" alt="{{ $card['title'] }}" class="w-full h-full object-cover block rounded-[20px] animate-float">
            <div class="absolute top-0 left-0 right-0 p-[15px] flex justify-between items-start">
              <span class="font-['Newsreader'] text-[18px] lg:text-[22px] z-10 max-w-[150px] leading-tight text-stroke-white">{{ $card['title'] }}</span>
              <div class="bg-black text-white rounded-full w-[32px] h-[32px] flex items-center justify-center font-bold text-[14px] lg:text-[16px] mt-1 transition-all duration-300 hover:bg-black hover:text-white z-10 hover-glow">
                ↗
              </div>
            </div>
          </div>
        </a>
        @endforeach
      </div>

      <!-- Center Column -->
      <div class="flex flex-col items-center gap-[20px] max-w-[320px] text-center" data-scroll-animation="slide-in-down" data-delay="400">
        <div class="w-full">
          <p class="font-['Fondamento'] text-white text-[50px] md:text-[60px] lg:text-[70px] leading-none tracking-wider animate-pulse-slow">{{ __('messages.talent_our') }}</p>
          <p class="font-['Fondamento'] text-white text-[50px] md:text-[60px] lg:text-[70px] leading-none tracking-wider -mt-2 animate-pulse-slow">{{ __('messages.talent_talents') }}</p>
        </div>
        <a href="#new-models" class="talent-card-link block w-full max-w-[320px] transition-all duration-300 hover:scale-105">
          <div class="relative w-full h-[400px] rounded-[20px] overflow-hidden hover-lift" data-scroll-animation="scale-in" data-delay="600">
            <img src="{{ asset('img/talent/model3.jpg') }}" alt="{{ __('messages.talent_new') }}" class="w-full h-full object-cover block rounded-[20px] animate-float">
            <div class="absolute top-0 left-0 right-0 p-[15px] flex justify-between items-start">
              <span class="font-['Newsreader'] text-[18px] z-10 max-w-[150px] leading-tight text-stroke-white">{{ __('messages.talent_new') }}</span>
              <div class="bg-black text-white rounded-full w-[28px] h-[28px] flex items-center justify-center font-bold text-[14px] mt-1 transition-all duration-300 hover:bg-black hover:text-white z-10 hover-glow">↗</div>
            </div>
          </div>
        </a>
        <p class="font-['Newsreader'] text-[18px] md:text-[22px] lg:text-[26px] text-gray-300 leading-relaxed mt-2" data-scroll-animation="fade-in-up" data-delay="800">
          {{ __('messages.talent_ignite') }}
        </p>
      </div>

      <!-- Right Column -->
      <div class="grid grid-cols-2 gap-4 lg:flex lg:flex-col" data-scroll-animation="fade-in-right" data-delay="200">
        @php
          $rightCards = [
            ['img' => 'img/talent/model4.jpg', 'title' => __('messages.talent_explore'), 'link' => url('/models')],
            ['img' => 'img/talent/model5.jpg', 'title' => __('messages.talent_popular'), 'link' => '#popular-talent'],
          ];
        @endphp

        @foreach($rightCards as $index => $card)
        <a href="{{ $card['link'] }}" class="talent-card-link block w-full max-w-[280px] transition-all duration-300 hover:scale-105">
          <div class="relative w-full h-[350px] rounded-[20px] overflow-hidden hover-lift" data-scroll-animation="scale-in" data-delay="{{ 300 + ($index * 100) }}">
            <img src="{{ asset($card['img']) }}" alt="{{ $card['title'] }}" class="w-full h-full object-cover block rounded-[20px] animate-float">
            <div class="absolute top-0 left-0 right-0 p-[15px] flex justify-between items-start">
              <span class="font-['Newsreader'] text-[18px] z-10 max-w-[150px] leading-tight text-stroke-white">{{ $card['title'] }}</span>
              <div class="bg-black text-white rounded-full w-[28px] h-[28px] flex items-center justify-center font-bold text-[14px] mt-1 transition-all duration-300 hover:bg-black hover:text-white z-10 hover-glow">
                ↗
              </div>
            </div>
          </div>
        </a>
        @endforeach
      </div>

    </div>
  </div>
</section>

      
      


<!-- Faces of Models Section (Responsive 1 kode) -->
<section id="face-of-models" class="mt-[100px] bg-black lazy-section opacity-0 -translate-y-10 transition duration-700 ease-in-out" data-scroll-animation="fade-in-up">
  <div class="max-w-[1200px] mx-auto px-4">
    <h2 class="font-['Fondamento'] text-white text-[40px] sm:text-[50px] md:text-[60px] text-center mb-16 animate-pulse-slow" data-scroll-animation="slide-in-down" data-delay="200">
      {{ __('messages.face_of_models') }}
    </h2>
    
    <!-- Responsive Grid: 2 cols on mobile/tablet, 4 cols on desktop -->
    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 place-items-center">
      @foreach($ourTalents as $index => $ft)
        <div class="relative w-full max-w-[250px] h-[320px] rounded-[20px] overflow-hidden bg-neutral-800 flex flex-col hover-lift" data-scroll-animation="scale-in" data-delay="{{ 400 + ($index * 100) }}">
          <img src="{{ asset('storage/' . ltrim($ft->image ?? '', '/')) }}" alt="Our Talent {{ $loop->iteration }}" class="w-full h-full object-cover animate-float">


          <!-- Portfolio Button -->
<a href="{{ url('/models') }}"
  class="absolute top-2 right-2 bg-white bg-opacity-80 rounded-full p-2 shadow hover:bg-black group transition hover-glow">
  <svg xmlns="http://www.w3.org/2000/svg"
    class="w-4 h-4 text-black group-hover:text-white transition"
    fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
  </svg>
</a>


          <!-- Model Name — gaya seragam dengan Hire Models / Sabrina -->
          <div class="absolute left-3 bottom-3 z-10 text-left">
            <h3 class="model-card-name">
              Talent {{ $loop->iteration }}
            </h3>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<!-- Find Models Button -->
<div class="flex justify-center mt-8 mb-12 w-full" data-scroll-animation="fade-in-up" data-delay="800">
  <a href="{{ url('/models') }}">
    <button class="flex items-center gap-2 bg-transparent border border-white rounded-full px-6 py-3 text-white transition duration-300 hover:bg-white hover:text-black animate-shimmer hover-glow">
      {{ __('messages.find_models') }}
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
      </svg>
    </button>
  </a>
</div>



<!-- New Models Section -->
<section id="new-models" class="mt-[100px] bg-black lazy-section opacity-0 translate-y-10 transition duration-700 ease-in-out" data-scroll-animation="fade-in-up">
  <div class="max-w-[1200px] mx-auto px-4">
    <h2 class="font-['Fondamento'] text-white text-[40px] sm:text-[50px] md:text-[60px] text-center mb-16 animate-pulse-slow" data-scroll-animation="slide-in-down" data-delay="200">
      {{ __('messages.new_models_title2') }}
    </h2>

    <!-- Responsive Grid: 2 cols on mobile/tablet, 4 on desktop -->
    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 place-items-center" id="newModelsDesktop" data-scroll-animation="fade-in-up" data-delay="400">
    </div>

    <!-- Button -->
    <div class="flex justify-center mt-8 mb-12 w-full" data-scroll-animation="fade-in-up" data-delay="800">
      <a href="{{ url('/models') }}">
        <button class="flex items-center gap-2 bg-transparent border border-white rounded-full px-6 py-3 text-white transition duration-300 hover:bg-white hover:text-black animate-shimmer hover-glow">
          {{ __('messages.find_models') }}
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
          </svg>
        </button>
      </a>
    </div>
  </div>
</section>


<script>
function renderNewModels(models, target) {
  let html = '';
  if (!models.length) {
    html = '<div class="col-span-full text-center text-gray-400">No new models found.</div>';
  } else {
    models.forEach((model, index) => {
      let firstName = model.nama_model ? model.nama_model.split(' ')[0] : '';
      let photo = model.photo || '';
      // DB bisa simpan "photos/xxx.png" atau hanya "xxx.png"
      if (photo && !photo.startsWith('http') && !photo.startsWith('/')) {
        photo = photo.startsWith('photos/') ? ('/storage/' + photo) : ('/storage/photos/' + photo);
      } else if (!photo) {
        photo = '/img/models_list/jasmine.png';
      }
      html += `<div class="relative w-full aspect-[5/7] max-w-[250px] rounded-[20px] overflow-hidden bg-neutral-800 flex flex-col items-center justify-end hover-lift" data-scroll-animation="scale-in" data-delay="${400 + (index * 100)}">
  <img src="${photo}" alt="${firstName}" class="w-full h-full object-cover animate-float">
  <a href="/portofolio/${model.id_model}" class="absolute top-2 right-2 bg-white bg-opacity-80 rounded-full p-2 shadow transition hover:bg-black group hover-glow">
  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-black transition group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
  </svg>
</a>
  <div class="absolute left-3 bottom-3 z-10 font-['Newsreader'] text-black text-left">
    <h3 class="text-[22px] font-bold leading-none">${firstName}</h3>
  </div>
</div>`;
    });
  }
  document.getElementById(target).innerHTML = html;
  
  // Re-initialize animations for dynamically added content
  const newElements = document.querySelectorAll('[data-scroll-animation]');
  newElements.forEach((element, index) => {
    element.style.opacity = '0';
    setTimeout(() => {
      element.style.opacity = '1';
      element.classList.add('animate-scale-in');
    }, index * 100);
  });
}

fetch('/api/new-models')
  .then(res => res.json())
  .then(models => {
    renderNewModels(models, 'newModelsDesktop');
  });
</script>


<!-- Popular Talent Section -->
<section id="popular-talent" class="mt-[100px] bg-black lazy-section opacity-0 -translate-y-10 transition duration-1000 ease-in-out" data-scroll-animation="fade-in-up">
  <div class="max-w-[1200px] mx-auto px-4">
    <h2 class="font-['Fondamento'] text-white text-[60px] text-center mb-16 animate-pulse-slow" data-scroll-animation="slide-in-down" data-delay="200">
      {{ __('messages.new_popular_title2') }}
    </h2>

    <!-- Responsive grid -->
    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-8 place-items-center">
      @foreach($popularTalents as $index => $pt)
        <div class="relative w-full max-w-[250px] aspect-[5/7] rounded-[20px] overflow-hidden bg-neutral-800 hover-lift" data-scroll-animation="scale-in" data-delay="{{ 400 + ($index * 100) }}">
          <img src="{{ asset('storage/' . ltrim($pt->image ?? '', '/')) }}" alt="Popular Talent {{ $loop->iteration }}" class="w-full h-full object-cover animate-float">
        </div>
      @endforeach
    </div>

    <div class="flex justify-center mt-8 mb-12 w-full" data-scroll-animation="fade-in-up" data-delay="800">
      <a href="{{ url('/models') }}">
        <button class="flex items-center gap-2 bg-transparent border border-white rounded-full px-6 py-3 text-white transition duration-300 hover:bg-white hover:text-black animate-shimmer hover-glow">
          {{ __('messages.find_models') }}
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
          </svg>
        </button>
      </a>
    </div>
  </div>
</section>


<!-- Benefits Section -->
<section class="hidden sm:block mt-[160px] bg-black lazy-section opacity-0 translate-y-10 transition duration-700 ease-in-out" data-scroll-animation="fade-in-up">
  <div class="max-w-[1200px] mx-auto px-4">
    
    <!-- Title & Tabs -->
    <div class="text-center mb-16" data-scroll-animation="slide-in-down" data-delay="200">
      <h2 class="font-['Fondamento'] text-white text-[70px] mb-8 animate-pulse-slow">
        {{ __('messages.benefits_title') }}
      </h2>
      <div class="flex justify-center gap-8 text-[30px]">
        <button onclick="showBenefits('clients')" id="clientsTab" class="text-white opacity-100 transition-opacity duration-300 hover-glow">
          {{ __('messages.benefits_clients') }}
        </button>
        <button onclick="showBenefits('models')" id="modelsTab" class="text-white opacity-40 transition-opacity duration-300 hover-glow">
          {{ __('messages.benefits_models') }}
        </button>
      </div>
    </div>

    <!-- Clients Benefits -->
    <div id="clientsBenefits" class="grid grid-cols-3 gap-8">
      @for ($i = 1; $i <= 6; $i++)
        @php
          $colors = ['FF4E4E', '00C2FF', 'FFB800', 'FF00B8', 'B800FF', '00FFB8'];
        @endphp
        <div class="bg-white rounded-[20px] p-4 hover-lift" data-scroll-animation="scale-in" data-delay="{{ ($i - 1) * 100 }}">
          <div class="flex items-center gap-4 mb-4">
            <span class="font-['Newsreader'] text-[24px] font-bold text-black border border-[#{{ $colors[$i - 1] }}] rounded-full w-[45px] h-[45px] flex items-center justify-center animate-pulse-slow">
              0{{ $i }}
            </span>
          </div>
          <ul class="text-sm leading-relaxed text-black list-disc pl-4 font-semibold space-y-2">
            <li>{{ __("messages.benefits_clients_{$i}") }}</li>
            @if ($i == 1)
              <li>{{ __('messages.benefits_clients_1b') }}</li>
            @endif
          </ul>
        </div>
      @endfor
    </div>

    <!-- Models Benefits -->
    <div id="modelsBenefits" class="hidden mt-12">
      <!-- Top 3 -->
      <div class="grid grid-cols-3 gap-8 mb-8">
        @php
          $modelsColors = ['FF4E4E', '00C2FF', 'FFB800', 'FF00B8', 'B800FF'];
        @endphp
        @for ($i = 1; $i <= 3; $i++)
          <div class="bg-white rounded-[20px] p-4 hover-lift" data-scroll-animation="scale-in" data-delay="{{ ($i - 1) * 100 }}">
            <div class="flex items-center gap-4 mb-4">
              <span class="font-['Newsreader'] text-[24px] font-bold text-black border border-[#{{ $modelsColors[$i - 1] }}] rounded-full w-[45px] h-[45px] flex items-center justify-center animate-pulse-slow">
                0{{ $i }}
              </span>
            </div>
            <ul class="text-sm leading-relaxed text-black list-disc pl-4 font-semibold space-y-2">
              <li>{{ __("messages.benefits_models_{$i}") }}</li>
              @if (in_array($i, [1, 3]))
                <li>{{ __("messages.benefits_models_{$i}") }}</li>
              @endif
            </ul>
          </div>
        @endfor
      </div>

      <!-- Last 2 centered -->
      <div class="flex justify-center gap-8">
        @for ($i = 4; $i <= 5; $i++)
          <div class="bg-white rounded-[20px] p-4 w-[384px] hover-lift" data-scroll-animation="scale-in" data-delay="{{ ($i - 1) * 100 }}">
            <div class="flex items-center gap-4 mb-4">
              <span class="font-['Newsreader'] text-[24px] font-bold text-black border border-[#{{ $modelsColors[$i - 1] }}] rounded-full w-[45px] h-[45px] flex items-center justify-center animate-pulse-slow">
                0{{ $i }}
              </span>
            </div>
            <ul class="text-sm leading-relaxed text-black list-disc pl-4 font-semibold">
              <li>{{ __("messages.benefits_models_{$i}") }}</li>
            </ul>
          </div>
        @endfor
      </div>
    </div>

  </div>
</section>


<!-- Benefits Section Mobile -->
<section class="sm:hidden flex flex-col items-center bg-black px-4 pt-10" data-scroll-animation="fade-in-up">
  <!-- Title -->
  <h2 class="font-['Fondamento'] text-white text-[70px] mb-8 text-center animate-pulse-slow" data-scroll-animation="slide-in-down" data-delay="200">
    {{ __('messages.benefits_title') }}
  </h2>

  <!-- Tabs -->
  <div class="flex justify-center gap-4 mb-6" data-scroll-animation="fade-in-up" data-delay="400">
    <button id="mobileClientsTab" class="text-white text-[22px] font-bold opacity-100 border-b-2 border-white px-3 py-1 transition hover-glow">
      {{ __('messages.benefits_clients') }}
    </button>
    <button id="mobileModelsTab" class="text-white text-[22px] font-bold opacity-40 border-b-2 border-transparent px-3 py-1 transition hover-glow">
      {{ __('messages.benefits_models') }}
    </button>
  </div>

  <!-- Clients Benefits -->
  <div id="mobileClientsBenefits" class="flex flex-col gap-4 w-full max-w-[400px]">
    @php $colors = ['#FF4E4E', '#00C2FF', '#FFB800', '#FF00B8', '#B800FF', '#00FFB8']; @endphp
    @for ($i = 1; $i <= 6; $i++)
      <div class="bg-white rounded-[20px] p-4 hover-lift" data-scroll-animation="scale-in" data-delay="{{ ($i - 1) * 100 }}">
        <div class="flex items-center gap-4 mb-2">
          <span class="font-['Newsreader'] text-[20px] font-bold text-black border rounded-full w-[38px] h-[38px] flex items-center justify-center animate-pulse-slow"
                style="border-color: {{ $colors[$i - 1] }}">
            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
          </span>
        </div>
        <ul class="text-sm leading-relaxed text-black list-disc pl-4 font-semibold space-y-1">
          <li>{{ __("messages.benefits_clients_{$i}") }}</li>
          @if($i == 1)
            <li>{{ __('messages.benefits_clients_1b') }}</li>
          @endif
        </ul>
      </div>
    @endfor
  </div>

  <!-- Models Benefits -->
  <div id="mobileModelsBenefits" class="hidden w-full max-w-[400px] flex-col gap-4">
    @php $modelColors = ['#FF4E4E', '#00C2FF', '#FFB800', '#FF00B8', '#B800FF']; @endphp
    @for ($i = 1; $i <= 5; $i++)
      <div class="bg-white rounded-[20px] p-4 hover-lift" data-scroll-animation="scale-in" data-delay="{{ ($i - 1) * 100 }}">
        <div class="flex items-center gap-4 mb-2">
          <span class="font-['Newsreader'] text-[20px] font-bold text-black border rounded-full w-[38px] h-[38px] flex items-center justify-center animate-pulse-slow"
                style="border-color: {{ $modelColors[$i - 1] }}">
            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
          </span>
        </div>
        <ul class="text-sm leading-relaxed text-black list-disc pl-4 font-semibold space-y-1">
          <li>{{ __("messages.benefits_models_{$i}") }}</li>
          @if(in_array($i, [1, 3]))
            <li>{{ __("messages.benefits_models_{$i}") }}</li>
          @endif
        </ul>
      </div>
    @endfor
  </div>
</section>


<!-- Become a Model Section -->
<section class="mt-[200px] lazy-section opacity-0 translate-y-10 transition duration-700 ease-in-out" data-scroll-animation="fade-in-up">
  <div class="flex flex-col lg:flex-row h-auto lg:h-screen">
    
    <!-- Right Panel (Image on top for mobile/tablet) -->
    <div class="w-full lg:w-1/2 h-[300px] sm:h-[400px] lg:h-auto overflow-hidden" data-scroll-animation="fade-in-left" data-delay="200">
      <img src="{{ asset('img/becomeamodel.png') }}" alt="Model Image" class="w-full h-full object-cover animate-float">
    </div>

    <!-- Left Panel -->
    <div class="w-full lg:w-1/2 px-6 sm:px-[60px] py-[40px] sm:py-[60px] lg:py-[80px] flex flex-col justify-center bg-black" data-scroll-animation="fade-in-right" data-delay="400">
      <h1 class="font-['Fondamento'] text-[40px] sm:text-[50px] lg:text-[60px] leading-tight mb-[20px] sm:mb-[30px] text-white animate-pulse-slow">
        {{ __('messages.become_model_title') }}
      </h1>
      <p class="text-[14px] sm:text-[15px] mb-4 leading-[1.8] text-white" data-scroll-animation="fade-in-up" data-delay="600">
        {{ __('messages.become_model_description') }}
      </p>
      <p class="text-[14px] sm:text-[15px] mb-4 leading-[1.8] text-white" data-scroll-animation="fade-in-up" data-delay="800">
        {{ __('messages.become_model_description2') }}
      </p>
      <a href="{{ url('/joinacademy') }}" class="flex items-center border border-white rounded-full pl-6 pr-2 py-2 mt-5 w-[220px] sm:w-[250px] h-[52px] sm:h-[60px] group transition-colors duration-300 bg-black animate-shimmer" data-scroll-animation="scale-in" data-delay="1000">
        <span class="text-white text-[16px] sm:text-lg tracking-wide flex-1">{{ __('messages.join_now') }}</span>
        <span class="bg-white rounded-full w-10 h-10 sm:w-12 sm:h-12 flex items-center justify-center transition group-hover:bg-black ml-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 sm:w-7 sm:h-7 text-black group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16l7-7m0 0v6m0-6H9" transform="rotate(45 12 12)" />
          </svg>
        </span>
      </a>
    </div>

  </div>
</section>


<!-- Hire Models Section - Responsive All -->
<section class="py-20 px-4 bg-black lazy-section opacity-0 translate-y-10 transition duration-700 ease-in-out" data-scroll-animation="fade-in-up">
  <h1 class="font-['Fondamento'] text-[40px] sm:text-[56px] text-center mb-4 text-white leading-none animate-pulse-slow" data-scroll-animation="slide-in-down" data-delay="200">
    {{ __('messages.hire_models_title') }}
  </h1>
  <p class="text-[16px] sm:text-[18px] text-center text-white leading-relaxed mb-10 sm:mb-12" data-scroll-animation="fade-in-up" data-delay="400">
    {{ __('messages.hire_models_description') }}
  </p>

  <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-8 max-w-[1200px] mx-auto">
  @foreach($hireModels as $index => $model)
    <div class="relative w-full max-w-[200px] aspect-[3/4] bg-white rounded-[16px] shadow-lg overflow-hidden mx-auto flex flex-col justify-between hover-lift" data-scroll-animation="scale-in" data-delay="{{ 600 + ($index * 100) }}">
      <img src="{{ asset('storage/' . ltrim($model->photo ?? '', '/')) }}" alt="{{ $model->nama_model }}" class="w-full h-full object-cover absolute inset-0 animate-float" />
      <div class="absolute top-2 right-2 z-10">
        <a href="{{ url('/portofolio/' . $model->id_model) }}" class="hover-glow">
          <span class="flex items-center justify-center text-black text-xl font-bold">➝</span>
        </a>
      </div>
      <div class="absolute left-2 bottom-2 z-10 font-['Newsreader'] text-black text-left">
        <h3 class="text-[16px] sm:text-[22px] font-bold leading-none" style="text-shadow: 0 0 4px #fff, 0 0 2px #fff;">
          {{ explode(' ', $model->nama_model ?? 'Model')[0] }}
        </h3>
        <p class="text-[12px] sm:text-[13px] leading-none">
          {{ explode(',', $model->categories ?? '-')[0] }}
        </p>
      </div>
    </div>
  @endforeach
</div>


  <a href="{{ url('/models') }}" data-scroll-animation="fade-in-up" data-delay="800">
    <p class="mt-10 text-center text-white underline cursor-pointer text-[14px] sm:text-[16px] animate-shimmer hover-glow">
      {{ __('messages.see_more') }}
    </p>
  </a>
</section>


@php
  $whyChooseUsItems = [
    [
      'number' => 1,
      'title' => __('messages.why_choose_us_1'),
      'desc' => __('messages.why_choose_us_1b'),
      'color' => '#FF4E4E',
    ],
    [
      'number' => 2,
      'title' => __('messages.why_choose_us_2'),
      'desc' => __('messages.why_choose_us_2b'),
      'color' => '#00BCD4',
    ],
    [
      'number' => 3,
      'title' => __('messages.why_choose_us_3'),
      'desc' => __('messages.why_choose_us_3b'),
      'color' => '#9C27B0',
    ],
    [
      'number' => 4,
      'title' => __('messages.why_choose_us_4'),
      'desc' => __('messages.why_choose_us_4b'),
      'color' => '#FF9800',
    ],
    [
      'number' => 5,
      'title' => __('messages.why_choose_us_5'),
      'desc' => __('messages.why_choose_us_5b'),
      'color' => '#2196F3',
    ],
  ];
@endphp

<section class="px-4 sm:px-[60px] lg:px-[120px] py-[80px] w-full bg-black lazy-section opacity-0 translate-y-10 transition duration-700 ease-in-out" data-scroll-animation="fade-in-up">
  <h2 class="text-white text-[36px] sm:text-[48px] lg:text-[64px] text-center mb-16 font-['Fondamento'] animate-pulse-slow" data-scroll-animation="slide-in-down" data-delay="200">
    {{ __('messages.why_choose_us_title') }}
  </h2>

  <!-- Mobile & Tablet -->
  <div class="block lg:hidden space-y-10">
    @foreach ($whyChooseUsItems as $index => $item)
      <div class="border-b border-white pb-6" data-scroll-animation="fade-in-up" data-delay="{{ 400 + ($index * 100) }}">
        <div class="w-[60px] h-[60px] sm:w-[80px] sm:h-[80px] rounded-full text-white text-center font-bold text-3xl sm:text-4xl leading-[60px] sm:leading-[80px] mb-4 animate-pulse-slow" style="background-color: {{ $item['color'] }};">
          {{ $item['number'] }}
        </div>
        <h3 class="text-lg sm:text-xl font-semibold text-white">{{ $item['title'] }}</h3>
        <p class="text-sm sm:text-base text-gray-300 leading-relaxed mt-1">{{ $item['desc'] }}</p>
      </div>
    @endforeach
  </div>

  <!-- Desktop -->
  <div class="hidden lg:flex">
    <!-- Left Side -->
    <div class="w-1/2 border-r border-white" data-scroll-animation="fade-in-left" data-delay="400">
      @foreach ([0, 2, 4] as $i)
        <div class="p-8 flex flex-col {{ $i !== 4 ? 'border-b border-white' : '' }}" data-scroll-animation="fade-in-up" data-delay="{{ 600 + ($i * 100) }}">
          <div class="w-[80px] h-[80px] rounded-full text-white text-center font-bold text-4xl leading-[80px] mb-4 animate-pulse-slow" style="background-color: {{ $whyChooseUsItems[$i]['color'] }};">
            {{ $whyChooseUsItems[$i]['number'] }}
          </div>
          <h3 class="text-xl font-semibold text-white">{{ $whyChooseUsItems[$i]['title'] }}</h3>
          <p class="text-base text-gray-300 leading-relaxed mt-2">{{ $whyChooseUsItems[$i]['desc'] }}</p>
        </div>
      @endforeach
    </div>

    <!-- Right Side -->
    <div class="w-1/2 mt-24" data-scroll-animation="fade-in-right" data-delay="400">
      @foreach ([1, 3] as $i)
        <div class="h-[300px] p-8 {{ $i === 1 ? 'border-b border-white' : '' }}" data-scroll-animation="fade-in-up" data-delay="{{ 600 + ($i * 100) }}">
          <div class="w-[80px] h-[80px] rounded-full text-white text-center font-bold text-4xl leading-[80px] animate-pulse-slow" style="background-color: {{ $whyChooseUsItems[$i]['color'] }};">
            {{ $whyChooseUsItems[$i]['number'] }}
          </div>
          <h3 class="text-2xl font-semibold text-white mt-4">{{ $whyChooseUsItems[$i]['title'] }}</h3>
          <p class="text-base text-gray-300 mt-2 w-[500px]">{{ $whyChooseUsItems[$i]['desc'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>


<!-- Contact Section -->
<section id="contactus" class="flex flex-col items-center justify-center min-h-screen bg-black relative py-16 sm:py-20 px-4 lazy-section opacity-0 translate-y-10 transition duration-700 ease-in-out" data-scroll-animation="fade-in-up">
  <!-- Content Row (tetap row di semua ukuran) -->
  <div class="flex flex-row items-center justify-center w-full gap-2 sm:gap-6 md:gap-10">
    
    <!-- CONTACT (vertical) -->
    <div class="flex flex-col items-center justify-center h-[150px] sm:h-[200px] md:h-[300px] lg:h-[400px] z-20" data-scroll-animation="fade-in-left" data-delay="200">
      <span class="font-fondamento text-white text-[40px] sm:text-[60px] md:text-[80px] lg:text-[110px] animate-pulse-slow" style="writing-mode: vertical-rl; transform: rotate(180deg); letter-spacing: 0.1em;">
        {{ __('messages.contact') }}
      </span>
    </div>

    <!-- Image -->
    <div class="z-10" data-scroll-animation="scale-in" data-delay="400">
      <img src="{{ asset('img/contact-model.png') }}" alt="Contact Model"
        class="w-[180px] sm:w-[280px] md:w-[400px] lg:w-[510px] h-auto object-cover rounded-[16px] bg-gray-200 animate-float" />
    </div>

    <!-- US -->
    <div class="flex items-end h-[150px] sm:h-[200px] md:h-[300px] lg:h-[700px] z-20" data-scroll-animation="fade-in-right" data-delay="200">
      <span class="font-fondamento text-white text-[40px] sm:text-[60px] md:text-[80px] lg:text-[110px] tracking-wider animate-pulse-slow">
        {{ __('messages.us') }}
      </span>
    </div>
  </div>

  <!-- Card -->
  <div class="mt-8 bg-[#C4C4C4] rounded-[24px] lg:rounded-[30px] shadow-lg w-full max-w-[1000px] px-6 sm:px-10 md:px-12 py-8 flex flex-col items-center z-30 hover-lift" data-scroll-animation="slide-in-up" data-delay="600">
    <h2 class="font-fondamento text-black text-[28px] sm:text-[36px] md:text-[44px] lg:text-[50px] text-center mb-2 animate-pulse-slow">
      {{ __('messages.where_dreams_walk_the_runway') }}
    </h2>
    <p class="text-center text-gray-700 mb-6 text-[14px] sm:text-[15px] md:text-[16px] max-w-[600px]" data-scroll-animation="fade-in-up" data-delay="800">
      {{ __('messages.from_photoshoot_to_runway_coaching') }}
    </p>
    <div class="flex flex-col sm:flex-row items-center justify-center w-full gap-4" data-scroll-animation="fade-in-up" data-delay="1000">
      <span class="font-bold text-black text-[20px] sm:text-[24px] md:text-[28px] text-center sm:text-left">
        {{ __('messages.join_the_movement') }}
      </span>
      @php
              $pesan1 = "Halo, saya tertarik untuk mengetahui lebih banyak tentang Academy Next Top Model. Mohon infonya ya, terima kasih!";
          @endphp

          <a href="https://api.whatsapp.com/send?phone=6281214237742&text={{ urlencode($pesan1) }}" target="_blank" rel="noopener noreferrer"
          class="bg-[#000000] text-white rounded-[16px] px-12 py-2 ml-10 text-[14px] sm:text-[16px] font-semibold whitespace-nowrap animate-shimmer hover-glow inline-flex items-center justify-center transition-all duration-300 hover:scale-105 hover:shadow-lg whatsapp-link">
          {{ __('messages.lets_collaborate') }}
          <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
          </svg>
        </a>
    </div>
  </div>
</section>





<!-- Footer -->
<footer class="w-full bg-white py-4 px-6 sm:px-8 flex flex-col sm:flex-row justify-between items-center gap-2 sm:gap-0 text-black text-[14px] sm:text-[16px] font-['Newsreader']" data-scroll-animation="fade-in-up" data-delay="200">
  <span class="animate-pulse-slow">{{ __('messages.footer') }}</span>
  <a href="https://www.instagram.com/academynexttopmodel?igsh=MTdmMDdyZXRxdjk3MA==" target="_blank" rel="noopener noreferrer" class="animate-pulse-slow hover:text-[#30B0C7] transition-colors duration-300 flex items-center gap-2 group instagram-link relative tooltip">
    <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-300" fill="currentColor" viewBox="0 0 24 24">
      <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
    </svg>
    {{ __('messages.instagram') }}
  </a>
</footer>

<!-- Scroll to Top Button -->
<button id="scrollToTopBtn" class="fixed bottom-6 right-6 w-12 h-12 bg-[#FFF5F2] text-black rounded-full shadow-lg hover:bg-[#D6DAC8] transition-all duration-300 transform hover:scale-110 hover:shadow-xl opacity-0 pointer-events-none z-50 flex items-center justify-center group scroll-to-top-btn">
  <svg class="w-6 h-6 transform group-hover:-translate-y-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
  </svg>
</button>


<script>
// Scroll to Top functionality
const scrollToTopBtn = document.getElementById('scrollToTopBtn');

// Show/hide button based on scroll position
window.addEventListener('scroll', () => {
  if (window.pageYOffset > 300) {
    scrollToTopBtn.classList.remove('opacity-0', 'pointer-events-none');
    scrollToTopBtn.classList.add('opacity-100', 'pointer-events-auto');
  } else {
    scrollToTopBtn.classList.add('opacity-0', 'pointer-events-none');
    scrollToTopBtn.classList.remove('opacity-100', 'pointer-events-auto');
  }
});

// Smooth scroll to top when button is clicked
scrollToTopBtn.addEventListener('click', () => {
  // Add click animation
  scrollToTopBtn.style.transform = 'scale(0.9)';
  setTimeout(() => {
    scrollToTopBtn.style.transform = 'scale(1.1)';
    setTimeout(() => {
      scrollToTopBtn.style.transform = '';
    }, 150);
  }, 100);

  // Smooth scroll to top
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  });

  // Add success feedback
  const originalHTML = scrollToTopBtn.innerHTML;
  scrollToTopBtn.innerHTML = `
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
  `;
  
  setTimeout(() => {
    scrollToTopBtn.innerHTML = originalHTML;
  }, 1000);
});

// Add keyboard navigation support
scrollToTopBtn.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    scrollToTopBtn.click();
  }
});

// Add focus management
scrollToTopBtn.addEventListener('focus', () => {
  scrollToTopBtn.style.transform = 'scale(1.05)';
});

scrollToTopBtn.addEventListener('blur', () => {
  scrollToTopBtn.style.transform = '';
});


function showBenefits(type) {
  const clientsTab = document.getElementById('clientsTab');
  const modelsTab = document.getElementById('modelsTab');
  const clientsBenefits = document.getElementById('clientsBenefits');
  const modelsBenefits = document.getElementById('modelsBenefits');

  if (type === 'clients') {
    clientsTab.classList.remove('opacity-40');
    clientsTab.classList.add('opacity-100');
    modelsTab.classList.remove('opacity-100');
    modelsTab.classList.add('opacity-40');
    clientsBenefits.classList.remove('hidden');
    modelsBenefits.classList.add('hidden');
  } else {
    modelsTab.classList.remove('opacity-40');
    modelsTab.classList.add('opacity-100');
    clientsTab.classList.remove('opacity-100');
    clientsTab.classList.add('opacity-40');
    modelsBenefits.classList.remove('hidden');
    clientsBenefits.classList.add('hidden');
  }
}

  const hamburgerBtn = document.getElementById('hamburgerBtn');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileLinks = mobileMenu.querySelectorAll('a');

  let menuOpen = false;
  hamburgerBtn.addEventListener('click', function() {
    menuOpen = !menuOpen;
    if (menuOpen) {
      mobileMenu.classList.remove('max-h-0', 'pointer-events-none', 'py-0', 'px-0');
      mobileMenu.classList.add('max-h-[600px]', 'py-4', 'px-6');
    } else {
      mobileMenu.classList.add('max-h-0', 'pointer-events-none', 'py-0', 'px-0');
      mobileMenu.classList.remove('max-h-[600px]', 'py-4', 'px-6');
    }
  });

  // Tutup menu saat link diklik
  mobileLinks.forEach(link => {
    link.addEventListener('click', function() {
      menuOpen = false;
      mobileMenu.classList.add('max-h-0', 'pointer-events-none', 'py-0', 'px-0');
      mobileMenu.classList.remove('max-h-[600px]', 'py-4', 'px-6');
    });
  });

  // Toggle Benefits Mobile
  const mobileClientsTab = document.getElementById('mobileClientsTab');
  const mobileModelsTab = document.getElementById('mobileModelsTab');
  const mobileClientsBenefits = document.getElementById('mobileClientsBenefits');
  const mobileModelsBenefits = document.getElementById('mobileModelsBenefits');

  if (mobileClientsTab && mobileModelsTab) {
    mobileClientsTab.addEventListener('click', function() {
      mobileClientsTab.classList.add('opacity-100', 'border-white');
      mobileClientsTab.classList.remove('opacity-40', 'border-transparent');
      mobileModelsTab.classList.remove('opacity-100', 'border-white');
      mobileModelsTab.classList.add('opacity-40', 'border-transparent');
      mobileClientsBenefits.classList.remove('hidden');
      mobileClientsBenefits.classList.add('flex');
      mobileModelsBenefits.classList.add('hidden');
      mobileModelsBenefits.classList.remove('flex');
    });
    mobileModelsTab.addEventListener('click', function() {
      mobileModelsTab.classList.add('opacity-100', 'border-white');
      mobileModelsTab.classList.remove('opacity-40', 'border-transparent');
      mobileClientsTab.classList.remove('opacity-100', 'border-white');
      mobileClientsTab.classList.add('opacity-40', 'border-transparent');
      mobileModelsBenefits.classList.remove('hidden');
      mobileModelsBenefits.classList.add('flex');
      mobileClientsBenefits.classList.add('hidden');
      mobileClientsBenefits.classList.remove('flex');
    });
  }
</script>
@include('chatbot.widget_fixed')
</body>
</html>

