<?php
header('Content-Type: application/json');
require_once 'db.php';

// Get user message
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['success' => false, 'error' => 'No message provided']);
    exit;
}

// Load API key from environment variable or chatbot_config.php
$chatbotConfigFile = __DIR__ . '/chatbot_config.php';
$geminiApiKey = getenv('GEMINI_API_KEY') ?: (file_exists($chatbotConfigFile) ? ((require $chatbotConfigFile)['gemini_api_key'] ?? '') : '');

if (empty($geminiApiKey) || $geminiApiKey === 'YOUR_GEMINI_API_KEY_HERE') {
    // Graceful fallback to pre-programmed responses if API key is not configured
    echo json_encode([
        'success' => true,
        'response' => getFallbackResponse($userMessage)
    ]);
    exit;
}

// Gemini API endpoint - Using gemini-2.5-flash
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $geminiApiKey;

// Build context about InvoX system for the AI
$systemContext = "You are a helpful AI assistant for InvoX Invoice Management System. Be concise and friendly.

InvoX Features:
- Dashboard: View stats, charts, and recent invoices
- Customers: Add, edit, search customers with pagination
- Products: Manage product/service catalog
- Invoices: Create, edit, delete, email, and download invoices as PDF
- Invoice dates: Issue Date and Due Date tracking
- Reports: Generate monthly/quarterly/yearly financial reports
- System Users: Manage user accounts
- Tax: Automatic 8% tax calculation and also has tax control settings
- Recurring Invoices: Set up and manage recurring invoices, automated generation
- Invoice Reminders: Automated email reminders for overdue invoices
- Invoice Status: Open, Pending, Paid, Overdue

Answer user questions clearly in 4-5 sentences max.";

// Prepare the request payload
$payload = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => $systemContext . "\n\nUser: " . $userMessage
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.8,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 500,
        'candidateCount' => 1,
    ],
    'safetySettings' => [
        [
            'category' => 'HARM_CATEGORY_HARASSMENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_HATE_SPEECH',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ],
        [
            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
        ]
    ]
];

// Make API request
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Handle errors
if ($error) {
    echo json_encode([
        'success' => false,
        'error' => 'Connection error: ' . $error,
        'debug' => 'cURL failed to connect to Gemini API'
    ]);
    exit;
}

if ($httpCode !== 200) {
    // Parse error response
    $errorData = json_decode($response, true);
    $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : 'Unknown error';
    
    // Log detailed error for debugging
    error_log("Gemini API Error: HTTP $httpCode - Message: $errorMessage - Full Response: $response");
    
    // Return detailed error to frontend for debugging
    echo json_encode([
        'success' => false,
        'error' => "Gemini API Error (HTTP $httpCode): $errorMessage",
        'debug' => [
            'http_code' => $httpCode,
            'error_message' => $errorMessage,
            'full_response' => $response
        ],
        'fallback_used' => true,
        'response' => getFallbackResponse($userMessage)
    ]);
    exit;
}

// Parse Gemini response
$data = json_decode($response, true);

// Debug logging
error_log("Gemini API Success: HTTP $httpCode - Response: " . substr($response, 0, 500));

if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
    $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'];
    
    // Clean up the response
    $aiResponse = trim($aiResponse);
    
    // Convert markdown-style formatting to HTML
    $aiResponse = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $aiResponse); // Bold
    $aiResponse = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $aiResponse); // Italic
    $aiResponse = nl2br($aiResponse); // Line breaks
    
    echo json_encode([
        'success' => true,
        'response' => $aiResponse
    ]);
} else if (isset($data['error'])) {
    // API returned an error
    error_log("Gemini API Error: " . json_encode($data['error']));
    
    $fallbackResponse = getFallbackResponse($userMessage);
    echo json_encode([
        'success' => true,
        'response' => $fallbackResponse
    ]);
} else {
    // Unknown response format
    error_log("Unexpected Gemini response format: " . $response);
    
    $fallbackResponse = getFallbackResponse($userMessage);
    echo json_encode([
        'success' => true,
        'response' => $fallbackResponse
    ]);
}

// Fallback responses when API is not configured
function getFallbackResponse($message) {
    $message = strtolower($message);
    
    if (strpos($message, 'hello') !== false || strpos($message, 'hi') !== false) {
        return "👋 Hello! How can I help you with InvoX today?";
    }
    
    if (strpos($message, 'invoice') !== false && strpos($message, 'create') !== false) {
        return "📝 To create an invoice:<br>1. Click <strong>'Create Invoice'</strong> in the sidebar<br>2. Select a customer<br>3. Choose issue date and due date<br>4. Add products/services<br>5. Review and generate!";
    }
    
    if (strpos($message, 'pending') !== false) {
        return "⏳ To view pending invoices:<br>Go to <strong>Invoices</strong> page and filter by 'Pending' status. You can also see them on the Dashboard.";
    }
    
    if (strpos($message, 'tax') !== false) {
        return "🧮 Tax is automatically calculated at <strong>10%</strong> on all invoices. The system adds it during invoice creation.";
    }
    
    if (strpos($message, 'customer') !== false) {
        return "👥 Customer Management:<br>• View all customers on the <strong>Customers</strong> page<br>• Add new customers<br>• Edit existing customer details<br>• Search and filter customers";
    }
    
    if (strpos($message, 'report') !== false) {
        return "📊 Generate reports from the <strong>Reports</strong> page:<br>• Monthly, Quarterly, or Yearly reports<br>• Custom date ranges<br>• View invoice statistics<br>• Download as PDF";
    }
    
    if (strpos($message, 'help') !== false) {
        return "🆘 <strong>InvoX Features:</strong><br>• <strong>Dashboard</strong> - Overview & stats<br>• <strong>Customers</strong> - Manage clients<br>• <strong>Products</strong> - Service catalog<br>• <strong>Invoices</strong> - View all invoices<br>• <strong>Create Invoice</strong> - New invoice<br>• <strong>Reports</strong> - Financial reports<br>• <strong>System Users</strong> - User management<br><br>What would you like to know more about?";
    }
    
    return "💡 I'm here to help! Ask me about:<br>• Creating invoices<br>• Managing customers<br>• Tax calculations<br>• Invoice statuses<br>• Reports<br>• Any InvoX features<br><br><small>Note: For AI-powered responses, configure the Gemini API key in chatbot_api.php</small>";
}
?>
