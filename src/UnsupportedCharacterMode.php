<?php

declare(strict_types=1);

namespace SOS\Translator;

/**
 * Unsupported Character Mode Constants
 * 
 * Defines how the translator should handle characters that don't have Morse code representations.
 * 
 * @package SOS\Translator
 */
class UnsupportedCharacterMode
{
    public const THROW_EXCEPTION = 'throw';
    public const SKIP = 'skip';
    public const REPLACE = 'replace';
    
    private const VALID_MODES = [
        self::THROW_EXCEPTION,
        self::SKIP,
        self::REPLACE
    ];
    
    /**
     * Check if a mode is valid
     * 
     * @param string $mode Mode to validate
     * @return bool True if mode is valid
     */
    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::VALID_MODES, true);
    }
    
    /**
     * Get list of valid modes
     * 
     * @return array Array of valid mode constants
     */
    public static function getValidModes(): array
    {
        return self::VALID_MODES;
    }
}