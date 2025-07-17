<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;

// ANSI escape codes
const RESET = "\033[0m";
const CLEAR_LINE = "\033[2K\r";
const HIDE_CURSOR = "\033[?25l";
const SHOW_CURSOR = "\033[?25h";

// Light states
const LIGHT_ON = "\033[43;30m"; // Yellow background, black text
const LIGHT_OFF = "\033[40;37m"; // Black background, white text

function displayLight(bool $on): void
{
    echo CLEAR_LINE;
    if ($on) {
        echo LIGHT_ON;
        echo "    ████████████████████████████    ";
        echo RESET;
        echo " [ON]";
    } else {
        echo LIGHT_OFF;
        echo "    ░░░░░░░░░░░░░░░░░░░░░░░░░░    ";
        echo RESET;
        echo " [OFF]";
    }
    flush();
}

// Get input
$text = $argv[1] ?? 'SOS';
$wpm = isset($argv[2]) ? intval($argv[2]) : 15;

// Translate to Morse
$translator = new Translator();
$morseCode = $translator->textToMorse($text);

// Calculate timings (matching AudioGenerator logic)
$ditDuration = intval(1200 / $wpm); // milliseconds
$dahDuration = $ditDuration * 3;
$elementSpacing = $ditDuration;
$characterSpacing = $ditDuration * 3;
$wordSpacing = $ditDuration * 7;

echo "Terminal Morse Code Blinker\n";
echo "===========================\n";
echo "Text: $text\n";
echo "Morse: $morseCode\n";
echo "Speed: $wpm WPM\n";
echo "Press Ctrl+C to stop\n\n";
sleep(2);

// Hide cursor
echo HIDE_CURSOR;

try {
    // Parse and blink the Morse code
    for ($i = 0; $i < strlen($morseCode); $i++) {
        $char = $morseCode[$i];
        
        switch ($char) {
            case '.':
                displayLight(true);
                usleep($ditDuration * 1000);
                displayLight(false);
                break;
                
            case '-':
                displayLight(true);
                usleep($dahDuration * 1000);
                displayLight(false);
                break;
                
            case ' ':
                // Check if this is a word separator
                if ($i + 1 < strlen($morseCode) && $morseCode[$i + 1] === '/') {
                    usleep($wordSpacing * 1000);
                    $i += 2; // Skip the / and next space
                    continue;
                } else {
                    // Character spacing
                    usleep($characterSpacing * 1000);
                }
                break;
                
            case '/':
                // Word separator
                usleep($wordSpacing * 1000);
                break;
        }
        
        // Element spacing (unless next char is space or end)
        if (($char === '.' || $char === '-') && 
            $i + 1 < strlen($morseCode) && 
            $morseCode[$i + 1] !== ' ' && 
            $morseCode[$i + 1] !== '/') {
            usleep($elementSpacing * 1000);
        }
    }
    
    echo "\n\n";
} finally {
    // Restore cursor
    echo SHOW_CURSOR;
    echo "Done!\n";
}