<?php

declare(strict_types=1);

/**
 * Base Controller - Parent class for all controllers
 * Follows SOLID principles, optimized for PHP 8.2-8.5
 */

namespace App\Controllers;

class Controller
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Set view data
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function with(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get view data
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Render view
     *
     * @param string $view
     * @param array<string, mixed> $data
     * @return void
     */
    protected function view(string $view, array $data = []): void
    {
        $data = array_merge($this->data, $data);
        extract($data, EXTR_SKIP);
        
        $viewFile = base_path('views/' . $view . '.php');
        
        if (!file_exists($viewFile)) {
            abort(500, "View not found: $view");
        }
        
        include $viewFile;
    }

    /**
     * Return JSON response
     *
     * @param mixed $data
     * @param int $statusCode
     * @return never
     */
    protected function json(mixed $data, int $statusCode = 200): never
    {
        json_response($data, $statusCode);
    }

    /**
     * Redirect to URL
     *
     * @param string $url
     * @return never
     */
    protected function redirect(string $url): never
    {
        redirect($url);
    }

    /**
     * Abort with error
     *
     * @param int $code
     * @param string $message
     * @return never
     */
    protected function abort(int $code, string $message = ''): never
    {
        abort($code, $message);
    }

    /**
     * Validate request input
     *
     * @param array<string, array<string>> $rules
     * @return array<string, mixed>|false
     */
    protected function validate(array $rules): array|false
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleSet) {
            $value = input($field);
            
            foreach ($ruleSet as $rule) {
                if (!$this->validateRule($field, $value, $rule)) {
                    $errors[$field][] = $this->getRuleMessage($field, $rule);
                }
            }

            $validated[$field] = $value;
        }

        if (!empty($errors)) {
            $_SESSION['_validation_errors'] = $errors;
            $_SESSION['_old_input'] = $_REQUEST;
            return false;
        }

        return $validated;
    }

    /**
     * Validate single rule
     *
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @return bool
     */
    protected function validateRule(string $field, mixed $value, string $rule): bool
    {
        return match ($rule) {
            'required' => !empty($value),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'numeric' => is_numeric($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'min:3' => strlen((string)$value) >= 3,
            'max:255' => strlen((string)$value) <= 255,
            default => true,
        };
    }

    /**
     * Get validation error message
     *
     * @param string $field
     * @param string $rule
     * @return string
     */
    protected function getRuleMessage(string $field, string $rule): string
    {
        return match ($rule) {
            'required' => "$field is required",
            'email' => "$field must be a valid email",
            'numeric' => "$field must be numeric",
            'min:3' => "$field must be at least 3 characters",
            'max:255' => "$field must not exceed 255 characters",
            default => "$field is invalid",
        };
    }

    /**
     * Get old input value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old_input'][$key] ?? $default;
    }

    /**
     * Clear old input
     *
     * @return void
     */
    protected function clearOldInput(): void
    {
        unset($_SESSION['_old_input']);
    }

    /**
     * Get validation errors
     *
     * @return array<string, array<string>>
     */
    protected function getValidationErrors(): array
    {
        $errors = $_SESSION['_validation_errors'] ?? [];
        unset($_SESSION['_validation_errors']);
        return $errors;
    }
}
