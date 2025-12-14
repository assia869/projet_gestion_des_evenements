<?php

class Chatbot {

    private $responses;

    public function __construct() {
        $this->responses = json_decode(
            file_get_contents(__DIR__ . '/../chatbot/chatbot_responses.json'),
            true
        );
    }

    public function getResponse($question) {
        $question = strtolower($question);

        foreach ($this->responses as $item) {
            foreach ($item['keywords'] as $keyword) {
                if (str_contains($question, $keyword)) {
                    return $item['response'];
                }
            }
        }

        return "Je n'ai pas compris votre question 😕. Essayez autrement.";
    }
}
