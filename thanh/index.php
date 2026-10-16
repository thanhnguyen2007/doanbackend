<?php
// Xử lý API Search khi nhận request POST dạng JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xóa mọi output thừa (nếu có warning/notice) để không làm hỏng JSON
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $query = trim($input['query'] ?? '');

    if (empty($query)) {
        echo json_encode(['status' => 'error', 'message' => 'Truy vấn trống']);
        exit;
    }

    // Dữ liệu nội bộ
    $internalKnowledgeBase = [
        [
            'keywords' => ['quy định', 'sơ yếu lý lịch', 'hồ sơ', 'tuyển dụng'],
            'title' => 'Quy định nộp hồ sơ nhân sự',
            'snippet' => 'Ứng viên cần nộp CV, CCCD phôtô và bằng cấp liên quan trước ngày 15 hằng tháng.',
            'url' => '#docs-hoso'
        ],
        [
            'keywords' => ['gpt-4o', 'api', 'giá', 'chi phí', 'gói'],
            'title' => 'Gói GPT-4o API Standard',
            'snippet' => 'Giá gói là $20/tháng, bao gồm 1 triệu tokens/tháng phù hợp cho cá nhân.',
            'url' => '#services-gpt4o'
        ],
        [
            'keywords' => ['hoàn tiền', 'chính sách', 'đổi trả', 'thanh toán'],
            'title' => 'Chính sách hoàn tiền dịch vụ AI',
            'snippet' => 'Khách hàng được yêu cầu hoàn tiền trong vòng 7 ngày nếu dịch vụ gián đoạn quá 24h.',
            'url' => '#policy-refund'
        ]
    ];

    $matchedResults = [];
    $queryLower = mb_strtolower($query, 'UTF-8');

    foreach ($internalKnowledgeBase as$item) {
        foreach ($item['keywords'] as$keyword) {
            if (mb_strpos($queryLower,$keyword, 0, 'UTF-8') !== false) {
                $matchedResults[] =$item;
                break;
            }
        }
    }

    if (!empty($matchedResults)) {$count = count($matchedResults);$aiAnswer = "Tôi đã tìm thấy <strong>{$count}</strong> thông tin nội bộ phù hợp với từ khóa <em>\"" . htmlspecialchars($query) . "\"</em>:";
    } else {
        $aiAnswer = "Xin lỗi, tôi không tìm thấy tài liệu nội bộ nào phù hợp với câu hỏi <em>\"" . htmlspecialchars($query) . "\"</em>. Gợi ý từ khóa: <strong>GPT-4o, Quy định, Hoàn tiền</strong>.";
    }

    echo json_encode([
        'status' => 'success',
        'query' => $query,
        'answer' => $aiAnswer,
        'results' => $matchedResults
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ Hệ Thống Nội Bộ</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 40px; color: #334155; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .container h1 { font-size: 26px; margin-top: 0; color: #1e293b; }
        
        #ai-copilot-widget { position: fixed; bottom: 25px; right: 25px; z-index: 9999; }
        .copilot-btn { width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; border: none; box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4); cursor: pointer; font-size: 26px; display: flex; align-items: center; justify-content: center; }
        .copilot-box { display: none; position: absolute; bottom: 75px; right: 0; width: 360px; height: 500px; background: #ffffff; border-radius: 16px; box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15); flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0; }
        .copilot-box.active { display: flex !important; }
        .copilot-header { background: #1e293b; color: #ffffff; padding: 16px; display: flex; justify-content: space-between; align-items: center; }
        .copilot-header h4 { margin: 0; font-size: 16px; }
        .copilot-close { background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; }
        .copilot-messages { flex: 1; padding: 16px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; background-color: #f8fafc; }
        .msg { max-width: 85%; padding: 10px 14px; border-radius: 12px; font-size: 14px; line-height: 1.5; }
        .msg-user { align-self: flex-end; background: #6366f1; color: #ffffff; border-bottom-right-radius: 2px; }
        .msg-ai { align-self: flex-start; background: #ffffff; color: #1e293b; border: 1px solid #e2e8f0; border-bottom-left-radius: 2px; }
        .result-card { background: #f1f5f9; border-left: 3px solid #6366f1; padding: 8px 12px; margin-top: 8px; border-radius: 4px; font-size: 13px; }
        .result-card a { color: #4f46e5; text-decoration: none; font-weight: 600; }
        .copilot-input-area { padding: 12px; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; background: #ffffff; }
        .copilot-input-area input { flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 14px; }
        .copilot-input-area button { background: #6366f1; color: #fff; border: none; padding: 0 16px; border-radius: 8px; cursor: pointer; font-weight: 600; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Trang Chủ Hệ Thống Nội Bộ</h1>
        <p>Thử bấm vào biểu tượng 🤖 Trợ lý AI ở góc dưới bên phải để tìm kiếm dữ liệu nội bộ như: <em>"Giá gói GPT-4o"</em>, <em>"Quy định nộp hồ sơ"</em>, hoặc <em>"Chính sách hoàn tiền"</em>.</p>
    </div>

    <div id="ai-copilot-widget">
        <div class="copilot-box" id="copilot-box">
            <div class="copilot-header">
                <h4>🤖 AI Search Copilot</h4>
                <button class="copilot-close" id="copilot-close">&times;</button>
            </div>
            
            <div class="copilot-messages" id="copilot-messages">
                <div class="msg msg-ai">
                    Xin chào! Tôi có thể giúp gì cho bạn trong việc tra cứu dữ liệu nội bộ hôm nay?
                </div>
            </div>

            <div class="copilot-input-area">
                <input type="text" id="copilot-input" placeholder="Nhập từ khóa tìm kiếm..." autocomplete="off">
                <button id="copilot-send">Gửi</button>
            </div>
        </div>

        <button class="copilot-btn" id="copilot-toggle" type="button" title="Mở Trợ lý AI">🤖</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtn = document.getElementById('copilot-toggle');
            const chatBox = document.getElementById('copilot-box');
            const closeBtn = document.getElementById('copilot-close');
            const sendBtn = document.getElementById('copilot-send');
            const inputField = document.getElementById('copilot-input');
            const messagesContainer = document.getElementById('copilot-messages');

            toggleBtn.onclick = (e) => {
                e.stopPropagation();
                chatBox.classList.toggle('active');
            };

            closeBtn.onclick = () => chatBox.classList.remove('active');

            sendBtn.onclick = handleSendMessage;
            inputField.onkeypress = (e) => {
                if (e.key === 'Enter') handleSendMessage();
            };

            async function handleSendMessage() {
                const query = inputField.value.trim();
                if (!query) return;

                appendMessage(query, 'user');
                inputField.value = '';

                const loadingId = appendMessage('🤖 Đang tìm kiếm dữ liệu nội bộ...', 'ai');

                try {
                    // Gọi chính URL hiện tại của trang
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ query: query })
                    });

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
                    console.error('Fetch error:', error);
                    removeMessage(loadingId);
                    appendMessage('Không thể kết nối đến máy chủ. Hãy đảm bảo bạn chạy web qua http://localhost/', 'ai');
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
    </script>
</body>
</html>