<?php
class UniversityController {
    public function __construct(private PDO $db) {}

    public function handle(string $method, ?int $id, array $body, ?string $action = null): array {
        // Route spéciale : POST /universities/{id}/contact
        if ($method === 'POST' && $id && $action === 'contact') {
            return $this->contact($id, $body);
        }
        return match($method) {
            'GET'    => $id ? $this->show($id) : $this->index(),
            'POST'   => $this->store($body),
            default  => $this->notAllowed(),
        };
    }

    // GET /universities — liste avec filtres optionnels (country, langue)
    private function index(): array {
        $where  = [];
        $params = [];

        // Filtre par pays (correspondance exacte)
        if (!empty($_GET['country'])) {
            $where[]  = 'u.country = ?';
            $params[] = htmlspecialchars(trim($_GET['country']), ENT_QUOTES, 'UTF-8');
        }
        // Filtre par langue (correspondance partielle — "Anglais" correspond à "Allemand, Anglais")
        if (!empty($_GET['langue'])) {
            // Echapper les caractères spéciaux LIKE avant de construire le pattern
            $safe     = str_replace(['%', '_'], ['\\%', '\\_'], trim($_GET['langue']));
            $where[]  = 'u.langue LIKE ?';
            $params[] = "%$safe%";
        }

        $sql = "SELECT u.id, u.name, u.city, u.country, u.website, u.erasmus_code,
                       u.langue, u.email_contact, u.description, u.nb_etudiants_etrangers,
                       u.destination_id
                FROM universities u";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY u.country ASC, u.name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $universities = $stmt->fetchAll();

        // Options de filtres : listes distinctes de tous les pays et langues existants
        $countries = $this->db->query(
            "SELECT DISTINCT country FROM universities ORDER BY country ASC"
        )->fetchAll(PDO::FETCH_COLUMN);

        // Langues : on récupère toutes les valeurs brutes (peut contenir "Anglais, Français")
        $langues = $this->db->query(
            "SELECT DISTINCT langue FROM universities WHERE langue IS NOT NULL ORDER BY langue ASC"
        )->fetchAll(PDO::FETCH_COLUMN);

        return [
            'data'    => $universities,
            'filters' => ['countries' => $countries, 'langues' => $langues],
        ];
    }

    // GET /universities/{id} — fiche complète + logements associés
    private function show(int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM universities WHERE id = ?");
        $stmt->execute([$id]);
        $uni = $stmt->fetch();
        if (!$uni) return $this->notFound();

        // Logements étudiants liés à cette université (résidences, colocations…)
        $stmt2 = $this->db->prepare(
            "SELECT id, name, type, ville, address, prix_nuit, price_per_month,
                    distance_campus_km, available_rooms, description
             FROM student_housing
             WHERE university_id = ?
             ORDER BY distance_campus_km ASC"
        );
        $stmt2->execute([$id]);
        $housing = $stmt2->fetchAll();

        return ['data' => array_merge($uni, ['housing' => $housing])];
    }

    // POST /universities/{id}/contact — stocke le message de contact en BDD
    private function contact(int $universityId, array $body): array {
        // Vérification que l'université existe
        $check = $this->db->prepare("SELECT id FROM universities WHERE id = ?");
        $check->execute([$universityId]);
        if (!$check->fetch()) return $this->notFound();

        // Validation des champs obligatoires
        $required = ['subject', 'message', 'sender_name', 'sender_email'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                http_response_code(422);
                return ['error' => "Le champ '$field' est requis"];
            }
        }
        if (!filter_var($body['sender_email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            return ['error' => 'Format email invalide'];
        }
        if (mb_strlen(trim($body['message'])) < 20) {
            http_response_code(422);
            return ['error' => 'Le message doit contenir au moins 20 caractères'];
        }

        // Sanitisation XSS : protection contre l'injection de balises HTML
        $subject     = htmlspecialchars(trim($body['subject']),      ENT_QUOTES, 'UTF-8');
        $message     = htmlspecialchars(trim($body['message']),      ENT_QUOTES, 'UTF-8');
        $senderName  = htmlspecialchars(trim($body['sender_name']),  ENT_QUOTES, 'UTF-8');
        $senderEmail = htmlspecialchars(trim($body['sender_email']), ENT_QUOTES, 'UTF-8');
        // user_id optionnel : fourni si l'expéditeur est connecté
        $userId = isset($body['user_id']) && is_numeric($body['user_id']) ? (int)$body['user_id'] : null;

        $stmt = $this->db->prepare(
            "INSERT INTO university_contacts
             (university_id, user_id, subject, message, sender_name, sender_email)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$universityId, $userId, $subject, $message, $senderName, $senderEmail]);

        http_response_code(201);
        return [
            'success' => true,
            'message' => 'Votre message a été transmis au bureau des relations internationales.',
        ];
    }

    // POST /universities — création d'une fiche université (admin)
    private function store(array $data): array {
        if (empty($data['name']) || empty($data['city']) || empty($data['country'])) {
            http_response_code(422);
            return ['error' => 'name, city et country sont requis'];
        }
        $stmt = $this->db->prepare(
            "INSERT INTO universities
             (name, city, country, website, erasmus_code, langue, email_contact,
              description, nb_etudiants_etrangers, destination_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            htmlspecialchars($data['name'],    ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($data['city'],    ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($data['country'], ENT_QUOTES, 'UTF-8'),
            $data['website']               ?? null,
            $data['erasmus_code']          ?? null,
            $data['langue']                ?? null,
            $data['email_contact']         ?? null,
            $data['description']           ?? null,
            $data['nb_etudiants_etrangers'] ?? 0,
            $data['destination_id']        ?? null,
        ]);
        http_response_code(201);
        return ['data' => ['id' => (int)$this->db->lastInsertId()]];
    }

    private function notFound(): array {
        http_response_code(404);
        return ['error' => 'Université introuvable'];
    }

    private function notAllowed(): array {
        http_response_code(405);
        return ['error' => 'Méthode non autorisée'];
    }
}
