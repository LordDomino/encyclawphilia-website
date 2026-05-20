<?php

namespace App\Core;

class View
{
    /**
     * Renders a primary template file and safely injects data payload array.
     */
    public static function render(string $template, array $data = []): void
    {
        // Extract array keys into real local variables
        // e.g., ['ordinances' => $array] becomes a local variable named $ordinances
        extract($data);

        // Build absolute path using your global anchor constant
        $templateFile = APP_ROOT . '/Views/' . $template . '.php';

        if (file_exists($templateFile)) {
            include $templateFile;
        } else {
            trigger_error("Template file not found: $templateFile", E_USER_ERROR);
        }
    }

    /**
     * Helper shorthand to let a template file include another sub-template partial
     * while preserving or adding data attributes safely.
     */
    public static function partial(string $partialName, array $data = []): void
    {
        extract($data);
        $partialFile = APP_ROOT . '/Views/partials/' . $partialName . '.php';

        if (file_exists($partialFile)) {
            include $partialFile; // Use include to allow multiple components (e.g., cards)
        }
    }
}
