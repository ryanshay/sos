<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;
use SOS\Translator\AudioGenerator;

echo "Text to Morse Code Audio Example\n";
echo "================================\n\n";

// Initialize the translator
$translator = new Translator();

// The English sentence we want to convert
$englishText = "The quick brown fox jumps over the lazy dog";

echo "1. Original English text:\n";
echo "   \"$englishText\"\n\n";

// Translate to Morse code
$morseCode = $translator->textToMorse($englishText);

echo "2. Morse code translation:\n";
echo "   $morseCode\n\n";

// Generate audio file with default settings
$outputFile = 'morse_message.wav';
$translator->generateAudioFile($englishText, $outputFile);

echo "3. Audio file generated:\n";
echo "   File: $outputFile\n";
echo "   Settings: Default (20 WPM, 600 Hz)\n\n";

// Generate another version with custom settings for easier learning
$audioGenerator = new AudioGenerator();
$audioGenerator->setSpeed(12); // Slower speed for beginners
$audioGenerator->setFrequency(700); // Slightly higher pitch
$audioGenerator->setVolume(0.8); // 80% volume

$learningFile = 'morse_message_slow.wav';
$translator->generateAudioFile($englishText, $learningFile, $audioGenerator);

echo "4. Learning version generated:\n";
echo "   File: $learningFile\n";
echo "   Settings: 12 WPM, 700 Hz (slower for learning)\n\n";

// Show file sizes
$size1 = filesize($outputFile);
$size2 = filesize($learningFile);

echo "5. File information:\n";
echo "   $outputFile: " . number_format($size1) . " bytes\n";
echo "   $learningFile: " . number_format($size2) . " bytes\n\n";

// Create a short message with custom settings
echo "6. Bonus - Emergency message:\n";
$emergencyText = "SOS HELP";
$emergencyMorse = $translator->textToMorse($emergencyText);
echo "   Text: \"$emergencyText\"\n";
echo "   Morse: $emergencyMorse\n";

$emergencyAudio = new AudioGenerator();
$emergencyAudio->setSpeed(15); // Clear but not too fast
$emergencyAudio->setFrequency(800); // Higher pitch for attention
$emergencyAudio->setVolume(1.0); // Maximum volume

$translator->generateAudioFile($emergencyText, 'emergency.wav', $emergencyAudio);
echo "   File: emergency.wav (15 WPM, 800 Hz, max volume)\n\n";

echo "✓ All audio files have been generated successfully!\n";
echo "  You can play these WAV files with any audio player.\n";