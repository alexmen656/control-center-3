<?php
include "head.php";
include "ai_config.php";

class AIAssistant {
    private $openaiApiKey;

    public function __construct() {
        $this->openaiApiKey = getenv('OPENAI_API_KEY') ?: '';
    }

    public function processQuestion($question, $fileContent, $language, $isAgentMode = false, $chatHistory = []) {
        if (empty($this->openaiApiKey)) {
            return $this->generateSimpleResponse($question, $fileContent, $language);
        }

        $messages = $this->buildMessages($question, $fileContent, $language, $isAgentMode, $chatHistory);

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

    private function buildMessages($question, $fileContent, $language, $isAgentMode, $chatHistory) {
        $systemPrompt = 'You are an expert code assistant. Answer user questions based on the provided file content and programming language.';
        
        if ($isAgentMode) {
            $systemPrompt .= ' In agent mode, when you need to make code changes, use the following format:
```REPLACE
OLD_CODE:
[exact code to replace]
NEW_CODE:
[new code to replace with]
END_REPLACE```

Be very precise with the OLD_CODE - it should match exactly including whitespace and indentation.';
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
        $prompt = "The user has asked the following question about a $language file:\n\n" .
                  "Question: $question\n\n" .
                  "File Content:\n$fileContent\n\n" .
                  "Provide a detailed and helpful response.";

        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];

        return $messages;
    }

    private function extractCodeReplacements($response) {
        $replacements = [];
        $pattern = '/```REPLACE\s*OLD_CODE:\s*(.*?)\s*NEW_CODE:\s*(.*?)\s*END_REPLACE```/s';
        
        if (preg_match_all($pattern, $response, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $replacements[] = [
                    'oldCode' => trim($match[1]),
                    'newCode' => trim($match[2])
                ];
            }
        }
        
        return $replacements;
    }

    private function buildPrompt($question, $fileContent, $language) {
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

    private function generateSimpleResponse($question, $fileContent, $language) {
        return "(Fallback) Your question: '$question' about the $language file was received. File content: \n$fileContent";
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

    $assistant = new AIAssistant();
    $response = $assistant->processQuestion(
        $input['question'], 
        $input['fileContent'], 
        $input['language'], 
        $isAgentMode, 
        $chatHistory
    );

    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
exit;
