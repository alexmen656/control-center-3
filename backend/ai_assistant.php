<?php
include "head.php";
include "ai_config.php";

class AIAssistant {
    private $openaiApiKey;

    public function __construct() {
        $this->openaiApiKey = getenv('OPENAI_API_KEY') ?: '';
    }

    public function processQuestion($question, $fileContent, $language, $isAgentMode = false, $chatHistory = [], $fileName = 'untitled') {
        if (empty($this->openaiApiKey)) {
            return $this->generateSimpleResponse($question, $fileContent, $language, $fileName);
        }

        $messages = $this->buildMessages($question, $fileContent, $language, $isAgentMode, $chatHistory, $fileName);

        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.5
        ];

        $response = $this->makeOpenAIRequest($data);

        if ($response) {
            $result = [
                'success' => true,
                'answer' => $response
            ];

            // If agent mode, try to extract code replacements
            if ($isAgentMode) {
                $replacements = $this->extractCodeReplacements($response);
                if (!empty($replacements)) {
                    $result['replacements'] = $replacements;
                }
            }

            return $result;
        }

        return [
            'success' => false,
            'message' => 'Failed to process the question using AI.'
        ];
    }

    private function buildMessages($question, $fileContent, $language, $isAgentMode, $chatHistory, $fileName) {
        $systemPrompt = 'You are an expert code assistant. Answer user questions based on the provided file content and programming language.';
        
        if ($isAgentMode) {
            $systemPrompt .= ' In agent mode, when you need to make code changes, you MUST use the following EXACT format for code replacements:

```REPLACE
OLD_CODE:
[exact code to replace - must match exactly including all whitespace and indentation]
NEW_CODE:
[new code to replace with]
END_REPLACE```

IMPORTANT RULES:
1. Use this format EVERY TIME you want to make code changes in agent mode
2. The OLD_CODE must match the existing code EXACTLY (including spaces, tabs, newlines)
3. If adding new content, use an empty OLD_CODE or a small existing anchor point
4. Always provide working, complete code in NEW_CODE
5. You can have multiple REPLACE blocks in one response
6. Explain what you are changing before or after the REPLACE blocks

Example for adding HTML sections:
```REPLACE
OLD_CODE:
    <h1>Welcome to trmt!</h1>

    <script src="main.js"></script>
NEW_CODE:
    <h1>Welcome to trmt!</h1>

    <!-- First section -->
    <section class="content-section">
        <h2>About Us</h2>
        <p>This is our about section with important information.</p>
    </section>

    <!-- Second section -->
    <section class="content-section">
        <h2>Our Services</h2>
        <p>Here we describe our services and what we offer.</p>
    </section>

    <script src="main.js"></script>
END_REPLACE```';
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ]
        ];

        // Add chat history
        foreach ($chatHistory as $message) {
            $messages[] = $message;
        }

        // Add current question with file context
        $fileContentTrimmed = trim($fileContent);
        $isMinimalContent = empty($fileContentTrimmed) || 
                           strlen($fileContentTrimmed) < 50 ||
                           strpos($fileContentTrimmed, 'console.log("Hello Monaco!")') !== false ||
                           preg_match('/^\/\/.*$/', $fileContentTrimmed) ||
                           count(explode("\n", $fileContentTrimmed)) <= 2;
        
        $prompt = "The user has asked the following question about a $language file:\n\n" .
                  "Question: $question\n\n" .
                  "File Content:\n$fileContent\n\n" .
                  "File Name:\n$fileName\n\n";
        
        if ($isAgentMode) {
            if ($isMinimalContent) {
                $prompt .= "AGENT MODE: The file content is minimal or empty. When providing complete new code (like full HTML pages), use an empty OLD_CODE or just the minimal existing content as OLD_CODE. For example:\n\n```REPLACE\nOLD_CODE:\n// Schreibe hier deinen Code...\nconsole.log(\"Hello Monaco!\")\nNEW_CODE:\n[complete new code here]\nEND_REPLACE```\n\nNEVER include END_REPLACE inside the NEW_CODE section.";
            } else {
                $prompt .= "AGENT MODE: Please provide code changes using the REPLACE format when making modifications. Be precise with OLD_CODE matching.";
            }
        } else {
            $prompt .= "Provide a detailed and helpful response.";
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];

        return $messages;
    }

    private function extractCodeReplacements($response) {
        $replacements = [];
        
        // Try different patterns to catch REPLACE blocks
        $patterns = [
            '/```REPLACE\s*OLD_CODE:\s*(.*?)\s*NEW_CODE:\s*(.*?)\s*END_REPLACE```/s',
            '/```REPLACE\s*OLD_CODE:\s*(.*?)\s*NEW_CODE:\s*(.*?)\s*```/s',
            '/REPLACE\s*OLD_CODE:\s*(.*?)\s*NEW_CODE:\s*(.*?)\s*END_REPLACE/s',
            '/```REPLACE\s*OLD_CODE:\s*(.*?)\s*NEW_CODE:\s*(.*?)(?=\s*(?:```|END_REPLACE))/s'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $response, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $oldCode = trim($match[1]);
                    $newCode = trim($match[2]);
                    
                    // Clean up any trailing END_REPLACE, closing backticks, or similar markers
                    $newCode = preg_replace('/\s*(?:END_REPLACE|```)\s*$/', '', $newCode);
                    $newCode = trim($newCode);
                    
                    // Additional cleanup for common issues
                    $newCode = preg_replace('/END_REPLACE\s*\n?\s*<\//', '</', $newCode);
                    
                    // Skip empty replacements
                    if (!empty($newCode)) {
                        $replacements[] = [
                            'oldCode' => $oldCode,
                            'newCode' => $newCode
                        ];
                    }
                }
                break; // Use first matching pattern
            }
        }
        
        return $replacements;
    }

    private function buildPrompt($question, $fileContent, $fileName, $language) {
        return "The user has asked the following question about a $language file:\n\n" .
               "Question: $question\n\n" .
               "File Content:\n$fileContent\n\n" .
               "Provide a detailed and helpful response.";
    }

    private function makeOpenAIRequest($data) {
        $jsonData = json_encode($data);

        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->openaiApiKey
                ],
                'method' => 'POST',
                'content' => $jsonData
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents('https://api.openai.com/v1/chat/completions', false, $context);

        if ($response === false) {
            error_log('OpenAI API request failed.');
            return null;
        }

        $decoded = json_decode($response, true);

        if (isset($decoded['choices'][0]['message']['content'])) {
            return $decoded['choices'][0]['message']['content'];
        }

        return null;
    }

    private function generateSimpleResponse($question, $fileContent, $language, $fileName) {
        return "(Fallback) Your question: '$question' about the $language file '$fileName' was received. File content: \n$fileContent";
    }
}

// Handle API request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['question']) || !isset($input['fileContent']) || !isset($input['language'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
        exit;
    }

    $isAgentMode = isset($input['agentMode']) ? $input['agentMode'] : false;
    $chatHistory = isset($input['chatHistory']) ? $input['chatHistory'] : [];
    $fileName = isset($input['filename']) ? $input['filename'] : 'untitled';

    $assistant = new AIAssistant();
    $response = $assistant->processQuestion(
        $input['question'], 
        $input['fileContent'], 
        $input['language'], 
        $isAgentMode, 
        $chatHistory,
        $fileName
    );

    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
exit;
