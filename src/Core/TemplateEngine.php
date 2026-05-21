<?php
namespace App\Core;

class TemplateEngine {
    public static function compile(string $templateFile, array $context = []): string {
        extract($context);
        
        // Start trapping all browser output internally
        ob_start();
        
        include APP_ROOT . '/Views/' . $templateFile . '.php';
        
        // Grab the trapped markup string and erase the buffer memory
        return ob_get_clean(); 
    }
}