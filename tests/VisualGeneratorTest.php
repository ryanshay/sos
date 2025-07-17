<?php

namespace SOS\Translator\Tests;

use PHPUnit\Framework\TestCase;
use SOS\Translator\VisualGenerator;
use SOS\Translator\Exceptions\EmptyInputException;
use SOS\Translator\Exceptions\InvalidMorseCodeException;

class VisualGeneratorTest extends TestCase
{
    private VisualGenerator $visualGenerator;
    private string $tempDir;

    protected function setUp(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }
        
        $this->visualGenerator = new VisualGenerator();
        $this->tempDir = sys_get_temp_dir() . '/morse_visual_test_' . uniqid();
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

    public function testGeneratePNGImage(): void
    {
        $filename = $this->tempDir . '/test.png';
        $this->visualGenerator->generateImage('... --- ...', $filename);
        
        $this->assertFileExists($filename);
        
        // Verify it's a valid PNG
        $imageInfo = getimagesize($filename);
        $this->assertNotFalse($imageInfo);
        $this->assertEquals(IMAGETYPE_PNG, $imageInfo[2]);
    }

    public function testGenerateJPEGImage(): void
    {
        $filename = $this->tempDir . '/test.jpg';
        $this->visualGenerator->generateImage('... --- ...', $filename);
        
        $this->assertFileExists($filename);
        
        // Verify it's a valid JPEG
        $imageInfo = getimagesize($filename);
        $this->assertNotFalse($imageInfo);
        $this->assertEquals(IMAGETYPE_JPEG, $imageInfo[2]);
    }

    public function testGenerateSVG(): void
    {
        $filename = $this->tempDir . '/test.svg';
        $this->visualGenerator->generateSVG('... --- ...', $filename);
        
        $this->assertFileExists($filename);
        
        // Verify SVG content
        $content = file_get_contents($filename);
        $this->assertStringContainsString('<?xml version="1.0"', $content);
        $this->assertStringContainsString('<svg', $content);
        $this->assertStringContainsString('</svg>', $content);
    }

    public function testGenerateImageWithText(): void
    {
        $filename = $this->tempDir . '/test_with_text.png';
        $this->visualGenerator->generateImage('... --- ...', $filename, 'SOS');
        
        $this->assertFileExists($filename);
    }

    public function testEmptyMorseCodeThrowsException(): void
    {
        $this->expectException(EmptyInputException::class);
        $this->visualGenerator->generateImage('', $this->tempDir . '/test.png');
    }

    public function testInvalidMorseCodeThrowsException(): void
    {
        $this->expectException(InvalidMorseCodeException::class);
        $this->visualGenerator->generateImage('... ABC ...', $this->tempDir . '/test.png');
    }

    public function testSetDotWidth(): void
    {
        $this->visualGenerator->setDotWidth(30);
        $filename = $this->tempDir . '/custom_dot.png';
        $this->visualGenerator->generateImage('...', $filename);
        $this->assertFileExists($filename);
    }

    public function testInvalidDotWidthThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Dot width must be between 5 and 100 pixels");
        $this->visualGenerator->setDotWidth(200);
    }

    public function testSetElementHeight(): void
    {
        $this->visualGenerator->setElementHeight(30);
        $filename = $this->tempDir . '/custom_height.png';
        $this->visualGenerator->generateImage('---', $filename);
        $this->assertFileExists($filename);
    }

    public function testInvalidElementHeightThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Element height must be between 5 and 100 pixels");
        $this->visualGenerator->setElementHeight(200);
    }

    public function testSetMaxWidth(): void
    {
        $this->visualGenerator->setMaxWidth(400);
        $filename = $this->tempDir . '/narrow.png';
        // Long morse code that should wrap
        $longMorse = '.... . .-.. .-.. --- / .-- --- .-. .-.. -.. / - .... .. ... / .. ... / .- / .-.. --- -. --. / -- . ... ... .- --. .';
        $this->visualGenerator->generateImage($longMorse, $filename);
        
        $this->assertFileExists($filename);
        
        // Check that width is constrained
        $imageInfo = getimagesize($filename);
        $this->assertEquals(400, $imageInfo[0]);
    }

    public function testInvalidMaxWidthThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Maximum width must be at least 200 pixels");
        $this->visualGenerator->setMaxWidth(100);
    }

    public function testCustomColors(): void
    {
        $this->visualGenerator->setColors(
            [0, 0, 0],       // Black background
            [255, 255, 255], // White elements
            [128, 128, 128]  // Gray text
        );
        
        $filename = $this->tempDir . '/custom_colors.png';
        $this->visualGenerator->generateImage('... --- ...', $filename);
        $this->assertFileExists($filename);
    }

    public function testComplexMorseCode(): void
    {
        $filename = $this->tempDir . '/complex.png';
        $morseCode = '.... . .-.. .-.. --- / .-- --- .-. .-.. -..'; // HELLO WORLD
        
        $this->visualGenerator->generateImage($morseCode, $filename, 'HELLO WORLD');
        
        $this->assertFileExists($filename);
        $this->assertGreaterThan(1000, filesize($filename)); // Should be a reasonable size
    }

    public function testSVGWithText(): void
    {
        $filename = $this->tempDir . '/svg_with_text.svg';
        $this->visualGenerator->generateSVG('... --- ...', $filename, 'SOS');
        
        $this->assertFileExists($filename);
        
        $content = file_get_contents($filename);
        $this->assertStringContainsString('SOS', $content);
    }

    public function testLineWrapping(): void
    {
        $this->visualGenerator->setMaxWidth(300);
        $filename = $this->tempDir . '/wrapped.png';
        
        // Very long morse code that must wrap
        $longMorse = str_repeat('.... . .-.. .-.. --- ', 10);
        $this->visualGenerator->generateImage($longMorse, $filename);
        
        $this->assertFileExists($filename);
        
        // Image should be taller due to wrapping
        $imageInfo = getimagesize($filename);
        $this->assertGreaterThan(100, $imageInfo[1]); // Height should be significant
    }
}