<?php
function loadLanguage($lang = 'fr') {
    // Seulement FR et EN
    if (!in_array($lang, ['fr', 'en'])) {
        $lang = 'fr'; // Par défaut français
    }
    
    $lang_file = __DIR__ . '/' . $lang . '.php';
    if (file_exists($lang_file)) {
        return include $lang_file;
    }
    return include __DIR__ . '/fr.php'; // Fallback
}

function t($key) {
    global $translations;
    return $translations[$key] ?? $key;
}

function getCurrentLanguage() {
    return $_SESSION['lang'] ?? 'fr';
}

function getLanguageName($lang) {
    $names = [
        'fr' => 'Français',
        'en' => 'English'
    ];
    return $names[$lang] ?? 'Français';
}

function getLanguageFlag($lang) {
    $flags = [
        'fr' => '🇫🇷',
        'en' => '🇬🇧'
    ];
    return $flags[$lang] ?? '🇫🇷';
}
?>