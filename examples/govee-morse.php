<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\Translator;
use SOS\Translator\GoveeController;

echo "Govee Light Morse Code Controller\n";
echo "=================================\n\n";

$controller = new GoveeController();

echo "Discovering Govee devices on the network...\n";
echo "(Make sure your Govee device has LAN Control enabled in the Govee Home app)\n\n";

try {
    $devices = $controller->discoverDevices();
    
    if (empty($devices)) {
        echo "No Govee devices found on the network.\n";
        echo "Please ensure:\n";
        echo "1. Your Govee light is powered on\n";
        echo "2. LAN Control is enabled in the Govee Home app\n";
        echo "3. Your computer and Govee device are on the same network\n";
        exit(1);
    }
    
    echo "Found " . count($devices) . " Govee device(s):\n\n";
    
    foreach ($devices as $index => $device) {
        echo ($index + 1) . ". " . ($device['device'] ?? 'Unknown Device') . "\n";
        echo "   IP: " . $device['ip'] . "\n";
        echo "   Model: " . ($device['sku'] ?? 'Unknown') . "\n";
        echo "   Hardware Version: " . ($device['hardwareVersion'] ?? 'Unknown') . "\n";
        echo "   Software Version: " . ($device['softwareVersion'] ?? 'Unknown') . "\n\n";
    }
    
    if (count($devices) > 1) {
        echo "Select device [1-" . count($devices) . "]: ";
        $selection = intval(trim(fgets(STDIN))) - 1;
        
        if ($selection < 0 || $selection >= count($devices)) {
            echo "Invalid selection.\n";
            exit(1);
        }
    } else {
        $selection = 0;
    }
    
    $controller->selectDevice($selection);
    $selectedDevice = $controller->getSelectedDevice();
    echo "\nSelected: " . ($selectedDevice['device'] ?? 'Unknown Device') . " (" . $selectedDevice['ip'] . ")\n\n";
    
} catch (Exception $e) {
    echo "Error discovering devices: " . $e->getMessage() . "\n";
    exit(1);
}

$translator = new Translator();

// Get message from command line or interactive
if (isset($argv[1])) {
    $message = $argv[1];
} else {
    echo "Enter message to send (or 'SOS' for emergency): ";
    $message = trim(fgets(STDIN)) ?: 'HELLO';
}

$message = strtoupper($message);

echo "\nMessage: $message\n";
$morseCode = $translator->textToMorse($message);
echo "Morse Code: $morseCode\n\n";

// Configure the controller
echo "Select mode:\n";
echo "1. Normal (yellow light)\n";
echo "2. Emergency (red light)\n";
echo "3. Custom color\n";
echo "\nChoice [1-3]: ";

$mode = trim(fgets(STDIN)) ?: '1';

switch ($mode) {
    case '2':
        echo "\nEmergency mode - Red flashing\n";
        $controller->setColors([255, 0, 0]); // Red
        $controller->setSpeed(15); // Faster for emergency
        $controller->setBrightness(100); // Maximum brightness
        break;
        
    case '3':
        echo "\nEnter RGB values (0-255) separated by spaces: ";
        $rgb = explode(' ', trim(fgets(STDIN)));
        if (count($rgb) !== 3 || !isset($rgb[0], $rgb[1], $rgb[2])) {
            echo "Invalid color. Using default yellow.\n";
            $controller->setColors([255, 255, 0]);
        } else {
            $r = filter_var($rgb[0], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 255]]);
            $g = filter_var($rgb[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 255]]);
            $b = filter_var($rgb[2], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 255]]);
            
            if ($r === false || $g === false || $b === false) {
                echo "Invalid RGB values. Using default yellow.\n";
                $controller->setColors([255, 255, 0]);
            } else {
                $controller->setColors([$r, $g, $b]);
            }
        }
        break;
        
    default:
        echo "\nNormal mode - Yellow light\n";
        $controller->setColors([255, 255, 0]); // Yellow
        break;
}

// Speed setting
echo "\nEnter speed in WPM (5-60, default 20): ";
$wpm = intval(trim(fgets(STDIN))) ?: 20;
if ($wpm >= 5 && $wpm <= 60) {
    $controller->setSpeed($wpm);
}

// Brightness setting
echo "Enter brightness (1-100, default 100): ";
$brightness = intval(trim(fgets(STDIN))) ?: 100;
if ($brightness >= 1 && $brightness <= 100) {
    $controller->setBrightness($brightness);
}

echo "\nReady to send Morse code to Govee light.\n";
echo "Press Enter to start (Ctrl+C to cancel)...";
fgets(STDIN);

try {
    // Repeat count
    echo "\nHow many times to repeat? [1-10]: ";
    $repeats = intval(trim(fgets(STDIN))) ?: 1;
    $repeats = max(1, min(10, $repeats));
    
    for ($i = 0; $i < $repeats; $i++) {
        if ($i > 0) {
            echo "\nRepeat " . ($i + 1) . " of $repeats\n";
            sleep(2); // Pause between repeats
        }
        
        $translator->blinkOnGoveeLight($message, $controller);
    }
    
    echo "\nMorse code transmission complete!\n";
    
    // Turn off the light
    echo "Turn off the light? [Y/n]: ";
    $turnOff = strtolower(trim(fgets(STDIN))) !== 'n';
    
    if ($turnOff) {
        $controller->turnLight(false);
        echo "Light turned off.\n";
    }
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nDone!\n";