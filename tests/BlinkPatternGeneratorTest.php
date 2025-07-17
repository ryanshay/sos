<?php

namespace SOS\Translator\Tests;

use PHPUnit\Framework\TestCase;
use SOS\Translator\BlinkPatternGenerator;
use SOS\Translator\Exceptions\EmptyInputException;
use SOS\Translator\Exceptions\InvalidMorseCodeException;

class BlinkPatternGeneratorTest extends TestCase
{
    private BlinkPatternGenerator $generator;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->generator = new BlinkPatternGenerator();
        $this->tempDir = sys_get_temp_dir() . '/morse_blink_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    public function testGenerateHTML(): void
    {
        $filename = $this->tempDir . '/test.html';
        $this->generator->generateHTML('... --- ...', $filename, 'SOS');
        
        $this->assertFileExists($filename);
        
        $content = file_get_contents($filename);
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('Morse Code: SOS', $content);
        $this->assertStringContainsString('... --- ...', $content);
        $this->assertStringContainsString('timings =', $content);
    }

    public function testExportTimingArray(): void
    {
        $timings = $this->generator->exportTimingArray('.-');
        
        $this->assertCount(3, $timings);
        $this->assertEquals(['state' => 'on', 'duration' => 60], $timings[0]);
        $this->assertEquals(['state' => 'off', 'duration' => 60], $timings[1]);
        $this->assertEquals(['state' => 'on', 'duration' => 180], $timings[2]);
    }

    public function testExportJSON(): void
    {
        $filename = $this->tempDir . '/test.json';
        $this->generator->exportJSON('...', $filename);
        
        $this->assertFileExists($filename);
        
        $data = json_decode(file_get_contents($filename), true);
        $this->assertEquals('...', $data['morse']);
        $this->assertArrayHasKey('timings', $data);
        $this->assertArrayHasKey('settings', $data);
        $this->assertEquals(60, $data['settings']['dit_duration']);
    }

    public function testExportCSV(): void
    {
        $filename = $this->tempDir . '/test.csv';
        $this->generator->exportCSV('.-', $filename);
        
        $this->assertFileExists($filename);
        
        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        $this->assertEquals('index,state,duration_ms', $lines[0]);
        $this->assertEquals('0,on,60', $lines[1]);
        $this->assertEquals('1,off,60', $lines[2]);
        $this->assertEquals('2,on,180', $lines[3]);
    }

    public function testGenerateArduinoCode(): void
    {
        $code = $this->generator->generateArduinoCode('...');
        
        $this->assertStringContainsString('const int LED_PIN = 13;', $code);
        $this->assertStringContainsString('pinMode(LED_PIN, OUTPUT);', $code);
        $this->assertStringContainsString('digitalWrite(LED_PIN, HIGH);', $code);
        $this->assertStringContainsString('delay(60);', $code);
    }

    public function testSetSpeed(): void
    {
        $this->generator->setSpeed(20); // 20 WPM
        $timings = $this->generator->exportTimingArray('.');
        
        $this->assertEquals(60, $timings[0]['duration']); // 1200/20 = 60ms
    }

    public function testInvalidSpeedThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("WPM must be between 5 and 60");
        $this->generator->setSpeed(100);
    }

    public function testSetLightSize(): void
    {
        $this->generator->setLightSize(300);
        $filename = $this->tempDir . '/test_size.html';
        $this->generator->generateHTML('...', $filename);
        
        $content = file_get_contents($filename);
        $this->assertStringContainsString('width: 300px', $content);
        $this->assertStringContainsString('height: 300px', $content);
    }

    public function testInvalidLightSizeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Light size must be between 50 and 500 pixels");
        $this->generator->setLightSize(1000);
    }

    public function testEmptyMorseCodeThrowsException(): void
    {
        $this->expectException(EmptyInputException::class);
        $this->generator->exportTimingArray('');
    }

    public function testInvalidMorseCodeThrowsException(): void
    {
        $this->expectException(InvalidMorseCodeException::class);
        $this->generator->exportTimingArray('ABC');
    }

    public function testCustomColors(): void
    {
        $this->generator->setColors('#FF0000', '#0000FF');
        $filename = $this->tempDir . '/test_colors.html';
        $this->generator->generateHTML('...', $filename);
        
        $content = file_get_contents($filename);
        $this->assertStringContainsString('background-color: #0000FF', $content);
        $this->assertStringContainsString('background-color: #FF0000', $content);
    }

    public function testNoControls(): void
    {
        $this->generator->setIncludeControls(false);
        $filename = $this->tempDir . '/test_no_controls.html';
        $this->generator->generateHTML('...', $filename);
        
        $content = file_get_contents($filename);
        $this->assertStringNotContainsString('id="playBtn"', $content);
        $this->assertStringNotContainsString('class="controls"', $content);
    }

    public function testWriteFailureThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Could not write file/');
        $this->generator->generateHTML('...', '/invalid/path/test.html');
    }
}