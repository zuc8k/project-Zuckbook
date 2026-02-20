<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = intval($_SESSION['user_id']);

echo "<h2>Cancellation Debug Tool</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .warning { color: orange; } .info { color: blue; } pre { background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto; }</style>";

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo "<h3>Current User Data:</h3>";
echo "<pre>";
print_r($user);
echo "</pre>";

// Check for cancellation tickets
echo "<h3>Cancellation Tickets:</h3>";
$ticketStmt = $conn->prepare("
    SELECT * FROM support_tickets 
    WHERE user_id = ? 
    ORDER BY id DESC
");
$ticketStmt->bind_param("i", $user_id);
$ticketStmt->execute();
$tickets = $ticketStmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($tickets)) {
    echo "<p class='warning'>No tickets found</p>";
} else {
    foreach ($tickets as $ticket) {
        $isCancellation = false;
        
        // Check if it's a cancellation ticket
        if (isset($ticket['subject']) && stripos($ticket['subject'], 'Cancellation') !== false) {
            $isCancellation = true;
        }
        if (isset($ticket['ticket_code']) && stripos($ticket['ticket_code'], 'CANCEL') !== false) {
            $isCancellation = true;
        }
        if (isset($ticket['cancellation_data']) && !empty($ticket['cancellation_data']) && $ticket['cancellation_data'] !== 'null') {
            $isCancellation = true;
        }
        
        $class = $isCancellation ? 'info' : '';
        echo "<div style='border: 2px solid " . ($isCancellation ? '#3b82f6' : '#e5e7eb') . "; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
        echo "<h4 class='{$class}'>Ticket #{$ticket['id']} " . ($isCancellation ? '(CANCELLATION TICKET)' : '') . "</h4>";
        echo "<p><strong>Code:</strong> {$ticket['ticket_code']}</p>";
        echo "<p><strong>Status:</strong> {$ticket['status']}</p>";
        
        if (isset($ticket['subject'])) {
            echo "<p><strong>Subject:</strong> {$ticket['subject']}</p>";
        }
        
        if (isset($ticket['cancellation_data']) && !empty($ticket['cancellation_data']) && $ticket['cancellation_data'] !== 'null') {
            echo "<p><strong>Cancellation Data:</strong></p>";
            echo "<pre>";
            $data = json_decode($ticket['cancellation_data'], true);
            print_r($data);
            echo "</pre>";
            
            // Test cancellation button
            if ($ticket['status'] !== 'done' && $isCancellation) {
                echo "<p><a href='?process_ticket={$ticket['id']}' style='padding: 10px 20px; background: #ef4444; color: white; text-decoration: none; border-radius: 8px;'>Simulate Admin Clicking 'Done'</a></p>";
            }
        }
        
        echo "</div>";
    }
}

// Process ticket if requested
if (isset($_GET['process_ticket'])) {
    $ticket_id = intval($_GET['process_ticket']);
    
    echo "<hr><h3 class='info'>Processing Ticket #{$ticket_id}...</h3>";
    
    // Get ticket
    $ticketStmt = $conn->prepare("SELECT * FROM support_tickets WHERE id = ?");
    $ticketStmt->bind_param("i", $ticket_id);
    $ticketStmt->execute();
    $ticket = $ticketStmt->get_result()->fetch_assoc();
    
    if (!$ticket) {
        echo "<p class='error'>✗ Ticket not found</p>";
    } else {
        // Check if it's a cancellation ticket
        $isCancellationTicket = false;
        
        if (isset($ticket['subject']) && stripos($ticket['subject'], 'Cancellation') !== false) {
            $isCancellationTicket = true;
            echo "<p class='success'>✓ Detected via subject</p>";
        }
        
        if (isset($ticket['ticket_code']) && stripos($ticket['ticket_code'], 'CANCEL') !== false) {
            $isCancellationTicket = true;
            echo "<p class='success'>✓ Detected via ticket_code</p>";
        }
        
        if (isset($ticket['cancellation_data']) && !empty($ticket['cancellation_data']) && $ticket['cancellation_data'] !== 'null') {
            $isCancellationTicket = true;
            echo "<p class='success'>✓ Detected via cancellation_data</p>";
        }
        
        if ($isCancellationTicket) {
            echo "<p class='success'><strong>✓ This is a CANCELLATION TICKET</strong></p>";
            
            try {
                $cancellationData = json_decode($ticket['cancellation_data'], true);
                
                if ($cancellationData && isset($cancellationData['user_id'])) {
                    $cancel_user_id = intval($cancellationData['user_id']);
                    $refund_amount = intval($cancellationData['refund_amount'] ?? 0);
                    
                    echo "<p>User ID: {$cancel_user_id}</p>";
                    echo "<p>Refund Amount: {$refund_amount} coins</p>";
                    
                    // Start transaction
                    $conn->begin_transaction();
                    
                    // Cancel subscription
                    $updateStmt = $conn->prepare("
                        UPDATE users 
                        SET subscription_tier = 'free',
                            subscription_expires = NULL,
                            is_verified = 0,
                            coins = coins + ?
                        WHERE id = ?
                    ");
                    $updateStmt->bind_param("ii", $refund_amount, $cancel_user_id);
                    
                    if ($updateStmt->execute()) {
                        echo "<p class='success'>✓ User subscription cancelled</p>";
                        echo "<p class='success'>✓ Refunded {$refund_amount} coins</p>";
                    } else {
                        throw new Exception("Failed to update user: " . $conn->error);
                    }
                    
                    // Update subscription record
                    $checkSubTable = $conn->query("SHOW TABLES LIKE 'subscriptions'");
                    if ($checkSubTable->num_rows > 0) {
                        $updateSubStmt = $conn->prepare("
                            UPDATE subscriptions 
                            SET status = 'cancelled' 
                            WHERE user_id = ? 
                            AND status = 'active'
                        ");
                        $updateSubStmt->bind_param("i", $cancel_user_id);
                        
                        if ($updateSubStmt->execute()) {
                            echo "<p class='success'>✓ Subscription record updated</p>";
                        }
                    }
                    
                    // Update ticket status
                    $updateTicketStmt = $conn->prepare("UPDATE support_tickets SET status = 'done' WHERE id = ?");
                    $updateTicketStmt->bind_param("i", $ticket_id);
                    $updateTicketStmt->execute();
                    
                    $conn->commit();
                    
                    echo "<p class='success'><strong>✓ CANCELLATION COMPLETED SUCCESSFULLY!</strong></p>";
                    echo "<p><a href='debug_cancellation.php'>Refresh to see changes</a></p>";
                    
                } else {
                    echo "<p class='error'>✗ Invalid cancellation data</p>";
                }
                
            } catch (Exception $e) {
                $conn->rollback();
                echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
            }
            
        } else {
            echo "<p class='error'>✗ This is NOT a cancellation ticket</p>";
        }
    }
}

echo "<hr>";
echo "<p><a href='my_subscription.php' style='padding: 10px 20px; background: #1877f2; color: white; text-decoration: none; border-radius: 8px;'>Go to My Subscription</a></p>";
echo "<p><a href='admin/tickets.php' style='padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 8px;'>Go to Admin Tickets</a></p>";
echo "<p><a href='home.php' style='padding: 10px 20px; background: #65676b; color: white; text-decoration: none; border-radius: 8px;'>Back to Home</a></p>";
?>
