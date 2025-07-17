<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;
use SOS\Translator\AudioGenerator;
use SOS\Translator\VisualGenerator;
use SOS\Translator\BlinkPatternGenerator;

echo "Morse Code Translator - Feature Showcase\n";
echo "========================================\n\n";

$translator = new Translator();
$message = "HELLO WORLD";

echo "Message: $message\n";
$morseCode = $translator->textToMorse($message);
echo "Morse Code: $morseCode\n\n";

// 1. Audio Generation
echo "1. Generating Audio File...\n";
$audioGen = new AudioGenerator();
$audioGen->setSpeed(15);
$audioGen->setFrequency(600);
$audioGen->setVolume(0.7);
$translator->generateAudioFile($message, 'showcase_audio.wav', $audioGen);
echo "   ✓ Created: showcase_audio.wav (15 WPM, 600 Hz)\n\n";

// 2. Visual Representations
echo "2. Generating Visual Files...\n";
$visualGen = new VisualGenerator();
$visualGen->setDotWidth(25);
$visualGen->setColors([0, 0, 0], [255, 255, 255], [128, 128, 128]);
$translator->generateVisualFile($message, 'showcase_visual.png', $visualGen);
$translator->generateVisualFile($message, 'showcase_visual.svg');
echo "   ✓ Created: showcase_visual.png (dark theme)\n";
echo "   ✓ Created: showcase_visual.svg (scalable)\n\n";

// 3. Blink Pattern HTML
echo "3. Generating Blink Pattern...\n";
$blinkGen = new BlinkPatternGenerator();
$blinkGen->setSpeed(12);
$blinkGen->setColors('#00FF00', '#000000');
$blinkGen->setLightSize(250);
$translator->generateBlinkPattern($message, 'showcase_blink.html', $blinkGen);
echo "   ✓ Created: showcase_blink.html (interactive)\n\n";

// 4. Data Export
echo "4. Exporting Timing Data...\n";
$translator->generateBlinkPattern($message, 'showcase_timings.json');
$translator->generateBlinkPattern($message, 'showcase_timings.csv');
echo "   ✓ Created: showcase_timings.json\n";
echo "   ✓ Created: showcase_timings.csv\n\n";

// 5. Arduino Code
echo "5. Generating Arduino Sketch...\n";
$arduinoCode = $translator->exportBlinkTimings($message, 'arduino');
file_put_contents('showcase_arduino.ino', $arduinoCode);
echo "   ✓ Created: showcase_arduino.ino\n\n";

// 6. Terminal Demo (brief)
echo "6. Terminal Blink Demo...\n";
echo "   Watch the message blink:\n\n";
$translator->blinkInTerminal("SOS", ['speed' => 20]);

echo "\n\n";
echo "All files have been generated successfully!\n";
echo "You can find them in the current directory.\n\n";

// Summary
echo "Summary of Generated Files:\n";
echo "- Audio: showcase_audio.wav\n";
echo "- Images: showcase_visual.png, showcase_visual.svg\n";
echo "- Interactive: showcase_blink.html\n";
echo "- Data: showcase_timings.json, showcase_timings.csv\n";
echo "- Arduino: showcase_arduino.ino\n";