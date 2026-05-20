<?php

namespace App\Controllers;

use App\Core\View;

class OrdinanceController
{
    public function showDetail()
    {
        // High-level parent variables
        $activeUser = $_SESSION['username'] ?? 'Guest';
        $ordinanceData = ['number' => '123', 'title' => 'Anti-Littering Ordinance'];

        // Stylized hyperlink

        View::render('partials/ordinance_view', [
            'data'      => $ordinanceData,
            'viewer'    => $activeUser
        ]);
    }
}
