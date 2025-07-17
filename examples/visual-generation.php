<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;
use SOS\Translator\VisualGenerator;

echo "Morse Code Visual Generation Examples\n";
echo "=====================================\n\n";

$translator = new Translator();
$visualGenerator = new VisualGenerator();

// Example 1: Generate basic PNG image
echo "1. Basic PNG image:\n";
$text = "SOS";
$morseCode = $translator->textToMorse($text);
echo "   Text: '$text'\n";
echo "   Morse: '$morseCode'\n";

$visualGenerator->generateImage($morseCode, 'sos_visual.png', $text);
echo "   Generated: sos_visual.png\n\n";

// Example 2: Generate SVG image
echo "2. SVG format:\n";
$text = "HELP";
$morseCode = $translator->textToMorse($text);
$visualGenerator->generateSVG($morseCode, 'help_visual.svg', $text);
echo "   Generated: help_visual.svg\n\n";

// Example 3: Custom styling
echo "3. Custom styling (larger dots/dashes):\n";
$text = "MORSE";
$morseCode = $translator->textToMorse($text);

$customVisual = new VisualGenerator();
$customVisual->setDotWidth(30); // Larger dots (dashes will be 3x = 90px)
$customVisual->setElementHeight(30); // Taller elements
$customVisual->setSpacing(15, 40, 80); // More spacing
$customVisual->generateImage($morseCode, 'morse_large.png', $text);
echo "   Generated: morse_large.png (larger elements)\n\n";

// Example 4: Dark theme
echo "4. Dark theme:\n";
$text = "NIGHT";
$morseCode = $translator->textToMorse($text);

$darkVisual = new VisualGenerator();
$darkVisual->setColors(
    [0, 0, 0],       // Black background
    [0, 255, 0],     // Green elements (like old terminals)
    [0, 200, 0]      // Darker green text
);
$darkVisual->generateImage($morseCode, 'night_dark.png', $text);
echo "   Generated: night_dark.png (dark theme with green elements)\n\n";

// Example 5: Compact layout for long text
echo "5. Compact layout:\n";
$text = "THE QUICK BROWN FOX";
$morseCode = $translator->textToMorse($text);

$compactVisual = new VisualGenerator();
$compactVisual->setDotWidth(10); // Smaller elements
$compactVisual->setElementHeight(10);
$compactVisual->setSpacing(5, 15, 30); // Tighter spacing
$compactVisual->setMaxWidth(600); // Narrower image
$compactVisual->generateImage($morseCode, 'fox_compact.png', $text);
echo "   Generated: fox_compact.png (compact layout)\n\n";

// Example 6: Multiple formats
echo "6. Multiple formats for same message:\n";
$text = "HELLO WORLD";
$morseCode = $translator->textToMorse($text);

// PNG
$visualGenerator->generateImage($morseCode, 'hello_world.png', $text);
echo "   Generated: hello_world.png\n";

// JPEG
$visualGenerator->generateImage($morseCode, 'hello_world.jpg', $text);
echo "   Generated: hello_world.jpg\n";

// SVG
$visualGenerator->generateSVG($morseCode, 'hello_world.svg', $text);
echo "   Generated: hello_world.svg\n\n";

// Example 7: Learning card style
echo "7. Learning card style:\n";
$words = ['CAT', 'DOG', 'BIRD'];
foreach ($words as $word) {
    $morseCode = $translator->textToMorse($word);
    
    $learningVisual = new VisualGenerator();
    $learningVisual->setDotWidth(25);
    $learningVisual->setElementHeight(25);
    $learningVisual->setColors(
        [245, 245, 245], // Light gray background
        [50, 50, 50],    // Dark gray elements
        [100, 100, 100]  // Medium gray text
    );
    
    $filename = strtolower($word) . '_card.png';
    $learningVisual->generateImage($morseCode, $filename, $word);
    echo "   Generated: $filename\n";
}
echo "\n";

// Example 8: Morse code chart
echo "8. Character reference chart:\n";
$chartVisual = new VisualGenerator();
$chartVisual->setMaxWidth(1000);
$chartVisual->setDotWidth(15);

// Generate A-E reference
$letters = ['A', 'B', 'C', 'D', 'E'];
$chartMorse = '';
foreach ($letters as $i => $letter) {
    if ($i > 0) $chartMorse .= ' / ';
    $chartMorse .= $translator->textToMorse($letter);
}

$chartVisual->generateImage($chartMorse, 'morse_chart_ae.png', implode(' ', $letters));
echo "   Generated: morse_chart_ae.png (A-E reference)\n\n";

echo "All visual files have been generated successfully!\n";
echo "You can view PNG/JPEG files in any image viewer.\n";
echo "SVG files can be viewed in web browsers or vector graphics editors.\n";