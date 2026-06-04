<?php
session_start();
include("../../config/database.php"); 
$user_id = $_SESSION['user_id'] ?? 0;
error_reporting(E_ALL);
ini_set('display_errors', 1);
$stud_id = 0;
$stud_fname = "Student";

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT stud_id, stud_fname FROM tb_students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $stud_id = (int)$row['stud_id'];
        $stud_fname = $row['stud_fname'] ?: $stud_fname;
    }
    $stmt->close();
    $_SESSION['stud_id'] = $stud_id;
    $_SESSION['stud_fname'] = $stud_fname;
}

// send JSON 
function json_exit($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// history
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'history') {
    if ($stud_id <= 0) {
        json_exit(['error' => 'Not logged in or student not found.']);
    }

    $stmt = $conn->prepare("SELECT chatbot_id, chatbot_userMessage, chatbot_aiResponse, chatbot_created FROM tb_chatbot WHERE stud_id = ? ORDER BY chatbot_created ASC");
    $stmt->bind_param("i", $stud_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $history = [];
    while ($row = $res->fetch_assoc()) {
        $history[] = [
            'id' => (int)$row['chatbot_id'],
            'user' => $row['chatbot_userMessage'],
            'ai' => $row['chatbot_aiResponse'],
            'created' => $row['chatbot_created']
        ];
    }
    $stmt->close();
    json_exit(['history' => $history]);
}


// send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_GET['action']) || $_GET['action'] === '')) {
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=utf-8");

    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['message']) || empty(trim($input['message']))) {
        json_exit(['error' => 'Invalid or empty input']);
    }

    $user_message = trim($input['message']);
    // $api_key = NEED NEW API KEY
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash-lite:generateContent?key=" . urlencode($api_key);

    $system_prompt = "You are Mediko Bot, a healthcare AI assistant who talks like a professional. Only answer questions about healthcare. If someone's condition sounds really bad or serious, tell them to see a doctor right away. Keep it general, but always safe and accurate. If the question isn't about healthcare, say something like 'Hey, I'm all about health stuff, ask me about that, thank you!'";

    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $system_prompt . "\n\nUser: " . $user_message]
                ]
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $err = 'Curl error: ' . curl_error($ch);
        curl_close($ch);
        json_exit(['error' => $err]);
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        json_exit(['error' => 'Google Gemini API error. HTTP code: ' . $http_code . '. Response: ' . $response]);
    }

    $response_data = json_decode($response, true);

    $ai_response = '';
    if (is_array($response_data) && isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
        $ai_response = trim($response_data['candidates'][0]['content']['parts'][0]['text']);
    } else {
        $ai_response = isset($response_data['output'][0]['content']) ? json_encode($response_data['output'][0]['content']) : 'No valid response from AI.';
    }

    if ($stud_id > 0) {
        $stmt = $conn->prepare("INSERT INTO tb_chatbot (stud_id, chatbot_userMessage, chatbot_aiResponse, chatbot_created) VALUES (?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("iss", $stud_id, $user_message, $ai_response);
            $stmt->execute();
            $stmt->close();
        }
    }

    json_exit(['response' => $ai_response]);
}

// delete 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    if ($stud_id <= 0) {
        json_exit(['error' => 'Student not found or not logged in.']);
    }
    $stmt = $conn->prepare("DELETE FROM tb_chatbot WHERE stud_id = ?");
    $stmt->bind_param("i", $stud_id);
    $stmt->execute();
    $stmt->close();
    json_exit(['success' => true]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mediko | User AI ChatBot</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background-image: url('../../images/bg_login.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; display: flex; flex-direction: column; min-height: 100vh; margin: 0; overflow-y: auto; }
.main-content { flex: 1; display: flex; justify-content: center; align-items: center; padding: 20px 0; }
#chat-container { width: 1200px !important; height: 600px !important; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); display: flex; flex-direction: column; }
#chat-box { flex: 1; overflow-y: auto; padding: 15px; border-radius: 10px; background: #ffffff; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
.message-row { display: flex; margin: 8px 0; }
.message-row.bot { justify-content: flex-start; }
.message-row.user { justify-content: flex-end; }
.bot-avatar { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; }
.user-message { background: #0b1e4a; color: white; padding: 10px; border-radius: 10px; max-width: 75%; text-align: justify; }
.bot-message { background: #f5f5f5; color: #000; padding: 10px; border-radius: 10px; max-width: 75%; text-align: justify; }
#user-input { flex: 1; padding: 12px; border-radius: 20px; border: 1px solid #0b1e4a; outline: none; }
button { padding: 12px 20px; border-radius: 20px; border: none; }
#send-btn { background: #0b1e4a; color: white; border-radius: 8px;}
#delete-btn { background: #dc3545; color: white; font-size: 13px; padding: 4px 10px; border-radius: 15px; }
.input-container { display: flex; margin-top: 15px; gap: 10px; align-items: center; }
#chat-box::-webkit-scrollbar { width: 8px; }
#chat-box::-webkit-scrollbar-thumb { background: #0b1e4a; border-radius: 10px; }
.chat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.chat-header h4 { display: flex; align-items: center; gap: 8px; margin: 0; color: #0b1e4a !important; }
.chat-header h4 img { height: 32px; }
</style>
</head>
<body>
<?php include("../../template/header.php"); ?>

<div class="main-content">
    <div id="chat-container">
        <div class="chat-header">
            <h4>
                <img src="../../images/chatbot.png" alt="logo">
                Mediko Bot
            </h4>
            <button id="delete-btn">Delete Conversation</button>
        </div>
        <div id="chat-box"></div>
        <div class="input-container">
            <input type="text" id="user-input" placeholder="Type your message..." class="form-control">
            <button id="send-btn">Send</button>
        </div>
    </div>
</div>
<?php include("../../template/footer.php"); ?>

<script>
const studentName = <?php echo json_encode($stud_fname ?? 'Student'); ?>;
const CHAT_HISTORY_URL = window.location.pathname + '?action=history';
const SEND_URL = window.location.href;

function createBotRow(text) {
    const botRow = document.createElement('div');
    botRow.className = 'message-row bot';
    const avatar = document.createElement('img');
    avatar.src = '../../images/chatbot.png';
    avatar.className = 'bot-avatar';
    botRow.appendChild(avatar);
    const msg = document.createElement('div');
    msg.className = 'bot-message';
    msg.textContent = text;
    botRow.appendChild(msg);
    return botRow;
}

function createUserRow(text) {
    const userRow = document.createElement('div');
    userRow.className = 'message-row user';
    const msg = document.createElement('div');
    msg.className = 'user-message';
    msg.textContent = text;
    userRow.appendChild(msg);
    return userRow;
}

function addInitialBotMessage() {
    const chatBox = document.getElementById('chat-box');
    if (chatBox.children.length === 0) {
        const botRow = document.createElement('div');
        botRow.className = 'message-row bot';

        const avatar = document.createElement('img');
        avatar.src = '../../images/chatbot.png';
        avatar.alt = 'Mediko Bot';
        avatar.className = 'bot-avatar';
        botRow.appendChild(avatar);

        const botMessage = document.createElement('div');
        botMessage.className = 'bot-message';
        botMessage.textContent = `Hello ${studentName}, I am Mediko Bot. How can I help you today?`;
        botRow.appendChild(botMessage);

        chatBox.appendChild(botRow);
    }
}


function renderHistory(history) {
    const chatBox = document.getElementById('chat-box');
    chatBox.innerHTML = '';
    if (!history || history.length === 0) {
        addInitialBotMessage();
        return;
    }
    history.forEach(item => {
        if (item.user) chatBox.appendChild(createUserRow(item.user));
        if (item.ai) chatBox.appendChild(createBotRow(item.ai));
    });
    chatBox.scrollTop = chatBox.scrollHeight;
}

function loadHistory() {
    fetch(CHAT_HISTORY_URL)
        .then(res => res.json())
        .then(data => {
            if (data.error) addInitialBotMessage();
            else renderHistory(data.history || []);
        })
        .catch(err => {
            console.error('Failed to load history', err);
            addInitialBotMessage();
        });
}

function sendMessage() {
    const input = document.getElementById('user-input');
    const text = input.value.trim();
    if (text === '') return;
    const chatBox = document.getElementById('chat-box');
    chatBox.appendChild(createUserRow(text));
    chatBox.scrollTop = chatBox.scrollHeight;
    input.disabled = true;
    document.getElementById('send-btn').disabled = true;

    fetch(SEND_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({message: text})
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) chatBox.appendChild(createBotRow('Error: ' + data.error));
        else if (data.warning) { chatBox.appendChild(createBotRow(data.warning)); if(data.response) chatBox.appendChild(createBotRow(data.response)); }
        else chatBox.appendChild(createBotRow(data.response));
        chatBox.scrollTop = chatBox.scrollHeight;
        input.value = '';
    })
    .catch(err => { chatBox.appendChild(createBotRow('Error: Failed to send message.')); chatBox.scrollTop = chatBox.scrollHeight; })
    .finally(() => { input.disabled = false; document.getElementById('send-btn').disabled = false; input.focus(); });
}

// Delete conversation
document.getElementById('delete-btn').addEventListener('click', () => {
    if (!confirm('Are you sure you want to delete your conversation? This cannot be undone.')) return;

    fetch(window.location.href + '?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const chatBox = document.getElementById('chat-box');
            chatBox.innerHTML = '';
            addInitialBotMessage();
        } else if (data.error) {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => alert('Failed to delete conversation.'));
});

document.addEventListener('DOMContentLoaded', () => {
    loadHistory();
    document.getElementById('send-btn').addEventListener('click', sendMessage);
    document.getElementById('user-input').addEventListener('keypress', (e) => { if(e.key==='Enter') sendMessage(); });
});
</script>
</body>
</html>
