<?php

declare(strict_types=1);

namespace SOS\Translator;

use InvalidArgumentException;
use RuntimeException;
use SOS\Translator\Contracts\FileGeneratorInterface;
use SOS\Translator\Exceptions\EmptyInputException;
use SOS\Translator\Exceptions\InvalidMorseCodeException;
use SOS\Translator\Traits\MorseValidation;

/**
 * Visual Morse Code Generator
 * 
 * Generates visual representations of Morse code as images (PNG, JPG, GIF) or SVG.
 * Supports customizable colors, sizes, and spacing.
 * 
 * @package SOS\Translator
 */
class VisualGenerator implements FileGeneratorInterface
{
    use MorseValidation;
    private int $dotWidth = 20;
    private int $dashWidth = 60;
    private int $elementHeight = 20;
    private int $elementSpacing = 10;
    private int $characterSpacing = 30;
    private int $wordSpacing = 60;
    private int $padding = 20;
    private int $lineHeight = 40;
    private int $maxWidth = 800;
    
    // Colors (RGB)
    private array $backgroundColor = [255, 255, 255]; // White
    private array $elementColor = [0, 0, 0]; // Black
    private array $textColor = [128, 128, 128]; // Gray
    private int $fontSize = 12;
    
    // Speed-related properties for interface compliance
    private int $ditDuration = 60; // milliseconds
    
    /**
     * Constructor
     * 
     * @throws \RuntimeException If GD extension is not loaded
     */
    public function __construct()
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException("GD extension is required for visual generation");
        }
    }
    
    /**
     * Set the width of dots in pixels
     * 
     * @param int $width Dot width (5-100 pixels)
     * @throws \InvalidArgumentException If width out of valid range
     */
    public function setDotWidth(int $width): void
    {
        if ($width < 5 || $width > 100) {
            throw new InvalidArgumentException("Dot width must be between 5 and 100 pixels");
        }
        $this->dotWidth = $width;
        $this->dashWidth = $width * 3; // Maintain 1:3 ratio
    }
    
    /**
     * Set the height of Morse elements
     * 
     * @param int $height Element height (5-100 pixels)
     * @throws \InvalidArgumentException If height out of valid range
     */
    public function setElementHeight(int $height): void
    {
        if ($height < 5 || $height > 100) {
            throw new InvalidArgumentException("Element height must be between 5 and 100 pixels");
        }
        $this->elementHeight = $height;
    }
    
    /**
     * Set spacing between elements
     * 
     * @param int $element Spacing between dots/dashes
     * @param int $character Spacing between characters
     * @param int $word Spacing between words
     */
    public function setSpacing(int $element, int $character, int $word): void
    {
        $this->elementSpacing = $element;
        $this->characterSpacing = $character;
        $this->wordSpacing = $word;
    }
    
    /**
     * Set colors for the visualization
     * 
     * @param array $background RGB values for background [0-255, 0-255, 0-255]
     * @param array $element RGB values for Morse elements [0-255, 0-255, 0-255]
     * @param array $text RGB values for text labels [0-255, 0-255, 0-255]
     */
    public function setColors(array $background, array $element, array $text): void
    {
        $this->backgroundColor = $background;
        $this->elementColor = $element;
        $this->textColor = $text;
    }
    
    /**
     * Set maximum image width
     * 
     * @param int $width Maximum width in pixels (minimum 200)
     * @throws \InvalidArgumentException If width less than 200
     */
    public function setMaxWidth(int $width): void
    {
        if ($width < 200) {
            throw new InvalidArgumentException("Maximum width must be at least 200 pixels");
        }
        $this->maxWidth = $width;
    }
    
    /**
     * Set the Morse code speed (not used in visual generation)
     * 
     * @param int $wpm Words per minute (5-60)
     * @throws InvalidArgumentException If WPM out of valid range
     */
    public function setSpeed(int $wpm): void
    {
        if ($wpm < 5 || $wpm > 60) {
            throw new InvalidArgumentException("WPM must be between 5 and 60");
        }
        // Speed doesn't affect static visual output
        $this->ditDuration = intval(1200 / $wpm);
    }
    
    /**
     * Generate visual file from Morse code
     * 
     * @param string $morseCode Morse code to visualize
     * @param string $filename Output filename
     * @param mixed $context Optional text label
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     */
    public function generate(string $morseCode, string $filename, $context = null): void
    {
        $text = is_string($context) ? $context : null;
        
        // Determine format based on file extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === 'svg') {
            $this->generateSVG($morseCode, $filename, $text);
        } else {
            $this->generateImage($morseCode, $filename, $text);
        }
    }
    
    /**
     * Generate image file from Morse code
     * 
     * @param string $morseCode Morse code to visualize
     * @param string $filename Output filename (extension determines format)
     * @param string|null $text Optional text label to display
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     */
    public function generateImage(string $morseCode, string $filename, ?string $text = null): void
    {
        if (empty($morseCode)) {
            throw new EmptyInputException("Morse code cannot be empty");
        }
        
        if (!$this->isValidMorseCode($morseCode)) {
            throw new InvalidMorseCodeException($morseCode);
        }
        
        // Calculate dimensions
        $lines = $this->layoutMorseCode($morseCode);
        $imageWidth = $this->maxWidth;
        $imageHeight = ($this->padding * 2) + (count($lines) * $this->lineHeight);
        
        if ($text !== null) {
            $imageHeight += 30; // Extra space for text label
        }
        
        // Create image
        $image = imagecreatetruecolor($imageWidth, $imageHeight);
        
        // Set colors
        $bgColor = imagecolorallocate($image, ...$this->backgroundColor);
        $elColor = imagecolorallocate($image, ...$this->elementColor);
        $txtColor = imagecolorallocate($image, ...$this->textColor);
        
        // Fill background
        imagefilledrectangle($image, 0, 0, $imageWidth, $imageHeight, $bgColor);
        
        // Draw text label if provided
        $yOffset = $this->padding;
        if ($text !== null) {
            imagestring($image, 5, $this->padding, $yOffset, $text, $txtColor);
            $yOffset += 30;
        }
        
        // Draw Morse code
        foreach ($lines as $line) {
            $this->drawMorseLine($image, $line, $yOffset, $elColor, $txtColor);
            $yOffset += $this->lineHeight;
        }
        
        // Save image
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'png':
                imagepng($image, $filename);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($image, $filename, 90);
                break;
            case 'gif':
                imagegif($image, $filename);
                break;
            default:
                imagepng($image, $filename); // Default to PNG
        }
        
        imagedestroy($image);
    }
    
    /**
     * Generate SVG file from Morse code
     * 
     * @param string $morseCode Morse code to visualize
     * @param string $filename Output SVG filename
     * @param string|null $text Optional text label to display
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     */
    public function generateSVG(string $morseCode, string $filename, ?string $text = null): void
    {
        if (empty($morseCode)) {
            throw new EmptyInputException("Morse code cannot be empty");
        }
        
        if (!$this->isValidMorseCode($morseCode)) {
            throw new InvalidMorseCodeException($morseCode);
        }
        
        // Calculate dimensions
        $lines = $this->layoutMorseCode($morseCode);
        $imageWidth = $this->maxWidth;
        $imageHeight = ($this->padding * 2) + (count($lines) * $this->lineHeight);
        
        if ($text !== null) {
            $imageHeight += 30;
        }
        
        // Start SVG
        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $svg .= '<svg width="' . $imageWidth . '" height="' . $imageHeight . '" ';
        $svg .= 'xmlns="http://www.w3.org/2000/svg">' . "\n";
        
        // Background
        $svg .= '<rect width="' . $imageWidth . '" height="' . $imageHeight . '" ';
        $svg .= 'fill="rgb(' . implode(',', $this->backgroundColor) . ')"/>' . "\n";
        
        // Text label if provided
        $yOffset = $this->padding;
        if ($text !== null) {
            $svg .= '<text x="' . $this->padding . '" y="' . ($yOffset + 15) . '" ';
            $svg .= 'font-family="Arial" font-size="14" ';
            $svg .= 'fill="rgb(' . implode(',', $this->textColor) . ')">';
            $svg .= htmlspecialchars($text) . '</text>' . "\n";
            $yOffset += 30;
        }
        
        // Draw Morse code
        foreach ($lines as $line) {
            $svg .= $this->drawMorseLineSVG($line, $yOffset);
            $yOffset += $this->lineHeight;
        }
        
        $svg .= '</svg>';
        
        file_put_contents($filename, $svg);
    }
    
    /**
     * Layout Morse code elements into lines
     * 
     * @param string $morseCode Morse code to layout
     * @return array Array of lines containing elements
     */
    private function layoutMorseCode(string $morseCode): array
    {
        $lines = [];
        $currentLine = [];
        $currentWidth = $this->padding;
        
        $elements = $this->parseMorseCode($morseCode);
        
        foreach ($elements as $element) {
            $elementWidth = $this->getElementWidth($element);
            
            if ($currentWidth + $elementWidth > $this->maxWidth - $this->padding) {
                // Start new line
                if (!empty($currentLine)) {
                    $lines[] = $currentLine;
                    $currentLine = [];
                    $currentWidth = $this->padding;
                }
            }
            
            $currentLine[] = $element;
            $currentWidth += $elementWidth;
        }
        
        if (!empty($currentLine)) {
            $lines[] = $currentLine;
        }
        
        return $lines;
    }
    
    /**
     * Parse Morse code into element array
     * 
     * @param string $morseCode Morse code to parse
     * @return array Array of parsed elements
     */
    private function parseMorseCode(string $morseCode): array
    {
        $elements = [];
        $morseCode = trim($morseCode);
        
        for ($i = 0; $i < strlen($morseCode); $i++) {
            $char = $morseCode[$i];
            
            if ($char === '.' || $char === '-') {
                $elements[] = ['type' => 'element', 'value' => $char];
            } elseif ($char === ' ') {
                if ($i + 1 < strlen($morseCode) && $morseCode[$i + 1] === '/') {
                    $elements[] = ['type' => 'word_space'];
                    $i += 2; // Skip the / and next space
                } else {
                    $elements[] = ['type' => 'char_space'];
                }
            } elseif ($char === '/') {
                $elements[] = ['type' => 'word_space'];
            }
        }
        
        return $elements;
    }
    
    /**
     * Calculate width of an element
     * 
     * @param array $element Element data
     * @return int Width in pixels
     */
    private function getElementWidth(array $element): int
    {
        switch ($element['type']) {
            case 'element':
                return ($element['value'] === '.') ? 
                    $this->dotWidth + $this->elementSpacing : 
                    $this->dashWidth + $this->elementSpacing;
            case 'char_space':
                return $this->characterSpacing;
            case 'word_space':
                return $this->wordSpacing;
            default:
                return 0;
        }
    }
    
    /**
     * Draw a line of Morse code elements
     * 
     * @param resource $image GD image resource
     * @param array $elements Elements to draw
     * @param int $y Y position
     * @param resource $elementColor Element color resource
     * @param resource $textColor Text color resource
     */
    private function drawMorseLine($image, array $elements, int $y, $elementColor, $textColor): void
    {
        $x = $this->padding;
        $charStart = $x;
        $currentChar = [];
        
        foreach ($elements as $i => $element) {
            switch ($element['type']) {
                case 'element':
                    $width = ($element['value'] === '.') ? $this->dotWidth : $this->dashWidth;
                    imagefilledrectangle(
                        $image,
                        $x,
                        $y + ($this->lineHeight - $this->elementHeight) / 2,
                        $x + $width,
                        $y + ($this->lineHeight + $this->elementHeight) / 2,
                        $elementColor
                    );
                    $currentChar[] = $element['value'];
                    $x += $width + $this->elementSpacing;
                    break;
                    
                case 'char_space':
                case 'word_space':
                    // Draw character label
                    if (!empty($currentChar)) {
                        $charMorse = implode('', $currentChar);
                        $charWidth = imagefontwidth(2) * strlen($charMorse);
                        $charX = $charStart + (($x - $charStart - $this->elementSpacing - $charWidth) / 2);
                        imagestring($image, 2, $charX, $y + $this->lineHeight - 15, $charMorse, $textColor);
                        $currentChar = [];
                    }
                    
                    $x += ($element['type'] === 'char_space') ? 
                        $this->characterSpacing : $this->wordSpacing;
                    $charStart = $x;
                    break;
            }
        }
        
        // Draw last character label
        if (!empty($currentChar)) {
            $charMorse = implode('', $currentChar);
            $charWidth = imagefontwidth(2) * strlen($charMorse);
            $charX = $charStart + (($x - $charStart - $this->elementSpacing - $charWidth) / 2);
            imagestring($image, 2, $charX, $y + $this->lineHeight - 15, $charMorse, $textColor);
        }
    }
    
    /**
     * Generate SVG for a line of Morse code
     * 
     * @param array $elements Elements to draw
     * @param int $y Y position
     * @return string SVG markup
     */
    private function drawMorseLineSVG(array $elements, int $y): string
    {
        $svg = '';
        $x = $this->padding;
        $charStart = $x;
        $currentChar = [];
        
        foreach ($elements as $element) {
            switch ($element['type']) {
                case 'element':
                    $width = ($element['value'] === '.') ? $this->dotWidth : $this->dashWidth;
                    $svg .= '<rect x="' . $x . '" y="' . ($y + ($this->lineHeight - $this->elementHeight) / 2) . '" ';
                    $svg .= 'width="' . $width . '" height="' . $this->elementHeight . '" ';
                    $svg .= 'fill="rgb(' . implode(',', $this->elementColor) . ')"/>' . "\n";
                    $currentChar[] = $element['value'];
                    $x += $width + $this->elementSpacing;
                    break;
                    
                case 'char_space':
                case 'word_space':
                    // Draw character label
                    if (!empty($currentChar)) {
                        $charMorse = implode('', $currentChar);
                        $charX = $charStart + (($x - $charStart - $this->elementSpacing) / 2);
                        $svg .= '<text x="' . $charX . '" y="' . ($y + $this->lineHeight - 5) . '" ';
                        $svg .= 'font-family="monospace" font-size="10" text-anchor="middle" ';
                        $svg .= 'fill="rgb(' . implode(',', $this->textColor) . ')">';
                        $svg .= $charMorse . '</text>' . "\n";
                        $currentChar = [];
                    }
                    
                    $x += ($element['type'] === 'char_space') ? 
                        $this->characterSpacing : $this->wordSpacing;
                    $charStart = $x;
                    break;
            }
        }
        
        // Draw last character label
        if (!empty($currentChar)) {
            $charMorse = implode('', $currentChar);
            $charX = $charStart + (($x - $charStart - $this->elementSpacing) / 2);
            $svg .= '<text x="' . $charX . '" y="' . ($y + $this->lineHeight - 5) . '" ';
            $svg .= 'font-family="monospace" font-size="10" text-anchor="middle" ';
            $svg .= 'fill="rgb(' . implode(',', $this->textColor) . ')">';
            $svg .= $charMorse . '</text>' . "\n";
        }
        
        return $svg;
    }
    
}