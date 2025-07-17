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
 * Blink Pattern Generator
 * 
 * Generates timing patterns and export formats for Morse code blinking.
 * Supports JSON, CSV, HTML, and Arduino code generation.
 * 
 * @package SOS\Translator
 */
class BlinkPatternGenerator implements FileGeneratorInterface
{
    use MorseValidation;
    private int $ditDuration = 60; // milliseconds
    private int $dahDuration = 180; // milliseconds (3x dit)
    private int $elementSpacing = 60; // space between dots/dashes (1x dit)
    private int $characterSpacing = 180; // space between characters (3x dit)
    private int $wordSpacing = 420; // space between words (7x dit)
    
    private string $lightColor = '#FFFF00'; // Yellow
    private string $backgroundColor = '#000000'; // Black
    private int $lightSize = 200; // pixels
    private bool $includeControls = true;
    
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
        
        $this->ditDuration = intval(1200 / $wpm);
        $this->dahDuration = $this->ditDuration * 3;
        $this->elementSpacing = $this->ditDuration;
        $this->characterSpacing = $this->ditDuration * 3;
        $this->wordSpacing = $this->ditDuration * 7;
    }
    
    /**
     * Set colors for HTML output
     * 
     * @param string $lightColor CSS color for light on state
     * @param string $backgroundColor CSS color for background
     */
    public function setColors(string $lightColor, string $backgroundColor): void
    {
        $this->lightColor = $lightColor;
        $this->backgroundColor = $backgroundColor;
    }
    
    /**
     * Set light size for HTML output
     * 
     * @param int $size Size in pixels (50-500)
     * @throws \InvalidArgumentException If size out of valid range
     */
    public function setLightSize(int $size): void
    {
        if ($size < 50 || $size > 500) {
            throw new InvalidArgumentException("Light size must be between 50 and 500 pixels");
        }
        $this->lightSize = $size;
    }
    
    /**
     * Set whether to include controls in HTML output
     * 
     * @param bool $include True to include play/stop controls
     */
    public function setIncludeControls(bool $include): void
    {
        $this->includeControls = $include;
    }
    
    /**
     * Generate pattern file from Morse code
     * 
     * @param string $morseCode Morse code pattern
     * @param string $filename Output filename
     * @param mixed $context Optional text label
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     * @throws InvalidArgumentException If file format not supported
     * @throws RuntimeException If file generation fails
     */
    public function generate(string $morseCode, string $filename, $context = null): void
    {
        $text = is_string($context) ? $context : null;
        
        // Determine format based on file extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'json':
                $this->exportJSON($morseCode, $filename);
                break;
            case 'csv':
                $this->exportCSV($morseCode, $filename);
                break;
            case 'html':
            case 'htm':
                $this->generateHTML($morseCode, $filename, $text);
                break;
            default:
                throw new InvalidArgumentException("Unsupported file format: $extension");
        }
    }
    
    /**
     * Generate interactive HTML file with blink pattern
     * 
     * @param string $morseCode Morse code pattern
     * @param string $filename Output HTML filename
     * @param string|null $text Optional text label
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     * @throws \RuntimeException If file cannot be written
     */
    public function generateHTML(string $morseCode, string $filename, ?string $text = null): void
    {
        if (empty($morseCode)) {
            throw new EmptyInputException("Morse code cannot be empty");
        }
        
        if (!$this->isValidMorseCode($morseCode)) {
            throw new InvalidMorseCodeException($morseCode);
        }
        
        $timingArray = $this->generateTimingArray($morseCode);
        $timingJSON = json_encode($timingArray);
        
        $html = $this->generateHTMLTemplate($morseCode, $text, $timingJSON);
        
        if (file_put_contents($filename, $html) === false) {
            throw new RuntimeException("Could not write file: $filename");
        }
    }
    
    /**
     * Export timing array for Morse code pattern
     * 
     * @param string $morseCode Morse code to analyze
     * @return array Array of timing data with state and duration
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     */
    public function exportTimingArray(string $morseCode): array
    {
        if (empty($morseCode)) {
            throw new EmptyInputException("Morse code cannot be empty");
        }
        
        if (!$this->isValidMorseCode($morseCode)) {
            throw new InvalidMorseCodeException($morseCode);
        }
        
        return $this->generateTimingArray($morseCode);
    }
    
    /**
     * Export timing data as JSON file
     * 
     * @param string $morseCode Morse code pattern
     * @param string $filename Output JSON filename
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     * @throws \RuntimeException If file cannot be written
     */
    public function exportJSON(string $morseCode, string $filename): void
    {
        $data = [
            'morse' => $morseCode,
            'timings' => $this->exportTimingArray($morseCode),
            'settings' => [
                'dit_duration' => $this->ditDuration,
                'dah_duration' => $this->dahDuration,
                'element_spacing' => $this->elementSpacing,
                'character_spacing' => $this->characterSpacing,
                'word_spacing' => $this->wordSpacing
            ]
        ];
        
        if (file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT)) === false) {
            throw new RuntimeException("Could not write file: $filename");
        }
    }
    
    /**
     * Export timing data as CSV file
     * 
     * @param string $morseCode Morse code pattern
     * @param string $filename Output CSV filename
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     * @throws \RuntimeException If file cannot be written
     */
    public function exportCSV(string $morseCode, string $filename): void
    {
        $timings = $this->exportTimingArray($morseCode);
        
        $handle = fopen($filename, 'w');
        if (!$handle) {
            $error = error_get_last();
            throw new RuntimeException("Could not open file for writing: $filename. " . ($error['message'] ?? 'Unknown error'));
        }
        
        try {
            // Write header
            if (fputcsv($handle, ['index', 'state', 'duration_ms']) === false) {
                throw new RuntimeException("Failed to write CSV header");
            }
            
            // Write data
            foreach ($timings as $index => $timing) {
                if (fputcsv($handle, [$index, $timing['state'], $timing['duration']]) === false) {
                    throw new RuntimeException("Failed to write CSV row at index $index");
                }
            }
        } finally {
            fclose($handle);
        }
    }
    
    /**
     * Generate Arduino sketch code for blink pattern
     * 
     * @param string $morseCode Morse code pattern
     * @return string Complete Arduino sketch code
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     */
    public function generateArduinoCode(string $morseCode): string
    {
        $timings = $this->exportTimingArray($morseCode);
        
        $code = "// Morse Code Blink Pattern\n";
        $code .= "// Generated by SOS Morse Code Translator\n\n";
        $code .= "const int LED_PIN = 13;\n\n";
        $code .= "void setup() {\n";
        $code .= "  pinMode(LED_PIN, OUTPUT);\n";
        $code .= "}\n\n";
        $code .= "void loop() {\n";
        
        foreach ($timings as $timing) {
            $state = $timing['state'] === 'on' ? 'HIGH' : 'LOW';
            $code .= "  digitalWrite(LED_PIN, $state);\n";
            $code .= "  delay({$timing['duration']});\n";
        }
        
        $code .= "  \n";
        $code .= "  // Pause before repeating\n";
        $code .= "  delay(2000);\n";
        $code .= "}\n";
        
        return $code;
    }
    
    /**
     * Generate timing array from Morse code
     * 
     * @param string $morseCode Morse code to process
     * @return array Array of timing data
     */
    private function generateTimingArray(string $morseCode): array
    {
        $timings = [];
        
        for ($i = 0; $i < strlen($morseCode); $i++) {
            $char = $morseCode[$i];
            
            switch ($char) {
                case '.':
                    $timings[] = ['state' => 'on', 'duration' => $this->ditDuration];
                    break;
                    
                case '-':
                    $timings[] = ['state' => 'on', 'duration' => $this->dahDuration];
                    break;
                    
                case ' ':
                    if ($i + 1 < strlen($morseCode) && $morseCode[$i + 1] === '/') {
                        $timings[] = ['state' => 'off', 'duration' => $this->wordSpacing];
                        $i += 2;
                        continue 2;
                    } else {
                        $timings[] = ['state' => 'off', 'duration' => $this->characterSpacing];
                    }
                    break;
                    
                case '/':
                    $timings[] = ['state' => 'off', 'duration' => $this->wordSpacing];
                    break;
            }
            
            // Add element spacing after dots and dashes
            if (($char === '.' || $char === '-') && 
                $i + 1 < strlen($morseCode) && 
                $morseCode[$i + 1] !== ' ' && 
                $morseCode[$i + 1] !== '/') {
                $timings[] = ['state' => 'off', 'duration' => $this->elementSpacing];
            }
        }
        
        return $timings;
    }
    
    /**
     * Generate HTML template with embedded JavaScript
     * 
     * @param string $morseCode Morse code pattern
     * @param string|null $text Optional text label
     * @param string $timingJSON JSON-encoded timing data
     * @return string Complete HTML document
     */
    private function generateHTMLTemplate(string $morseCode, ?string $text, string $timingJSON): string
    {
        // Sanitize all user input
        $title = htmlspecialchars($text ? "Morse Code: $text" : "Morse Code Pattern", ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $morseCodeSafe = htmlspecialchars($morseCode, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $controls = $this->includeControls ? $this->generateControlsHTML() : '';
        
        // Validate and sanitize colors
        $lightColorSafe = $this->sanitizeColor($this->lightColor);
        $backgroundColorSafe = $this->sanitizeColor($this->backgroundColor);
        $lightSizeSafe = intval($this->lightSize);
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: {$backgroundColorSafe};
            color: white;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        #light {
            width: {$lightSizeSafe}px;
            height: {$lightSizeSafe}px;
            border-radius: 50%;
            background-color: #333;
            transition: background-color 0.1s;
            margin: 20px;
            box-shadow: 0 0 50px rgba(0,0,0,0.5);
        }
        
        #light.on {
            background-color: {$lightColorSafe};
            box-shadow: 0 0 100px {$lightColorSafe};
        }
        
        .info {
            text-align: center;
            margin: 20px;
        }
        
        .morse-display {
            font-family: monospace;
            font-size: 24px;
            margin: 10px;
        }
        
        .controls {
            margin: 20px;
        }
        
        button {
            padding: 10px 20px;
            margin: 5px;
            font-size: 16px;
            cursor: pointer;
            background-color: #444;
            color: white;
            border: none;
            border-radius: 5px;
        }
        
        button:hover {
            background-color: #666;
        }
        
        button:disabled {
            background-color: #222;
            cursor: not-allowed;
        }
        
        .speed-control {
            margin: 10px;
        }
        
        #speedDisplay {
            display: inline-block;
            width: 50px;
            text-align: center;
        }
        
        @media (max-width: 600px) {
            #light {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="info">
        <h1>$title</h1>
        <div class="morse-display">$morseCodeSafe</div>
    </div>
    
    <div id="light"></div>
    
    $controls
    
    <script>
        const timings = $timingJSON;
        let currentIndex = 0;
        let isPlaying = false;
        let timeoutId = null;
        let speedMultiplier = 1.0;
        
        const light = document.getElementById('light');
        const playBtn = document.getElementById('playBtn');
        const stopBtn = document.getElementById('stopBtn');
        const speedRange = document.getElementById('speedRange');
        const speedDisplay = document.getElementById('speedDisplay');
        
        function updateLight(isOn) {
            if (isOn) {
                light.classList.add('on');
            } else {
                light.classList.remove('on');
            }
        }
        
        function playNext() {
            if (!isPlaying || currentIndex >= timings.length) {
                if (currentIndex >= timings.length) {
                    // Loop back to start
                    currentIndex = 0;
                    // Add a pause before repeating
                    timeoutId = setTimeout(playNext, 2000 / speedMultiplier);
                    return;
                }
                return;
            }
            
            const timing = timings[currentIndex];
            updateLight(timing.state === 'on');
            
            currentIndex++;
            timeoutId = setTimeout(playNext, timing.duration / speedMultiplier);
        }
        
        function play() {
            if (isPlaying) return;
            
            isPlaying = true;
            if (playBtn) playBtn.disabled = true;
            if (stopBtn) stopBtn.disabled = false;
            
            playNext();
        }
        
        function stop() {
            isPlaying = false;
            currentIndex = 0;
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }
            updateLight(false);
            
            if (playBtn) playBtn.disabled = false;
            if (stopBtn) stopBtn.disabled = true;
        }
        
        function updateSpeed() {
            speedMultiplier = parseFloat(speedRange.value);
            speedDisplay.textContent = speedMultiplier.toFixed(1) + 'x';
        }
        
        // Initialize controls if they exist
        if (playBtn) {
            playBtn.addEventListener('click', play);
            stopBtn.addEventListener('click', stop);
            speedRange.addEventListener('input', updateSpeed);
            
            // Auto-play on load
            setTimeout(play, 1000);
        } else {
            // No controls, auto-play continuously
            isPlaying = true;
            setTimeout(playNext, 1000);
        }
    </script>
</body>
</html>
HTML;
    }
    
    /**
     * Generate HTML for playback controls
     * 
     * @return string HTML markup for controls
     */
    private function generateControlsHTML(): string
    {
        return <<<HTML
    <div class="controls">
        <button id="playBtn">Play</button>
        <button id="stopBtn" disabled>Stop</button>
        <div class="speed-control">
            <label for="speedRange">Speed: </label>
            <input type="range" id="speedRange" min="0.5" max="3.0" step="0.1" value="1.0">
            <span id="speedDisplay">1.0x</span>
        </div>
    </div>
HTML;
    }
    
    /**
     * Validate Morse code format
     * 
     * @param string $morse Morse code to validate
     * @return bool True if valid format
     */
    /**
     * Sanitize color value for safe HTML usage
     * 
     * @param string $color Color value to sanitize
     * @return string Sanitized color value
     */
    private function sanitizeColor(string $color): string
    {
        // Allow hex colors and basic color names
        if (preg_match('/^#[0-9A-Fa-f]{3,6}$/', $color)) {
            return $color;
        }
        
        // Allow rgb/rgba
        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*[0-9.]+)?\s*\)$/', $color)) {
            return $color;
        }
        
        // Allow common color names
        $allowedColors = ['black', 'white', 'red', 'green', 'blue', 'yellow', 'cyan', 'magenta', 
                         'gray', 'grey', 'orange', 'purple', 'brown', 'pink', 'lime', 'navy'];
        if (in_array(strtolower($color), $allowedColors, true)) {
            return strtolower($color);
        }
        
        // Default to black if invalid
        return '#000000';
    }
    
}