<?php

namespace Daniel\Origins\Exceptions;

use Exception;
use Throwable;

class CompositeException extends Exception
{
    protected array $errors;

    public function __construct(array $errors = [], string $message = "", int $code = 422)
    {
        $this->errors = $errors;
        parent::__construct($message, $code);
    }

    /**
     * Retorna os erros de validação como array.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

     /**
     * Retorna os erros de validação como array de strings.
     */
    public function getErrorsAsString(): array
    {
        $messages = [];
        foreach ($this->errors as $error) {
            if ($error instanceof \Throwable) {
                $messages[] = $error->getMessage();
            } elseif (is_string($error)) {
                $messages[] = $error;
            }
        }
        return $messages;
    }

    /**
     * adiciona um erro a lista.
     */
    public function addError(Throwable $error): void
    {
        $this->errors[] = $error;
        $parentMessage = parent::getMessage();
        if (empty($parentMessage)) {
            $this->message = $this->extractMessageFromErrors($this->errors);
        }
    }

    /**
     * Retorna os erros em formato JSON legível.
     */
    public function toJson(): string
    {
        return json_encode([
            "message" => $this->getMessage(),
            "errors" => $this->errors
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Extrai uma mensagem legível a partir do array de erros.
     */
    protected function extractMessageFromErrors(array $errors): string
    {

        $messages = [];

        foreach ($errors as $error) {
            if ($error instanceof \Throwable) {
                $messages[] = $error->getMessage();
            } elseif (is_string($error)) {
                $messages[] = $error;
            }
        }

        if (empty($messages)) {
            return "Erro";
        }

        return implode(", ", $messages);
    }

}
