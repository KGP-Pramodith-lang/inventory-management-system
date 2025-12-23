<?php

function ims_ensure_refunds_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS refunds (
            id INT(11) NOT NULL AUTO_INCREMENT,
            bill_id INT(11) NOT NULL,
            refund_amount DECIMAL(10,2) NOT NULL,
            reason TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            requested_by VARCHAR(50) NOT NULL,
            requested_role VARCHAR(20) NOT NULL,
            requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_by VARCHAR(50) NULL,
            reviewed_at TIMESTAMP NULL DEFAULT NULL,
            review_note VARCHAR(255) NULL,
            PRIMARY KEY (id),
            KEY idx_bill_id (bill_id),
            KEY idx_status (status),
            CONSTRAINT fk_refunds_bill_id FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    // Basic safety: enforce valid default status for older installs.
    try {
        $pdo->exec("UPDATE refunds SET status = 'pending' WHERE status IS NULL OR status = ''");
    } catch (Throwable $e) {
        // Non-fatal.
    }
}
