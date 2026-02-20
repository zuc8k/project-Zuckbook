<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = intval($_SESSION['user_id']);

echo "<h2>Testing Subscription Cancellation</h2>";

// Get latest cancellation ticket
$ticketStmt = $conn->prepare("
    SELECT * FROM support_tickets 
    WHERE user_id = ? 
    AND (ticket_code LIKE '%CANCEL%' OR subject LIKE '%Cancellation%')
    ORDER BY id DESC 
    LIMIT 1
");
$ticketStmt->bind_param("i", $user_id);
$ticketStmt->execute();
$ticket = $ticketStmt->get_result()->fetch_assoc();

if (!$ticket) {
    die("<p style='color: red;'>No cancellation ticket found for your account.</p>");
}

echo "<h3>Found Ticket:</h3>";
echo "<pre>";
print_r($ticket);
echo "</pre>";

// Check cancellation_data
if (isset($ticket['cancellation_data']) && !empty($ticket['cancellation_data'])) {
    $cancellationData = json_decode($ticket['cancellation_data'], true);
    
    echo "<h3>Cancellation Data:</h3>";
    echo "<pre>";
    print_r($cancellationData);
    echo "</pre>";
    
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        echo "<h3 style='color: orange;'>Processing Cancellation...</h3>";
        
        try {
            $refund_amount = $cancellationData['refund_amount'] ?? 0;
            
            // Start transaction
            $conn->begin_transaction();
            
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
            
            if ($updateStmt->execute()) {
                echo "<p style='color: green;'>✓ User subscription cancelled</p>";
                echo "<p style='color: green;'>✓ Refunded {$refund_amount} coins</p>";
            } else {
                throw new Exception("Failed to update user: " . $conn->error);
            }
            
            // Update subscription record
            $updateSubStmt = $conn->prepare("
                UPDATE subscriptions 
                SET status = 'cancelled' 
                WHERE user_id = ? 
                AND status = 'active'
            ");
            $updateSubStmt->bind_param("i", $user_id);
            
            if ($updateSubStmt->execute()) {
                echo "<p style='color: green;'>✓ Subscription record updated</p>";
            }
            
            // Update ticket status
            $updateTicketStmt = $conn->prepare("UPDATE support_tickets SET status = 'done' WHERE id = ?");
            $updateTicketStmt->bind_param("i", $ticket['id']);
            $updateTicketStmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            echo "<h3 style='color: green;'>✅ Cancellation Completed Successfully!</h3>";
            echo "<p><a href='/my_subscription.php' style='padding: 10px 20px; background: #1877f2; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>Check My Subscription</a></p>";
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<h3>Ready to Cancel?</h3>";
        echo "<p>Refund Amount: <strong>{$cancellationData['refund_amount']} coins</strong></p>";
        echo "<p><a href='?confirm=yes' style='padding: 10px 20px; background: #ef4444; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>Yes, Cancel My Subscription</a></p>";
    }
    
} else {
    echo "<p style='color: red;'>No cancellation data found in ticket.</p>";
    echo "<p>This might be an old ticket format. Please create a new cancellation request.</p>";
}
?>
