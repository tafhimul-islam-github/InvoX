// Chatbot State
let isTyping = false;

function toggleChat() {
    const chat = document.getElementById('chatWindow');
    const isVisible = chat.style.display === 'flex';
    
    if (isVisible) {
        chat.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            chat.style.display = 'none';
        }, 300);
    } else {
        chat.style.display = 'flex';
        chat.style.animation = 'slideIn 0.3s ease-out';
    }
}

function handleEnter(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function sendMessage() {
    const input = document.getElementById('userMsg');
    const text = input.value.trim();
    
    if (text === "" || isTyping) return;

    const chatBody = document.getElementById('chatBody');
    
    // Remove welcome message if exists
    const welcome = chatBody.querySelector('.welcome-message');
    if (welcome) welcome.remove();
    
    // User Message
    addMessage(text, 'user');
    input.value = "";
    input.focus();
    
    // Show typing indicator
    showTypingIndicator();
    
    // Get AI Response
    getAIResponse(text);
}

function addMessage(text, sender) {
    const chatBody = document.getElementById('chatBody');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}-message`;
    
    if (sender === 'user') {
        messageDiv.innerHTML = `
            <div class="message-bubble user-bubble">
                <p>${escapeHtml(text)}</p>
            </div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="message-bubble bot-bubble">
                <div class="bot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="bot-content">
                    <p>${text}</p>
                </div>
            </div>
        `;
    }
    
    chatBody.appendChild(messageDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function showTypingIndicator() {
    const chatBody = document.getElementById('chatBody');
    const typingDiv = document.createElement('div');
    typingDiv.className = 'typing-indicator';
    typingDiv.id = 'typingIndicator';
    typingDiv.innerHTML = `
        <div class="message-bubble bot-bubble">
            <div class="bot-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="typing-dots">
                <span></span><span></span><span></span>
            </div>
        </div>
    `;
    chatBody.appendChild(typingDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
    isTyping = true;
}

function hideTypingIndicator() {
    const typingIndicator = document.getElementById('typingIndicator');
    if (typingIndicator) typingIndicator.remove();
    isTyping = false;
}

async function getAIResponse(userMessage) {
    try {
        // Call Gemini API via PHP backend
        const response = await fetch('chatbot_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ message: userMessage })
        });
        
        const data = await response.json();
        
        hideTypingIndicator();
        
        if (data.success) {
            addMessage(data.response, 'bot');
        } else {
            addMessage(data.error || 'Sorry, I encountered an error. Please try again.', 'bot');
        }
    } catch (error) {
        hideTypingIndicator();
        addMessage('Sorry, I\'m having trouble connecting. Please try again later.', 'bot');
        console.error('Chatbot Error:', error);
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Quick action buttons
function sendQuickMessage(message) {
    document.getElementById('userMsg').value = message;
    sendMessage();
}

// Initialize chatbot
document.addEventListener('DOMContentLoaded', function() {
    const chatBody = document.getElementById('chatBody');
    if (chatBody && !chatBody.querySelector('.message')) {
        chatBody.innerHTML = `
            <div class="welcome-message">
                <div class="bot-avatar-large">
                    <i class="fas fa-robot"></i>
                </div>
                <h6>Hi! I'm InvoX AI Assistant</h6>
                <p class="text-muted small">How can I help you today?</p>
                <div class="quick-actions">
                    <button class="quick-btn" onclick="sendQuickMessage('How do I create an invoice?')">
                        <i class="fas fa-file-invoice"></i> Create Invoice
                    </button>
                    <button class="quick-btn" onclick="sendQuickMessage('Show my pending invoices')">
                        <i class="fas fa-clock"></i> Pending Invoices
                    </button>
                    <button class="quick-btn" onclick="sendQuickMessage('How does the tax calculation work?')">
                        <i class="fas fa-calculator"></i> Tax Info
                    </button>
                    <button class="quick-btn" onclick="sendQuickMessage('Help me with customer management')">
                        <i class="fas fa-users"></i> Customers
                    </button>
                </div>
            </div>
        `;
    }
});

