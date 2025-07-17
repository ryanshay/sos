<?php

declare(strict_types=1);

namespace SOS\Translator;

use InvalidArgumentException;
use RuntimeException;
use SOS\Translator\Exceptions\EmptyInputException;
use SOS\Translator\Exceptions\InvalidCharacterException;
use SOS\Translator\Exceptions\InvalidMorseCodeException;
use SOS\Translator\Traits\MorseValidation;

/**
 * Morse Code Translator
 * 
 * Main class for translating between text and Morse code.
 * Supports audio generation, visual output, terminal blinking, and IoT device control.
 * 
 * @package SOS\Translator
 */
class Translator
{
    use MorseValidation;
    private array $morseCodeMap = [
        'A' => '.-',
        'B' => '-...',
        'C' => '-.-.',
        'D' => '-..',
        'E' => '.',
        'F' => '..-.',
        'G' => '--.',
        'H' => '....',
        'I' => '..',
        'J' => '.---',
        'K' => '-.-',
        'L' => '.-..',
        'M' => '--',
        'N' => '-.',
        'O' => '---',
        'P' => '.--.',
        'Q' => '--.-',
        'R' => '.-.',
        'S' => '...',
        'T' => '-',
        'U' => '..-',
        'V' => '...-',
        'W' => '.--',
        'X' => '-..-',
        'Y' => '-.--',
        'Z' => '--..',
        '0' => '-----',
        '1' => '.----',
        '2' => '..---',
        '3' => '...--',
        '4' => '....-',
        '5' => '.....',
        '6' => '-....',
        '7' => '--...',
        '8' => '---..',
        '9' => '----.',
        '.' => '.-.-.-',
        ',' => '--..--',
        '?' => '..--..',
        '\'' => '.----.',
        '!' => '-.-.--',
        '/' => '-..-.',
        '(' => '-.--.',
        ')' => '-.--.-',
        '&' => '.-...',
        ':' => '---...',
        ';' => '-.-.-.',
        '=' => '-...-',
        '+' => '.-.-.',
        '-' => '-....-',
        '_' => '..--.-',
        '"' => '.-..-.',
        '$' => '...-..-',
        '@' => '.--.-.',
        ' ' => '/'
    ];

    private array $reverseMorseCodeMap;
    private string $unsupportedCharacterMode = UnsupportedCharacterMode::THROW_EXCEPTION;
    private string $replacementCharacter = '?';
    private ?string $outputDir = null;
    private int $maxFileSize = 52428800; // 50MB default

    /**
     * Constructor
     * 
     * @param string $unsupportedCharacterMode How to handle unsupported characters (throw, skip, or replace)
     */
    public function __construct(string $unsupportedCharacterMode = UnsupportedCharacterMode::THROW_EXCEPTION)
    {
        $this->reverseMorseCodeMap = array_flip($this->morseCodeMap);
        $this->setUnsupportedCharacterMode($unsupportedCharacterMode);
    }

    /**
     * Set how to handle unsupported characters during translation
     * 
     * @param string $mode One of: throw, skip, or replace
     * @throws InvalidArgumentException If mode is invalid
     */
    public function setUnsupportedCharacterMode(string $mode): void
    {
        if (!UnsupportedCharacterMode::isValid($mode)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Invalid unsupported character mode '%s'. Valid modes are: %s",
                    $mode,
                    implode(', ', UnsupportedCharacterMode::getValidModes())
                )
            );
        }
        $this->unsupportedCharacterMode = $mode;
    }

    /**
     * Set the character to use when replacing unsupported characters
     * 
     * @param string $character Single character to use as replacement
     * @throws InvalidArgumentException If not exactly one character
     */
    public function setReplacementCharacter(string $character): void
    {
        if (strlen($character) !== 1) {
            throw new InvalidArgumentException("Replacement character must be exactly one character");
        }
        $this->replacementCharacter = $character;
    }

    /**
     * Set the directory for generated output files
     * 
     * @param string $directory Path to output directory
     * @throws InvalidArgumentException If directory doesn't exist or isn't writable
     */
    public function setOutputDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException("Output directory does not exist: $directory");
        }
        if (!is_writable($directory)) {
            throw new InvalidArgumentException("Output directory is not writable: $directory");
        }
        $this->outputDir = rtrim($directory, DIRECTORY_SEPARATOR);
    }

    /**
     * Set the maximum allowed file size for generated files
     * 
     * @param int $bytes Maximum file size in bytes
     * @throws InvalidArgumentException If bytes is not positive
     */
    public function setMaxFileSize(int $bytes): void
    {
        if ($bytes <= 0) {
            throw new InvalidArgumentException("Max file size must be greater than 0");
        }
        $this->maxFileSize = $bytes;
    }

    /**
     * Convert text to Morse code
     * 
     * @param string $text Text to convert
     * @return string Morse code representation
     * @throws EmptyInputException If text is empty
     * @throws InvalidCharacterException If text contains unsupported characters (when in throw mode)
     */
    public function textToMorse(string $text): string
    {
        if (empty($text)) {
            throw new EmptyInputException("Text to translate cannot be empty");
        }

        $text = strtoupper($text);
        $morse = [];
        
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            if (isset($this->morseCodeMap[$char])) {
                $morse[] = $this->morseCodeMap[$char];
            } else {
                switch ($this->unsupportedCharacterMode) {
                    case UnsupportedCharacterMode::THROW_EXCEPTION:
                        throw new InvalidCharacterException($char, $i);
                    case UnsupportedCharacterMode::SKIP:
                        continue 2;
                    case UnsupportedCharacterMode::REPLACE:
                        if (isset($this->morseCodeMap[$this->replacementCharacter])) {
                            $morse[] = $this->morseCodeMap[$this->replacementCharacter];
                        }
                        break;
                }
            }
        }
        
        return implode(' ', $morse);
    }

    /**
     * Convert Morse code to text
     * 
     * @param string $morse Morse code to convert
     * @return string Decoded text
     * @throws EmptyInputException If morse code is empty
     * @throws InvalidMorseCodeException If morse code contains invalid characters or sequences
     */
    public function morseToText(string $morse): string
    {
        $morse = trim($morse);
        
        if (empty($morse)) {
            throw new EmptyInputException("Morse code to translate cannot be empty");
        }
        
        // Validate Morse code format
        if (!$this->isValidMorseCode($morse)) {
            throw new InvalidMorseCodeException($morse);
        }
        
        $text = '';
        
        // Replace multiple spaces with a single space and handle word separators
        $morse = preg_replace('/\s+/', ' ', $morse);
        
        // Split by word separator first
        $words = explode(' / ', $morse);
        
        foreach ($words as $wordIndex => $word) {
            if ($wordIndex > 0) {
                $text .= ' ';
            }
            
            $morseChars = explode(' ', trim($word));
            foreach ($morseChars as $morseChar) {
                if ($morseChar !== '') {
                    if (isset($this->reverseMorseCodeMap[$morseChar])) {
                        $text .= $this->reverseMorseCodeMap[$morseChar];
                    } else {
                        switch ($this->unsupportedCharacterMode) {
                            case UnsupportedCharacterMode::THROW_EXCEPTION:
                                throw new InvalidMorseCodeException($morseChar, "Unknown Morse code sequence: '$morseChar'");
                            case UnsupportedCharacterMode::SKIP:
                                continue 2;
                            case UnsupportedCharacterMode::REPLACE:
                                $text .= $this->replacementCharacter;
                                break;
                        }
                    }
                }
            }
        }
        
        return $text;
    }

    /**
     * Get the complete Morse code mapping
     * 
     * @return array Array mapping characters to their Morse code representations
     */
    public function getMorseCodeMap(): array
    {
        return $this->morseCodeMap;
    }

    /**
     * Get the Morse code for a single character
     * 
     * @param string $character Single character to look up
     * @return string|null Morse code or null if character not supported
     * @throws EmptyInputException If character is empty
     * @throws InvalidArgumentException If more than one character provided
     */
    public function getCharacterMorse(string $character): ?string
    {
        if (empty($character)) {
            throw new EmptyInputException("Character cannot be empty");
        }
        
        if (strlen($character) > 1) {
            throw new InvalidArgumentException("Only single characters are allowed");
        }
        
        $character = strtoupper($character);
        return $this->morseCodeMap[$character] ?? null;
    }


    /**
     * Prepare safe file path for output
     * 
     * @param string $filename Filename to prepare
     * @return string Safe absolute file path
     * @throws RuntimeException If output directory not set
     */
    private function prepareFilePath(string $filename): string
    {
        if ($this->outputDir === null) {
            throw new RuntimeException("Output directory must be set before generating files. Use setOutputDirectory() method.");
        }
        
        // Normalize and validate output directory
        $realOutputDir = realpath($this->outputDir);
        if ($realOutputDir === false) {
            throw new RuntimeException("Output directory does not exist: {$this->outputDir}");
        }
        
        // Extract just the filename, removing any path components
        $safeFilename = basename($filename);
        
        // Check for empty or special filenames
        if (empty($safeFilename) || $safeFilename === '.' || $safeFilename === '..') {
            throw new InvalidArgumentException("Invalid filename provided");
        }
        
        // Validate filename characters (allow alphanumeric, dots, dashes, underscores)
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $safeFilename)) {
            throw new InvalidArgumentException("Filename contains invalid characters. Only alphanumeric, dots, dashes, and underscores are allowed.");
        }
        
        // Additional security: check for double extensions that might bypass filters
        if (preg_match('/\.(php|phtml|phar)[0-9]*$/i', $safeFilename)) {
            throw new InvalidArgumentException("Executable file extensions are not allowed");
        }
        
        // Build the full path
        $fullPath = $realOutputDir . DIRECTORY_SEPARATOR . $safeFilename;
        
        // Final validation: ensure the path is within output directory
        // This handles symbolic links and other edge cases
        $dirPath = dirname($fullPath);
        if ($dirPath !== $realOutputDir) {
            throw new InvalidArgumentException("Invalid filename: file would be created outside output directory");
        }
        
        return $fullPath;
    }

    /**
     * Check if estimated file size exceeds limit
     * 
     * @param int $estimatedSize Estimated file size in bytes
     * @throws RuntimeException If size exceeds maximum allowed
     */
    private function checkFileSizeLimit(int $estimatedSize): void
    {
        if ($estimatedSize > $this->maxFileSize) {
            $maxSizeMB = round($this->maxFileSize / 1048576, 2);
            $estimatedSizeMB = round($estimatedSize / 1048576, 2);
            throw new RuntimeException(
                "Estimated file size ({$estimatedSizeMB}MB) exceeds maximum allowed size ({$maxSizeMB}MB). " .
                "Use setMaxFileSize() to increase the limit."
            );
        }
    }

    /**
     * Generate audio file from text
     * 
     * @param string $text Text to convert to Morse audio
     * @param string $filename Output filename
     * @param AudioGenerator|null $audioGenerator Optional custom audio generator
     * @throws EmptyInputException If text is empty
     * @throws InvalidCharacterException If text contains unsupported characters
     * @throws RuntimeException If file generation fails
     * @throws InvalidMorseCodeException
     */
    public function generateAudioFile(string $text, string $filename, ?AudioGenerator $audioGenerator = null): void
    {
        if ($audioGenerator === null) {
            $audioGenerator = new AudioGenerator();
        }
        
        $safeFilePath = $this->prepareFilePath($filename);
        $morseCode = $this->textToMorse($text);
        
        // Check estimated file size
        $estimatedSize = $audioGenerator->estimateFileSize($morseCode);
        $this->checkFileSizeLimit($estimatedSize);
        
        $audioGenerator->generateWavFile($morseCode, $safeFilePath);
    }

    /**
     * Generate visual representation file from text
     * 
     * @param string $text Text to convert to visual Morse code
     * @param string $filename Output filename (extension determines format: png, jpg, gif, svg)
     * @param VisualGenerator|null $visualGenerator Optional custom visual generator
     * @throws EmptyInputException If text is empty
     * @throws InvalidCharacterException If text contains unsupported characters
     * @throws RuntimeException If file generation fails
     * @throws InvalidMorseCodeException
 */
    public function generateVisualFile(string $text, string $filename, ?VisualGenerator $visualGenerator = null): void
    {
        if ($visualGenerator === null) {
            $visualGenerator = new VisualGenerator();
        }
        
        $safeFilePath = $this->prepareFilePath($filename);
        $morseCode = $this->textToMorse($text);
        
        // Determine format based on file extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === 'svg') {
            $visualGenerator->generateSVG($morseCode, $safeFilePath, $text);
        } else {
            $visualGenerator->generateImage($morseCode, $safeFilePath, $text);
        }
    }

    /**
     * Blink Morse code in terminal
     * 
     * @param string $text Text to blink as Morse code
     * @param array $options Options: speed, fullscreen, repeats
     * @throws EmptyInputException If text is empty
     * @throws InvalidCharacterException If text contains unsupported characters
     * @throws InvalidMorseCodeException
 */
    public function blinkInTerminal(string $text, array $options = []): void
    {
        $blinker = new TerminalBlinker();
        
        // Apply options
        if (isset($options['speed'])) {
            $blinker->setSpeed($options['speed']);
        }
        if (isset($options['fullscreen']) && $options['fullscreen']) {
            $morseCode = $this->textToMorse($text);
            $blinker->blinkFullscreen($morseCode, $text, $options['repeats'] ?? 2);
        } else {
            $morseCode = $this->textToMorse($text);
            $blinker->blinkInline($morseCode, $text);
        }
    }

    /**
     * Generate blink pattern file from text
     * 
     * @param string $text Text to convert to blink pattern
     * @param string $filename Output filename (extension determines format: json, csv, html)
     * @param BlinkPatternGenerator|null $generator Optional custom pattern generator
     * @throws EmptyInputException If text is empty
     * @throws InvalidCharacterException If text contains unsupported characters
     * @throws InvalidArgumentException If file format not supported
     * @throws RuntimeException If file generation fails
     * @throws InvalidMorseCodeException
 */
    public function generateBlinkPattern(string $text, string $filename, ?BlinkPatternGenerator $generator = null): void
    {
        if ($generator === null) {
            $generator = new BlinkPatternGenerator();
        }
        
        $safeFilePath = $this->prepareFilePath($filename);
        $morseCode = $this->textToMorse($text);
        
        // Determine format based on file extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'json':
                $generator->exportJSON($morseCode, $safeFilePath);
                break;
            case 'csv':
                $generator->exportCSV($morseCode, $safeFilePath);
                break;
            case 'html':
            case 'htm':
                $generator->generateHTML($morseCode, $safeFilePath, $text);
                break;
            default:
                throw new InvalidArgumentException("Unsupported file format: $extension");
        }
    }

    /**
     * Export blink timing data in specified format
     * 
     * @param string $text Text to convert to blink timings
     * @param string $format Output format: 'array' or 'arduino'
     * @return mixed Array of timings or Arduino code string
     * @throws EmptyInputException If text is empty
     * @throws InvalidCharacterException If text contains unsupported characters
     * @throws InvalidArgumentException If format not supported
     * @throws InvalidMorseCodeException
 */
    public function exportBlinkTimings(string $text, string $format = 'array'): mixed
    {
        $generator = new BlinkPatternGenerator();
        $morseCode = $this->textToMorse($text);
        
        switch ($format) {
            case 'array':
                return $generator->exportTimingArray($morseCode);
            case 'arduino':
                return $generator->generateArduinoCode($morseCode);
            default:
                throw new InvalidArgumentException("Unsupported format: $format");
        }
    }

    /**
     * Blink Morse code on Govee smart light
     * 
     * @param string $text Text to blink as Morse code
     * @param GoveeController|null $controller Optional custom Govee controller
     * @throws EmptyInputException If text is empty
     * @throws InvalidCharacterException If text contains unsupported characters
     * @throws RuntimeException If no Govee device selected
     */
    public function blinkOnGoveeLight(string $text, ?GoveeController $controller = null): void
    {
        if ($controller === null) {
            $controller = new GoveeController();
        }
        
        // Check if a device is selected
        if ($controller->getSelectedDevice() === null) {
            throw new RuntimeException("No Govee device selected. Use GoveeController::selectDevice() first.");
        }
        
        $morseCode = $this->textToMorse($text);
        $controller->blinkMorseCode($morseCode, $text);
    }
}