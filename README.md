# Morse Code Translator

A comprehensive PHP library for translating between text and Morse code with support for audio generation, visual representations, and blinking light patterns.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Detailed Usage Examples](#detailed-usage-examples)
  - [Text Translation](#text-translation)
  - [Audio Generation](#audio-generation)
  - [Visual Representations](#visual-representations)
  - [Blinking Light Patterns](#blinking-light-patterns)
  - [Data Export](#data-export)
  - [Error Handling](#error-handling)
- [Supported Characters](#supported-characters)
- [Configuration Options](#configuration-options)
- [Class Structure](#class-structure)
- [Examples Directory](#examples-directory)
- [Running Tests](#running-tests)
- [Requirements](#requirements)
- [License](#license)
- [Contributing](#contributing)
- [Author](#author)

## Features

- **Text Translation**: Convert between text and Morse code bidirectionally
- **Audio Generation**: Create WAV audio files with configurable speed and tone
- **Visual Representations**: Generate images (PNG, JPEG, GIF) and SVG files showing Morse patterns
- **Blinking Patterns**: Real-time terminal flashing and HTML/JavaScript animations
- **Data Export**: Export timing data in JSON, CSV, or Arduino-compatible formats
- **Error Handling**: Comprehensive exception handling with configurable behavior
- **Character Support**: Letters (A-Z), numbers (0-9), common punctuation marks
- **Customization**: Extensive options for audio, visual, and timing parameters

## Use Cases

- **Education**: Teaching Morse code with visual and audio aids
- **Emergency Signaling**: Generate SOS patterns in multiple formats
- **Amateur Radio**: Practice tools for ham radio operators
- **Accessibility**: Alternative communication methods
- **Hardware Projects**: Arduino/Raspberry Pi integration
- **Web Applications**: Embed Morse code features in websites
- **Mobile Apps**: Backend for Morse code mobile applications
- **Historical Preservation**: Demonstrate historical communication methods

## Installation

Install via Composer:

```bash
composer require rshay/sos
```

## Quick Start

```php
use SOS\Translator\Translator;

$translator = new Translator();

// Basic translation
$morse = $translator->textToMorse('Hello World');
echo $morse; // .... . .-.. .-.. --- / .-- --- .-. .-.. -..

$text = $translator->morseToText('... --- ...');
echo $text; // SOS
```

## Detailed Usage Examples

### Text Translation

```php
use SOS\Translator\Translator;
use SOS\Translator\UnsupportedCharacterMode;

$translator = new Translator();

// Basic text to Morse
$morse = $translator->textToMorse('HELLO WORLD');
// Output: .... . .-.. .-.. --- / .-- --- .-. .-.. -..

// Morse to text
$text = $translator->morseToText('... --- ...');
// Output: SOS

// Single character lookup
$charMorse = $translator->getCharacterMorse('A');
// Output: .-

// Handle unsupported characters
$translator = new Translator(UnsupportedCharacterMode::SKIP);
$morse = $translator->textToMorse('Hello @ World'); // @ is skipped

$translator = new Translator(UnsupportedCharacterMode::REPLACE);
$morse = $translator->textToMorse('Hello @ World'); // @ replaced with ?
```

### Audio Generation

```php
use SOS\Translator\Translator;
use SOS\Translator\AudioGenerator;

$translator = new Translator();

// Simple audio generation
$translator->generateAudioFile('SOS', 'sos.wav');

// Custom audio settings
$audioGen = new AudioGenerator();
$audioGen->setSpeed(20);        // 20 words per minute
$audioGen->setFrequency(800);   // 800 Hz tone
$audioGen->setVolume(0.7);      // 70% volume
$audioGen->setSampleRate(22050); // Lower sample rate for smaller files

$translator->generateAudioFile('HELLO WORLD', 'custom.wav', $audioGen);

// Direct Morse to audio
$morseCode = '... --- ...';
$audioGen->generateWavFile($morseCode, 'morse.wav');
```

### Visual Representations

```php
use SOS\Translator\Translator;
use SOS\Translator\VisualGenerator;

$translator = new Translator();

// Generate PNG image
$translator->generateVisualFile('SOS', 'sos.png');

// Generate SVG (scalable)
$translator->generateVisualFile('HELLO', 'hello.svg');

// Custom visual settings
$visualGen = new VisualGenerator();
$visualGen->setDotWidth(40);           // Larger dots
$visualGen->setElementHeight(30);      // Taller elements
$visualGen->setMaxWidth(600);          // Narrower image
$visualGen->setColors(
    [255, 255, 255],  // White background
    [0, 0, 0],        // Black elements
    [128, 128, 128]   // Gray labels
);

$translator->generateVisualFile('MORSE CODE', 'custom.png', $visualGen);
```

### Blinking Light Patterns

#### Terminal Blinking

```php
use SOS\Translator\Translator;

$translator = new Translator();

// Inline terminal blinking
$translator->blinkInTerminal('SOS');

// Fullscreen terminal flashing
$translator->blinkInTerminal('EMERGENCY', [
    'fullscreen' => true,
    'speed' => 15,      // 15 WPM
    'repeats' => 3      // Repeat 3 times
]);

// Direct control with TerminalBlinker
use SOS\Translator\TerminalBlinker;

$blinker = new TerminalBlinker();
$blinker->setSpeed(20);
$blinker->setColors("\033[41m", "\033[40m"); // Red on/off
$blinker->blinkInline('... --- ...', 'SOS');
```

#### HTML/JavaScript Patterns

```php
use SOS\Translator\Translator;
use SOS\Translator\BlinkPatternGenerator;

$translator = new Translator();

// Generate HTML with blinking animation
$translator->generateBlinkPattern('HELLO', 'hello.html');

// Custom HTML pattern
$blinkGen = new BlinkPatternGenerator();
$blinkGen->setSpeed(10);                    // Slow for learning
$blinkGen->setColors('#00FF00', '#000000'); // Green on black
$blinkGen->setLightSize(300);               // Large light
$blinkGen->setIncludeControls(false);       // Auto-play only

$translator->generateBlinkPattern('SOS', 'emergency.html', $blinkGen);
```

### Data Export

```php
use SOS\Translator\Translator;

$translator = new Translator();

// Export as JSON
$translator->generateBlinkPattern('TEST', 'timings.json');

// Export as CSV
$translator->generateBlinkPattern('TEST', 'timings.csv');

// Get timing array
$timings = $translator->exportBlinkTimings('SOS', 'array');
// Returns: [
//   ['state' => 'on', 'duration' => 60],
//   ['state' => 'off', 'duration' => 60],
//   ...
// ]

// Generate Arduino code
$arduinoCode = $translator->exportBlinkTimings('SOS', 'arduino');
// Returns complete Arduino sketch
```

### Error Handling

```php
use SOS\Translator\Translator;
use SOS\Translator\UnsupportedCharacterMode;
use SOS\Translator\Exceptions\InvalidCharacterException;
use SOS\Translator\Exceptions\InvalidMorseCodeException;

// Throw exceptions (default)
$translator = new Translator();
try {
    $morse = $translator->textToMorse('Hello @');
} catch (InvalidCharacterException $e) {
    echo "Invalid character: " . $e->getCharacter();
    echo " at position: " . $e->getPosition();
}

// Skip invalid characters
$translator = new Translator(UnsupportedCharacterMode::SKIP);
$morse = $translator->textToMorse('Hello @ World');
// @ is supported, but if using an unsupported character:
// Skip mode will ignore it
// Replace mode will substitute it

// Replace invalid characters
$translator = new Translator(UnsupportedCharacterMode::REPLACE);
$translator->setReplacementCharacter('?');
$morse = $translator->textToMorse('Hello ~ World');
// ~ becomes ?, result includes ..--..
```

## Supported Characters

### Letters
A-Z (case-insensitive)

### Numbers
0-9

### Punctuation
- Period (.)
- Comma (,)
- Question mark (?)
- Apostrophe (')
- Exclamation mark (!)
- Slash (/)
- Parentheses ()
- Ampersand (&)
- Colon (:)
- Semicolon (;)
- Equals (=)
- Plus (+)
- Minus (-)
- Underscore (_)
- Quotation mark (")
- Dollar sign ($)
- At sign (@)

### Special
- Space (word separator in Morse: /)

## Configuration Options

### Audio Generator
- **Speed**: 5-60 WPM (words per minute)
- **Frequency**: 100-4000 Hz
- **Volume**: 0.0-1.0
- **Sample Rate**: 8000-192000 Hz

### Visual Generator
- **Dot Width**: 5-100 pixels
- **Element Height**: 5-100 pixels
- **Max Width**: 200+ pixels
- **Colors**: RGB arrays for background, elements, and text

### Blink Pattern Generator
- **Speed**: 5-60 WPM
- **Light Size**: 50-500 pixels
- **Colors**: Hex color codes
- **Controls**: Optional play/pause/speed controls

## Class Structure

### Main Classes

- **`Translator`** - Core translation class with integration methods
  - `textToMorse($text)` - Convert text to Morse code
  - `morseToText($morse)` - Convert Morse code to text
  - `generateAudioFile($text, $filename, $audioGenerator)` - Create audio file
  - `generateVisualFile($text, $filename, $visualGenerator)` - Create image/SVG
  - `blinkInTerminal($text, $options)` - Terminal flashing
  - `generateBlinkPattern($text, $filename, $generator)` - Create HTML/data files
  - `exportBlinkTimings($text, $format)` - Export timing data

- **`AudioGenerator`** - WAV file generation
  - Configurable speed, frequency, volume, and sample rate
  - Smooth fade in/out to prevent audio clicks

- **`VisualGenerator`** - Image and SVG generation
  - Support for PNG, JPEG, GIF, and SVG formats
  - Customizable colors, sizes, and spacing
  - Automatic line wrapping

- **`TerminalBlinker`** - Terminal-based flashing
  - Inline and fullscreen modes
  - ANSI escape code support
  - Configurable colors and symbols

- **`BlinkPatternGenerator`** - HTML and data export
  - Self-contained HTML with JavaScript
  - JSON, CSV, and Arduino code export
  - Configurable appearance and controls

### Exception Classes

- **`TranslatorException`** - Base exception class
- **`InvalidCharacterException`** - Invalid character in input
- **`InvalidMorseCodeException`** - Invalid Morse code format
- **`EmptyInputException`** - Empty input provided

### Utility Classes

- **`UnsupportedCharacterMode`** - Constants for handling invalid characters
  - `THROW_EXCEPTION` - Throw exception (default)
  - `SKIP` - Skip invalid characters
  - `REPLACE` - Replace with configurable character

## Examples Directory

The library includes comprehensive examples:

- `basic-usage.php` - Simple translation examples
- `audio-generation.php` - Audio file creation with various settings
- `visual-generation.php` - Image and SVG generation
- `terminal-blink.php` - Terminal flashing demonstrations
- `blink-pattern.php` - HTML pattern generation
- `emergency-signal.php` - Emergency signaling example
- `error-handling.php` - Exception handling examples
- `text-to-audio.php` - Complete text to audio workflow
- `text-to-visual.php` - Complete text to visual workflow

## Running Tests

```bash
composer install
vendor/bin/phpunit
```

## Output File Formats

### Audio
- **WAV**: 16-bit PCM, mono, configurable sample rate (8-192 kHz)

### Images
- **PNG**: Lossless compression, transparency support
- **JPEG**: Lossy compression, smaller file sizes
- **GIF**: Limited colors, small file sizes
- **SVG**: Vector format, infinitely scalable

### Data
- **HTML**: Self-contained with embedded CSS/JavaScript
- **JSON**: Structured timing data with metadata
- **CSV**: Simple tabular format for spreadsheets
- **Arduino**: Complete sketch file ready to upload

## Requirements

- PHP 7.4 or higher
- GD extension (for image generation)
- Terminal with ANSI escape code support (for terminal blinking)

## License

MIT License - see LICENSE file for details

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Author

RCS - ryancshay@gmail.com