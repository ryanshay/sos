<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;

// ANSI escape codes
const RESET = "\033[0m";
const CLEAR_SCREEN = "\033[2J\033[H";
const HIDE_CURSOR = "\033[?25l";
const SHOW_CURSOR = "\033[?25h";
const BOLD = "\033[1m";

// Full screen light states
const SCREEN_ON = "\033[103m\033[30m"; // Bright yellow background, black text
const SCREEN_OFF = "\033[40m\033[37m"; // Black background, white text

function getTerminalSize(): array
{
    $size = shell_exec('stty size');
    if ($size) {
        $parts = explode(' ', trim($size));
        return [
            'rows' => intval($parts[0]),
            'cols' => intval($parts[1])
        ];
    }
    return ['rows' => 24, 'cols' => 80]; // Default
}

function displayFullscreenLight(bool $on, string $text, string $morse, int $rows, int $cols): void
{
    echo CLEAR_SCREEN;
    
    $color = $on ? SCREEN_ON : SCREEN_OFF;
    echo $color;
    
    // Fill screen
    $emptyLine = str_repeat(' ', $cols);
    
    // Calculate center positions
    $centerRow = intval($rows / 2);
    $textRow = $centerRow - 2;
    $morseRow = $centerRow;
    $statusRow = $centerRow + 2;
    
    for ($i = 0; $i < $rows; $i++) {
        if ($i === $textRow) {
            // Display original text centered
            $textDisplay = "[ $text ]";
            $padding = intval(($cols - strlen($textDisplay)) / 2);
            echo str_repeat(' ', $padding) . BOLD . $textDisplay . RESET . $color;
            echo str_repeat(' ', $cols - $padding - strlen($textDisplay));
        } elseif ($i === $morseRow) {
            // Display morse code centered
            $padding = intval(($cols - strlen($morse)) / 2);
            echo str_repeat(' ', $padding) . $morse;
            echo str_repeat(' ', $cols - $padding - strlen($morse));
        } elseif ($i === $statusRow) {
            // Display status centered
            $status = $on ? "••• SIGNAL ON •••" : "--- SIGNAL OFF ---";
            $padding = intval(($cols - strlen($status)) / 2);
            echo str_repeat(' ', $padding) . BOLD . $status . RESET . $color;
            echo str_repeat(' ', $cols - $padding - strlen($status));
        } else {
            echo $emptyLine;
        }
        
        if ($i < $rows - 1) {
            echo "\n";
        }
    }
    
    echo RESET;
    flush();
}

// Get input
$text = $argv[1] ?? 'SOS';
$wpm = isset($argv[2]) ? intval($argv[2]) : 15;

// Get terminal size
$termSize = getTerminalSize();
$rows = $termSize['rows'];
$cols = $termSize['cols'];

// Translate to Morse
$translator = new Translator();
$morseCode = $translator->textToMorse($text);

// Calculate timings
$ditDuration = intval(1200 / $wpm);
$dahDuration = $ditDuration * 3;
$elementSpacing = $ditDuration;
$characterSpacing = $ditDuration * 3;
$wordSpacing = $ditDuration * 7;

// Initial display
echo CLEAR_SCREEN;
echo "Fullscreen Morse Code Blinker\n";
echo "=============================\n";
echo "Text: $text\n";
echo "Morse: $morseCode\n";
echo "Speed: $wpm WPM\n";
echo "Terminal: {$rows}x{$cols}\n";
echo "\nPress Ctrl+C to stop\n";
echo "Starting in 3 seconds...";
sleep(3);

// Hide cursor and clear screen
echo HIDE_CURSOR;
echo CLEAR_SCREEN;

// Show initial off state
displayFullscreenLight(false, $text, $morseCode, $rows, $cols);
sleep(1);

try {
    // Blink the pattern twice
    for ($repeat = 0; $repeat < 2; $repeat++) {
        if ($repeat > 0) {
            usleep($wordSpacing * 2000); // Pause between repeats
        }
        
        for ($i = 0; $i < strlen($morseCode); $i++) {
            $char = $morseCode[$i];
            
            switch ($char) {
                case '.':
                    displayFullscreenLight(true, $text, $morseCode, $rows, $cols);
                    usleep($ditDuration * 1000);
                    displayFullscreenLight(false, $text, $morseCode, $rows, $cols);
                    break;
                    
                case '-':
                    displayFullscreenLight(true, $text, $morseCode, $rows, $cols);
                    usleep($dahDuration * 1000);
                    displayFullscreenLight(false, $text, $morseCode, $rows, $cols);
                    break;
                    
                case ' ':
                    if ($i + 1 < strlen($morseCode) && $morseCode[$i + 1] === '/') {
                        usleep($wordSpacing * 1000);
                        $i += 2;
                        continue;
                    } else {
                        usleep($characterSpacing * 1000);
                    }
                    break;
                    
                case '/':
                    usleep($wordSpacing * 1000);
                    break;
            }
            
            if (($char === '.' || $char === '-') && 
                $i + 1 < strlen($morseCode) && 
                $morseCode[$i + 1] !== ' ' && 
                $morseCode[$i + 1] !== '/') {
                usleep($elementSpacing * 1000);
            }
        }
    }
    
} finally {
    // Restore terminal
    echo CLEAR_SCREEN;
    echo SHOW_CURSOR;
    echo "Done!\n";
}