<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;
use SOS\Translator\TerminalBlinker;
use SOS\Translator\BlinkPatternGenerator;

echo "Emergency Morse Code Signaling\n";
echo "==============================\n\n";

$translator = new Translator();

// Get emergency message from command line or use default
$message = $argv[1] ?? 'SOS';
$message = strtoupper($message);

echo "Emergency Message: $message\n";
$morseCode = $translator->textToMorse($message);
echo "Morse Code: $morseCode\n\n";

echo "Select signaling method:\n";
echo "1. Terminal flash (fullscreen)\n";
echo "2. Terminal flash (inline)\n";
echo "3. Generate HTML file\n";
echo "4. Both terminal and HTML\n";
echo "\nChoice [1-4]: ";

$choice = trim(fgets(STDIN)) ?: '1';

switch ($choice) {
    case '1':
        echo "\nStarting fullscreen emergency signal...\n";
        echo "Press Ctrl+C to stop\n";
        sleep(2);
        
        $translator->blinkInTerminal($message, [
            'fullscreen' => true,
            'speed' => 15,
            'repeats' => 5
        ]);
        break;
        
    case '2':
        echo "\nStarting inline emergency signal...\n";
        echo "Press Ctrl+C to stop\n\n";
        sleep(1);
        
        $blinker = new TerminalBlinker();
        $blinker->setSpeed(15);
        $blinker->setColors("\033[41;37m", "\033[40;37m"); // Red background for emergency
        $blinker->blinkInline($morseCode, "EMERGENCY: $message");
        
        // Repeat 5 times
        for ($i = 1; $i < 5; $i++) {
            sleep(2);
            $blinker->blinkInline($morseCode, "EMERGENCY: $message");
        }
        break;
        
    case '3':
        echo "\nGenerating HTML emergency signal...\n";
        
        $generator = new BlinkPatternGenerator();
        $generator->setColors('#FF0000', '#000000'); // Red on black
        $generator->setLightSize(400); // Large light
        $generator->setIncludeControls(false); // Auto-play
        $generator->setSpeed(15);
        
        $filename = 'emergency_signal.html';
        $translator->generateBlinkPattern($message, $filename, $generator);
        
        echo "Generated: $filename\n";
        echo "Opening in browser...\n";
        
        $openCommand = PHP_OS_FAMILY === 'Darwin' ? 'open' : 
                      (PHP_OS_FAMILY === 'Windows' ? 'start' : 'xdg-open');
        @exec("$openCommand $filename");
        break;
        
    case '4':
        echo "\nGenerating HTML file...\n";
        
        $generator = new BlinkPatternGenerator();
        $generator->setColors('#FF0000', '#000000');
        $generator->setLightSize(400);
        $generator->setIncludeControls(false);
        $generator->setSpeed(15);
        
        $filename = 'emergency_signal.html';
        $translator->generateBlinkPattern($message, $filename, $generator);
        echo "Generated: $filename\n";
        
        echo "\nStarting terminal signal in 2 seconds...\n";
        echo "Press Ctrl+C to stop\n";
        sleep(2);
        
        $translator->blinkInTerminal($message, [
            'fullscreen' => true,
            'speed' => 15,
            'repeats' => 5
        ]);
        break;
        
    default:
        echo "Invalid choice\n";
        exit(1);
}

echo "\nEmergency signal complete.\n";