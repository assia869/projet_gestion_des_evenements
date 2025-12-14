<?php
// FILE: config/app.php

declare(strict_types=1);

define('APP_NAME', 'Gestion des Événements');

/**
 * IMPORTANT:
 * Mets ici l’URL ABSOLUE de ton projet (utilisée pour le QR code).
 * Exemples:
 * - http://localhost/gestion-evenements
 * - https://monsite.ma/gestion-evenements
 */
define('APP_URL', 'http://localhost/gestion-evenements');

define('APP_CONTACT_EMAIL', 'contact@evenements.com');
define('APP_CONTACT_PHONE', '+212 6 00 00 00 00');

// Dossiers de stockage (dans la racine du projet)
define('APP_STORAGE_DIR', __DIR__ . '/../storage');
define('APP_TICKETS_DIR', APP_STORAGE_DIR . '/tickets');
define('APP_QRCODES_DIR', APP_STORAGE_DIR . '/qrcodes');
