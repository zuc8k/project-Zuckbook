<?php
session_start();
require_once __DIR__ . "/backend/config.php";

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة غير صحيحة']);
    exit;
}

$ticket_id = intval($_POST['ticket_id']);
$message = trim($_POST['message']);
$user_id = intval($_SESSION['user_id']);

// Validate inputs
if(empty($ticket_id) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
    exit;
}

if(strlen($message) < 2) {
    echo json_encode(['success' => false, 'message' => 'الرسالة قصيرة جداً']);
    exit;
}

// Check if ticket exists and belongs to user
$ticketStmt = $conn->prepare("SELECT id FROM support_tickets WHERE id=? AND user_id=?");
$ticketStmt->bind_param("ii", $ticket_id, $user_id);
$ticketStmt->execute();
$ticket = $ticketStmt->get_result()->fetch_assoc();

if(!$ticket) {
    echo json_encode(['success' => false, 'message' => 'التذكرة غير موجودة']);
    exit;
}

// Insert message
$stmt = $conn->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'user', ?)");
$stmt->bind_param("is", $ticket_id, $message);

if($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'تم إرسال الرسالة بنجاح',
        'message_id' => $stmt->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الإرسال']);
}

exit;
?>
