<?php
/**
 * CÍVICA EQUIPAMENTOS — API de Equipamentos
 * GET    /api/machines.php        → lista todos (público)
 * POST   /api/machines.php        → criar (requer sessão admin)
 * PUT    /api/machines.php?id=X   → atualizar (requer sessão admin)
 * DELETE /api/machines.php?id=X   → eliminar (requer sessão admin)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Preflight CORS (same-site, mas necessário para alguns browsers)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once __DIR__ . '/config.php';

// ── Auth check ─────────────────────────────────────────────────────
function requireAdmin() {
    if (!isset($_SESSION['civica_admin']) || $_SESSION['civica_admin'] !== true) {
        http_response_code(401);
        echo json_encode(['error' => 'Não autorizado. Faça login primeiro.']);
        exit;
    }
}

// ── DB connection ──────────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Não foi possível ligar à base de dados. Verifique as credenciais em api/config.php']);
        exit;
    }
}

// ── Format machine row ─────────────────────────────────────────────
function formatMachine(array $m): array {
    $m['specs']     = json_decode($m['specs'] ?? '[]', true) ?: [];
    $m['available'] = (bool)(int)$m['available'];
    $m['featured']  = (bool)(int)$m['featured'];
    $m['price']     = (float)$m['price'];
    $m['createdAt'] = (int)$m['created_at'];
    unset($m['created_at']);
    return $m;
}

// ── Router ────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? trim($_GET['id']) : null;

try {
    $db = getDB();

    // ── GET ──────────────────────────────────────────────────────
    if ($method === 'GET') {
        if ($id) {
            $stmt = $db->prepare('SELECT * FROM civica_machines WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) { http_response_code(404); echo json_encode(['error' => 'Equipamento não encontrado']); exit; }
            echo json_encode(formatMachine($row));
        } else {
            $stmt = $db->query('SELECT * FROM civica_machines ORDER BY created_at DESC');
            $rows = $stmt->fetchAll();
            echo json_encode(array_map('formatMachine', $rows));
        }
        exit;
    }

    // ── POST (create) ────────────────────────────────────────────
    if ($method === 'POST') {
        requireAdmin();
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['name']) || empty($data['category'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nome e categoria são obrigatórios']);
            exit;
        }
        $newId = 'm' . time() . rand(100, 999);
        $stmt  = $db->prepare(
            'INSERT INTO civica_machines
             (id, name, category, type, description, price, price_unit, image, specs, available, featured, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $newId,
            substr($data['name'], 0, 200),
            substr($data['category'], 0, 100),
            $data['type'] ?? 'aluguer',
            $data['description'] ?? '',
            (float)($data['price'] ?? 0),
            $data['priceUnit'] ?? 'dia',
            substr($data['image'] ?? '', 0, 500),
            json_encode($data['specs'] ?? []),
            ($data['available'] ?? true) ? 1 : 0,
            ($data['featured'] ?? false) ? 1 : 0,
            round(microtime(true) * 1000),
        ]);
        echo json_encode(['id' => $newId, 'success' => true]);
        exit;
    }

    // ── PUT (update) ──────────────────────────────────────────────
    if ($method === 'PUT') {
        requireAdmin();
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID obrigatório']); exit; }
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare(
            'UPDATE civica_machines SET
             name=?, category=?, type=?, description=?,
             price=?, price_unit=?, image=?, specs=?,
             available=?, featured=?
             WHERE id=?'
        );
        $stmt->execute([
            substr($data['name'] ?? '', 0, 200),
            substr($data['category'] ?? '', 0, 100),
            $data['type'] ?? 'aluguer',
            $data['description'] ?? '',
            (float)($data['price'] ?? 0),
            $data['priceUnit'] ?? 'dia',
            substr($data['image'] ?? '', 0, 500),
            json_encode($data['specs'] ?? []),
            ($data['available'] ?? true) ? 1 : 0,
            ($data['featured'] ?? false) ? 1 : 0,
            $id,
        ]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ── DELETE ────────────────────────────────────────────────────
    if ($method === 'DELETE') {
        requireAdmin();
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID obrigatório']); exit; }
        $stmt = $db->prepare('DELETE FROM civica_machines WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método não suportado']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro na base de dados: ' . $e->getMessage()]);
}
