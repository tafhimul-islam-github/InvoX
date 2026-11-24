                </div>
            </div> <!-- End Page Content -->
        </div> <!-- End Main Content Col -->
        </div> <!-- End Row -->
    </div> <!-- End Container -->

    <!-- Modern Chatbot Widget -->
    <div class="chatbot-btn" onclick="toggleChat()">
        <i class="fas fa-robot"></i>
        <span class="chatbot-badge">AI</span>
    </div>
    
    <div class="chat-window" id="chatWindow">
        <div class="chat-header">
            <div class="header-content">
                <div class="bot-status">
                    <div class="bot-avatar-header">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <div class="bot-name">InvoX AI Assistant</div>
                        <div class="bot-status-text">
                            <span class="status-dot"></span> Online
                        </div>
                    </div>
                </div>
                <button class="close-btn" onclick="toggleChat()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="chat-body" id="chatBody">
            <!-- Messages will be dynamically added here -->
        </div>
        
        <div class="chat-footer">
            <div class="chat-input">
                <input type="text" 
                       id="userMsg" 
                       class="chat-input-field" 
                       placeholder="Ask me anything..." 
                       onkeypress="handleEnter(event)"
                       autocomplete="off">
                <button class="send-btn" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
           
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="chatbot.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const sidebarCol = document.querySelector('.col-md-2.sidebar');
            const mainContentCol = document.querySelector('.col-md-10');
            
            sidebar.classList.toggle('collapsed');
            
            // Adjust column widths
            if (sidebar.classList.contains('collapsed')) {
                sidebarCol.style.maxWidth = '80px';
                sidebarCol.style.flex = '0 0 80px';
                mainContentCol.style.maxWidth = 'calc(100% - 80px)';
                mainContentCol.style.flex = '0 0 calc(100% - 80px)';
            } else {
                sidebarCol.style.maxWidth = '';
                sidebarCol.style.flex = '';
                mainContentCol.style.maxWidth = '';
                mainContentCol.style.flex = '';
            }
        }
        
        // Store sidebar state in localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarState = localStorage.getItem('sidebarCollapsed');
            if (sidebarState === 'true') {
                toggleSidebar();
            }
        });
        
        // Save state when toggling
        const originalToggle = toggleSidebar;
        toggleSidebar = function() {
            originalToggle();
            const sidebar = document.querySelector('.sidebar');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        };
    </script>
</body>
</html>