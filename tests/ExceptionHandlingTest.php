<?php

namespace SOS\Translator\Tests;

use PHPUnit\Framework\TestCase;
use SOS\Translator\Translator;
use SOS\Translator\UnsupportedCharacterMode;
use SOS\Translator\Exceptions\EmptyInputException;
use SOS\Translator\Exceptions\InvalidCharacterException;
use SOS\Translator\Exceptions\InvalidMorseCodeException;

class ExceptionHandlingTest extends TestCase
{
    private Translator $translator;

    protected function setUp(): void
    {
        $this->translator = new Translator();
    }

    public function testEmptyTextThrowsException(): void
    {
        $this->expectException(EmptyInputException::class);
        $this->expectExceptionMessage("Text to translate cannot be empty");
        $this->translator->textToMorse('');
    }

    public function testEmptyMorseCodeThrowsException(): void
    {
        $this->expectException(EmptyInputException::class);
        $this->expectExceptionMessage("Morse code to translate cannot be empty");
        $this->translator->morseToText('');
    }

    public function testEmptyCharacterThrowsException(): void
    {
        $this->expectException(EmptyInputException::class);
        $this->expectExceptionMessage("Character cannot be empty");
        $this->translator->getCharacterMorse('');
    }

    public function testMultipleCharactersThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Only single characters are allowed");
        $this->translator->getCharacterMorse('AB');
    }

    public function testInvalidCharacterThrowsException(): void
    {
        $this->expectException(InvalidCharacterException::class);
        $this->expectExceptionMessage("Invalid character '~' at position 5");
        $this->translator->textToMorse('HELLO~WORLD');
    }

    public function testInvalidCharacterExceptionProperties(): void
    {
        try {
            $this->translator->textToMorse('ABC#DEF');
        } catch (InvalidCharacterException $e) {
            $this->assertEquals('#', $e->getCharacter());
            $this->assertEquals(3, $e->getPosition());
        }
    }

    public function testInvalidMorseCodeThrowsException(): void
    {
        $this->expectException(InvalidMorseCodeException::class);
        $this->translator->morseToText('.... . .-.. .-.. --- abc');
    }

    public function testUnknownMorseSequenceThrowsException(): void
    {
        $this->expectException(InvalidMorseCodeException::class);
        $this->expectExceptionMessage("Unknown Morse code sequence: '......'");
        $this->translator->morseToText('.... . ...... .-.. ---');
    }

    public function testSkipModeIgnoresInvalidCharacters(): void
    {
        $translator = new Translator(UnsupportedCharacterMode::SKIP);
        $result = $translator->textToMorse('HELLO WORLD#!');
        // # is skipped, space between HELLO and WORLD remains
        $this->assertEquals('.... . .-.. .-.. --- / .-- --- .-. .-.. -.. -.-.--', $result);
    }

    public function testReplaceModeReplacesInvalidCharacters(): void
    {
        $translator = new Translator(UnsupportedCharacterMode::REPLACE);
        $result = $translator->textToMorse('HELLO~WORLD');
        // ~ is replaced with ? which is ..--..
        $this->assertEquals('.... . .-.. .-.. --- ..--.. .-- --- .-. .-.. -..', $result);
    }

    public function testCustomReplacementCharacter(): void
    {
        $translator = new Translator(UnsupportedCharacterMode::REPLACE);
        $translator->setReplacementCharacter('.');
        $result = $translator->textToMorse('HELLO~WORLD');
        // ~ is replaced with . which is .-.-.-
        $this->assertEquals('.... . .-.. .-.. --- .-.-.- .-- --- .-. .-.. -..', $result);
    }

    public function testInvalidReplacementCharacterThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Replacement character must be exactly one character");
        $this->translator->setReplacementCharacter('??');
    }

    public function testInvalidUnsupportedCharacterModeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid unsupported character mode 'invalid'. Valid modes are: throw, skip, replace");
        $this->translator->setUnsupportedCharacterMode('invalid');
    }

    public function testSkipModeWithMorseToText(): void
    {
        $translator = new Translator(UnsupportedCharacterMode::SKIP);
        // Using ...... (6 dots) which is not a valid Morse sequence
        $result = $translator->morseToText('.... . ...... .-.. .-.. ---');
        $this->assertEquals('HELLO', $result);
    }

    public function testReplaceModeWithMorseToText(): void
    {
        $translator = new Translator(UnsupportedCharacterMode::REPLACE);
        // Using ...... (6 dots) which is not a valid Morse sequence
        $result = $translator->morseToText('.... . ...... .-.. .-.. ---');
        $this->assertEquals('HE?LLO', $result);
    }
}