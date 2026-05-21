<?php

namespace App\Services;

use App\Core\ApiResponse;
use App\Models\OrdinanceModel;

class AdminSearchService
{
    public function __construct(private readonly OrdinanceModel $ordinanceModel) {}

    public function search(string $searchKeywords): array
    {
        $cleanKeywords = trim($searchKeywords); // basic sanitation

        if ($cleanKeywords === '') {
            return ['ok' => false, 'message' => 'Search keywords empty.'];
        }

        $outcome = $this->ordinanceModel->getAllOrdinances($cleanKeywords);

        if ($outcome['total_count'] < 1) {
            return ['ok' => false, 'message' => 'Search results are empty.'];
        }

        return [
            'ok' => true,
            'records'       => $outcome['records'],
            'total_count'   => $outcome['total_count'],
            'limit'         => $outcome['limit'],
            'offset'        => $outcome['offset']
        ];
    }
}
