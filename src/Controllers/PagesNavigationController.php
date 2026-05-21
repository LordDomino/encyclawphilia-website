<?php

namespace App\Controllers;

class PagesNavigationController
{
    /**
     * Handles requests directed to the root domain.
     */
    public function home(): void
    {
        // Enforce secure rendering of the homepage view asset
        require_once __DIR__ . '/../Views/pages/home.php';
    }

    public function browse(): void
    {
        // Enforce secure rendering of the homepage view asset
        require_once __DIR__ . '/../Views/pages/browse.php';
    }

    /**
     * Handles requests directed to the root domain.
     */
    public function about(): void
    {
        // Enforce secure rendering of the homepage view asset
        require_once __DIR__ . '/../Views/pages/about.php';
    }

    /**
     * Handles requests directed to the root domain.
     */
    public function login(): void
    {
        // Enforce secure rendering of the homepage view asset
        require_once __DIR__ . '/../Views/pages/login.php';
    }

    /**
     * Handles requests directed to the root domain.
     */
    public function ordinance(): void
    {
        // Enforce secure rendering of the homepage view asset
        require_once __DIR__ . '/../Views/pages/ordinance.php';
    }

    /**
     * Handles requests directed to the root domain.
     */
    public function account(): void
    {
        // Enforce secure rendering of the homepage view asset
        require_once __DIR__ . '/../Views/pages/account.php';
    }

    public function adminDashboard(): void
    {
        // Enforce secure rendering of the homepage view asset
        require_once __DIR__ . '/../Views/pages/admin_dashboard.php';
    }
    
    public function addOrdinance(): void
    {
        require_once __DIR__ . '/../Views/pages/add_ordinance.php';
    }

}
