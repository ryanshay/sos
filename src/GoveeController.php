<?php

declare(strict_types=1);

namespace SOS\Translator;

use InvalidArgumentException;
use RuntimeException;
use SOS\Translator\Contracts\DisplayGeneratorInterface;
use SOS\Translator\Exceptions\EmptyInputException;
use SOS\Translator\Exceptions\InvalidMorseCodeException;

/**
 * Govee Smart Light Controller
 * 
 * Controls Govee WiFi-enabled smart lights to display Morse code patterns.
 * Uses UDP multicast for device discovery and control.
 * 
 * @package SOS\Translator
 */
class GoveeController implements DisplayGeneratorInterface
{
    private const MULTICAST_ADDR = '239.255.255.250';
    private const DEVICE_PORT = 4001;
    private const CLIENT_PORT = 4002;
    private const CONTROL_PORT = 4003;
    private const TIMEOUT = 3; // seconds
    
    private array $devices = [];
    private ?array $selectedDevice = null;
    
    private int $ditDuration = 60; // milliseconds
    private int $dahDuration = 180; // milliseconds (3x dit)
    private int $elementSpacing = 60; // space between dots/dashes (1x dit)
    private int $characterSpacing = 180; // space between characters (3x dit)
    private int $wordSpacing = 420; // space between words (7x dit)
    
    private int $brightness = 100; // 1-100
    private array $onColor = [255, 255, 0]; // Yellow RGB
    private array $offColor = [0, 0, 0]; // Black/Off
    
    /**
     * Set the Morse code speed
     * 
     * @param int $wpm Words per minute (5-60)
     * @throws \InvalidArgumentException If WPM out of valid range
     */
    public function setSpeed(int $wpm): void
    {
        if ($wpm < 5 || $wpm > 60) {
            throw new InvalidArgumentException("WPM must be between 5 and 60");
        }
        
        $this->ditDuration = intval(1200 / $wpm);
        $this->dahDuration = $this->ditDuration * 3;
        $this->elementSpacing = $this->ditDuration;
        $this->characterSpacing = $this->ditDuration * 3;
        $this->wordSpacing = $this->ditDuration * 7;
    }
    
    /**
     * Set the light brightness
     * 
     * @param int $brightness Brightness level (1-100)
     * @throws \InvalidArgumentException If brightness out of valid range
     */
    public function setBrightness(int $brightness): void
    {
        if ($brightness < 1 || $brightness > 100) {
            throw new InvalidArgumentException("Brightness must be between 1 and 100");
        }
        $this->brightness = $brightness;
    }
    
    /**
     * Set the colors for on/off states
     * 
     * @param array $onColor RGB values for on state [0-255, 0-255, 0-255]
     * @param array $offColor RGB values for off state [0-255, 0-255, 0-255]
     * @throws \InvalidArgumentException If colors not valid RGB arrays
     */
    public function setColors(array $onColor, array $offColor = [0, 0, 0]): void
    {
        if (count($onColor) !== 3 || count($offColor) !== 3) {
            throw new InvalidArgumentException("Colors must be RGB arrays with 3 values");
        }
        
        foreach ($onColor as $value) {
            if ($value < 0 || $value > 255) {
                throw new InvalidArgumentException("RGB values must be between 0 and 255");
            }
        }
        
        $this->onColor = array_map('intval', $onColor);
        $this->offColor = array_map('intval', $offColor);
    }
    
    /**
     * Discover Govee devices on the local network
     * 
     * @return array List of discovered devices with IP and device info
     * @throws \RuntimeException If socket operations fail
     */
    public function discoverDevices(): array
    {
        $this->devices = [];
        $receiveSocket = null;
        $sendSocket = null;
        
        try {
            // Create UDP socket for receiving responses
            $receiveSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if (!$receiveSocket) {
                throw new RuntimeException("Failed to create UDP socket: " . socket_strerror(socket_last_error()));
            }
            
            // Set socket to reuse address to avoid "Address already in use" errors
            if (!socket_set_option($receiveSocket, SOL_SOCKET, SO_REUSEADDR, 1)) {
                throw new RuntimeException("Failed to set socket option SO_REUSEADDR: " . socket_strerror(socket_last_error($receiveSocket)));
            }
            
            // Bind to client port
            if (!socket_bind($receiveSocket, '0.0.0.0', self::CLIENT_PORT)) {
                throw new RuntimeException("Failed to bind to port " . self::CLIENT_PORT . ": " . socket_strerror(socket_last_error($receiveSocket)));
            }
            
            // Set receive timeout
            if (!socket_set_option($receiveSocket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => self::TIMEOUT, 'usec' => 0])) {
                throw new RuntimeException("Failed to set socket timeout: " . socket_strerror(socket_last_error($receiveSocket)));
            }
            
            // Create sending socket
            $sendSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if (!$sendSocket) {
                throw new RuntimeException("Failed to create UDP send socket: " . socket_strerror(socket_last_error()));
            }
            
            // Enable broadcast
            if (!socket_set_option($sendSocket, SOL_SOCKET, SO_BROADCAST, 1)) {
                throw new RuntimeException("Failed to set socket option SO_BROADCAST: " . socket_strerror(socket_last_error($sendSocket)));
            }
            
            // Send socket doesn't need to be bound to a specific port for multicast
            // The OS will assign an ephemeral port automatically
            
            // Validate multicast address
            if (!filter_var(self::MULTICAST_ADDR, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                throw new RuntimeException("Invalid multicast address configured");
            }
            
            // Send discovery message
            $scanMessage = json_encode([
                'msg' => [
                    'cmd' => 'scan',
                    'data' => [
                        'account_topic' => 'reserve'
                    ]
                ]
            ]);
            
            if ($scanMessage === false) {
                throw new RuntimeException("Failed to encode discovery message");
            }
            
            $bytesSent = socket_sendto($sendSocket, $scanMessage, strlen($scanMessage), 0, self::MULTICAST_ADDR, self::DEVICE_PORT);
            if ($bytesSent === false) {
                throw new RuntimeException("Failed to send discovery message: " . socket_strerror(socket_last_error($sendSocket)));
            }
            
            // Listen for responses
            $startTime = time();
            while (time() - $startTime < self::TIMEOUT) {
                $buffer = '';
                $from = '';
                $port = 0;
                
                $bytes = socket_recvfrom($receiveSocket, $buffer, 1024, 0, $from, $port);
                if ($bytes > 0) {
                    $data = json_decode($buffer, true);
                    if ($data && isset($data['msg']['data'])) {
                        $deviceInfo = $data['msg']['data'];
                        $deviceInfo['ip'] = $from;
                        $deviceInfo['port'] = $port;
                        $this->devices[] = $deviceInfo;
                    }
                }
            }
        } finally {
            if ($receiveSocket !== null) {
                socket_close($receiveSocket);
            }
            if ($sendSocket !== null) {
                socket_close($sendSocket);
            }
        }
        
        return $this->devices;
    }
    
    /**
     * Get list of previously discovered devices
     * 
     * @return array Array of device information
     */
    public function getDevices(): array
    {
        return $this->devices;
    }
    
    /**
     * Select a device to control by index
     * 
     * @param int $index Device index from getDevices()
     * @throws \InvalidArgumentException If index is invalid
     */
    public function selectDevice(int $index): void
    {
        if (!isset($this->devices[$index])) {
            throw new InvalidArgumentException("Invalid device index");
        }
        $this->selectedDevice = $this->devices[$index];
    }
    
    /**
     * Select a device to control by IP address
     * 
     * @param string $ip Device IP address
     * @throws \InvalidArgumentException If device not found
     */
    public function selectDeviceByIp(string $ip): void
    {
        // Validate IP address format
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new InvalidArgumentException("Invalid IP address format: $ip");
        }
        
        foreach ($this->devices as $device) {
            if ($device['ip'] === $ip) {
                $this->selectedDevice = $device;
                return;
            }
        }
        throw new \InvalidArgumentException("Device with IP $ip not found");
    }
    
    /**
     * Send a command to the selected device
     * 
     * @param array $command Command data structure
     * @return bool True if sent successfully
     * @throws \RuntimeException If no device selected
     */
    private function sendCommand(array $command): bool
    {
        if (!$this->selectedDevice) {
            throw new RuntimeException("No device selected. Use selectDevice() first.");
        }
        
        // Validate IP address before using it
        if (!filter_var($this->selectedDevice['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new RuntimeException("Invalid device IP address");
        }
        
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return false;
        }
        
        try {
            $message = json_encode(['msg' => $command]);
            if ($message === false) {
                throw new RuntimeException("Failed to encode command");
            }
            
            $result = socket_sendto(
                $socket, 
                $message, 
                strlen($message), 
                0, 
                $this->selectedDevice['ip'], 
                self::CONTROL_PORT
            );
            
            return $result !== false;
        } finally {
            socket_close($socket);
        }
    }
    
    /**
     * Turn the light on or off
     * 
     * @param bool $on True for on, false for off
     * @return bool True if command sent successfully
     */
    public function turnLight(bool $on): bool
    {
        return $this->sendCommand([
            'cmd' => 'turn',
            'data' => [
                'value' => $on ? 1 : 0
            ]
        ]);
    }
    
    /**
     * Set the brightness immediately
     * 
     * @param int $brightness Brightness level (1-100)
     * @return bool True if command sent successfully
     * @throws \InvalidArgumentException If brightness out of valid range
     */
    public function setBrightnessNow(int $brightness): bool
    {
        if ($brightness < 1 || $brightness > 100) {
            throw new InvalidArgumentException("Brightness must be between 1 and 100");
        }
        
        return $this->sendCommand([
            'cmd' => 'brightness',
            'data' => [
                'value' => $brightness
            ]
        ]);
    }
    
    /**
     * Set the light color
     * 
     * @param array $rgb RGB values [0-255, 0-255, 0-255]
     * @return bool True if command sent successfully
     * @throws \InvalidArgumentException If RGB array invalid
     */
    public function setColor(array $rgb): bool
    {
        if (count($rgb) !== 3) {
            throw new InvalidArgumentException("RGB must have 3 values");
        }
        
        return $this->sendCommand([
            'cmd' => 'colorwc',
            'data' => [
                'color' => [
                    'r' => intval($rgb[0]),
                    'g' => intval($rgb[1]),
                    'b' => intval($rgb[2])
                ],
                'colorTemInKelvin' => 0
            ]
        ]);
    }
    
    /**
     * Blink Morse code on the Govee light
     * 
     * @param string $morseCode Morse code pattern to blink
     * @param string|null $text Original text for display purposes
     * @throws EmptyInputException If Morse code is empty
     * @throws \RuntimeException If no device selected
     */
    public function blinkMorseCode(string $morseCode, ?string $text = null): void
    {
        if (empty(trim($morseCode))) {
            throw new EmptyInputException("Morse code cannot be empty");
        }
        
        // Ensure light is on and set initial color
        $this->turnLight(true);
        $this->setBrightnessNow($this->brightness);
        $this->setColor($this->offColor);
        usleep(500000); // 500ms pause before starting
        
        // Display info
        if ($text) {
            echo "Blinking: $text\n";
        }
        echo "Morse: $morseCode\n";
        echo "Device: " . ($this->selectedDevice['device'] ?? 'Unknown') . " (" . $this->selectedDevice['ip'] . ")\n\n";
        
        // Blink the pattern
        for ($i = 0; $i < strlen($morseCode); $i++) {
            $char = $morseCode[$i];
            
            switch ($char) {
                case '.':
                    $this->blinkLight($this->ditDuration);
                    break;
                    
                case '-':
                    $this->blinkLight($this->dahDuration);
                    break;
                    
                case ' ':
                    // Check for word separator
                    if ($i + 1 < strlen($morseCode) && $morseCode[$i + 1] === '/') {
                        usleep($this->wordSpacing * 1000);
                        $i++; // Skip the /
                        continue 2;
                    } else {
                        usleep($this->characterSpacing * 1000);
                    }
                    break;
                    
                case '/':
                    usleep($this->wordSpacing * 1000);
                    break;
            }
            
            // Add element spacing after dots and dashes
            if (($char === '.' || $char === '-') && 
                $i + 1 < strlen($morseCode) && 
                $morseCode[$i + 1] !== ' ' && 
                $morseCode[$i + 1] !== '/') {
                usleep($this->elementSpacing * 1000);
            }
        }
        
        // Turn off at the end
        $this->setColor($this->offColor);
    }
    
    /**
     * Blink the light for a specified duration
     * 
     * @param int $duration Duration in milliseconds
     */
    private function blinkLight(int $duration): void
    {
        $this->setColor($this->onColor);
        usleep($duration * 1000);
        $this->setColor($this->offColor);
    }
    
    /**
     * Get the currently selected device info
     * 
     * @return array|null Device info or null if none selected
     */
    public function getSelectedDevice(): ?array
    {
        return $this->selectedDevice;
    }
    
    /**
     * Display Morse code pattern on Govee light
     * 
     * @param string $morseCode Morse code pattern
     * @param string|null $label Optional label (original text)
     * @throws EmptyInputException If Morse code is empty
     * @throws RuntimeException If no device selected
     */
    public function display(string $morseCode, ?string $label = null): void
    {
        $this->blinkMorseCode($morseCode, $label);
    }
}