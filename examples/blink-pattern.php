<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;
use SOS\Translator\BlinkPatternGenerator;

echo "Morse Code Blink Pattern Generator\n";
echo "==================================\n\n";

$translator = new Translator();

// Example 1: Generate basic HTML blink pattern
echo "1. Basic HTML pattern:\n";
$translator->generateBlinkPattern('SOS', 'sos_blink.html');
echo "   Generated: sos_blink.html\n\n";

// Example 2: Custom styled HTML pattern
echo "2. Custom styled pattern:\n";
$generator = new BlinkPatternGenerator();
$generator->setColors('#00FF00', '#000000'); // Green light on black
$generator->setLightSize(300); // Larger light
$generator->setSpeed(10); // Slow for learning

$translator->generateBlinkPattern('HELLO', 'hello_custom.html', $generator);
echo "   Generated: hello_custom.html (green light, 10 WPM)\n\n";

// Example 3: Emergency signal without controls
echo "3. Emergency signal (no controls, auto-play):\n";
$emergencyGen = new BlinkPatternGenerator();
$emergencyGen->setColors('#FF0000', '#FFFFFF'); // Red on white
$emergencyGen->setIncludeControls(false); // Auto-play only
$emergencyGen->setSpeed(20);

$translator->generateBlinkPattern('SOS EMERGENCY', 'emergency.html', $emergencyGen);
echo "   Generated: emergency.html\n\n";

// Example 4: Export timing data
echo "4. Export timing data:\n";

// JSON format
$translator->generateBlinkPattern('TEST', 'timings.json');
echo "   Generated: timings.json\n";

// CSV format
$translator->generateBlinkPattern('TEST', 'timings.csv');
echo "   Generated: timings.csv\n\n";

// Example 5: Get Arduino code
echo "5. Arduino code generation:\n";
$arduinoCode = $translator->exportBlinkTimings('SOS', 'arduino');
echo "   Arduino sketch:\n";
echo "   --------------\n";
echo substr($arduinoCode, 0, 200) . "...\n\n";

// Example 6: Get timing array for custom use
echo "6. Raw timing array:\n";
$timings = $translator->exportBlinkTimings('HI', 'array');
echo "   Timings for 'HI':\n";
foreach ($timings as $i => $timing) {
    echo "   [$i] {$timing['state']} for {$timing['duration']}ms\n";
}

echo "\n";

// Open the first HTML file in browser (platform-specific)
$openCommand = PHP_OS_FAMILY === 'Darwin' ? 'open' : (PHP_OS_FAMILY === 'Windows' ? 'start' : 'xdg-open');
echo "Opening sos_blink.html in your browser...\n";
@exec("$openCommand sos_blink.html");

echo "\nAll files generated successfully!\n";