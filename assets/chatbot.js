document.addEventListener('DOMContentLoaded', () => {

    const chatbotButton = document.getElementById('chatbot-button');
    const chatbotWidget = document.getElementById('chatbot-widget');
    const chatbotClose = document.getElementById('chatbot-close');
    const chatbotSend = document.getElementById('chatbot-send');
    const chatbotInput = document.getElementById('chatbot-input');
    const chatbotMessages = document.getElementById('chatbot-messages');

    if (!chatbotButton) return;

    chatbotButton.onclick = () => {
        chatbotWidget.style.display = 'flex';
    };

    chatbotClose.onclick = () => {
        chatbotWidget.style.display = 'none';
    };

    chatbotSend.onclick = sendMessage;

    chatbotInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    function sendMessage() {
        const message = chatbotInput.value.trim();
        if (!message) return;

        chatbotMessages.innerHTML += `
            <div class="user-message">${message}</div>
        `;
        chatbotInput.value = '';

        fetch('/gestion-evenements/chatbot/chatbot_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ question: message })
        })
        .then(response => response.json())
        .then(data => {
            chatbotMessages.innerHTML += `
                <div class="bot-message">${data.answer}</div>
            `;
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        })
        .catch(error => {
            chatbotMessages.innerHTML += `
                <div class="bot-message text-danger">
                    Erreur serveur. Veuillez réessayer.
                </div>
            `;
            console.error(error);
        });
    }

});
