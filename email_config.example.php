<?php
// SMTP Settings Example Configuration
// Copy this file to email_config.php and update with your real credentials.

return [
    // SMTP Settings (e.g. Gmail)
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_username' => 'your_email@gmail.com',
    'smtp_password' => 'your_app_password_here',
    
    // Sender Information
    'from_email' => 'your_email@gmail.com',
    'from_name' => 'InvoX Invoice System',
    'reply_to' => 'your_email@gmail.com',
    
    // Debug Settings (0 = off, 1 = client, 2 = client and server)
    'smtp_debug' => 0,
];
