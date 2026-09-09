<?php

declare(strict_types=1);

/**
 * Mailer Facade - Unified interface for sending emails
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Support\Mail;

class Mailer
{
    protected AbstractDriver $driver;

    /**
     * Constructor with driver
     *
     * @param AbstractDriver $driver
     */
    public function __construct(AbstractDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Create new instance with SMTP driver
     *
     * @return static
     */
    public static function smtp(): static
    {
        return new static(new SMTP());
    }

    /**
     * Create new instance with Mailketing driver
     *
     * @return static
     */
    public static function mailketing(): static
    {
        return new static(new Mailketing());
    }

    /**
     * Set custom driver
     *
     * @param AbstractDriver $driver
     * @return static
     */
    public function setDriver(AbstractDriver $driver): static
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Get current driver
     *
     * @return AbstractDriver
     */
    public function getDriver(): AbstractDriver
    {
        return $this->driver;
    }

    /**
     * Set sender
     *
     * @param string $email
     * @param string $name
     * @return static
     */
    public function from(string $email, string $name = ''): static
    {
        $this->driver->from($email, $name);
        return $this;
    }

    /**
     * Set recipient
     *
     * @param string $email
     * @param string $name
     * @return static
     */
    public function to(string $email, string $name = ''): static
    {
        $this->driver->to($email, $name);
        return $this;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return static
     */
    public function subject(string $subject): static
    {
        $this->driver->subject($subject);
        return $this;
    }

    /**
     * Set body
     *
     * @param string $body
     * @param bool $isHtml
     * @return static
     */
    public function body(string $body, bool $isHtml = true): static
    {
        $this->driver->body($body, $isHtml);
        return $this;
    }

    /**
     * Send email
     *
     * @return bool
     */
    public function send(): bool
    {
        return $this->driver->send();
    }

    /**
     * Get error if send failed
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->driver->getError();
    }

    /**
     * Send email safely (returns success status without throwing)
     *
     * @return array{success: bool, error: string|null}
     */
    public function sendSafe(): array
    {
        $success = $this->send();
        return [
            'success' => $success,
            'error' => $success ? null : $this->getError()
        ];
    }
}
