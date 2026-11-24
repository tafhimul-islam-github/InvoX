# 🤖 InvoX AI Chatbot - Setup Guide

## ✨ Features
- **Modern UI Design** - Beautiful gradient design with smooth animations
- **Google Gemini AI** - Powered by Google's latest AI model
- **Smart Responses** - Context-aware answers about invoicing
- **Quick Actions** - Pre-built question buttons
- **Real-time Typing** - Typing indicator for natural feel
- **Responsive** - Works on mobile and desktop


## 💡 How It Works

### Frontend (`chatbot.js`)
- Handles user interactions
- Sends messages to backend
- Displays responses with animations

### Backend (`chatbot_api.php`)
- Receives user messages
- Calls Gemini API
- Returns AI-generated responses
- Falls back to basic responses if API not configured

### Styling (`style.css`)
- Modern gradient design (purple/blue)
- Smooth animations and transitions
- Responsive layout
- Custom scrollbars

---


## 🐛 Troubleshooting

### Chatbot doesn't open?
- Check browser console (F12) for errors
- Ensure `chatbot.js` is loaded in footer.php

### API not working?
- Verify API key is correct
- Check internet connection
- Ensure curl is enabled in PHP
- Check `chatbot_api.php` for errors

### Styling issues?
- Clear browser cache (Ctrl + Shift + R)
- Check if `style.css` is loaded
- Verify no CSS conflicts

---

## 📱 Mobile Support
The chatbot is fully responsive:
- Adapts to screen size
- Touch-friendly buttons
- Optimized for mobile keyboards

---


## 📊 API Limits

**Gemini API Free Tier:**
- 60 requests per minute
- 1,500 requests per day
- Perfect for small to medium usage

For higher limits, check [Google AI pricing](https://ai.google.dev/pricing)

---


