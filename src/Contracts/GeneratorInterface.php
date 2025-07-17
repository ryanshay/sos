<?php

declare(strict_types=1);

namespace SOS\Translator\Contracts;

/**
 * Base interface for all Morse code generators
 * 
 * @package SOS\Translator\Contracts
 */
interface GeneratorInterface
{
    /**
     * Set the Morse code speed in words per minute
     * 
     * @param int $wpm Words per minute (5-60)
     * @throws \InvalidArgumentException If WPM out of valid range
     */
    public function setSpeed(int $wpm): void;
}