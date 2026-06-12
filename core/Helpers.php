<?php

namespace Core;

class Helpers
{
    public static function formatMoney($amount, bool $symbol = true): string
    {
        $formatted = number_format((float) $amount, 2);
        return $symbol ? CURRENCY_SYMBOL . ' ' . $formatted : $formatted;
    }

    public static function formatDate(?string $date, string $format = DATE_FORMAT): string
    {
        if (!$date) {
            return '';
        }
        return date($format, strtotime($date));
    }

    public static function formatDateTime(?string $datetime): string
    {
        return self::formatDate($datetime, DATETIME_FORMAT);
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    public static function generateProductCode(
        ?string $categoryCode,
        ?string $productType,
        ?string $size,
        ?string $origin,
        int $tenantId,
        \PDO $db
    ): string {
        $parts = [];

        $parts[] = $categoryCode ? str_pad(preg_replace('/\D/', '', $categoryCode) ?: '00', 2, '0', STR_PAD_LEFT) : self::nextSeqPart($tenantId, $db, 'cat');

        $parts[] = $productType
            ? strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $productType), 0, 4))
            : self::nextSeqPart($tenantId, $db, 'type');

        $parts[] = $size
            ? strtoupper(preg_replace('/\s+/', '', $size))
            : self::nextSeqPart($tenantId, $db, 'size');

        $originMap = ['china' => 'CHN', 'pakistan' => 'PAK', 'other' => 'OTH'];
        $originKey = strtolower(trim($origin ?? ''));
        $parts[] = $originMap[$originKey] ?? ($origin ? strtoupper(substr($origin, 0, 3)) : self::nextSeqPart($tenantId, $db, 'origin'));

        $code = implode('-', $parts);

        $stmt = $db->prepare(
            "SELECT COUNT(*) as cnt FROM products WHERE tenant_id = :tid AND product_code = :code AND is_deleted = 0"
        );
        $stmt->execute(['tid' => $tenantId, 'code' => $code]);
        if ((int) $stmt->fetch()['cnt'] > 0) {
            $code .= '-' . self::nextSeqPart($tenantId, $db, 'dup');
        }

        return $code;
    }

    private static function nextSeqPart(int $tenantId, \PDO $db, string $type): string
    {
        static $counters = [];
        $key = "{$tenantId}_{$type}";
        if (!isset($counters[$key])) {
            $counters[$key] = 1;
        }
        return str_pad((string) $counters[$key]++, 2, '0', STR_PAD_LEFT);
    }

    public static function generateBarcode(int $productId, int $tenantId): string
    {
        return str_pad((string) $tenantId, 4, '0', STR_PAD_LEFT) . str_pad((string) $productId, 8, '0', STR_PAD_LEFT);
    }

    public static function generateSaleNumber(int $tenantId, \PDO $db, string $shopSlug = ''): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $shopSlug) ?: 'SHOP', 0, 4));
        $date = date('Ymd');

        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "SELECT id, last_sequence FROM sale_sequences 
                 WHERE tenant_id = :tid AND sale_date = CURDATE() FOR UPDATE"
            );
            $stmt->execute(['tid' => $tenantId]);
            $row = $stmt->fetch();

            if ($row) {
                $seq = $row['last_sequence'] + 1;
                $upd = $db->prepare("UPDATE sale_sequences SET last_sequence = :seq WHERE id = :id");
                $upd->execute(['seq' => $seq, 'id' => $row['id']]);
            } else {
                $seq = 1;
                $ins = $db->prepare(
                    "INSERT INTO sale_sequences (tenant_id, sale_date, last_sequence) VALUES (:tid, CURDATE(), 1)"
                );
                $ins->execute(['tid' => $tenantId]);
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            $seq = random_int(1000, 9999);
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $seq);
    }

    public static function validateImage(array $file): array
    {
        $errors = [];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = 'File upload failed.';
            }
            return $errors;
        }
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            $errors[] = 'File size exceeds 2MB limit.';
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
            $errors[] = 'Invalid image type. Allowed: JPEG, PNG, WebP, GIF.';
        }
        return $errors;
    }

    public static function productImageUrl(?int $productId): ?string
    {
        if (!$productId) {
            return null;
        }
        return Auth::baseUrl('products/image?id=' . $productId);
    }

    public static function shopLogoUrl(): string
    {
        return Auth::baseUrl('settings/logo');
    }

    public static function serveUploadFile(string $relativePath): void
    {
        $relativePath = str_replace(['\\', '..'], ['/', ''], $relativePath);
        $relativePath = ltrim($relativePath, '/');

        if (!preg_match('#^(products|shops|logos)/[a-zA-Z0-9._-]+$#', $relativePath)) {
            http_response_code(404);
            exit;
        }

        $fullPath = dirname(__DIR__) . '/uploads/' . $relativePath;
        if (!is_file($fullPath)) {
            http_response_code(404);
            exit;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        header('Content-Type: ' . $finfo->file($fullPath));
        header('Content-Length: ' . (string) filesize($fullPath));
        header('Cache-Control: private, max-age=86400');
        readfile($fullPath);
        exit;
    }

    public static function uploadImage(array $file, string $subdir = 'products'): ?string
    {
        $errors = self::validateImage($file);
        if ($errors) {
            return null;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = hash('sha256', uniqid('', true)) . '.' . strtolower($ext);
        $dir = dirname(__DIR__) . '/uploads/' . $subdir;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dest = $dir . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return $subdir . '/' . $filename;
        }
        return null;
    }

    public static function paginationHtml(array $paginated, string $baseUrl): string
    {
        if ($paginated['last_page'] <= 1) {
            return '';
        }

        $html = '<nav><ul class="pagination justify-content-center">';
        $page = $paginated['current_page'];
        $last = $paginated['last_page'];

        $sep = str_contains($baseUrl, '?') ? '&' : '?';

        if ($page > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseUrl . $sep . 'page=' . ($page - 1)) . '">&laquo;</a></li>';
        }

        for ($i = max(1, $page - 2); $i <= min($last, $page + 2); $i++) {
            $active = $i === $page ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . htmlspecialchars($baseUrl . $sep . 'page=' . $i) . '">' . $i . '</a></li>';
        }

        if ($page < $last) {
            $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseUrl . $sep . 'page=' . ($page + 1)) . '">&raquo;</a></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }

    public static function sortLink(string $column, string $label, string $currentSort, string $currentDir, string $baseUrl): string
    {
        $dir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
        $icon = '';
        if ($currentSort === $column) {
            $icon = $currentDir === 'asc' ? ' &uarr;' : ' &darr;';
        }
        $sep = str_contains($baseUrl, '?') ? '&' : '?';
        return '<a href="' . htmlspecialchars($baseUrl . $sep . 'sort=' . $column . '&dir=' . $dir) . '">' . htmlspecialchars($label) . $icon . '</a>';
    }

    public static function parseFilterDate(?string $input, ?string $default = null): ?string
    {
        if ($input === null || $input === '') {
            return $default;
        }
        $input = trim($input);
        $dt = \DateTime::createFromFormat('d-m-Y', $input);
        if ($dt && $dt->format('d-m-Y') === $input) {
            return $dt->format('Y-m-d');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
            return $input;
        }
        return $default;
    }

    public static function defaultDateRange(): array
    {
        return [
            'from' => date('Y-m-01'),
            'to'   => date('Y-m-d'),
            'from_display' => date('d-m-Y', strtotime('first day of this month')),
            'to_display'   => date('d-m-Y'),
        ];
    }
}
