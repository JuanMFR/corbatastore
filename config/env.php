<?php
/**
 * Environment configuration loader
 * Loads configuration from .env file
 */

class EnvConfig {
    private static $config = [];
    
    public static function load($path = null) {
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        
        if (!file_exists($path)) {
            throw new Exception('.env file not found at: ' . $path);
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$config[$key] = $value;
                
                // Also set as environment variable
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
    
    public static function get($key, $default = null) {
        return self::$config[$key] ?? $_ENV[$key] ?? getenv($key) ?? $default;
    }
    
    public static function isProduction() {
        return self::get('APP_ENV') === 'production';
    }
    
    public static function isDevelopment() {
        return self::get('APP_ENV') === 'development';
    }
}