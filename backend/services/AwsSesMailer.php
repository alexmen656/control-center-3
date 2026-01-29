<?php
namespace ControlCenter;

use Aws\SesV2\SesV2Client;
use Aws\Exception\AwsException;

/**
 * AWS SES v2 Mail Service
 *
 * Zentraler Mail-Dienst für alle E-Mail-Kommunikation.
 * Konfiguration erfolgt über Umgebungsvariablen oder direkte Parameter.
 */
class AwsSesMailer
{
    private SesV2Client $client;
    private string $defaultFromEmail;
    private string $defaultFromName;
    private bool $sandboxMode;

    /**
     * @param array $config Optional config override
     *   - region: AWS Region (default: eu-central-1)
     *   - key: AWS Access Key ID
     *   - secret: AWS Secret Access Key
     *   - from_email: Default sender email
     *   - from_name: Default sender name
     *   - sandbox: Enable sandbox mode (default: false)
     */
    public function __construct(array $config = [])
    {
        include 'creds.php';
        //$region = $config['region'] ?? getenv('AWS_SES_REGION') ?: 'eu-central-1';
        //$key = $config['key'] ?? getenv('AWS_SES_KEY') ?: getenv('AWS_ACCESS_KEY_ID');
        //$secret = $config['secret'] ?? getenv('AWS_SES_SECRET') ?: getenv('AWS_SECRET_ACCESS_KEY');


        $this->defaultFromEmail = $config['from_email'] ?? getenv('AWS_SES_FROM_EMAIL') ?: 'noreply@fringelo.com';
        $this->defaultFromName = $config['from_name'] ?? getenv('AWS_SES_FROM_NAME') ?: 'Fringelo';
        $this->sandboxMode = $config['sandbox'] ?? (getenv('AWS_SES_SANDBOX') === 'true');

        $clientConfig = [
            'version' => '2019-09-27',
            'region' => $region,
        ];

        // Credentials nur setzen wenn vorhanden (sonst nutzt SDK Instance Profile/Environment)
        if ($key && $secret) {
            $clientConfig['credentials'] = [
                'key' => $key,
                'secret' => $secret,
            ];
        }

        $this->client = new SesV2Client($clientConfig);
    }

    /**
     * Sende eine E-Mail
     *
     * @param string $to Empfänger E-Mail (oder "Name <email>")
     * @param string $subject Betreff
     * @param string $htmlBody HTML-Inhalt
     * @param array $options Zusätzliche Optionen:
     *   - from: Absender überschreiben
     *   - from_name: Absendername überschreiben
     *   - text_body: Plain-Text Alternative
     *   - reply_to: Reply-To Adresse
     *   - cc: CC Empfänger (string oder array)
     *   - bcc: BCC Empfänger (string oder array)
     *   - configuration_set: SES Configuration Set Name
     *   - tags: Array von ['Name' => 'Value'] für Tracking
     * @return array ['success' => bool, 'message_id' => string|null, 'error' => string|null]
     */
    public function send(string $to, string $subject, string $htmlBody, array $options = []): array
    {
        try {
            $fromEmail = $options['from'] ?? $this->defaultFromEmail;
            $fromName = $options['from_name'] ?? $this->defaultFromName;
            $from = $fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail;

            // Empfänger parsen
            $toAddresses = $this->parseAddresses($to);

            // E-Mail Content aufbauen
            $content = [
                'Simple' => [
                    'Subject' => [
                        'Data' => $subject,
                        'Charset' => 'UTF-8',
                    ],
                    'Body' => [
                        'Html' => [
                            'Data' => $htmlBody,
                            'Charset' => 'UTF-8',
                        ],
                    ],
                ],
            ];

            // Plain-Text Alternative
            if (!empty($options['text_body'])) {
                $content['Simple']['Body']['Text'] = [
                    'Data' => $options['text_body'],
                    'Charset' => 'UTF-8',
                ];
            }

            // Request aufbauen
            $request = [
                'FromEmailAddress' => $from,
                'Destination' => [
                    'ToAddresses' => $toAddresses,
                ],
                'Content' => $content,
            ];

            // CC
            if (!empty($options['cc'])) {
                $request['Destination']['CcAddresses'] = $this->parseAddresses($options['cc']);
            }

            // BCC
            if (!empty($options['bcc'])) {
                $request['Destination']['BccAddresses'] = $this->parseAddresses($options['bcc']);
            }

            // Reply-To
            if (!empty($options['reply_to'])) {
                $request['ReplyToAddresses'] = $this->parseAddresses($options['reply_to']);
            }

            // Configuration Set (für Tracking, Bounce-Handling etc.)
            if (!empty($options['configuration_set'])) {
                $request['ConfigurationSetName'] = $options['configuration_set'];
            }

            // Tags für Tracking
            if (!empty($options['tags']) && is_array($options['tags'])) {
                $request['EmailTags'] = [];
                foreach ($options['tags'] as $name => $value) {
                    $request['EmailTags'][] = [
                        'Name' => $name,
                        'Value' => $value,
                    ];
                }
            }

            // E-Mail senden
            $result = $this->client->sendEmail($request);

            return [
                'success' => true,
                'message_id' => $result['MessageId'] ?? null,
                'error' => null,
            ];

        } catch (AwsException $e) {
            error_log("AWS SES Error: " . $e->getAwsErrorMessage());

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getAwsErrorMessage() ?: $e->getMessage(),
            ];
        } catch (\Exception $e) {
            error_log("Mail Error: " . $e->getMessage());

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sende eine E-Mail an mehrere Empfänger (Bulk)
     *
     * @param array $recipients Array von E-Mail-Adressen
     * @param string $subject Betreff
     * @param string $htmlBody HTML-Inhalt
     * @param array $options Wie bei send()
     * @return array ['success' => bool, 'sent' => int, 'failed' => int, 'errors' => array]
     */
    public function sendBulk(array $recipients, string $subject, string $htmlBody, array $options = []): array
    {
        $sent = 0;
        $failed = 0;
        $errors = [];

        // SES hat ein Limit von 50 Empfängern pro Request
        $chunks = array_chunk($recipients, 50);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $recipient) {
                $result = $this->send($recipient, $subject, $htmlBody, $options);

                if ($result['success']) {
                    $sent++;
                } else {
                    $failed++;
                    $errors[$recipient] = $result['error'];
                }
            }
        }

        return [
            'success' => $failed === 0,
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Sende eine Template-E-Mail
     *
     * @param string $to Empfänger
     * @param string $templateName SES Template Name
     * @param array $templateData Template-Variablen
     * @param array $options Zusätzliche Optionen
     * @return array
     */
    public function sendTemplate(string $to, string $templateName, array $templateData, array $options = []): array
    {
        try {
            $fromEmail = $options['from'] ?? $this->defaultFromEmail;
            $fromName = $options['from_name'] ?? $this->defaultFromName;
            $from = $fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail;

            $request = [
                'FromEmailAddress' => $from,
                'Destination' => [
                    'ToAddresses' => $this->parseAddresses($to),
                ],
                'Content' => [
                    'Template' => [
                        'TemplateName' => $templateName,
                        'TemplateData' => json_encode($templateData),
                    ],
                ],
            ];

            if (!empty($options['configuration_set'])) {
                $request['ConfigurationSetName'] = $options['configuration_set'];
            }

            $result = $this->client->sendEmail($request);

            return [
                'success' => true,
                'message_id' => $result['MessageId'] ?? null,
                'error' => null,
            ];

        } catch (AwsException $e) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getAwsErrorMessage() ?: $e->getMessage(),
            ];
        }
    }

    /**
     * Prüfe ob eine E-Mail-Adresse verifiziert ist (für Sandbox Mode)
     */
    public function isEmailVerified(string $email): bool
    {
        try {
            $result = $this->client->getEmailIdentity([
                'EmailIdentity' => $email,
            ]);

            return $result['VerifiedForSendingStatus'] ?? false;
        } catch (AwsException $e) {
            return false;
        }
    }

    /**
     * Hole aktuelle Sende-Quota
     */
    public function getSendQuota(): array
    {
        try {
            $result = $this->client->getAccount();

            return [
                'max_24_hour_send' => $result['SendQuota']['Max24HourSend'] ?? 0,
                'max_send_rate' => $result['SendQuota']['MaxSendRate'] ?? 0,
                'sent_last_24_hours' => $result['SendQuota']['SentLast24Hours'] ?? 0,
                'production_access' => ($result['ProductionAccessEnabled'] ?? false),
            ];
        } catch (AwsException $e) {
            return [
                'error' => $e->getAwsErrorMessage(),
            ];
        }
    }

    /**
     * Parse Adressen (string oder array) zu Array
     */
    private function parseAddresses($addresses): array
    {
        if (is_array($addresses)) {
            return array_values($addresses);
        }

        // Komma-separierte Liste
        if (strpos($addresses, ',') !== false) {
            return array_map('trim', explode(',', $addresses));
        }

        return [$addresses];
    }

    /**
     * Statische Factory für einfache Nutzung
     */
    public static function create(array $config = []): self
    {
        return new self($config);
    }
}
