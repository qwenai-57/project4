<?php

declare(strict_types=1);

/**
 * Base Exception Handler - Parent class for custom exceptions
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Exceptions;

use Throwable;

class AppException extends \Exception
{
    protected string $severity = 'error';
    protected array<string, mixed> $context = [];

    /**
     * Constructor
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Set severity level
     *
     * @param string $severity
     * @return static
     */
    public function setSeverity(string $severity): static
    {
        $this->severity = $severity;
        return $this;
    }

    /**
     * Get severity level
     *
     * @return string
     */
    public function getSeverity(): string
    {
        return $this->severity;
    }

    /**
     * Set context data
     *
     * @param array<string, mixed> $context
     * @return static
     */
    public function setContext(array $context): static
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Get context data
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Log the exception
     *
     * @return void
     */
    public function log(): void
    {
        logger(
            strtoupper($this->severity),
            $this->getMessage(),
            array_merge($this->context, [
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => $this->getTraceAsString()
            ])
        );
    }
}
