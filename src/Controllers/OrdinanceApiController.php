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
        $this->assertXhr();

        [$keyword, $page, $limit] = $this->resolveSearchParams();
        $filters = $this->resolveFilterParams();
        $offset  = ($page - 1) * $limit;

        try {
            $result = (new OrdinanceModel(\App\Controllers\DatabaseController::getDatabaseConnection()))
                ->searchOrdinances(
                    keyword: $keyword,
                    categoryIds: $filters['categoryIds'],
                    dateFrom: $filters['dateFrom'],
                    dateTo: $filters['dateTo'],
                    statuses: $filters['statuses'],
                    hasSummary: $filters['hasSummary'],
                    hasFullText: $filters['hasFullText'],
                    hasPdf: $filters['hasPdf'],
                    sortBy: $filters['sortBy'],
                    sortDir: $filters['sortDir'],
                    limit: $limit,
                    offset: $offset,
                );
        } catch (PDOException $e) {
            error_log('OrdinanceApiController::search() failure: ' . $e->getMessage());
            ApiResponse::send(ApiResponse::error('A database error occurred. Please try again.', 500), 500);
        }

        // searchOrdinances() returns ordinance_number_display (SUBSTRING_INDEX slice) and
        // ordinance_number_full (raw). Normalize to ordinance_number for the JS card template.
        $rows = array_map(static function (array $row): array {
            $row['ordinance_number'] = $row['ordinance_number_display'] ?? '';
            unset($row['ordinance_number_display'], $row['ordinance_number_full'], $row['date_enacted']);
            return $row;
        }, $result['ordinances']);

        ApiResponse::send(
            ApiResponse::success(
                data: [
                    'results'    => $rows,
                    'pagination' => $this->buildPaginationMeta($result['total'], $page, $limit),
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
        $this->assertXhr();

        [$keyword,, $limit] = $this->resolveSearchParams();
        $filters = $this->resolveFilterParams();

        try {
            $result = (new OrdinanceModel(\App\Controllers\DatabaseController::getDatabaseConnection()))
                ->searchOrdinances(
                    keyword: $keyword,
                    categoryIds: $filters['categoryIds'],
                    dateFrom: $filters['dateFrom'],
                    dateTo: $filters['dateTo'],
                    statuses: $filters['statuses'],
                    hasSummary: $filters['hasSummary'],
                    hasFullText: $filters['hasFullText'],
                    hasPdf: $filters['hasPdf'],
                    sortBy: $filters['sortBy'],
                    sortDir: $filters['sortDir'],
                    limit: 1,   // minimum enforced by model; we only need total
                    offset: 0,
                );
        } catch (PDOException $e) {
            error_log('OrdinanceApiController::meta() failure: ' . $e->getMessage());
            ApiResponse::send(ApiResponse::error('A database error occurred. Please try again.', 500), 500);
        }

        ApiResponse::send(
            ApiResponse::success(
                data: ['pagination' => $this->buildPaginationMeta($result['total'], 1, $limit)],
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

    /**
     * Parses, validates, and normalises all sidebar filter parameters from $_GET.
     * All values are sanitised at this layer; the model layer performs its own
     * whitelist validation as a second line of defence.
     *
     * @return array{
     *   categoryIds: int[],
     *   dateFrom: string,
     *   dateTo: string,
     *   statuses: string[],
     *   hasSummary: bool,
     *   hasFullText: bool,
     *   hasPdf: bool,
     *   sortBy: string,
     *   sortDir: string,
     * }
     */
    private function resolveFilterParams(): array
    {
        // Category: single select → zero or one ID
        $rawCategory = $_GET['category_id'] ?? '';
        $categoryIds = ($rawCategory !== '' && ctype_digit((string) $rawCategory))
            ? [(int) $rawCategory]
            : [];

        // Date range: must be valid YYYY-MM-DD; reject anything else silently
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
        $dateFrom    = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
        $dateTo      = isset($_GET['date_to'])   ? trim($_GET['date_to'])   : '';
        if (!preg_match($datePattern, $dateFrom)) $dateFrom = '';
        if (!preg_match($datePattern, $dateTo))   $dateTo   = '';

        // Statuses: PHP converts status[]=Active to $_GET['status'] = ['Active', ...]
        $allowedStatuses = ['Pending', 'Active', 'Repealed', 'Amended'];
        $rawStatuses     = (isset($_GET['status']) && is_array($_GET['status']))
            ? $_GET['status']
            : [];
        $statuses = array_values(array_intersect($rawStatuses, $allowedStatuses));

        // Content-availability flags
        $hasSummary  = (($_GET['has_summary']   ?? '') === '1');
        $hasFullText = (($_GET['has_full_text'] ?? '') === '1');
        $hasPdf      = (($_GET['has_pdf']       ?? '') === '1');

        // Sort column — whitelist enforced here and again inside searchOrdinances()
        $allowedSortBy = ['date_enacted', 'created_at', 'title', 'series_year'];
        $sortBy = (isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowedSortBy, true))
            ? $_GET['sort_by']
            : 'date_enacted';

        // Sort direction
        $sortDir = (isset($_GET['sort_dir']) && strtoupper($_GET['sort_dir']) === 'ASC')
            ? 'ASC'
            : 'DESC';

        return compact(
            'categoryIds',
            'dateFrom',
            'dateTo',
            'statuses',
            'hasSummary',
            'hasFullText',
            'hasPdf',
            'sortBy',
            'sortDir'
        );
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
