<?php

declare(strict_types=1);

namespace SOS\Translator\Contracts;

/**
 * Interface for generators that display Morse code
 * 
 * @package SOS\Translator\Contracts
 */
interface DisplayGeneratorInterface extends GeneratorInterface
{
    /**
     * Display Morse code pattern
     * 
     * @param string $morseCode Morse code pattern
     * @param string|null $label Optional label to display
     * @throws \SOS\Translator\Exceptions\EmptyInputException If Morse code is empty
     * @throws \SOS\Translator\Exceptions\InvalidMorseCodeException If Morse code format is invalid
     */
    public function display(string $morseCode, ?string $label = null): void;
}