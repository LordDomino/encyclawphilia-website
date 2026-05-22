<?php

namespace App\Controllers;

use App\Core\ApiResponse;
use PDOException;
use App\Models\OrdinanceModel;

class OrdinanceApiController
{
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE     = 100;

    // ------------------------------------------------------------------
    // GET /api/ordinances/search?q=&page=&limit=
    // Full paginated result slice + pagination metadata.
    // ------------------------------------------------------------------
    public function search(): void
    {
        // $this->assertXhr();

        [$keyword, $page, $limit] = $this->resolveSearchParams();
        $offset = ($page - 1) * $limit;

        try {
            $ordModel   = new OrdinanceModel(\App\Controllers\DatabaseController::getDatabaseConnection());
            $result     = $ordModel->getOrdinancesByTitle($keyword, $limit, $offset);
        } catch (PDOException $e) {
            error_log('OrdinanceApiController::search() failure: ' . $e->getMessage());
            ApiResponse::send(
                ApiResponse::error('A database error occurred. Please try again.', 500),
                500
            );
        }

        ApiResponse::send(
            ApiResponse::success(
                data: [
                    'results'    => $result['rows'],
                    'pagination' => $this->buildPaginationMeta(
                        $result['total'], $page, $limit
                    ),
                ],
                message: 'OK'
            )
        );
    }

    // ------------------------------------------------------------------
    // GET /api/ordinances/meta?q=&limit=
    // Count-only endpoint — resolves total pages without fetching rows.
    // Useful for pre-rendering the pagination bar before the data lands.
    // ------------------------------------------------------------------
    public function meta(): void
    {
        // $this->assertXhr();

        [$keyword, , $limit] = $this->resolveSearchParams();

        try {
            $ordModel   = new OrdinanceModel(\App\Controllers\DatabaseController::getDatabaseConnection());
            // Offset 0, limit 0 — only the count query runs
            $result = $ordModel->getOrdinancesByTitle($keyword, 0, 0);
        } catch (PDOException $e) {
            error_log('OrdinanceApiController::meta() failure: ' . $e->getMessage());
            ApiResponse::send(
                ApiResponse::error('A database error occurred. Please try again.', 500),
                500
            );
        }

        ApiResponse::send(
            ApiResponse::success(
                data: [
                    'pagination' => $this->buildPaginationMeta($result['total'], 1, $limit),
                ],
                message: 'OK'
            )
        );
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function assertXhr(): void
    {
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
            ApiResponse::send(ApiResponse::error('Not an XHR request.', 400), 400);
        }
    }

    /**
     * @return array{string, int, int}  [keyword, page, limit]
     */
    private function resolveSearchParams(): array
    {
        $keyword = isset($_GET['q'])
            ? htmlspecialchars(trim($_GET['q']), ENT_QUOTES, 'UTF-8')
            : '';

        $limit = isset($_GET['limit'])
            ? max(1, min((int) $_GET['limit'], self::MAX_PER_PAGE))
            : self::DEFAULT_PER_PAGE;

        $page = isset($_GET['page'])
            ? max(1, (int) $_GET['page'])
            : 1;

        return [$keyword, $page, $limit];
    }

    private function buildPaginationMeta(int $total, int $currentPage, int $perPage): array
    {
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 0;

        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $currentPage,
            'total_pages'  => $totalPages,
            'has_prev'     => $currentPage > 1,
            'has_next'     => $currentPage < $totalPages,
        ];
    }
}