<?php

declare(strict_types=1);

namespace SOS\Translator;

use InvalidArgumentException;
use SOS\Translator\Contracts\DisplayGeneratorInterface;
use SOS\Translator\Exceptions\EmptyInputException;
use SOS\Translator\Exceptions\InvalidMorseCodeException;
use SOS\Translator\Traits\MorseValidation;

/**
 * Terminal Morse Code Blinker
 * 
 * Displays Morse code patterns in the terminal using ANSI escape codes.
 * Supports inline and fullscreen display modes with customizable colors.
 * 
 * @package SOS\Translator
 */
class TerminalBlinker implements DisplayGeneratorInterface
{
    use MorseValidation;
    // ANSI escape codes
    private const RESET = "\033[0m";
    private const CLEAR_LINE = "\033[2K\r";
    private const CLEAR_SCREEN = "\033[2J\033[H";
    private const HIDE_CURSOR = "\033[?25l";
    private const SHOW_CURSOR = "\033[?25h";
    private const BOLD = "\033[1m";
    
    // Default colors
    private const DEFAULT_ON_COLOR = "\033[43;30m"; // Yellow background, black text
    private const DEFAULT_OFF_COLOR = "\033[40;37m"; // Black background, white text
    
    private int $ditDuration = 60; // milliseconds
    private int $dahDuration = 180; // milliseconds (3x dit)
    private int $elementSpacing = 60; // space between dots/dashes (1x dit)
    private int $characterSpacing = 180; // space between characters (3x dit)
    private int $wordSpacing = 420; // space between words (7x dit)
    
    private string $onColor = self::DEFAULT_ON_COLOR;
    private string $offColor = self::DEFAULT_OFF_COLOR;
    private string $onSymbol = '████';
    private string $offSymbol = '░░░░';
    private bool $showLabels = true;
    
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
     * Set the ANSI color codes for on/off states
     * 
     * @param string $onColor ANSI escape code for on state
     * @param string $offColor ANSI escape code for off state
     */
    public function setColors(string $onColor, string $offColor): void
    {
        $this->onColor = $onColor;
        $this->offColor = $offColor;
    }
    
    /**
     * Set the display symbols for on/off states
     * 
     * @param string $onSymbol Symbol(s) to display when on
     * @param string $offSymbol Symbol(s) to display when off
     */
    public function setSymbols(string $onSymbol, string $offSymbol): void
    {
        $this->onSymbol = $onSymbol;
        $this->offSymbol = $offSymbol;
    }
    
    /**
     * Set whether to show labels and Morse code
     * 
     * @param bool $show True to show labels
     */
    public function setShowLabels(bool $show): void
    {
        $this->showLabels = $show;
    }
    
    /**
     * Display Morse code pattern in terminal
     * 
     * @param string $morseCode Morse code pattern
     * @param string|null $label Optional label to display
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     */
    public function display(string $morseCode, ?string $label = null): void
    {
        $this->blinkInline($morseCode, $label);
    }
    
    /**
     * Blink Morse code inline in the terminal
     * 
     * @param string $morseCode Morse code to blink
     * @param string|null $label Optional label to display
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     */
    public function blinkInline(string $morseCode, ?string $label = null): void
    {
        if (empty($morseCode)) {
            throw new EmptyInputException("Morse code cannot be empty");
        }
        
        if (!$this->isValidMorseCode($morseCode)) {
            throw new InvalidMorseCodeException($morseCode);
        }
        
        echo self::HIDE_CURSOR;
        
        try {
            // Show initial state
            $this->displayInlineLight(false, $morseCode, $label);
            sleep(1);
            
            // Blink the pattern
            $this->blinkPattern($morseCode, function($on) use ($morseCode, $label) {
                $this->displayInlineLight($on, $morseCode, $label);
            });
            
            echo "\n\n";
        } finally {
            echo self::SHOW_CURSOR;
        }
    }
    
    /**
     * Blink Morse code in fullscreen mode
     * 
     * @param string $morseCode Morse code to blink
     * @param string|null $text Original text to display
     * @param int $repeats Number of times to repeat the pattern
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     */
    public function blinkFullscreen(string $morseCode, ?string $text = null, int $repeats = 2): void
    {
        if (empty($morseCode)) {
            throw new EmptyInputException("Morse code cannot be empty");
        }
        
        if (!$this->isValidMorseCode($morseCode)) {
            throw new InvalidMorseCodeException($morseCode);
        }
        
        $termSize = $this->getTerminalSize();
        
        echo self::HIDE_CURSOR;
        echo self::CLEAR_SCREEN;
        
        try {
            // Show initial off state
            $this->displayFullscreenLight(false, $text, $morseCode, $termSize['rows'], $termSize['cols']);
            sleep(1);
            
            // Repeat pattern
            for ($i = 0; $i < $repeats; $i++) {
                if ($i > 0) {
                    usleep($this->wordSpacing * 2000); // Pause between repeats
                }
                
                $this->blinkPattern($morseCode, function($on) use ($text, $morseCode, $termSize) {
                    $this->displayFullscreenLight($on, $text, $morseCode, $termSize['rows'], $termSize['cols']);
                });
            }
        } finally {
            echo self::CLEAR_SCREEN;
            echo self::SHOW_CURSOR;
        }
    }
    
    /**
     * Get timing array for Morse code pattern
     * 
     * @param string $morseCode Morse code to analyze
     * @return array Array of timing information
     * @throws EmptyInputException If Morse code is empty
     * @throws InvalidMorseCodeException If Morse code format is invalid
     */
    public function getTimingArray(string $morseCode): array
    {
        if (empty($morseCode)) {
            throw new EmptyInputException("Morse code cannot be empty");
        }
        
        if (!$this->isValidMorseCode($morseCode)) {
            throw new InvalidMorseCodeException($morseCode);
        }
        
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
     * Execute the blink pattern
     * 
     * @param string $morseCode Morse code pattern
     * @param callable $displayCallback Callback to update display
     */
    private function blinkPattern(string $morseCode, callable $displayCallback): void
    {
        for ($i = 0; $i < strlen($morseCode); $i++) {
            $char = $morseCode[$i];
            
            switch ($char) {
                case '.':
                    $displayCallback(true);
                    usleep($this->ditDuration * 1000);
                    $displayCallback(false);
                    break;
                    
                case '-':
                    $displayCallback(true);
                    usleep($this->dahDuration * 1000);
                    $displayCallback(false);
                    break;
                    
                case ' ':
                    if ($i + 1 < strlen($morseCode) && $morseCode[$i + 1] === '/') {
                        usleep($this->wordSpacing * 1000);
                        $i += 2;
                        continue 2;
                    } else {
                        usleep($this->characterSpacing * 1000);
                    }
                    break;
                    
                case '/':
                    usleep($this->wordSpacing * 1000);
                    break;
            }
            
            // Element spacing
            if (($char === '.' || $char === '-') && 
                $i + 1 < strlen($morseCode) && 
                $morseCode[$i + 1] !== ' ' && 
                $morseCode[$i + 1] !== '/') {
                usleep($this->elementSpacing * 1000);
            }
        }
    }
    
    /**
     * Display inline light state
     * 
     * @param bool $on Light state
     * @param string $morseCode Current Morse code
     * @param string|null $label Optional label
     */
    private function displayInlineLight(bool $on, string $morseCode, ?string $label): void
    {
        echo self::CLEAR_LINE;
        
        if ($on) {
            echo $this->onColor;
            echo str_repeat($this->onSymbol, 8);
            echo self::RESET;
            echo " ON  ";
        } else {
            echo $this->offColor;
            echo str_repeat($this->offSymbol, 8);
            echo self::RESET;
            echo " OFF ";
        }
        
        if ($this->showLabels) {
            if ($label) {
                echo " | $label: ";
            } else {
                echo " | ";
            }
            echo $morseCode;
        }
        
        flush();
    }
    
    /**
     * Display fullscreen light state
     * 
     * @param bool $on Light state
     * @param string|null $text Original text
     * @param string $morse Morse code
     * @param int $rows Terminal rows
     * @param int $cols Terminal columns
     */
    private function displayFullscreenLight(bool $on, ?string $text, string $morse, int $rows, int $cols): void
    {
        echo self::CLEAR_SCREEN;
        
        $color = $on ? $this->onColor : $this->offColor;
        echo $color;
        
        $emptyLine = str_repeat(' ', $cols);
        
        // Calculate center positions
        $centerRow = intval($rows / 2);
        $textRow = $centerRow - 2;
        $morseRow = $centerRow;
        $statusRow = $centerRow + 2;
        
        for ($i = 0; $i < $rows; $i++) {
            if ($text && $i === $textRow) {
                $textDisplay = "[ $text ]";
                $padding = intval(($cols - strlen($textDisplay)) / 2);
                echo str_repeat(' ', $padding) . self::BOLD . $textDisplay . self::RESET . $color;
                echo str_repeat(' ', $cols - $padding - strlen($textDisplay));
            } elseif ($i === $morseRow) {
                $padding = intval(($cols - strlen($morse)) / 2);
                echo str_repeat(' ', $padding) . $morse;
                echo str_repeat(' ', $cols - $padding - strlen($morse));
            } elseif ($this->showLabels && $i === $statusRow) {
                $status = $on ? "••• SIGNAL ON •••" : "--- SIGNAL OFF ---";
                $padding = intval(($cols - strlen($status)) / 2);
                echo str_repeat(' ', $padding) . self::BOLD . $status . self::RESET . $color;
                echo str_repeat(' ', $cols - $padding - strlen($status));
            } else {
                echo $emptyLine;
            }
            
            if ($i < $rows - 1) {
                echo "\n";
            }
        }
        
        echo self::RESET;
        flush();
    }
    
    /**
     * Get terminal dimensions
     * 
     * @return array Array with 'rows' and 'cols' keys
     */
    private function getTerminalSize(): array
    {
        // Use safer approach to get terminal size
        if (function_exists('exec')) {
            $output = [];
            $return = 0;
            // Use proc_open for safer execution
            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ];
            
            $process = proc_open('stty size', $descriptorspec, $pipes);
            if (is_resource($process)) {
                fclose($pipes[0]);
                $output = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $return = proc_close($process);
                
                if ($return === 0 && !empty($output)) {
                    $output = [trim($output)];
                } else {
                    $output = [];
                    $return = 1;
                }
            } else {
                $output = [];
                $return = 1;
            }
            if ($return === 0 && !empty($output[0])) {
                $parts = explode(' ', trim($output[0]));
                if (count($parts) === 2) {
                    $rows = filter_var($parts[0], FILTER_VALIDATE_INT);
                    $cols = filter_var($parts[1], FILTER_VALIDATE_INT);
                    if ($rows !== false && $cols !== false && $rows > 0 && $cols > 0) {
                        return [
                            'rows' => $rows,
                            'cols' => $cols
                        ];
                    }
                }
            }
        }
        
        // Try environment variables as fallback
        $rows = getenv('LINES');
        $cols = getenv('COLUMNS');
        if ($rows !== false && $cols !== false) {
            $rows = filter_var($rows, FILTER_VALIDATE_INT);
            $cols = filter_var($cols, FILTER_VALIDATE_INT);
            if ($rows !== false && $cols !== false && $rows > 0 && $cols > 0) {
                return [
                    'rows' => $rows,
                    'cols' => $cols
                ];
            }
        }
        
        // Default terminal size
        return ['rows' => 24, 'cols' => 80];
    }
    
}