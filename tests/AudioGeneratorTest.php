<?php

namespace SOS\Translator\Tests;

use PHPUnit\Framework\TestCase;
use SOS\Translator\AudioGenerator;
use SOS\Translator\Exceptions\EmptyInputException;
use SOS\Translator\Exceptions\InvalidMorseCodeException;

class AudioGeneratorTest extends TestCase
{
    private AudioGenerator $audioGenerator;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->audioGenerator = new AudioGenerator();
        $this->tempDir = sys_get_temp_dir() . '/morse_audio_test_' . uniqid();
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

    public function testGenerateWavFile(): void
    {
        $filename = $this->tempDir . '/test.wav';
        $this->audioGenerator->generateWavFile('... --- ...', $filename);
        
        $this->assertFileExists($filename);
        $this->assertGreaterThan(44, filesize($filename)); // WAV header is 44 bytes
        
        // Verify WAV header
        $handle = fopen($filename, 'rb');
        $header = fread($handle, 4);
        fclose($handle);
        
        $this->assertEquals('RIFF', $header);
    }

    public function testEmptyMorseCodeThrowsException(): void
    {
        $this->expectException(EmptyInputException::class);
        $this->audioGenerator->generateWavFile('', $this->tempDir . '/test.wav');
    }

    public function testInvalidMorseCodeThrowsException(): void
    {
        $this->expectException(InvalidMorseCodeException::class);
        $this->audioGenerator->generateWavFile('... ABC ...', $this->tempDir . '/test.wav');
    }

    public function testSetFrequency(): void
    {
        $this->audioGenerator->setFrequency(800);
        $this->assertEquals(800, $this->audioGenerator->getFrequency());
    }

    public function testInvalidFrequencyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Frequency must be between 100 and 4000 Hz");
        $this->audioGenerator->setFrequency(50);
    }

    public function testSetSampleRate(): void
    {
        $this->audioGenerator->setSampleRate(22050);
        $this->assertEquals(22050, $this->audioGenerator->getSampleRate());
    }

    public function testInvalidSampleRateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Sample rate must be between 8000 and 192000 Hz");
        $this->audioGenerator->setSampleRate(4000);
    }

    public function testSetSpeed(): void
    {
        $this->audioGenerator->setSpeed(20); // 20 WPM
        $this->assertEquals(60, $this->audioGenerator->getDitDuration()); // 1200/20 = 60ms
        $this->assertEquals(180, $this->audioGenerator->getDahDuration()); // 3x dit
    }

    public function testInvalidSpeedThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("WPM must be between 5 and 60");
        $this->audioGenerator->setSpeed(100);
    }

    public function testSetVolume(): void
    {
        $this->audioGenerator->setVolume(0.8);
        $this->assertEquals(0.8, $this->audioGenerator->getVolume());
    }

    public function testInvalidVolumeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Volume must be between 0.0 and 1.0");
        $this->audioGenerator->setVolume(1.5);
    }

    public function testGenerateComplexMorseCode(): void
    {
        $filename = $this->tempDir . '/complex.wav';
        $morseCode = '.... . .-.. .-.. --- / .-- --- .-. .-.. -..'; // HELLO WORLD
        
        $this->audioGenerator->generateWavFile($morseCode, $filename);
        
        $this->assertFileExists($filename);
        
        // The file should be reasonably large for this amount of Morse code
        $fileSize = filesize($filename);
        $this->assertGreaterThan(10000, $fileSize); // Should be at least 10KB
    }

    public function testDifferentSpeeds(): void
    {
        $morseCode = '... --- ...'; // SOS
        
        // Slow speed (5 WPM)
        $this->audioGenerator->setSpeed(5);
        $slowFile = $this->tempDir . '/slow.wav';
        $this->audioGenerator->generateWavFile($morseCode, $slowFile);
        $slowSize = filesize($slowFile);
        
        // Fast speed (30 WPM)
        $this->audioGenerator->setSpeed(30);
        $fastFile = $this->tempDir . '/fast.wav';
        $this->audioGenerator->generateWavFile($morseCode, $fastFile);
        $fastSize = filesize($fastFile);
        
        // Slow file should be larger (longer duration)
        $this->assertGreaterThan($fastSize, $slowSize);
    }

    public function testWriteToInvalidPath(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Could not open file for writing/');
        $this->audioGenerator->generateWavFile('...', '/invalid/path/test.wav');
    }
}