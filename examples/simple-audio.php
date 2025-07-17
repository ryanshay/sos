<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;

// Create translator instance
$translator = new Translator();

// English sentence
$sentence = "Hello World";

// Translate to Morse code
$morse = $translator->textToMorse($sentence);
echo "Text: $sentence\n";
echo "Morse: $morse\n";

// Generate audio file
$translator->generateAudioFile($sentence, 'hello_world.wav');
echo "Audio saved to: hello_world.wav\n";