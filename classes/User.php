<?php
// /gestion-evenements/classes/User.php

require_once __DIR__ . '/Database.php';

class User extends Database
{
    /* ==========================
       AUTH
    ========================== */

    public function register($nom, $prenom, $email, $password)
    {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (nom, prenom, email, mot_de_passe, role, date_creation, created_at)
                    VALUES (:nom, :prenom, :email, :password, 'user', NOW(), NOW())";

            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                ':nom'      => $nom,
                ':prenom'   => $prenom,
                ':email'    => $email,
                ':password' => $hashedPassword
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function login($email, $password)
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            return $user;
        }
        return false;
    }

    /* ==========================
       READ
    ========================== */

    public function getUserById($id)
    {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getProfileById(int $id): ?array
    {
        $sql = "SELECT id, nom, prenom, email, telephone, adresse, role, date_creation, created_at
                FROM users
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        return $u ?: null;
    }

    public function getAllUsers()
    {
        $sql = "SELECT id, nom, prenom, email, role, date_creation, telephone, adresse, created_at
                FROM users
                ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isAdmin($userId)
    {
        $user = $this->getUserById($userId);
        return $user && ($user['role'] === 'admin');
    }

    public function deleteUser($id)
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => (int)$id]);
    }

    /* ==========================
       PROFIL
    ========================== */

    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $params = [':email' => $email];

        if ($excludeId > 0) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    public function updateProfile(
        int $id,
        string $nom,
        string $prenom,
        string $email,
        ?string $telephone,
        ?string $adresse
    ): bool {
        $sql = "UPDATE users
                SET nom = :nom,
                    prenom = :prenom,
                    email = :email,
                    telephone = :telephone,
                    adresse = :adresse
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':nom'       => $nom,
            ':prenom'    => $prenom,
            ':email'     => $email,
            ':telephone' => $telephone ?: null,
            ':adresse'   => $adresse ?: null,
            ':id'        => $id
        ]);
    }

    public function verifyPassword(int $id, string $plain): bool
    {
        $stmt = $this->pdo->prepare("SELECT mot_de_passe FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $hash = $stmt->fetchColumn();
        if (!$hash) return false;
        return password_verify($plain, $hash);
    }

    public function updatePassword(int $id, string $newPlainPassword): bool
    {
        $hash = password_hash($newPlainPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET mot_de_passe = :p WHERE id = :id");
        return $stmt->execute([':p' => $hash, ':id' => $id]);
    }
}
