<?php

namespace SOS\Translator\Tests;

use PHPUnit\Framework\TestCase;
use SOS\Translator\TerminalBlinker;
use SOS\Translator\Exceptions\EmptyInputException;
use SOS\Translator\Exceptions\InvalidMorseCodeException;

class TerminalBlinkerTest extends TestCase
{
    private TerminalBlinker $blinker;

    protected function setUp(): void
    {
        $this->blinker = new TerminalBlinker();
    }

    public function testGetTimingArray(): void
    {
        $timings = $this->blinker->getTimingArray('.-');
        
        $this->assertCount(3, $timings); // dot, element spacing, dash
        $this->assertEquals('on', $timings[0]['state']);
        $this->assertEquals(60, $timings[0]['duration']); // dit
        $this->assertEquals('off', $timings[1]['state']);
        $this->assertEquals(60, $timings[1]['duration']); // element spacing
        $this->assertEquals('on', $timings[2]['state']);
        $this->assertEquals(180, $timings[2]['duration']); // dah
    }

    public function testGetTimingArrayWithSpaces(): void
    {
        $timings = $this->blinker->getTimingArray('. -');
        
        $this->assertCount(3, $timings); // dot, character spacing, dash
        $this->assertEquals('on', $timings[0]['state']);
        $this->assertEquals('off', $timings[1]['state']);
        $this->assertEquals(180, $timings[1]['duration']); // character spacing
    }

    public function testGetTimingArrayWithWordSeparator(): void
    {
        $timings = $this->blinker->getTimingArray('. / -');
        
        $this->assertCount(3, $timings); // dot, word spacing, dash
        $this->assertEquals('off', $timings[1]['state']);
        $this->assertEquals(420, $timings[1]['duration']); // word spacing
    }

    public function testSetSpeed(): void
    {
        $this->blinker->setSpeed(20); // 20 WPM
        $timings = $this->blinker->getTimingArray('.');
        
        $this->assertEquals(60, $timings[0]['duration']); // 1200/20 = 60ms
    }

    public function testInvalidSpeedThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("WPM must be between 5 and 60");
        $this->blinker->setSpeed(100);
    }

    public function testEmptyMorseCodeThrowsException(): void
    {
        $this->expectException(EmptyInputException::class);
        $this->blinker->getTimingArray('');
    }

    public function testInvalidMorseCodeThrowsException(): void
    {
        $this->expectException(InvalidMorseCodeException::class);
        $this->blinker->getTimingArray('ABC');
    }

    public function testComplexMorseCode(): void
    {
        // SOS: ... --- ...
        $timings = $this->blinker->getTimingArray('... --- ...');
        
        // Count expected elements:
        // S: dot, off, dot, off, dot, char_space (6)
        // O: dash, off, dash, off, dash, char_space (6)
        // S: dot, off, dot, off, dot (5)
        // Total: 17
        $expectedCount = 17;
        
        $this->assertCount($expectedCount, $timings);
        
        // Verify first dot
        $this->assertEquals('on', $timings[0]['state']);
        $this->assertEquals(60, $timings[0]['duration']);
    }

    public function testMultipleSpeedSettings(): void
    {
        // Test different WPM settings
        $speeds = [5, 10, 20, 30, 60];
        
        foreach ($speeds as $wpm) {
            $this->blinker->setSpeed($wpm);
            $timings = $this->blinker->getTimingArray('.');
            
            $expectedDit = intval(1200 / $wpm);
            $this->assertEquals($expectedDit, $timings[0]['duration']);
        }
    }
}