<?php

declare(strict_types=1);

namespace SOS\Translator\Exceptions;

use Exception;

/**
 * Base Exception for Morse Code Translator
 * 
 * All translator-specific exceptions extend from this class.
 * 
 * @package SOS\Translator\Exceptions
 */
class TranslatorException extends Exception
{
}