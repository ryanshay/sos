<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;

$translator = new Translator();

// Convert text to Morse code
echo "Text to Morse Code Examples:\n";
echo "----------------------------\n";

$text1 = "Hello World";
$morse1 = $translator->textToMorse($text1);
echo "'{$text1}' => '{$morse1}'\n";

$text2 = "SOS";
$morse2 = $translator->textToMorse($text2);
echo "'{$text2}' => '{$morse2}'\n";

$text3 = "PHP is awesome!";
$morse3 = $translator->textToMorse($text3);
echo "'{$text3}' => '{$morse3}'\n";

echo "\n";

// Convert Morse code to text
echo "Morse Code to Text Examples:\n";
echo "----------------------------\n";

$morseCode1 = ".... . .-.. .-.. --- / .-- --- .-. .-.. -..";
$decodedText1 = $translator->morseToText($morseCode1);
echo "'{$morseCode1}' => '{$decodedText1}'\n";

$morseCode2 = "... --- ...";
$decodedText2 = $translator->morseToText($morseCode2);
echo "'{$morseCode2}' => '{$decodedText2}'\n";

echo "\n";

// Get Morse code for a specific character
echo "Individual Character Examples:\n";
echo "-----------------------------\n";

$char1 = 'A';
$charMorse1 = $translator->getCharacterMorse($char1);
echo "'{$char1}' => '{$charMorse1}'\n";

$char2 = '5';
$charMorse2 = $translator->getCharacterMorse($char2);
echo "'{$char2}' => '{$charMorse2}'\n";

echo "\n";

// Show part of the Morse code map
echo "Morse Code Map (first 10 entries):\n";
echo "----------------------------------\n";

$map = $translator->getMorseCodeMap();
$count = 0;
foreach ($map as $char => $morse) {
    echo "'{$char}' => '{$morse}'\n";
    $count++;
    if ($count >= 10) break;
}