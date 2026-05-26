<?php
declare(strict_types=1);

// Load .env if present
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (!str_starts_with(trim($line), '#') && str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v);
        }
    }
}

// CORS
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/DestinationController.php';
require_once __DIR__ . '/controllers/TransportController.php';
require_once __DIR__ . '/controllers/AccommodationController.php';
require_once __DIR__ . '/controllers/ActivityController.php';
require_once __DIR__ . '/controllers/ItineraryController.php';
require_once __DIR__ . '/controllers/ReservationController.php';
require_once __DIR__ . '/controllers/NotificationController.php';
require_once __DIR__ . '/controllers/UniversityController.php';

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = '/' . ltrim(preg_replace('#^/api#', '', $path), '/');
$parts  = explode('/', trim($path, '/'));
$resource = $parts[0] ?? '';
$id       = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;
$sub      = $parts[1] ?? null;
// $action : troisième segment de l'URL — ex. "contact" dans /universities/1/contact
$action   = $parts[2] ?? null;

$body = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    $db = Database::connect();
    $response = match($resource) {
        'users'          => (new UserController($db))->handle($method, $id, $body),
        'destinations'   => (new DestinationController($db))->handle($method, $id, $body),
        'transports'     => (new TransportController($db))->handle($method, $id, $body),
        'accommodations' => (new AccommodationController($db))->handle($method, $id, $body),
        'activities'     => (new ActivityController($db))->handle($method, $id, $body),
        'itineraries'    => (new ItineraryController($db))->handle($method, $id, $body),
        'reservations'   => (new ReservationController($db))->handle($method, $id, $body),
        'notifications'  => (new NotificationController($db))->handle($method, $id, $body),
        'universities'   => (new UniversityController($db))->handle($method, $id, $body, $action),
        'auth'           => (new UserController($db))->handleAuth($sub, $body),
        default          => ['error' => 'Route not found'],
    };
    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
