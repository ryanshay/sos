<?php

declare(strict_types=1);

namespace SOS\Translator\Traits;

/**
 * Trait for Morse code validation
 * 
 * Provides common validation methods for Morse code format.
 * 
 * @package SOS\Translator\Traits
 */
trait MorseValidation
{
    /**
     * Validate Morse code format
     * 
     * @param string $morse Morse code to validate
     * @return bool True if valid format
     */
    private function isValidMorseCode(string $morse): bool
    {
        return preg_match('/^[.\-\s\/]+$/', $morse) === 1;
    }
}