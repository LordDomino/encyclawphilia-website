<?php

namespace App\Controllers;

use App\Models\OrdinanceModel;
use App\Services\AdminSearchService;
use App\Core\ApiResponse;

class DashboardController
{
    private function makeAdminSearchService(): AdminSearchService
    {
        $pdo = DatabaseController::getDatabaseConnection();
        return new AdminSearchService(new OrdinanceModel($pdo));
    }

    public function editOrdinance(): void
    {
        require_once __DIR__ . '/../Views/pages/add_ordinance.php';
    }

    public function storeOrdinance(): void
    {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            exit;
        }

        $sessionRoleId = (int)($_SESSION['role_id'] ?? 0);
        if (empty($_SESSION['user_id']) || $sessionRoleId !== 1) {
            header('Location: /login');
            exit;
        }

        $data = [
            'ordinance_number' => trim($_POST['ordinance_number'] ?? ''),
            'title'            => trim($_POST['title'] ?? ''),
            'author_sponsor'   => trim($_POST['author_sponsor'] ?? ''),
            'series_year'      => filter_var($_POST['series_year'] ?? null, FILTER_VALIDATE_INT),
            'category_id'      => filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT),
            'status'           => trim($_POST['status'] ?? 'Pending'),
            'barangay_id'      => filter_var($_POST['barangay_id'] ?? null, FILTER_VALIDATE_INT),
            'date_enacted'     => trim($_POST['date_enacted'] ?? ''),
            'summary'          => trim($_POST['summary'] ?? ''),
            'full_text'        => trim($_POST['full_text'] ?? ''),
            'pdf_file'         => null,
        ];

        $errors = [];
        if ($data['ordinance_number'] === '') {
            $errors[] = 'Ordinance number is required.';
        }
        if ($data['title'] === '') {
            $errors[] = 'Title is required.';
        }
        if ($data['author_sponsor'] === '') {
            $errors[] = 'Author / sponsor is required.';
        }
        if ($data['series_year'] === false || $data['series_year'] < 1900) {
            $errors[] = 'Series year is required and must be valid.';
        }
        if ($data['status'] === '') {
            $errors[] = 'Status is required.';
        }

        if (!empty($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $pdfFile = $_FILES['pdf_file'];
            if ($pdfFile['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'PDF upload failed. Please try again.';
            } else {
                if ($pdfFile['size'] > 20 * 1024 * 1024) {
                    $errors[] = 'PDF file must be 20 MB or smaller.';
                }

                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($pdfFile['tmp_name']);
                if ($mimeType !== 'application/pdf') {
                    $errors[] = 'Only PDF files are accepted.';
                }
            }
        }

        if ($errors !== []) {
            $_SESSION['admin_flash'] = [
                'type'  => 'error',
                'title' => 'Unable to save ordinance',
                'body'  => implode(' ', $errors),
            ];
            header('Location: /add-ordinance');
            exit;
        }

        if (!empty($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $publicDir = realpath(__DIR__ . '/../../public');
            $uploadDir = $publicDir . '/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['pdf_file']['name']));
            $destination = $uploadDir . '/' . uniqid('ordinance_', true) . '_' . $safeName;

            if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $destination)) {
                $_SESSION['admin_flash'] = [
                    'type'  => 'error',
                    'title' => 'Upload failed',
                    'body'  => 'Unable to store the PDF file on the server.',
                ];
                header('Location: /add-ordinance');
                exit;
            }

            $data['pdf_file'] = '/uploads/' . basename($destination);
        }

        if ($data['category_id'] === false) {
            $data['category_id'] = null;
        }
        if ($data['barangay_id'] === false) {
            $data['barangay_id'] = null;
        }
        if ($data['date_enacted'] === '') {
            $data['date_enacted'] = null;
        }
        if ($data['summary'] === '') {
            $data['summary'] = null;
        }
        if ($data['full_text'] === '') {
            $data['full_text'] = null;
        }

        try {
            $pdo = \App\Controllers\DatabaseController::getDatabaseConnection();
            $repository = new OrdinanceModel($pdo);
            $repository->addOrdinanceProcedure($data);

            $_SESSION['admin_flash'] = [
                'type'  => 'success',
                'title' => 'Ordinance created',
                'body'  => 'The ordinance was added successfully.',
            ];
            header('Location: /dashboard');
            exit;
        } catch (\PDOException $e) {
            error_log('Ordinance creation failed: ' . $e->getMessage());
            $_SESSION['admin_flash'] = [
                'type'  => 'error',
                'title' => 'Database error',
                'body'  => 'Unable to save the ordinance. Please try again.',
            ];
            header('Location: /add-ordinance');
            exit;
        }
    }

    public function searchOrdinance(): void
    {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Location: /dashboard');
            ApiResponse::send(
                ApiResponse::error('Request method invalid.', 401),
                401
            );
            exit;
        }

        $query = trim($_GET['q'] ?? '');

        $result = $this->makeAdminSearchService()->search($query);

        if ($result['ok']) {
            ApiResponse::send(
                ApiResponse::success(
                    data: $result
                ),
                httpStatus: 200
            );
            exit;
        }

        header('Location: /dashboard');
        ApiResponse::send(
            ApiResponse::error('Search failed', 401),
            401
        );
        exit;
    }
}
