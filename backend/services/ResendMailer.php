<?php
namespace ControlCenter;

class ResendMailer
{
    private $client = null;
    private string $apiKey;
    private string $apiUrl = 'https://api.resend.com';
    private string $defaultFromEmail;
    private string $defaultFromName;
    private bool $useSDK = false;

    public function __construct(array $config = [])
    {
        $this->apiKey = $config['api_key'] ?? getenv('RESEND_API_KEY') ?: '';

        if (empty($this->apiKey)) {
            $credsPath = __DIR__ . '/../creds.php';
            if (file_exists($credsPath)) {
                include $credsPath;
                $this->apiKey = $resend_api_key ?? '';
            }
        }

        if (empty($this->apiKey)) {
            throw new \Exception('Resend API key is required. Set $resend_api_key in creds.php');
        }

        $this->defaultFromEmail = $config['from_email'] ?? getenv('RESEND_FROM_EMAIL') ?: 'noreply@fringelo.com';
        $this->defaultFromName = $config['from_name'] ?? getenv('RESEND_FROM_NAME') ?: 'Fringelo';
        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        if (class_exists('\\Resend\\Resend', false)) {
            try {
                $this->client = \Resend\Resend::client($this->apiKey);
                $this->useSDK = true;
                error_log("ResendMailer: Using official SDK");
            } catch (\Exception $e) {
                error_log("ResendMailer: SDK failed, using cURL fallback: " . $e->getMessage());
                $this->useSDK = false;
            }
        } else {
            $this->useSDK = false;
            error_log("ResendMailer: SDK not available, using cURL");
        }
    }

    public function send(string $to, string $subject, string $htmlBody, array $options = []): array
    {
        try {
            $fromEmail = $options['from'] ?? $this->defaultFromEmail;
            $fromName = $options['from_name'] ?? $this->defaultFromName;
            $from = $fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail;

            $payload = [
                'from' => $from,
                'to' => $this->normalizeAddresses($to),
                'subject' => $subject,
                'html' => $htmlBody,
            ];

            if (!empty($options['text_body'])) {
                $payload['text'] = $options['text_body'];
            }

            if (!empty($options['cc'])) {
                $payload['cc'] = $this->normalizeAddresses($options['cc']);
            }

            if (!empty($options['bcc'])) {
                $payload['bcc'] = $this->normalizeAddresses($options['bcc']);
            }

            if (!empty($options['reply_to'])) {
                $payload['replyTo'] = $this->normalizeAddresses($options['reply_to']);
            }

            if (!empty($options['scheduled_at'])) {
                $payload['scheduledAt'] = $options['scheduled_at'];
            }

            if (!empty($options['headers']) && is_array($options['headers'])) {
                $payload['headers'] = $options['headers'];
            }

            if (!empty($options['tags']) && is_array($options['tags'])) {
                $payload['tags'] = [];
                foreach ($options['tags'] as $name => $value) {
                    $payload['tags'][] = [
                        'name' => $name,
                        'value' => (string) $value,
                    ];
                }
            }

            if (!empty($options['attachments']) && is_array($options['attachments'])) {
                $payload['attachments'] = [];
                foreach ($options['attachments'] as $attachment) {
                    $att = [
                        'filename' => $attachment['filename'] ?? 'attachment',
                    ];

                    if (isset($attachment['content'])) {
                        $att['content'] = base64_encode($attachment['content']);
                    } elseif (isset($attachment['path'])) {
                        $att['path'] = $attachment['path'];
                    }

                    if (isset($attachment['content_type'])) {
                        $att['contentType'] = $attachment['content_type'];
                    }

                    $payload['attachments'][] = $att;
                }
            }

            if ($this->useSDK && $this->client) {
                $email = $this->client->emails->send($payload);
                return [
                    'success' => true,
                    'email_id' => $email->id,
                    'error' => null,
                ];
            } else {
                $result = $this->apiRequest('POST', '/emails', $payload);

                if (isset($result['id'])) {
                    return [
                        'success' => true,
                        'email_id' => $result['id'],
                        'error' => null,
                    ];
                }

                return [
                    'success' => false,
                    'email_id' => null,
                    'error' => $result['message'] ?? 'Unknown error',
                ];
            }

        } catch (\Exception $e) {
            error_log("Resend SDK Error: " . $e->getMessage());

            return [
                'success' => false,
                'email_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendBatch(array $emails): array
    {
        try {
            $payload = [];

            foreach ($emails as $email) {
                $fromEmail = $email['from'] ?? $this->defaultFromEmail;
                $fromName = $email['from_name'] ?? $this->defaultFromName;
                $from = $fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail;

                $item = [
                    'from' => $from,
                    'to' => $this->normalizeAddresses($email['to']),
                    'subject' => $email['subject'],
                    'html' => $email['html'],
                ];

                if (!empty($email['text'])) {
                    $item['text'] = $email['text'];
                }

                if (!empty($email['cc'])) {
                    $item['cc'] = $this->normalizeAddresses($email['cc']);
                }

                if (!empty($email['bcc'])) {
                    $item['bcc'] = $this->normalizeAddresses($email['bcc']);
                }

                if (!empty($email['reply_to'])) {
                    $item['replyTo'] = $this->normalizeAddresses($email['reply_to']);
                }

                $payload[] = $item;
            }

            $chunks = array_chunk($payload, 100);
            $results = [];
            $sent = 0;
            $failed = 0;

            foreach ($chunks as $chunk) {
                if ($this->useSDK && $this->client) {
                    $response = $this->client->batch->send($chunk);

                    if (isset($response->data)) {
                        foreach ($response->data as $item) {
                            if (isset($item->id)) {
                                $sent++;
                                $results[] = ['success' => true, 'id' => $item->id];
                            } else {
                                $failed++;
                                $results[] = ['success' => false, 'error' => 'Unknown error'];
                            }
                        }
                    }
                } else {
                    $result = $this->apiRequest('POST', '/emails/batch', $chunk);

                    if (isset($result['data'])) {
                        foreach ($result['data'] as $item) {
                            if (isset($item['id'])) {
                                $sent++;
                                $results[] = ['success' => true, 'id' => $item['id']];
                            } else {
                                $failed++;
                                $results[] = ['success' => false, 'error' => $item['error'] ?? 'Unknown'];
                            }
                        }
                    }
                }
            }

            return [
                'success' => $failed === 0,
                'sent' => $sent,
                'failed' => $failed,
                'results' => $results,
            ];

        } catch (\Exception $e) {
            error_log("Resend SDK Batch Error: " . $e->getMessage());

            return [
                'success' => false,
                'sent' => 0,
                'failed' => count($emails),
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendBulk(array $recipients, string $subject, string $htmlBody, array $options = []): array
    {
        $emails = [];
        foreach ($recipients as $recipient) {
            $emails[] = array_merge($options, [
                'to' => $recipient,
                'subject' => $subject,
                'html' => $htmlBody,
            ]);
        }

        return $this->sendBatch($emails);
    }

    public function getEmail(string $emailId): ?array
    {
        try {
            if ($this->useSDK && $this->client) {
                $email = $this->client->emails->get($emailId);

                return [
                    'id' => $email->id,
                    'from' => $email->from ?? null,
                    'to' => $email->to ?? null,
                    'subject' => $email->subject ?? null,
                    'created_at' => $email->createdAt ?? null,
                    'last_event' => $email->lastEvent ?? null,
                ];
            } else {
                return $this->apiRequest('GET', "/emails/{$emailId}");
            }
        } catch (\Exception $e) {
            error_log("Resend get email error: " . $e->getMessage());
            return null;
        }
    }

    public function cancelScheduledEmail(string $emailId): bool
    {
        try {
            if ($this->useSDK && $this->client) {
                $result = $this->client->emails->cancel($emailId);
                return isset($result->id);
            } else {
                $result = $this->apiRequest('POST', "/emails/{$emailId}/cancel");
                return isset($result['id']);
            }
        } catch (\Exception $e) {
            error_log("Resend cancel email error: " . $e->getMessage());
            return false;
        }
    }

    private function normalizeAddresses($addresses): array
    {
        if (is_array($addresses)) {
            return array_values($addresses);
        }

        if (strpos($addresses, ',') !== false) {
            return array_map('trim', explode(',', $addresses));
        }

        return [$addresses];
    }

    private function apiRequest(string $method, string $endpoint, array $data = null): array
    {
        $url = $this->apiUrl . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL Error: $error");
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = $result['message'] ?? $result['error'] ?? "HTTP Error: $httpCode";
            throw new \Exception($errorMsg);
        }

        return $result ?? [];
    }

    public static function create(array $config = []): self
    {
        return new self($config);
    }
}
