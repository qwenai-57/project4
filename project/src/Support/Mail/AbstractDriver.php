<?php

declare(strict_types=1);

/**
 * Abstract Mail Driver - Base class for mail drivers
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Support\Mail;

abstract class AbstractDriver
{
    protected string $fromEmail = '';
    protected string $fromName = '';
    protected string $toEmail = '';
    protected string $toName = '';
    protected string $subject = '';
    protected string $body = '';
    protected bool $isHtml = true;
    protected array<string, string> $headers = [];
    protected array<string, array<string, string>> $attachments = [];

    /**
     * Set sender email and name
     *
     * @param string $email
     * @param string $name
     * @return static
     */
    public function from(string $email, string $name = ''): static
    {
        $this->fromEmail = $email;
        $this->fromName = $name;
        return $this;
    }

    /**
     * Set recipient email and name
     *
     * @param string $email
     * @param string $name
     * @return static
     */
    public function to(string $email, string $name = ''): static
    {
        $this->toEmail = $email;
        $this->toName = $name;
        return $this;
    }

    /**
     * Set email subject
     *
     * @param string $subject
     * @return static
     */
    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set email body
     *
     * @param string $body
     * @param bool $isHtml
     * @return static
     */
    public function body(string $body, bool $isHtml = true): static
    {
        $this->body = $body;
        $this->isHtml = $isHtml;
        return $this;
    }

    /**
     * Add custom header
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Add attachment
     *
     * @param string $path
     * @param string $name
     * @return static
     */
    public function attach(string $path, string $name = ''): static
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Attachment file not found: $path");
        }

        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?: basename($path)
        ];

        return $this;
    }

    /**
     * Send the email
     *
     * @return bool
     */
    abstract public function send(): bool;

    /**
     * Get error message if send fails
     *
     * @return string|null
     */
    abstract public function getError(): ?string;

    /**
     * Build headers string
     *
     * @return string
     */
    protected function buildHeaders(): string
    {
        $headers = [];

        // From header
        if ($this->fromName) {
            $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        } else {
            $headers[] = "From: {$this->fromEmail}";
        }

        // Reply-To header
        $headers[] = "Reply-To: {$this->fromEmail}";

        // Content-Type header
        if ($this->isHtml) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }

        // Custom headers
        foreach ($this->headers as $name => $value) {
            $headers[] = "$name: $value";
        }

        return implode("\r\n", $headers);
    }

    /**
     * Validate email address
     *
     * @param string $email
     * @return bool
     */
    protected function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Sanitize email content
     *
     * @param string $content
     * @return string
     */
    protected function sanitizeContent(string $content): string
    {
        // Remove potentially dangerous tags while keeping basic HTML
        $allowed = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><img><div><span>';
        return strip_tags($content, $allowed);
    }
}
