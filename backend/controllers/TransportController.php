<?php
class TransportController {
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
        $rows = $this->db->query("SELECT * FROM transports ORDER BY departure_time ASC")->fetchAll();
        return ['data' => $rows];
    }

    private function show(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM transports WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? ['data' => $row] : $this->notFound();
    }

    private function store(array $data): array {
        $required = ['type', 'departure_location', 'arrival_location', 'departure_time', 'arrival_time', 'price'];
        foreach ($required as $f) {
            if (empty($data[$f])) { http_response_code(422); return ['error' => "$f is required"]; }
        }
        $stmt = $this->db->prepare(
            "INSERT INTO transports (type, company, departure_location, arrival_location, departure_time, arrival_time, price, seats_available, destination_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$data['type'], $data['company'] ?? null, $data['departure_location'], $data['arrival_location'], $data['departure_time'], $data['arrival_time'], $data['price'], $data['seats_available'] ?? 0, $data['destination_id'] ?? null]);
        http_response_code(201);
        return ['data' => ['id' => (int)$this->db->lastInsertId()]];
    }

    private function update(?int $id, array $data): array {
        if (!$id) return $this->notFound();
        $stmt = $this->db->prepare(
            "UPDATE transports SET type=?, company=?, departure_location=?, arrival_location=?, departure_time=?, arrival_time=?, price=?, seats_available=?, destination_id=? WHERE id=?"
        );
        $stmt->execute([$data['type'], $data['company'] ?? null, $data['departure_location'], $data['arrival_location'], $data['departure_time'], $data['arrival_time'], $data['price'], $data['seats_available'] ?? 0, $data['destination_id'] ?? null, $id]);
        return ['success' => true];
    }

    private function destroy(?int $id): array {
        if (!$id) return $this->notFound();
        $this->db->prepare("DELETE FROM transports WHERE id = ?")->execute([$id]);
        return ['success' => true];
    }

    private function notFound(): array { http_response_code(404); return ['error' => 'Not found']; }
    private function notAllowed(): array { http_response_code(405); return ['error' => 'Method not allowed']; }
}
