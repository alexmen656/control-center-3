<?php
include "head.php";
include "ai_config.php";

class AIAssistant {
    private $openaiApiKey;

    public function __construct() {
        $this->openaiApiKey = getenv('OPENAI_API_KEY') ?: '';
    }

    public function processQuestion($question, $fileContent, $language) {
        if (empty($this->openaiApiKey)) {
            return $this->generateSimpleResponse($question, $fileContent, $language);
        }

        $prompt = $this->buildPrompt($question, $fileContent, $language);

        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert code assistant. Answer user questions based on the provided file content and programming language.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.5
        ];

        $response = $this->makeOpenAIRequest($data);

        if ($response) {
            return [
                'success' => true,
                'answer' => $response
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to process the question using AI.'
        ];
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

    $assistant = new AIAssistant();
    $response = $assistant->processQuestion($input['question'], $input['fileContent'], $input['language']);

    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
exit;
