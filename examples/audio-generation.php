<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;
use SOS\Translator\AudioGenerator;

echo "Morse Code Audio Generation Examples\n";
echo "====================================\n\n";

$translator = new Translator();
$audioGenerator = new AudioGenerator();

// Example 1: Generate SOS audio file
echo "1. Generating SOS audio file:\n";
$text = "SOS";
$morseCode = $translator->textToMorse($text);
echo "   Text: '$text'\n";
echo "   Morse: '$morseCode'\n";

$audioGenerator->generateWavFile($morseCode, 'sos.wav');
echo "   Generated: sos.wav\n\n";

// Example 2: Generate audio with different speeds
echo "2. Generating audio at different speeds:\n";
$text = "HELLO";
$morseCode = $translator->textToMorse($text);

// Slow speed (10 WPM)
$audioGenerator->setSpeed(10);
$audioGenerator->generateWavFile($morseCode, 'hello_slow.wav');
echo "   Generated: hello_slow.wav (10 WPM)\n";

// Normal speed (20 WPM)
$audioGenerator->setSpeed(20);
$audioGenerator->generateWavFile($morseCode, 'hello_normal.wav');
echo "   Generated: hello_normal.wav (20 WPM)\n";

// Fast speed (30 WPM)
$audioGenerator->setSpeed(30);
$audioGenerator->generateWavFile($morseCode, 'hello_fast.wav');
echo "   Generated: hello_fast.wav (30 WPM)\n\n";

// Example 3: Generate audio with different frequencies
echo "3. Generating audio with different frequencies:\n";
$text = "CQ";
$morseCode = $translator->textToMorse($text);

// Low frequency (400 Hz)
$audioGenerator->setSpeed(20); // Reset to normal speed
$audioGenerator->setFrequency(400);
$audioGenerator->generateWavFile($morseCode, 'cq_low.wav');
echo "   Generated: cq_low.wav (400 Hz)\n";

// Standard frequency (600 Hz)
$audioGenerator->setFrequency(600);
$audioGenerator->generateWavFile($morseCode, 'cq_standard.wav');
echo "   Generated: cq_standard.wav (600 Hz)\n";

// High frequency (800 Hz)
$audioGenerator->setFrequency(800);
$audioGenerator->generateWavFile($morseCode, 'cq_high.wav');
echo "   Generated: cq_high.wav (800 Hz)\n\n";

// Example 4: Generate a full message
echo "4. Generating a full message:\n";
$text = "HELLO WORLD";
$morseCode = $translator->textToMorse($text);
echo "   Text: '$text'\n";
echo "   Morse: '$morseCode'\n";

$audioGenerator->setFrequency(600); // Reset to standard
$audioGenerator->setSpeed(15); // Comfortable speed
$audioGenerator->setVolume(0.7); // 70% volume
$audioGenerator->generateWavFile($morseCode, 'hello_world.wav');
echo "   Generated: hello_world.wav (15 WPM, 600 Hz, 70% volume)\n\n";

// Example 5: Generate audio for special characters
echo "5. Generating audio with punctuation:\n";
$text = "HELLO, WORLD!";
$morseCode = $translator->textToMorse($text);
echo "   Text: '$text'\n";
echo "   Morse: '$morseCode'\n";

$audioGenerator->generateWavFile($morseCode, 'hello_punctuation.wav');
echo "   Generated: hello_punctuation.wav\n\n";

// Example 6: Custom settings showcase
echo "6. Custom audio settings:\n";
$customAudio = new AudioGenerator();

// Configure for learning (very slow, clear)
$customAudio->setSpeed(8); // Very slow for beginners
$customAudio->setFrequency(700); // Slightly higher pitch
$customAudio->setVolume(0.9); // Louder
$customAudio->setSampleRate(22050); // Lower sample rate (smaller file)

$text = "LEARN";
$morseCode = $translator->textToMorse($text);
$customAudio->generateWavFile($morseCode, 'learn_morse.wav');
echo "   Generated: learn_morse.wav (8 WPM, 700 Hz, optimized for learning)\n\n";

echo "All audio files have been generated in the current directory.\n";
echo "You can play them with any audio player that supports WAV format.\n";