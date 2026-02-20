<?php
session_start();
include "../backend/config.php";

$id = intval($_GET['id']);
$action = $_GET['action'];

// Map action to correct status value
$statusMap = [
    'claim' => 'claimed',
    'refuse' => 'refused',
    'done' => 'done',
    'open' => 'open'
];

$status = isset($statusMap[$action]) ? $statusMap[$action] : $action;

// Get ticket details - check all possible column names
$ticketStmt = $conn->prepare("SELECT * FROM support_tickets WHERE id = ?");
$ticketStmt->bind_param("i", $id);
$ticketStmt->execute();
$ticket = $ticketStmt->get_result()->fetch_assoc();

// Check if this is a cancellation ticket and action is 'done'
$isCancellationTicket = false;

// Check multiple ways to identify cancellation ticket
if ($ticket && $action === 'done') {
    // Method 1: Check subject (case-insensitive)
    if (isset($ticket['subject']) && stripos($ticket['subject'], 'Cancellation') !== false) {
        $isCancellationTicket = true;
    }
    
    // Method 2: Check ticket_code (case-insensitive)
    if (isset($ticket['ticket_code']) && stripos($ticket['ticket_code'], 'CANCEL') !== false) {
        $isCancellationTicket = true;
    }
    
    // Method 3: Check if cancellation_data exists and is not empty
    if (isset($ticket['cancellation_data']) && !empty($ticket['cancellation_data']) && $ticket['cancellation_data'] !== 'null') {
        $isCancellationTicket = true;
    }
}

if ($isCancellationTicket) {
    // Log for debugging
    error_log("=== CANCELLATION TICKET DETECTED ===");
    error_log("Ticket ID: " . $id);
    error_log("Ticket Data: " . print_r($ticket, true));
    
    try {
        $cancellationData = null;
        
        // Try to get cancellation data
        if (isset($ticket['cancellation_data']) && !empty($ticket['cancellation_data']) && $ticket['cancellation_data'] !== 'null') {
            $cancellationData = json_decode($ticket['cancellation_data'], true);
            error_log("Cancellation Data: " . print_r($cancellationData, true));
        }
        
        if ($cancellationData && isset($cancellationData['user_id'])) {
            $user_id = intval($cancellationData['user_id']);
            $refund_amount = intval($cancellationData['refund_amount'] ?? 0);
            
            error_log("Processing cancellation for user ID: {$user_id}, refund: {$refund_amount}");
            
            // Start transaction
            $conn->begin_transaction();
            
            // Get current user data before update
            $checkStmt = $conn->prepare("SELECT subscription_tier, subscription_expires, is_verified, coins FROM users WHERE id = ?");
            $checkStmt->bind_param("i", $user_id);
            $checkStmt->execute();
            $beforeData = $checkStmt->get_result()->fetch_assoc();
            error_log("Before update: " . print_r($beforeData, true));
            
            // Cancel subscription and refund coins
            $updateStmt = $conn->prepare("
                UPDATE users 
                SET subscription_tier = 'free',
                    subscription_expires = NULL,
                    is_verified = 0,
                    coins = coins + ?
                WHERE id = ?
            ");
            $updateStmt->bind_param("ii", $refund_amount, $user_id);
            
            if (!$updateStmt->execute()) {
                throw new Exception("Failed to update user: " . $conn->error);
            }
            
            $affectedRows = $updateStmt->affected_rows;
            error_log("Update affected rows: " . $affectedRows);
            
            // Get user data after update
            $checkStmt->execute();
            $afterData = $checkStmt->get_result()->fetch_assoc();
            error_log("After update: " . print_r($afterData, true));
            
            // Update subscription record (if table exists)
            $checkSubTable = $conn->query("SHOW TABLES LIKE 'subscriptions'");
            if ($checkSubTable->num_rows > 0) {
                $updateSubStmt = $conn->prepare("
                    UPDATE subscriptions 
                    SET status = 'cancelled' 
                    WHERE user_id = ? 
                    AND status = 'active'
                ");
                $updateSubStmt->bind_param("i", $user_id);
                $updateSubStmt->execute();
                error_log("Subscription table updated, affected rows: " . $updateSubStmt->affected_rows);
            }
            
            // Log admin action
            if (isset($_SESSION['user_id'])) {
                $admin_id = intval($_SESSION['user_id']);
                $checkLogTable = $conn->query("SHOW TABLES LIKE 'admin_logs'");
                if ($checkLogTable->num_rows > 0) {
                    $logStmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details) VALUES (?, 'cancel_subscription', ?)");
                    $details = "Cancelled subscription for user ID {$user_id}, refunded {$refund_amount} coins (Ticket #{$id})";
                    $logStmt->bind_param("is", $admin_id, $details);
                    $logStmt->execute();
                }
            }
            
            // Commit transaction
            $conn->commit();
            error_log("=== CANCELLATION COMPLETED SUCCESSFULLY ===");
        } else {
            error_log("ERROR: No cancellation data or user_id not found");
        }
    } catch (Exception $e) {
        $conn->rollback();
        // Log error but continue with ticket update
        error_log("Cancellation error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
}

// Update ticket status
$stmt = $conn->prepare("UPDATE support_tickets SET status=? WHERE id=?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

header("Location: tickets.php");
exit;
?>
