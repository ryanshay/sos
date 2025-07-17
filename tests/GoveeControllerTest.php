<?php

namespace SOS\Translator\Tests;

use PHPUnit\Framework\TestCase;
use SOS\Translator\GoveeController;
use SOS\Translator\Exceptions\EmptyInputException;

class GoveeControllerTest extends TestCase
{
    private GoveeController $controller;
    
    protected function setUp(): void
    {
        $this->controller = new GoveeController();
    }
    
    public function testSpeedSetting(): void
    {
        // Valid speeds
        $this->controller->setSpeed(20);
        $this->controller->setSpeed(5);
        $this->controller->setSpeed(60);
        
        // This should not throw
        $this->assertTrue(true);
    }
    
    public function testInvalidSpeedThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("WPM must be between 5 and 60");
        $this->controller->setSpeed(4);
    }
    
    public function testInvalidHighSpeedThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("WPM must be between 5 and 60");
        $this->controller->setSpeed(61);
    }
    
    public function testBrightnessSetting(): void
    {
        // Valid brightness values
        $this->controller->setBrightness(1);
        $this->controller->setBrightness(50);
        $this->controller->setBrightness(100);
        
        $this->assertTrue(true);
    }
    
    public function testInvalidBrightnessThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Brightness must be between 1 and 100");
        $this->controller->setBrightness(0);
    }
    
    public function testInvalidHighBrightnessThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Brightness must be between 1 and 100");
        $this->controller->setBrightness(101);
    }
    
    public function testColorSetting(): void
    {
        // Valid colors
        $this->controller->setColors([255, 0, 0]); // Red
        $this->controller->setColors([0, 255, 0]); // Green
        $this->controller->setColors([0, 0, 255]); // Blue
        $this->controller->setColors([255, 255, 0], [0, 0, 0]); // Yellow on, black off
        
        $this->assertTrue(true);
    }
    
    public function testInvalidColorArrayThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Colors must be RGB arrays with 3 values");
        $this->controller->setColors([255, 0]); // Only 2 values
    }
    
    public function testInvalidColorValueThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("RGB values must be between 0 and 255");
        $this->controller->setColors([256, 0, 0]); // Value too high
    }
    
    public function testDeviceSelectionWithoutDevicesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid device index");
        $this->controller->selectDevice(0);
    }
    
    public function testSelectDeviceByIpWithoutDevicesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Device with IP 192.168.1.100 not found");
        $this->controller->selectDeviceByIp('192.168.1.100');
    }
    
    public function testGetDevicesReturnsEmptyArrayInitially(): void
    {
        $devices = $this->controller->getDevices();
        $this->assertIsArray($devices);
        $this->assertEmpty($devices);
    }
    
    public function testGetSelectedDeviceReturnsNullInitially(): void
    {
        $device = $this->controller->getSelectedDevice();
        $this->assertNull($device);
    }
    
    public function testBlinkMorseCodeWithoutDeviceThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("No device selected");
        
        // Create a mock to avoid actual network calls
        $controller = $this->getMockBuilder(GoveeController::class)
            ->onlyMethods(['sendCommand'])
            ->getMock();
            
        $controller->blinkMorseCode("... --- ...");
    }
    
    public function testBlinkMorseCodeWithEmptyStringThrowsException(): void
    {
        $this->expectException(EmptyInputException::class);
        $this->expectExceptionMessage("Morse code cannot be empty");
        
        // Create a mock with a selected device
        $controller = $this->getMockBuilder(GoveeController::class)
            ->onlyMethods(['sendCommand', 'getSelectedDevice'])
            ->getMock();
            
        $controller->method('getSelectedDevice')->willReturn(['ip' => '192.168.1.100', 'device' => 'Test Device']);
        
        $controller->blinkMorseCode("");
    }
    
    public function testBlinkMorseCodeWithWhitespaceOnlyThrowsException(): void
    {
        $this->expectException(EmptyInputException::class);
        $this->expectExceptionMessage("Morse code cannot be empty");
        
        // Create a mock with a selected device
        $controller = $this->getMockBuilder(GoveeController::class)
            ->onlyMethods(['sendCommand', 'getSelectedDevice'])
            ->getMock();
            
        $controller->method('getSelectedDevice')->willReturn(['ip' => '192.168.1.100', 'device' => 'Test Device']);
        
        $controller->blinkMorseCode("   ");
    }
    
    /**
     * Test that discovery timeout is respected
     * This test uses reflection to verify the timeout constant
     */
    public function testDiscoveryTimeout(): void
    {
        $reflection = new \ReflectionClass(GoveeController::class);
        $timeoutConstant = $reflection->getConstant('TIMEOUT');
        
        $this->assertEquals(3, $timeoutConstant);
    }
    
    /**
     * Test that ports are correctly defined
     */
    public function testPortConstants(): void
    {
        $reflection = new \ReflectionClass(GoveeController::class);
        
        $this->assertEquals(4001, $reflection->getConstant('DEVICE_PORT'));
        $this->assertEquals(4002, $reflection->getConstant('CLIENT_PORT'));
        $this->assertEquals(4003, $reflection->getConstant('CONTROL_PORT'));
        $this->assertEquals('239.255.255.250', $reflection->getConstant('MULTICAST_ADDR'));
    }
}