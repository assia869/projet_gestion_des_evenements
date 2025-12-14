<?php
// /gestion-evenements/diagnostic/db_upgrade_tickets.php  (NOUVEAU - optionnel)
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$pdo = $db->getConnection();

$pdo->exec("ALTER TABLE inscriptions ADD COLUMN ticket_token VARCHAR(64) NULL AFTER statut");
$pdo->exec("ALTER TABLE inscriptions ADD COLUMN ticket_pdf_path VARCHAR(255) NULL AFTER ticket_token");
$pdo->exec("ALTER TABLE inscriptions ADD COLUMN ticket_created_at DATETIME NULL AFTER ticket_pdf_path");
$pdo->exec("CREATE UNIQUE INDEX uq_inscriptions_ticket_token ON inscriptions(ticket_token)");

echo "OK";
