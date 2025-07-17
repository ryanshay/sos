<?php

declare(strict_types=1);

namespace SOS\Translator\Contracts;

/**
 * Interface for generators that create files
 * 
 * @package SOS\Translator\Contracts
 */
interface FileGeneratorInterface extends GeneratorInterface
{
    /**
     * Generate output file from Morse code
     * 
     * @param string $morseCode Morse code pattern
     * @param string $filename Output filename
     * @param mixed $context Additional context (e.g., original text)
     * @throws \SOS\Translator\Exceptions\EmptyInputException If Morse code is empty
     * @throws \SOS\Translator\Exceptions\InvalidMorseCodeException If Morse code format is invalid
     * @throws \RuntimeException If file generation fails
     */
    public function generate(string $morseCode, string $filename, $context = null): void;
}