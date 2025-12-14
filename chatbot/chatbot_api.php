<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../classes/Chatbot.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['question'])) {
        echo json_encode([
            'answer' => "❓ Question non reçue."
        ]);
        exit;
    }

    $bot = new Chatbot();
    $response = $bot->getResponse($data['question']);

    echo json_encode([
        'answer' => $response
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'answer' => "❌ Erreur interne du chatbot."
    ]);
}
