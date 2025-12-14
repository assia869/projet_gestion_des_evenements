<?php
// /gestion-evenements/classes/Evenement.php

require_once __DIR__ . '/Database.php';

class Evenement extends Database {

    public function creerEvenement(
        $titre,
        $description,
        $date_evenement,
        $heure_debut,
        $heure_fin,
        $lieu,
        $categorie_id,
        $nb_max_participants = 50,
        $image = '',
        $latitude = null,
        $longitude = null,
        $contact_email = null,
        $contact_phone = null
    ) {
        $sql = "INSERT INTO evenements (
                    titre, description, date_evenement, heure_debut, heure_fin, lieu,
                    contact_email, contact_phone,
                    categorie_id, nb_max_participants, image, latitude, longitude, statut
                )
                VALUES (
                    :titre, :description, :date_evenement, :heure_debut, :heure_fin, :lieu,
                    :contact_email, :contact_phone,
                    :categorie_id, :nb_max_participants, :image, :latitude, :longitude, 'actif'
                )";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':titre' => $titre,
            ':description' => $description,
            ':date_evenement' => $date_evenement,
            ':heure_debut' => $heure_debut,
            ':heure_fin' => $heure_fin,
            ':lieu' => $lieu,
            ':contact_email' => $contact_email ?: null,
            ':contact_phone' => $contact_phone ?: null,
            ':categorie_id' => $categorie_id,
            ':nb_max_participants' => $nb_max_participants,
            ':image' => $image,
            ':latitude' => $latitude,
            ':longitude' => $longitude
        ]);
    }

    public function modifierEvenement(
        $id,
        $titre,
        $description,
        $date_evenement,
        $heure_debut,
        $heure_fin,
        $lieu,
        $categorie_id,
        $nb_max_participants,
        $image = null,
        $latitude = null,
        $longitude = null,
        $contact_email = null,
        $contact_phone = null
    ) {
        if ($image !== null) {
            $sql = "UPDATE evenements SET
                    titre = :titre,
                    description = :description,
                    date_evenement = :date_evenement,
                    heure_debut = :heure_debut,
                    heure_fin = :heure_fin,
                    lieu = :lieu,
                    contact_email = :contact_email,
                    contact_phone = :contact_phone,
                    categorie_id = :categorie_id,
                    nb_max_participants = :nb_max_participants,
                    image = :image,
                    latitude = :latitude,
                    longitude = :longitude
                    WHERE id = :id";

            $params = [
                ':id' => $id,
                ':titre' => $titre,
                ':description' => $description,
                ':date_evenement' => $date_evenement,
                ':heure_debut' => $heure_debut,
                ':heure_fin' => $heure_fin,
                ':lieu' => $lieu,
                ':contact_email' => $contact_email ?: null,
                ':contact_phone' => $contact_phone ?: null,
                ':categorie_id' => $categorie_id,
                ':nb_max_participants' => $nb_max_participants,
                ':image' => $image,
                ':latitude' => $latitude,
                ':longitude' => $longitude
            ];
        } else {
            $sql = "UPDATE evenements SET
                    titre = :titre,
                    description = :description,
                    date_evenement = :date_evenement,
                    heure_debut = :heure_debut,
                    heure_fin = :heure_fin,
                    lieu = :lieu,
                    contact_email = :contact_email,
                    contact_phone = :contact_phone,
                    categorie_id = :categorie_id,
                    nb_max_participants = :nb_max_participants,
                    latitude = :latitude,
                    longitude = :longitude
                    WHERE id = :id";

            $params = [
                ':id' => $id,
                ':titre' => $titre,
                ':description' => $description,
                ':date_evenement' => $date_evenement,
                ':heure_debut' => $heure_debut,
                ':heure_fin' => $heure_fin,
                ':lieu' => $lieu,
                ':contact_email' => $contact_email ?: null,
                ':contact_phone' => $contact_phone ?: null,
                ':categorie_id' => $categorie_id,
                ':nb_max_participants' => $nb_max_participants,
                ':latitude' => $latitude,
                ':longitude' => $longitude
            ];
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function supprimerEvenement($id) {
        $evenement = $this->getEvenementById($id);

        if ($evenement && !empty($evenement['image'])) {
            $image_path = $_SERVER['DOCUMENT_ROOT'] . $evenement['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        $sql = "DELETE FROM evenements WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function getAllEvenements() {
        $sql = "SELECT e.*, c.nom as categorie_nom,
                (SELECT COUNT(*) FROM inscriptions WHERE evenement_id = e.id AND statut = 'confirme') as nb_inscrits
                FROM evenements e
                LEFT JOIN categories c ON e.categorie_id = c.id
                ORDER BY e.date_evenement DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getEvenementById($id) {
        $sql = "SELECT e.*, c.nom as categorie_nom,
                (SELECT COUNT(*) FROM inscriptions WHERE evenement_id = e.id AND statut = 'confirme') as nb_inscrits
                FROM evenements e
                LEFT JOIN categories c ON e.categorie_id = c.id
                WHERE e.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getEvenementsActifs() {
        $sql = "SELECT e.*, c.nom as categorie_nom,
                (SELECT COUNT(*) FROM inscriptions WHERE evenement_id = e.id AND statut = 'confirme') as nb_inscrits
                FROM evenements e
                LEFT JOIN categories c ON e.categorie_id = c.id
                WHERE e.statut = 'actif' AND e.date_evenement >= CURDATE()
                ORDER BY e.date_evenement ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getCategories() {
        $sql = "SELECT * FROM categories ORDER BY nom ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function ajouterCategorie($nom, $description = '') {
        $sql = "INSERT INTO categories (nom, description) VALUES (:nom, :description)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':nom' => $nom, ':description' => $description]);
    }

    public function supprimerCategorie($id) {
        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
?>
