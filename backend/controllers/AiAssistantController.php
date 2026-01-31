<?php

require_once __DIR__ . '/../ai_config.php';

class AiAssistantController
{
    private string $openaiApiKey;

    public function __construct()
    {
        $this->openaiApiKey = getenv('OPENAI_API_KEY') ?: '';
    }

    /**
     * POST /v2/ai-assistant
     */
    public function processQuestion(Request $request, Response $response): void
    {
        $question = $request->input('question', '');
        $fileContent = $request->input('fileContent', '');
        $language = $request->input('language', '');

        if (empty($question) || $fileContent === null || empty($language)) {
            $response->error('Missing required parameters.', 400);
            return;
        }

        $isAgentMode = (bool) $request->input('agentMode', false);
        $chatHistory = $request->input('chatHistory', []);
        $fileName = $request->input('filename', 'untitled');

        if (empty($this->openaiApiKey)) {
            $response->json($this->generateSimpleResponse($question, $fileContent, $language, $fileName));
            return;
        }

        $messages = $this->buildMessages($question, $fileContent, $language, $isAgentMode, $chatHistory, $fileName);

        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.5
        ];

        $aiResponse = $this->makeOpenAIRequest($data);

        if ($aiResponse) {
            $result = [
                'success' => true,
                'answer' => $aiResponse
            ];

            if ($isAgentMode) {
                $replacements = $this->extractCodeReplacements($aiResponse);
                if (!empty($replacements)) {
                    $result['replacements'] = $replacements;
                }
            }

            $response->json($result);
            return;
        }

        $response->json([
            'success' => false,
            'message' => 'Failed to process the question using AI.'
        ]);
    }

    private function buildMessages(string $question, string $fileContent, string $language, bool $isAgentMode, array $chatHistory, string $fileName): array
    {
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

        foreach ($chatHistory as $message) {
            $messages[] = $message;
        }

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

    private function extractCodeReplacements(string $aiResponse): array
    {
        $replacements = [];

        $patterns = [
            '/```REPLACE\s*OLD_CODE:\s*(.*?)\s*NEW_CODE:\s*(.*?)\s*END_REPLACE```/s',
            '/```REPLACE\s*OLD_CODE:\s*(.*?)\s*NEW_CODE:\s*(.*?)\s*```/s',
            '/REPLACE\s*OLD_CODE:\s*(.*?)\s*NEW_CODE:\s*(.*?)\s*END_REPLACE/s',
            '/```REPLACE\s*OLD_CODE:\s*(.*?)\s*NEW_CODE:\s*(.*?)(?=\s*(?:```|END_REPLACE))/s'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $aiResponse, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $oldCode = trim($match[1]);
                    $newCode = trim($match[2]);

                    $newCode = preg_replace('/\s*(?:END_REPLACE|```)\\s*$/', '', $newCode);
                    $newCode = trim($newCode);
                    $newCode = preg_replace('/END_REPLACE\s*\n?\s*<\//', '</', $newCode);

                    if (!empty($newCode)) {
                        $replacements[] = [
                            'oldCode' => $oldCode,
                            'newCode' => $newCode
                        ];
                    }
                }
                break;
            }
        }

        return $replacements;
    }

    private function makeOpenAIRequest(array $data): ?string
    {
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
        $aiResponse = file_get_contents('https://api.openai.com/v1/chat/completions', false, $context);

        if ($aiResponse === false) {
            error_log('OpenAI API request failed.');
            return null;
        }

        $decoded = json_decode($aiResponse, true);

        if (isset($decoded['choices'][0]['message']['content'])) {
            return $decoded['choices'][0]['message']['content'];
        }

        return null;
    }

    private function generateSimpleResponse(string $question, string $fileContent, string $language, string $fileName): array
    {
        return [
            'success' => true,
            'answer' => "(Fallback) Your question: '$question' about the $language file '$fileName' was received. File content: \n$fileContent"
        ];
    }
}
