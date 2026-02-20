<?php
session_start();
require_once __DIR__ . "/../backend/config.php";
require_once __DIR__ . "/../backend/middleware.php";

requireRole(['cofounder','mod','sup']);

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$ticket_id = intval($_POST['ticket_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if(empty($ticket_id) || empty($message)){
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Verify ticket exists
$ticketStmt = $conn->prepare("SELECT id FROM support_tickets WHERE id=?");
$ticketStmt->bind_param("i", $ticket_id);
$ticketStmt->execute();
$ticket = $ticketStmt->get_result()->fetch_assoc();

if(!$ticket){
    echo json_encode(['success' => false, 'error' => 'Ticket not found']);
    exit;
}

// Insert message
$msgStmt = $conn->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'support', ?)");
$msgStmt->bind_param("is", $ticket_id, $message);

if($msgStmt->execute()){
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully',
        'data' => [
            'id' => $conn->insert_id,
            'ticket_id' => $ticket_id,
            'sender_type' => 'support',
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}
