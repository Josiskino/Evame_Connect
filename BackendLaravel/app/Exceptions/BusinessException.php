<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception métier : levée par les Actions quand une règle de gestion est violée
 * (ex. moto en rupture, contrat déjà soldé...). Rendue proprement par le handler.
 */
class BusinessException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $errors
     */
    public function __construct(
        string $message,
        protected int $statusCode = 422,
        protected ?array $errors = null,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }
}
