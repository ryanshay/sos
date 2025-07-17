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
 * Audio Generator for Morse Code
 * 
 * Generates WAV audio files from Morse code patterns.
 * Supports customizable speed, frequency, and volume settings.
 * 
 * @package SOS\Translator
 */
class AudioGenerator implements FileGeneratorInterface
{
    use MorseValidation;
    private int $sampleRate = 44100; // CD quality
    private int $frequency = 600; // Hz - standard Morse code frequency
    private int $ditDuration = 60; // milliseconds
    private int $dahDuration = 180; // milliseconds (3x dit)
    private int $elementSpacing = 60; // space between dots/dashes (1x dit)
    private int $characterSpacing = 180; // space between characters (3x dit)
    private int $wordSpacing = 420; // space between words (7x dit)
    private float $volume = 0.5; // 0.0 to 1.0

    /**
     * Set the audio sample rate
     * 
     * @param int $sampleRate Sample rate in Hz (8000-192000)
     * @throws \InvalidArgumentException If sample rate out of valid range
     */
    public function setSampleRate(int $sampleRate): void
    {
        if ($sampleRate < 8000 || $sampleRate > 192000) {
            throw new InvalidArgumentException("Sample rate must be between 8000 and 192000 Hz");
        }
        $this->sampleRate = $sampleRate;
    }

    /**
     * Set the tone frequency
     * 
     * @param int $frequency Frequency in Hz (100-4000)
     * @throws \InvalidArgumentException If frequency out of valid range
     */
    public function setFrequency(int $frequency): void
    {
        if ($frequency < 100 || $frequency > 4000) {
            throw new InvalidArgumentException("Frequency must be between 100 and 4000 Hz");
        }
        $this->frequency = $frequency;
    }

    /**
     * Set the Morse code speed
     * 
     * @param int $wpm Words per minute (5-60)
     * @throws \InvalidArgumentException If WPM out of valid range
     */
    public function setSpeed(int $wpm): void
    {
        if ($wpm < 5 || $wpm > 60) {
            throw new InvalidArgumentException("WPM must be between 5 and 60");
        }
        
        // Standard formula: dit duration in ms = 1200 / WPM
        $this->ditDuration = intval(1200 / $wpm);
        $this->dahDuration = $this->ditDuration * 3;
        $this->elementSpacing = $this->ditDuration;
        $this->characterSpacing = $this->ditDuration * 3;
        $this->wordSpacing = $this->ditDuration * 7;
    }

    /**
     * Set the audio volume
     * 
     * @param float $volume Volume level (0.0-1.0)
     * @throws \InvalidArgumentException If volume out of valid range
     */
    public function setVolume(float $volume): void
    {
        if ($volume < 0.0 || $volume > 1.0) {
            throw new InvalidArgumentException("Volume must be between 0.0 and 1.0");
        }
        $this->volume = $volume;
    }

    /**
     * Generate WAV audio file from Morse code
     * 
     * @param string $morseCode Morse code to convert to audio
     * @param string $filename Output WAV file path
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     * @throws \RuntimeException If file cannot be written
     */
    /**
     * Generate WAV audio file from Morse code
     * 
     * @param string $morseCode Morse code to convert to audio
     * @param string $filename Output WAV file path  
     * @param mixed $context Not used in audio generation
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     * @throws RuntimeException If file cannot be written
     */
    public function generate(string $morseCode, string $filename, $context = null): void
    {
        $this->generateWavFile($morseCode, $filename);
    }
    
    /**
     * Generate WAV audio file from Morse code
     * 
     * @param string $morseCode Morse code to convert to audio
     * @param string $filename Output WAV file path
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     * @throws RuntimeException If file cannot be written
     */
    public function generateWavFile(string $morseCode, string $filename): void
    {
        if (empty($morseCode)) {
            throw new EmptyInputException("Morse code cannot be empty");
        }

        if (!$this->isValidMorseCode($morseCode)) {
            throw new InvalidMorseCodeException($morseCode);
        }

        $audioData = $this->generateAudioData($morseCode);
        $this->writeWavFile($audioData, $filename);
    }

    /**
     * Generate audio sample data from Morse code
     * 
     * @param string $morseCode Morse code to convert
     * @return array Array of audio samples
     */
    private function generateAudioData(string $morseCode): array
    {
        $audioData = [];
        $morseCode = trim($morseCode);
        
        for ($i = 0; $i < strlen($morseCode); $i++) {
            $char = $morseCode[$i];
            
            switch ($char) {
                case '.':
                    $audioData = array_merge($audioData, $this->generateTone($this->ditDuration));
                    break;
                    
                case '-':
                    $audioData = array_merge($audioData, $this->generateTone($this->dahDuration));
                    break;
                    
                case ' ':
                    // Check if this is a word separator (multiple spaces or /)
                    if ($i + 1 < strlen($morseCode) && $morseCode[$i + 1] === '/') {
                        $audioData = array_merge($audioData, $this->generateSilence($this->wordSpacing));
                        $i += 2; // Skip the / and next space
                        continue 2;
                    } elseif ($i > 0 && $morseCode[$i - 1] !== ' ') {
                        // Character spacing
                        $audioData = array_merge($audioData, $this->generateSilence($this->characterSpacing));
                    }
                    break;
                    
                case '/':
                    // Word separator
                    $audioData = array_merge($audioData, $this->generateSilence($this->wordSpacing));
                    break;
            }
            
            // Add element spacing after dots and dashes
            if (($char === '.' || $char === '-') && 
                $i + 1 < strlen($morseCode) && 
                $morseCode[$i + 1] !== ' ' && 
                $morseCode[$i + 1] !== '/') {
                $audioData = array_merge($audioData, $this->generateSilence($this->elementSpacing));
            }
        }
        
        return $audioData;
    }

    /**
     * Generate tone samples for given duration
     * 
     * @param int $durationMs Duration in milliseconds
     * @return array Array of audio samples
     */
    private function generateTone(int $durationMs): array
    {
        $samples = intval($this->sampleRate * $durationMs / 1000);
        $data = [];
        
        for ($i = 0; $i < $samples; $i++) {
            $time = $i / $this->sampleRate;
            $value = sin(2 * M_PI * $this->frequency * $time) * $this->volume;
            
            // Apply fade in/out to prevent clicks
            $fadeSamples = min(100, $samples / 10);
            if ($i < $fadeSamples) {
                $value *= $i / $fadeSamples;
            } elseif ($i > $samples - $fadeSamples) {
                $value *= ($samples - $i) / $fadeSamples;
            }
            
            $data[] = $value;
        }
        
        return $data;
    }

    /**
     * Generate silence samples for given duration
     * 
     * @param int $durationMs Duration in milliseconds
     * @return array Array of silence samples
     */
    private function generateSilence(int $durationMs): array
    {
        $samples = intval($this->sampleRate * $durationMs / 1000);
        return array_fill(0, $samples, 0.0);
    }

    /**
     * Write audio data to WAV file
     * 
     * @param array $audioData Audio sample data
     * @param string $filename Output file path
     * @throws \RuntimeException If file cannot be written
     */
    private function writeWavFile(array $audioData, string $filename): void
    {
        $handle = fopen($filename, 'wb');
        if (!$handle) {
            $error = error_get_last();
            throw new RuntimeException("Could not open file for writing: $filename. " . ($error['message'] ?? 'Unknown error'));
        }

        try {
            $dataSize = count($audioData) * 2; // 16-bit samples
            $fileSize = 36 + $dataSize;
            
            // Write WAV header
            if (fwrite($handle, 'RIFF') === false) {
                throw new RuntimeException("Failed to write WAV header");
            }
            if (fwrite($handle, pack('V', $fileSize)) === false) {
                throw new RuntimeException("Failed to write file size");
            }
            if (fwrite($handle, 'WAVE') === false) {
                throw new RuntimeException("Failed to write WAVE marker");
            }
            
            // Format chunk
            if (fwrite($handle, 'fmt ') === false) {
                throw new RuntimeException("Failed to write format chunk");
            }
            if (fwrite($handle, pack('V', 16)) === false) { // Chunk size
                throw new RuntimeException("Failed to write chunk size");
            }
            if (fwrite($handle, pack('v', 1)) === false) { // Audio format (PCM)
                throw new RuntimeException("Failed to write audio format");
            }
            if (fwrite($handle, pack('v', 1)) === false) { // Number of channels (mono)
                throw new RuntimeException("Failed to write number of channels");
            }
            if (fwrite($handle, pack('V', $this->sampleRate)) === false) { // Sample rate
                throw new RuntimeException("Failed to write sample rate");
            }
            if (fwrite($handle, pack('V', $this->sampleRate * 2)) === false) { // Byte rate
                throw new RuntimeException("Failed to write byte rate");
            }
            if (fwrite($handle, pack('v', 2)) === false) { // Block align
                throw new RuntimeException("Failed to write block align");
            }
            if (fwrite($handle, pack('v', 16)) === false) { // Bits per sample
                throw new RuntimeException("Failed to write bits per sample");
            }
            
            // Data chunk
            if (fwrite($handle, 'data') === false) {
                throw new RuntimeException("Failed to write data chunk marker");
            }
            if (fwrite($handle, pack('V', $dataSize)) === false) {
                throw new RuntimeException("Failed to write data size");
            }
            
            // Write audio data
            foreach ($audioData as $sample) {
                // Convert float to 16-bit signed integer
                $intValue = intval($sample * 32767);
                $intValue = max(-32768, min(32767, $intValue));
                
                // Convert signed to unsigned for pack('v')
                // pack('v') expects unsigned 16-bit, so we need to handle negative values
                if ($intValue < 0) {
                    $unsignedValue = 65536 + $intValue; // Convert to unsigned
                } else {
                    $unsignedValue = $intValue;
                }
                
                if (fwrite($handle, pack('v', $unsignedValue)) === false) {
                    throw new RuntimeException("Failed to write audio sample");
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Validate Morse code format
     * 
     * @param string $morse Morse code to validate
     * @return bool True if valid format
     */

    /**
     * Get dit (dot) duration in milliseconds
     * 
     * @return int Dit duration
     */
    public function getDitDuration(): int
    {
        return $this->ditDuration;
    }

    /**
     * Get dah (dash) duration in milliseconds
     * 
     * @return int Dah duration
     */
    public function getDahDuration(): int
    {
        return $this->dahDuration;
    }

    /**
     * Get tone frequency in Hz
     * 
     * @return int Frequency
     */
    public function getFrequency(): int
    {
        return $this->frequency;
    }

    /**
     * Get sample rate in Hz
     * 
     * @return int Sample rate
     */
    public function getSampleRate(): int
    {
        return $this->sampleRate;
    }

    /**
     * Get volume level
     * 
     * @return float Volume (0.0-1.0)
     */
    public function getVolume(): float
    {
        return $this->volume;
    }

    /**
     * Estimate output file size for given Morse code
     * 
     * @param string $morseCode Morse code to estimate
     * @return int Estimated file size in bytes
     */
    public function estimateFileSize(string $morseCode): int
    {
        // Calculate total duration in milliseconds
        $totalDuration = 0;
        $morseCode = trim($morseCode);
        
        for ($i = 0; $i < strlen($morseCode); $i++) {
            $char = $morseCode[$i];
            
            switch ($char) {
                case '.':
                    $totalDuration += $this->ditDuration;
                    break;
                case '-':
                    $totalDuration += $this->dahDuration;
                    break;
                case ' ':
                    if ($i + 1 < strlen($morseCode) && $morseCode[$i + 1] === '/') {
                        $totalDuration += $this->wordSpacing;
                        $i += 2;
                        continue 2;
                    } elseif ($i > 0 && $morseCode[$i - 1] !== ' ') {
                        $totalDuration += $this->characterSpacing;
                    }
                    break;
                case '/':
                    $totalDuration += $this->wordSpacing;
                    break;
            }
            
            if (($char === '.' || $char === '-') && 
                $i + 1 < strlen($morseCode) && 
                $morseCode[$i + 1] !== ' ' && 
                $morseCode[$i + 1] !== '/') {
                $totalDuration += $this->elementSpacing;
            }
        }
        
        // Convert to samples: duration in seconds * sample rate * 2 bytes per sample
        $samples = intval(($totalDuration / 1000) * $this->sampleRate);
        $dataSize = $samples * 2; // 16-bit audio = 2 bytes per sample
        
        // WAV header is 44 bytes + data size
        return 44 + $dataSize;
    }
}