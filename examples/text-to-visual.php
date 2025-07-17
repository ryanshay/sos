<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;
use SOS\Translator\VisualGenerator;

echo "Text to Visual Morse Code Example\n";
echo "=================================\n\n";

// Initialize translator
$translator = new Translator();

// Example 1: Simple text to visual
$text = "HELLO WORLD";
echo "1. Converting text to visual:\n";
echo "   Text: \"$text\"\n";

// Generate PNG
$translator->generateVisualFile($text, 'hello_visual.png');
echo "   Generated: hello_visual.png\n";

// Generate SVG
$translator->generateVisualFile($text, 'hello_visual.svg');
echo "   Generated: hello_visual.svg\n\n";

// Example 2: Custom visual settings
echo "2. Custom visual settings:\n";
$text = "SOS";

$customVisual = new VisualGenerator();
$customVisual->setDotWidth(40); // Large dots
$customVisual->setElementHeight(40); // Tall elements
$customVisual->setColors(
    [255, 0, 0],    // Red background
    [255, 255, 255], // White elements
    [200, 200, 200]  // Light gray text
);

$translator->generateVisualFile($text, 'sos_alert.png', $customVisual);
echo "   Generated: sos_alert.png (emergency style)\n\n";

// Example 3: Educational flashcards
echo "3. Educational flashcards:\n";
$alphabet = ['A', 'B', 'C', 'D', 'E'];

$flashcardVisual = new VisualGenerator();
$flashcardVisual->setDotWidth(30);
$flashcardVisual->setElementHeight(30);
$flashcardVisual->setSpacing(10, 30, 60);

foreach ($alphabet as $letter) {
    $filename = "flashcard_$letter.png";
    $translator->generateVisualFile($letter, $filename, $flashcardVisual);
    echo "   Generated: $filename\n";
}

echo "\nAll visual files created successfully!\n";