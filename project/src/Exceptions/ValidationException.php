<?php

declare(strict_types=1);

/**
 * Validation Exception - Thrown when validation fails
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Exceptions;

class ValidationException extends AppException
{
    protected string $severity = 'warning';
    protected array<string, array<string>> $errors = [];

    /**
     * Constructor
     *
     * @param array<string, array<string>> $errors
     * @param string $message
     * @param int $code
     */
    public function __construct(
        array $errors = [],
        string $message = 'Validation failed',
        int $code = 422
    ) {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    /**
     * Get validation errors
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $field => $messages) {
            if (!empty($messages)) {
                return $messages[0];
            }
        }
        return null;
    }

    /**
     * Convert to array for JSON response
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->errors
        ];
    }
}
