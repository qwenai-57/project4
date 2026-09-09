<?php

declare(strict_types=1);

/**
 * SMTP Mail Driver - Send emails via SMTP
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Support\Mail;

class SMTP extends AbstractDriver
{
    protected string $host = 'localhost';
    protected int $port = 587;
    protected string $encryption = 'tls';
    protected string $username = '';
    protected string $password = '';
    protected ?string $error = null;
    protected ?resource $socket = null;
    protected int $timeout = 30;

    /**
     * Set SMTP configuration
     *
     * @param string $host
     * @param int $port
     * @param string $encryption
     * @param string $username
     * @param string $password
     * @return static
     */
    public function configure(
        string $host,
        int $port = 587,
        string $encryption = 'tls',
        string $username = '',
        string $password = ''
    ): static {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->username = $username;
        $this->password = $password;
        return $this;
    }

    /**
     * Send email via SMTP
     *
     * @return bool
     */
    public function send(): bool
    {
        try {
            // Validate required fields
            if (!$this->validate()) {
                return false;
            }

            // Connect to SMTP server
            if (!$this->connect()) {
                return false;
            }

            // Authenticate if credentials provided
            if ($this->username && $this->password) {
                if (!$this->authenticate()) {
                    $this->disconnect();
                    return false;
                }
            }

            // Send mail commands
            if (!$this->sendMailCommand()) {
                $this->disconnect();
                return false;
            }

            // Disconnect
            $this->disconnect();
            return true;

        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->disconnect();
            return false;
        }
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
        if (!$this->isValidEmail($this->fromEmail)) {
            $this->error = 'Invalid sender email address';
            return false;
        }

        if (!$this->isValidEmail($this->toEmail)) {
            $this->error = 'Invalid recipient email address';
            return false;
        }

        if ($this->subject === '') {
            $this->error = 'Email subject is required';
            return false;
        }

        if ($this->body === '') {
            $this->error = 'Email body is required';
            return false;
        }

        return true;
    }

    /**
     * Connect to SMTP server
     *
     * @return bool
     */
    protected function connect(): bool
    {
        $connectionString = sprintf('%s://%s:%d', $this->encryption, $this->host, $this->port);
        
        $this->socket = stream_socket_client(
            $connectionString,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ])
        );

        if (!$this->socket) {
            $this->error = "Failed to connect to SMTP server: $errstr ($errno)";
            return false;
        }

        stream_set_timeout($this->socket, $this->timeout);

        // Read greeting
        $response = $this->readResponse();
        if (!str_starts_with($response, '220')) {
            $this->error = 'SMTP server greeting failed';
            return false;
        }

        // EHLO command
        $this->writeCommand('EHLO ' . $this->host);
        $response = $this->readResponse();
        
        if (!str_starts_with($response, '250')) {
            // Try HELO as fallback
            $this->writeCommand('HELO ' . $this->host);
            $response = $this->readResponse();
        }

        // Start TLS if enabled
        if ($this->encryption === 'tls') {
            $this->writeCommand('STARTTLS');
            $response = $this->readResponse();
            
            if (str_starts_with($response, '220')) {
                stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                // Re-send EHLO after TLS
                $this->writeCommand('EHLO ' . $this->host);
                $this->readResponse();
            }
        }

        return true;
    }

    /**
     * Authenticate with SMTP server
     *
     * @return bool
     */
    protected function authenticate(): bool
    {
        // PLAIN authentication
        $authPlain = base64_encode("\0{$this->username}\0{$this->password}");
        $this->writeCommand('AUTH PLAIN ' . $authPlain);
        $response = $this->readResponse();

        if (!str_starts_with($response, '235')) {
            $this->error = 'SMTP authentication failed';
            return false;
        }

        return true;
    }

    /**
     * Send mail commands (MAIL FROM, RCPT TO, DATA)
     *
     * @return bool
     */
    protected function sendMailCommand(): bool
    {
        // MAIL FROM
        $this->writeCommand('MAIL FROM:<' . $this->fromEmail . '>');
        $response = $this->readResponse();
        if (!str_starts_with($response, '250')) {
            $this->error = 'MAIL FROM command failed';
            return false;
        }

        // RCPT TO
        $this->writeCommand('RCPT TO:<' . $this->toEmail . '>');
        $response = $this->readResponse();
        if (!str_starts_with($response, '250')) {
            $this->error = 'RCPT TO command failed';
            return false;
        }

        // DATA
        $this->writeCommand('DATA');
        $response = $this->readResponse();
        if (!str_starts_with($response, '354')) {
            $this->error = 'DATA command failed';
            return false;
        }

        // Send headers and body
        $headers = $this->buildHeaders();
        $message = "$headers\r\n\r\n" . $this->sanitizeContent($this->body);

        fwrite($this->socket, $message . "\r\n.\r\n");
        $response = $this->readResponse();

        if (!str_starts_with($response, '250')) {
            $this->error = 'Failed to send email data';
            return false;
        }

        return true;
    }

    /**
     * Disconnect from SMTP server
     *
     * @return void
     */
    protected function disconnect(): void
    {
        if ($this->socket) {
            $this->writeCommand('QUIT');
            $this->readResponse();
            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Write command to socket
     *
     * @param string $command
     * @return void
     */
    protected function writeCommand(string $command): void
    {
        if ($this->socket) {
            fwrite($this->socket, $command . "\r\n");
        }
    }

    /**
     * Read response from socket
     *
     * @return string
     */
    protected function readResponse(): string
    {
        $response = '';
        if ($this->socket) {
            while (($line = fgets($this->socket, 515)) !== false) {
                $response .= $line;
                if (substr($line, 3, 1) === ' ') {
                    break;
                }
            }
        }
        return trim($response);
    }
}
