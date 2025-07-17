<?php

declare(strict_types=1);

namespace SOS\Translator\Exceptions;

/**
 * Empty Input Exception
 * 
 * Thrown when empty input is provided where text or Morse code is expected.
 * 
 * @package SOS\Translator\Exceptions
 */
class EmptyInputException extends TranslatorException
{
    /**
     * Constructor
     * 
     * @param string $message Custom error message
     */
    public function __construct(string $message = "Input cannot be empty")
    {
        parent::__construct($message);
    }
}