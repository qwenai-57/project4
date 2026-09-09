<?php

declare(strict_types=1);

/**
 * Mailketing Driver - Marketing/bulk email driver
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Support\Mail;

class Mailketing extends AbstractDriver
{
    protected string $apiKey = '';
    protected string $apiEndpoint = '';
    protected string $templateId = '';
    protected array<string, mixed> $templateData = [];
    protected array<string> $recipientList = [];
    protected ?string $error = null;
    protected int $batchSize = 100;

    /**
     * Configure Mailketing API
     *
     * @param string $apiKey
     * @param string $apiEndpoint
     * @return static
     */
    public function configure(string $apiKey, string $apiEndpoint): static
    {
        $this->apiKey = $apiKey;
        $this->apiEndpoint = rtrim($apiEndpoint, '/');
        return $this;
    }

    /**
     * Set template ID
     *
     * @param string $templateId
     * @return static
     */
    public function setTemplate(string $templateId): static
    {
        $this->templateId = $templateId;
        return $this;
    }

    /**
     * Set template data
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public function setTemplateData(array $data): static
    {
        $this->templateData = $data;
        return $this;
    }

    /**
     * Add recipient to batch list
     *
     * @param string $email
     * @param string $name
     * @return static
     */
    public function addRecipient(string $email, string $name = ''): static
    {
        if ($this->isValidEmail($email)) {
            $this->recipientList[] = [
                'email' => $email,
                'name' => $name
            ];
        }
        return $this;
    }

    /**
     * Set batch size for bulk sending
     *
     * @param int $size
     * @return static
     */
    public function setBatchSize(int $size): static
    {
        $this->batchSize = max(1, min($size, 1000));
        return $this;
    }

    /**
     * Send single email via API
     *
     * @return bool
     */
    public function send(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $payload = $this->buildPayload();
        return $this->sendRequest($payload);
    }

    /**
     * Send bulk emails
     *
     * @return array{success: int, failed: int, errors: array<int, string>}
     */
    public function sendBulk(): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        if (empty($this->recipientList)) {
            $results['errors'][] = 'No recipients added';
            return $results;
        }

        // Process in batches
        $batches = array_chunk($this->recipientList, $this->batchSize);

        foreach ($batches as $batch) {
            $payload = $this->buildBulkPayload($batch);
            
            if ($this->sendRequest($payload)) {
                $results['success'] += count($batch);
            } else {
                $results['failed'] += count($batch);
                $results['errors'][] = $this->error ?? 'Unknown error';
            }

            // Rate limiting - small delay between batches
            if (count($batches) > 1) {
                usleep(100000); // 100ms delay
            }
        }

        return $results;
    }

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Validate email data
     *
     * @return bool
     */
    protected function validate(): bool
    {
        if (empty($this->apiKey)) {
            $this->error = 'API key is required';
            return false;
        }

        if (empty($this->apiEndpoint)) {
            $this->error = 'API endpoint is required';
            return false;
        }

        if (!$this->isValidEmail($this->fromEmail)) {
            $this->error = 'Invalid sender email address';
            return false;
        }

        if (!$this->isValidEmail($this->toEmail)) {
            $this->error = 'Invalid recipient email address';
            return false;
        }

        return true;
    }

    /**
     * Build API payload
     *
     * @return array<string, mixed>
     */
    protected function buildPayload(): array
    {
        $payload = [
            'from' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName ?: $this->fromEmail
            ],
            'to' => [
                'email' => $this->toEmail,
                'name' => $this->toName ?: $this->toEmail
            ],
            'subject' => $this->subject,
            'htmlContent' => $this->sanitizeContent($this->body)
        ];

        if (!empty($this->templateId)) {
            $payload['templateId'] = $this->templateId;
            $payload['templateData'] = $this->templateData;
        }

        return $payload;
    }

    /**
     * Build bulk payload
     *
     * @param array<int, array{email: string, name: string}> $recipients
     * @return array<string, mixed>
     */
    protected function buildBulkPayload(array $recipients): array
    {
        return [
            'from' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName ?: $this->fromEmail
            ],
            'recipients' => $recipients,
            'subject' => $this->subject,
            'htmlContent' => $this->sanitizeContent($this->body),
            'templateId' => $this->templateId,
            'templateData' => $this->templateData
        ];
    }

    /**
     * Send request to API
     *
     * @param array<string, mixed> $payload
     * @return bool
     */
    protected function sendRequest(array $payload): bool
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiEndpoint . '/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            $this->error = 'cURL error: ' . $curlError;
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $this->error = 'API request failed with HTTP code: ' . $httpCode;
            return false;
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error = 'Invalid JSON response from API';
            return false;
        }

        if (!($result['success'] ?? false)) {
            $this->error = $result['message'] ?? 'API reported failure';
            return false;
        }

        return true;
    }

    /**
     * Clear recipient list
     *
     * @return static
     */
    public function clearRecipients(): static
    {
        $this->recipientList = [];
        return $this;
    }
}
