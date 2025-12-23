<?php
$host = 'localhost';
$dbname = 'ims';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Disable emulated prepared statements for better security
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch(PDOException $e) {
    // Log the error (in production, log to file instead of displaying)
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display user-friendly error message
    if (php_sapi_name() === 'cli') {
        // Command line
        die("Database connection failed. Please check the configuration.\n");
    } else {
        // Web interface
        http_response_code(500);
        die("
            <!DOCTYPE html>
            <html>
            <head>
                <title>Database Error</title>
                <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
            </head>
            <body class='container mt-5'>
                <div class='alert alert-danger'>
                    <h4 class='alert-heading'>Database Connection Error</h4>
                    <p>Unable to connect to the database. Please contact the administrator.</p>
                    <hr>
                    <p class='mb-0'><small>Error details have been logged.</small></p>
                </div>
            </body>
            </html>
        ");
    }
}
?>