<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SOS\Translator\GoveeController;

echo "Govee Device Discovery Test\n";
echo "===========================\n\n";

$controller = new GoveeController();

echo "Scanning for Govee devices on the local network...\n";
echo "This will take about 3 seconds.\n\n";

try {
    $devices = $controller->discoverDevices();
    
    if (empty($devices)) {
        echo "No Govee devices found.\n\n";
        echo "Troubleshooting:\n";
        echo "1. Ensure your Govee device is powered on\n";
        echo "2. Enable 'LAN Control' in the Govee Home app:\n";
        echo "   - Open Govee Home app\n";
        echo "   - Go to device settings\n";
        echo "   - Look for 'LAN Control' option and enable it\n";
        echo "3. Ensure your computer and Govee device are on the same network\n";
        echo "4. Check that UDP ports 4001-4003 are not blocked by firewall\n";
    } else {
        echo "Found " . count($devices) . " Govee device(s):\n\n";
        
        foreach ($devices as $index => $device) {
            echo "Device " . ($index + 1) . ":\n";
            echo "  Name: " . ($device['device'] ?? 'Unknown') . "\n";
            echo "  IP Address: " . $device['ip'] . "\n";
            echo "  Model/SKU: " . ($device['sku'] ?? 'Unknown') . "\n";
            echo "  Hardware Version: " . ($device['hardwareVersion'] ?? 'Unknown') . "\n";
            echo "  Software Version: " . ($device['softwareVersion'] ?? 'Unknown') . "\n";
            
            if (isset($device['deviceName'])) {
                echo "  Device Name: " . $device['deviceName'] . "\n";
            }
            
            echo "\n";
        }
        
        echo "To control a device, use the govee-morse.php script.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nMake sure:\n";
    echo "- You have permission to bind to UDP port 4002\n";
    echo "- No other application is using port 4002\n";
}

echo "\nDiscovery complete.\n";