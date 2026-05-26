<?php
class ReservationController {
    public function __construct(private PDO $db) {}

    public function handle(string $method, ?int $id, array $body): array {
        return match($method) {
            'GET'    => $id ? $this->show($id) : $this->index(),
            'POST'   => $this->store($body),
            'PUT'    => $this->update($id, $body),
            'DELETE' => $this->destroy($id),
            default  => $this->notAllowed(),
        };
    }

    private function index(): array {
        $rows = $this->db->query(
            "SELECT r.*, u.name AS user_name FROM reservations r
             LEFT JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC"
        )->fetchAll();
        return ['data' => $rows];
    }

    private function show(int $id): array {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.name AS user_name FROM reservations r
             LEFT JOIN users u ON u.id = r.user_id WHERE r.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? ['data' => $row] : $this->notFound();
    }

    private function store(array $data): array {
        foreach (['user_id', 'type', 'reference_id', 'total_price'] as $f) {
            if (empty($data[$f]) && $data[$f] !== 0) { http_response_code(422); return ['error' => "$f is required"]; }
        }
        $stmt = $this->db->prepare(
            "INSERT INTO reservations (user_id, itinerary_id, type, reference_id, status, check_in, check_out, quantity, total_price, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$data['user_id'], $data['itinerary_id'] ?? null, $data['type'], $data['reference_id'], $data['status'] ?? 'pending', $data['check_in'] ?? null, $data['check_out'] ?? null, $data['quantity'] ?? 1, $data['total_price'], $data['notes'] ?? null]);
        http_response_code(201);
        return ['data' => ['id' => (int)$this->db->lastInsertId()]];
    }

    private function update(?int $id, array $data): array {
        if (!$id) return $this->notFound();
        $stmt = $this->db->prepare(
            "UPDATE reservations SET status=?, check_in=?, check_out=?, quantity=?, total_price=?, notes=? WHERE id=?"
        );
        $stmt->execute([$data['status'] ?? 'pending', $data['check_in'] ?? null, $data['check_out'] ?? null, $data['quantity'] ?? 1, $data['total_price'], $data['notes'] ?? null, $id]);
        return ['success' => true];
    }

    private function destroy(?int $id): array {
        if (!$id) return $this->notFound();
        $this->db->prepare("DELETE FROM reservations WHERE id = ?")->execute([$id]);
        return ['success' => true];
    }

    private function notFound(): array { http_response_code(404); return ['error' => 'Not found']; }
    private function notAllowed(): array { http_response_code(405); return ['error' => 'Method not allowed']; }
}
