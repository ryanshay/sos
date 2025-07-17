<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;
use SOS\Translator\AudioGenerator;

echo "Integrated Audio Generation Example\n";
echo "===================================\n\n";

$translator = new Translator();

// Example 1: Simple audio generation using the integrated method
echo "1. Simple audio generation:\n";
$translator->generateAudioFile('SOS', 'integrated_sos.wav');
echo "   Generated: integrated_sos.wav\n\n";

// Example 2: Custom audio settings
echo "2. Custom audio settings:\n";
$audioGen = new AudioGenerator();
$audioGen->setSpeed(12); // Slow for learning
$audioGen->setFrequency(700);
$audioGen->setVolume(0.8);

$translator->generateAudioFile('MORSE CODE', 'integrated_custom.wav', $audioGen);
echo "   Generated: integrated_custom.wav (12 WPM, 700 Hz)\n\n";

// Example 3: Generate multiple files with different settings
echo "3. Multiple files with different settings:\n";
$messages = [
    'HELLO' => ['speed' => 20, 'freq' => 600],
    'TESTING' => ['speed' => 25, 'freq' => 700],
    'GOODBYE' => ['speed' => 15, 'freq' => 500]
];

foreach ($messages as $text => $settings) {
    $audioGen = new AudioGenerator();
    $audioGen->setSpeed($settings['speed']);
    $audioGen->setFrequency($settings['freq']);
    
    $filename = strtolower($text) . '_audio.wav';
    $translator->generateAudioFile($text, $filename, $audioGen);
    echo "   Generated: $filename ({$settings['speed']} WPM, {$settings['freq']} Hz)\n";
}

echo "\nAll audio files have been generated successfully!\n";