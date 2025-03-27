<?php

/**
 * Attempts to allocate streaming accounts for a successfully paid order.
 * MUST be called within a database transaction if called alongside payment updates.
 *
 * @param string $order_id The ID of the order to process.
 * @param PDO $pdo The PDO database connection object.
 * @return bool True on successful allocation, false on failure.
 */
function allocateAccountsForOrder(string $order_id, PDO $pdo): bool
{
    try {
        // 1. Get Order Details for the given order
        $details_stmt = $pdo->prepare("SELECT od.order_detail_id, od.product_id, od.quantity, p.name as product_name
                                       FROM OrderDetails od
                                       JOIN Products p ON od.product_id = p.product_id
                                       WHERE od.order_id = ?");
        $details_stmt->execute([$order_id]);
        $order_details = $details_stmt->fetchAll();

        if (!$order_details) {
            error_log("Allocation Error: No order details found for order ID {$order_id}");
            return false; // Or throw exception
        }

        $allocated_accounts_info = []; // To potentially send to user later

        // 2. Loop through each product line in the order
        foreach ($order_details as $detail) {
            $order_detail_id = $detail['order_detail_id'];
            $product_id = $detail['product_id'];
            $quantity_needed = $detail['quantity'];

            // 3. Find and LOCK available accounts for this product
            // IMPORTANT: FOR UPDATE locks the selected rows until the transaction ends.
            $find_accounts_stmt = $pdo->prepare("SELECT streaming_account_id, username, password
                                                 FROM StreamingAccounts
                                                 WHERE product_id = ? AND status = 'พร้อมใช้งาน'
                                                 ORDER BY added_at ASC -- FIFO
                                                 LIMIT ?
                                                 FOR UPDATE"); // Lock rows
            $find_accounts_stmt->execute([$product_id, $quantity_needed]);
            $available_accounts = $find_accounts_stmt->fetchAll();

            // 4. Check if enough accounts were found and locked
            if (count($available_accounts) < $quantity_needed) {
                // Critical Error: Stock mismatch after payment!
                error_log("CRITICAL ALLOCATION ERROR: Not enough stock for product {$product_id} ({$detail['product_name']}) in order {$order_id}. Needed: {$quantity_needed}, Found: " . count($available_accounts));
                // The transaction should be rolled back by the caller.
                return false;
            }

            // 5. Allocate the found accounts
            $update_account_stmt = $pdo->prepare("UPDATE StreamingAccounts SET status = 'ถูกใช้งานแล้ว' WHERE streaming_account_id = ?");
            $insert_item_stmt = $pdo->prepare("INSERT INTO OrderItems (order_detail_id, streaming_account_id, delivered_at) VALUES (?, ?, NOW())");

            foreach ($available_accounts as $account) {
                $account_id = $account['streaming_account_id'];

                // Update account status
                $update_account_stmt->execute([$account_id]);

                // Insert into OrderItems to link account to order detail
                $insert_item_stmt->execute([$order_detail_id, $account_id]);

                // Store info for notification (handle password security carefully!)
                $allocated_accounts_info[] = [
                    'product_name' => $detail['product_name'], // Include product name
                    'username' => $account['username'],
                    // NEVER email plain password in production. This is just for demonstration.
                    // Consider a secure delivery method or one-time view link.
                    'password_insecure_demo' => $account['password']
                ];
            }

             // Optional: Add stock transaction record for 'sell'
             $stock_trans_stmt = $pdo->prepare("INSERT INTO StockTransactions (product_id, transaction_type, quantity, admin_id) VALUES (?, 'ขายออนไลน์', ?, ?)");
             $system_admin_id = 'A000'; // Assuming a system admin ID exists for automated actions
             $stock_trans_stmt->execute([$product_id, -$quantity_needed, $system_admin_id]);


        } // End loop through order details

        // If loop completes without error, allocation was successful for all items.
        // The transaction should be committed by the caller.

        // Store allocated info in session for temporary display/retrieval (DEMO ONLY)
        $_SESSION['last_allocated_accounts_' . $order_id] = $allocated_accounts_info;

        return true;

    } catch (PDOException $e) {
        error_log("Allocation Exception for order {$order_id}: " . $e->getMessage());
        // Ensure transaction is rolled back by the caller.
        return false;
    }
}
?>