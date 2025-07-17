<?php

declare(strict_types=1);

namespace SOS\Translator\Exceptions;

/**
 * Invalid Morse Code Exception
 * 
 * Thrown when Morse code contains invalid characters or sequences.
 * 
 * @package SOS\Translator\Exceptions
 */
class InvalidMorseCodeException extends TranslatorException
{
    private string $invalidCode;

    /**
     * Constructor
     * 
     * @param string $invalidCode The invalid Morse code
     * @param string $message Custom error message
     */
    public function __construct(string $invalidCode, string $message = "")
    {
        $this->invalidCode = $invalidCode;
        
        if (empty($message)) {
            $message = sprintf(
                "Invalid Morse code sequence '%s'. Morse code should only contain dots (.), dashes (-), spaces, and forward slashes (/).",
                $invalidCode
            );
        }
        
        parent::__construct($message);
    }

    /**
     * Get the invalid Morse code
     * 
     * @return string The Morse code that caused the exception
     */
    public function getInvalidCode(): string
    {
        return $this->invalidCode;
    }
}