<?php
/**
 * Konfigurasi Koneksi Database (PDO MySQL) & Helper RESTful API
 * Sistem Informasi Pengelolaan Kendaraan Dinas Operasional (SI-KDO BMD)
 */

$host = 'localhost';
$db   = 'db_kendaraan_dinas_operasional';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode([
        'status'  => 'error',
        'message' => 'Koneksi database gagal: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('api_headers')) {
    /**
     * Set header keamanan dasar & CORS untuk endpoint RESTful API
     */
    function api_headers(string $allowedMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS'): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Methods: {$allowedMethods}");
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Kirim response JSON standar dan hentikan eksekusi
     */
    function json_response(array $payload, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Membaca input request dari JSON body (php://input), POST form, atau query string
     */
    function get_request_input(): array
    {
        $raw = file_get_contents('php://input');
        $json = [];
        if ($raw !== false && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $json = $decoded;
            } else {
                parse_str($raw, $parsed);
                if (is_array($parsed)) {
                    $json = $parsed;
                }
            }
        }
        return array_merge($_GET, $_POST, $json);
    }

    /**
     * Sanitasi string input dasar
     */
    function clean_str($value): string
    {
        if ($value === null || is_array($value)) {
            return '';
        }
        return trim(strip_tags((string)$value));
    }
}
