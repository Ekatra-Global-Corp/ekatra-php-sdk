<?php

namespace Ekatra\Product\Exceptions;

use Exception;

/**
 * EkatraValidationException
 * 
 * Thrown when product or variant validation fails
 */
class EkatraValidationException extends Exception
{
    protected array $errors;

    public function __construct(string $message = "", array $errors = [], int $code = 422, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
