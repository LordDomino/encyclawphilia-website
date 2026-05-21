<?php

namespace App\Services;

use App\Core\ApiResponse;
use App\Models\OrdinanceModel;

class AdminSearchService
{
    public function __construct(private readonly OrdinanceModel $ordinanceModel) {}

    public function search(string $searchKeywords, int $limit = 20, int $offset = 0): array
    {
        $cleanKeywords = trim($searchKeywords);

        // Empty keyword is intentionally allowed — an empty string passed to
        // getAllOrdinances produces LIKE '%%', which matches all non-archived
        // records. This is the expected behaviour on initial page load.
        $outcome = $this->ordinanceModel->getAllOrdinances($cleanKeywords, $limit, $offset);

        // Zero results is a valid state (e.g. the table is empty), not an error.
        // The caller and frontend are both equipped to render an empty record set.
        return [
            'ok'          => true,
            'records'     => $outcome['records'],
            'total_count' => $outcome['total_count'],
            'limit'       => $outcome['limit'],
            'offset'      => $outcome['offset'],
        ];
    }
}
