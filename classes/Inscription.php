<?php
// /gestion-evenements/classes/Inscription.php  (REMPLACE TOUT LE FICHIER)
// ✅ Version compatible avec ta table (colonnes optionnelles) + calendrier FullCalendar (événements du user connecté)

require_once __DIR__ . '/Database.php';

class Inscription extends Database {

    private function columnExists(string $table, string $column): bool
    {
        $sql = "SELECT COUNT(*) AS c
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :t
                  AND COLUMN_NAME = :c";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':t' => $table, ':c' => $column]);
        $row = $stmt->fetch();
        return !empty($row['c']);
    }

    /* ======================================================
       INSCRIRE
    ====================================================== */
    public function inscrire($evenementId, $userId, $data) {
        if ($this->isEvenementComplet($evenementId)) {
            return ['success' => false, 'message' => 'Cet événement est complet'];
        }

        if ($this->isDejaInscrit($evenementId, $userId)) {
            return ['success' => false, 'message' => 'Vous êtes déjà inscrit à cet événement'];
        }

        $hasToken = $this->columnExists('inscriptions', 'ticket_token');

        $token = null;

        if ($hasToken) {
            $token = bin2hex(random_bytes(16));

            $sql = "INSERT INTO inscriptions (evenement_id, user_id, nom_participant, email_participant, telephone, statut, ticket_token)
                    VALUES (:evenement_id, :user_id, :nom, :email, :telephone, 'confirme', :token)";
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute([
                ':evenement_id' => $evenementId,
                ':user_id'      => $userId,
                ':nom'          => $data['nom'],
                ':email'        => $data['email'],
                ':telephone'    => $data['telephone'] ?? null,
                ':token'        => $token
            ]);
        } else {
            $sql = "INSERT INTO inscriptions (evenement_id, user_id, nom_participant, email_participant, telephone, statut)
                    VALUES (:evenement_id, :user_id, :nom, :email, :telephone, 'confirme')";
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute([
                ':evenement_id' => $evenementId,
                ':user_id'      => $userId,
                ':nom'          => $data['nom'],
                ':email'        => $data['email'],
                ':telephone'    => $data['telephone'] ?? null
            ]);
        }

        if (!$ok) {
            return ['success' => false, 'message' => 'Erreur lors de l\'inscription'];
        }

        $inscriptionId = (int)$this->pdo->lastInsertId();

        if ($hasToken && empty($token)) {
            $token = $this->ensureTicketToken($inscriptionId);
        }

        return [
            'success'        => true,
            'message'        => 'Inscription réussie',
            'inscription_id' => $inscriptionId,
            'ticket_token'   => $token
        ];
    }

    /* ======================================================
       HELPERS
    ====================================================== */
    private function isEvenementComplet($evenementId) {
        $sql = "SELECT e.nb_max_participants,
                       COUNT(i.id) as nb_inscrits
                FROM evenements e
                LEFT JOIN inscriptions i
                    ON e.id = i.evenement_id AND i.statut = 'confirme'
                WHERE e.id = :id
                GROUP BY e.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $evenementId]);
        $result = $stmt->fetch();

        return $result && ((int)$result['nb_inscrits'] >= (int)$result['nb_max_participants']);
    }

    private function isDejaInscrit($evenementId, $userId) {
        $sql = "SELECT COUNT(*) as count
                FROM inscriptions
                WHERE evenement_id = :evenement_id AND user_id = :user_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':evenement_id' => $evenementId, ':user_id' => $userId]);
        $result = $stmt->fetch();

        return ((int)$result['count']) > 0;
    }

    /* ======================================================
       ADMIN / LISTES
    ====================================================== */
    public function getInscritsByEvenement($evenementId) {
        $sql = "SELECT i.*, u.nom, u.prenom, u.email
                FROM inscriptions i
                INNER JOIN users u ON i.user_id = u.id
                WHERE i.evenement_id = :evenement_id
                ORDER BY i.date_inscription DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':evenement_id' => $evenementId]);
        return $stmt->fetchAll();
    }

    public function getMesInscriptions($userId) {
        // ✅ Ajout heure_fin pour usage éventuel (calendrier / détails)
        $sql = "SELECT i.*, e.titre, e.date_evenement, e.heure_debut, e.heure_fin, e.lieu, e.image, c.nom as categorie
                FROM inscriptions i
                INNER JOIN evenements e ON i.evenement_id = e.id
                LEFT JOIN categories c ON e.categorie_id = c.id
                WHERE i.user_id = :user_id
                ORDER BY e.date_evenement DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    // ✅ Pour FullCalendar (événements où le user est inscrit)
    public function getMesInscriptionsCalendrier(int $userId): array
    {
        $sql = "SELECT
                    e.id,
                    e.titre,
                    e.date_evenement,
                    e.heure_debut,
                    e.heure_fin,
                    e.lieu,
                    c.nom AS categorie
                FROM inscriptions i
                INNER JOIN evenements e ON e.id = i.evenement_id
                LEFT JOIN categories c ON c.id = e.categorie_id
                WHERE i.user_id = :user_id
                ORDER BY e.date_evenement ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /* ======================================================
       TICKETS (PDF / TOKEN)
    ====================================================== */
    public function getInscriptionByIdForUser(int $inscriptionId, int $userId): ?array
    {
        $sql = "SELECT * FROM inscriptions WHERE id = :id AND user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $inscriptionId, ':user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function ensureTicketToken(int $inscriptionId): string
    {
        if (!$this->columnExists('inscriptions', 'ticket_token')) {
            return '';
        }

        $stmt = $this->pdo->prepare("SELECT ticket_token FROM inscriptions WHERE id = :id");
        $stmt->execute([':id' => $inscriptionId]);
        $row = $stmt->fetch();

        if ($row && !empty($row['ticket_token'])) {
            return (string)$row['ticket_token'];
        }

        $token = bin2hex(random_bytes(16));

        $stmt2 = $this->pdo->prepare("UPDATE inscriptions SET ticket_token = :token WHERE id = :id");
        $stmt2->execute([':token' => $token, ':id' => $inscriptionId]);

        return $token;
    }

    public function saveTicketPdfPath(int $inscriptionId, string $pdfWebPath): void
    {
        if (!$this->columnExists('inscriptions', 'ticket_pdf_path')) {
            return;
        }

        $hasCreatedAt = $this->columnExists('inscriptions', 'ticket_created_at');

        if ($hasCreatedAt) {
            $sql = "UPDATE inscriptions
                    SET ticket_pdf_path = :path, ticket_created_at = NOW()
                    WHERE id = :id";
        } else {
            $sql = "UPDATE inscriptions
                    SET ticket_pdf_path = :path
                    WHERE id = :id";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':path' => $pdfWebPath, ':id' => $inscriptionId]);
    }

    public function getTicketDataByInscriptionIdForUser(int $inscriptionId, int $userId): ?array
    {
        $hasTok = $this->columnExists('inscriptions', 'ticket_token');
        $hasPdf = $this->columnExists('inscriptions', 'ticket_pdf_path');

        $selectTok = $hasTok ? "i.ticket_token AS ticket_token," : "NULL AS ticket_token,";
        $selectPdf = $hasPdf ? "i.ticket_pdf_path AS ticket_pdf_path," : "NULL AS ticket_pdf_path,";

        $sql = "SELECT
                    i.id AS inscription_id,
                    {$selectTok}
                    {$selectPdf}
                    i.nom_participant,
                    i.email_participant,
                    i.telephone,
                    e.id AS event_id,
                    e.titre,
                    e.date_evenement,
                    e.heure_debut,
                    e.heure_fin,
                    e.lieu,
                    e.contact_email,
                    e.contact_phone
                FROM inscriptions i
                INNER JOIN evenements e ON e.id = i.evenement_id
                WHERE i.id = :inscription_id AND i.user_id = :user_id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':inscription_id' => $inscriptionId, ':user_id' => $userId]);
        $row = $stmt->fetch();

        if (!$row) return null;

        if ($hasTok && empty($row['ticket_token'])) {
            $row['ticket_token'] = $this->ensureTicketToken((int)$row['inscription_id']);
        }

        return $row;
    }

    public function getTicketDataByToken(string $token): ?array
    {
        if (!$this->columnExists('inscriptions', 'ticket_token')) {
            return null;
        }

        $sql = "SELECT
                    i.ticket_token,
                    i.nom_participant,
                    i.email_participant,
                    i.telephone,
                    i.date_inscription,
                    i.statut,
                    e.titre,
                    e.date_evenement,
                    e.heure_debut,
                    e.heure_fin,
                    e.lieu,
                    e.contact_email,
                    e.contact_phone
                FROM inscriptions i
                INNER JOIN evenements e ON e.id = i.evenement_id
                WHERE i.ticket_token = :token
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /* ======================================================
       ANNULER
    ====================================================== */
    public function annulerInscription($inscriptionId, $userId) {
        if ($this->columnExists('inscriptions', 'ticket_pdf_path')) {
            $row = $this->getInscriptionByIdForUser((int)$inscriptionId, (int)$userId);
            if ($row && !empty($row['ticket_pdf_path'])) {
                $pdfFs = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $row['ticket_pdf_path'];
                if (is_file($pdfFs)) {
                    @unlink($pdfFs);
                }
            }
        }

        $sql = "DELETE FROM inscriptions WHERE id = :id AND user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $inscriptionId, ':user_id' => $userId]);
    }
}
