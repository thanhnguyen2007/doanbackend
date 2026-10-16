// Đảm bảo DOM đã tải xong hoàn toàn trước khi gán sự kiện
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('copilot-toggle');
    const chatBox = document.getElementById('copilot-box');
    const closeBtn = document.getElementById('copilot-close');
    const sendBtn = document.getElementById('copilot-send');
    const inputField = document.getElementById('copilot-input');
    const messagesContainer = document.getElementById('copilot-messages');

    // Kiểm tra nếu không tìm thấy phần tử HTML thì báo lỗi rõ ràng
    if (!toggleBtn || !chatBox) {
        console.error('Không tìm thấy Widget Copilot trong DOM.');
        return;
    }

    // 1. Mở / Đóng khung Chat khi bấm nút 🤖
    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        chatBox.classList.toggle('active');
    });

    closeBtn.addEventListener('click', () => {
        chatBox.classList.remove('active');
    });

    // 2. Bắt sự kiện gửi tin nhắn
    sendBtn.addEventListener('click', handleSendMessage);
    inputField.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') handleSendMessage();
    });

    async function handleSendMessage() {
        const query = inputField.value.trim();
        if (!query) return;

        appendMessage(query, 'user');
        inputField.value = '';

        const loadingId = appendMessage('🤖 Đang tìm kiếm dữ liệu nội bộ...', 'ai');

        try {
            // Gọi đến file api.php ở cùng thư mục
            const response = await fetch('./api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query })
            });

            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }

            const data = await response.json();
            removeMessage(loadingId);

            if (data.status === 'success') {
                let htmlContent = `<div>${data.answer}</div>`;

                if (data.results && data.results.length > 0) {
                    htmlContent += `<div style="margin-top:8px; font-weight:600; font-size:12px; color:#64748b;">Nguồn dữ liệu nội bộ:</div>`;
                    data.results.forEach(item => {
                        htmlContent += `
                            <div class="result-card">
                                <div><strong>${item.title}</strong></div>
                                <div>${item.snippet}</div>
                                <a href="${item.url}">Xem chi tiết →</a>
                            </div>
                        `;
                    });
                }
                appendMessage(htmlContent, 'ai', true);
            } else {
                appendMessage('Có lỗi xảy ra khi truy vấn dữ liệu.', 'ai');
            }
        } catch (error) {
            console.error(error);
            removeMessage(loadingId);
            appendMessage('Không thể kết nối đến máy chủ.', 'ai');
        }
    }

    function appendMessage(content, sender, isHTML = false) {
        const msgDiv = document.createElement('div');
        const id = 'msg-' + Date.now();
        msgDiv.id = id;
        msgDiv.classList.add('msg', sender === 'user' ? 'msg-user' : 'msg-ai');

        if (isHTML) {
            msgDiv.innerHTML = content;
        } else {
            msgDiv.textContent = content;
        }

        messagesContainer.appendChild(msgDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        return id;
    }

    function removeMessage(id) {
        const elem = document.getElementById(id);
        if (elem) elem.remove();
    }
});