<?php

namespace SOS\Translator\Tests;

use PHPUnit\Framework\TestCase;
use SOS\Translator\Translator;

class TranslatorTest extends TestCase
{
    private Translator $translator;

    protected function setUp(): void
    {
        $this->translator = new Translator();
    }

    public function testTextToMorse(): void
    {
        $this->assertEquals('.... . .-.. .-.. ---', $this->translator->textToMorse('HELLO'));
        $this->assertEquals('.... . .-.. .-.. --- / .-- --- .-. .-.. -..', $this->translator->textToMorse('HELLO WORLD'));
        $this->assertEquals('... --- ...', $this->translator->textToMorse('SOS'));
    }

    public function testTextToMorseWithNumbers(): void
    {
        $this->assertEquals('.---- ..--- ...--', $this->translator->textToMorse('123'));
        $this->assertEquals('.- -... -.-. / .---- ..--- ...--', $this->translator->textToMorse('ABC 123'));
    }

    public function testTextToMorseWithPunctuation(): void
    {
        $this->assertEquals('.... . .-.. .-.. --- --..-- / .-- --- .-. .-.. -.. -.-.--', $this->translator->textToMorse('HELLO, WORLD!'));
    }

    public function testMorseToText(): void
    {
        $this->assertEquals('HELLO', $this->translator->morseToText('.... . .-.. .-.. ---'));
        $this->assertEquals('HELLO WORLD', $this->translator->morseToText('.... . .-.. .-.. --- / .-- --- .-. .-.. -..'));
        $this->assertEquals('SOS', $this->translator->morseToText('... --- ...'));
    }

    public function testMorseToTextWithNumbers(): void
    {
        $this->assertEquals('123', $this->translator->morseToText('.---- ..--- ...--'));
        $this->assertEquals('ABC 123', $this->translator->morseToText('.- -... -.-. / .---- ..--- ...--'));
    }

    public function testMorseToTextWithPunctuation(): void
    {
        $this->assertEquals('HELLO, WORLD!', $this->translator->morseToText('.... . .-.. .-.. --- --..-- / .-- --- .-. .-.. -.. -.-.--'));
    }

    public function testGetCharacterMorse(): void
    {
        $this->assertEquals('.-', $this->translator->getCharacterMorse('A'));
        $this->assertEquals('.-', $this->translator->getCharacterMorse('a'));
        $this->assertEquals('.----', $this->translator->getCharacterMorse('1'));
        $this->assertNull($this->translator->getCharacterMorse('~'));
    }

    public function testGetMorseCodeMap(): void
    {
        $map = $this->translator->getMorseCodeMap();
        $this->assertIsArray($map);
        $this->assertArrayHasKey('A', $map);
        $this->assertEquals('.-', $map['A']);
    }

    public function testRoundTrip(): void
    {
        $originalText = 'HELLO WORLD! 123';
        $morse = $this->translator->textToMorse($originalText);
        $decoded = $this->translator->morseToText($morse);
        $this->assertEquals($originalText, $decoded);
    }

    public function testCaseInsensitive(): void
    {
        $this->assertEquals('.... . .-.. .-.. ---', $this->translator->textToMorse('hello'));
        $this->assertEquals('.... . .-.. .-.. ---', $this->translator->textToMorse('HELLO'));
        $this->assertEquals('.... . .-.. .-.. ---', $this->translator->textToMorse('HeLLo'));
    }
}