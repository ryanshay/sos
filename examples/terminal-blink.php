<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;

echo "Terminal Morse Code Blinker\n";
echo "===========================\n\n";

$translator = new Translator();

// Example 1: Simple inline blinking
echo "1. Inline blinking (SOS):\n";
echo "   Watch the light blink below...\n\n";
sleep(1);

$translator->blinkInTerminal('SOS', ['speed' => 15]);

echo "\n";

// Example 2: Different speed
echo "2. Faster blinking (HELLO at 25 WPM):\n";
echo "   Watch the faster pattern...\n\n";
sleep(1);

$translator->blinkInTerminal('HELLO', ['speed' => 25]);

echo "\n";

// Example 3: Fullscreen mode
echo "3. Fullscreen emergency signal:\n";
echo "   The entire terminal will flash!\n";
echo "   Press Ctrl+C to stop early.\n\n";
echo "   Starting in 3 seconds...";
sleep(3);

$translator->blinkInTerminal('SOS HELP', [
    'fullscreen' => true,
    'speed' => 20,
    'repeats' => 2
]);

echo "\nDemo complete!\n";