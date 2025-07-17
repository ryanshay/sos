<?php

declare(strict_types=1);

namespace SOS\Translator\Exceptions;

/**
 * Invalid Character Exception
 * 
 * Thrown when text contains characters that cannot be translated to Morse code.
 * 
 * @package SOS\Translator\Exceptions
 */
class InvalidCharacterException extends TranslatorException
{
    private string $character;
    private int $position;

    /**
     * Constructor
     * 
     * @param string $character The invalid character
     * @param int $position Position in the input string
     * @param string $message Custom error message
     */
    public function __construct(string $character, int $position, string $message = "")
    {
        $this->character = $character;
        $this->position = $position;
        
        if (empty($message)) {
            $message = sprintf(
                "Invalid character '%s' at position %d. Character is not supported in Morse code.",
                $character,
                $position
            );
        }
        
        parent::__construct($message);
    }

    /**
     * Get the invalid character
     * 
     * @return string The character that caused the exception
     */
    public function getCharacter(): string
    {
        return $this->character;
    }

    /**
     * Get the character position
     * 
     * @return int Position in the input string (0-indexed)
     */
    public function getPosition(): int
    {
        return $this->position;
    }
}