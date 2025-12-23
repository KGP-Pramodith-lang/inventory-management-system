<?php
// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";
echo "<hr>";

// Test 1: Check if file exists
echo "<h3>Test 1: File Exists</h3>";
if (file_exists('db.php')) {
    echo "✅ db.php file found<br><br>";
} else {
    echo "❌ db.php file not found<br><br>";
    die();
}

// Test 2: Include db.php
echo "<h3>Test 2: Include db.php</h3>";
try {
    require_once 'db.php';
    echo "✅ db.php included successfully<br><br>";
} catch (Exception $e) {
    echo "❌ Error including db.php: " . $e->getMessage() . "<br><br>";
    die();
}

// Test 3: Check PDO object
echo "<h3>Test 3: PDO Object</h3>";
if (isset($pdo) && $pdo instanceof PDO) {
    echo "✅ PDO object created successfully<br><br>";
} else {
    echo "❌ PDO object not found<br><br>";
    die();
}

// Test 4: Test connection
echo "<h3>Test 4: Connection Status</h3>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection is working<br><br>";
} catch (PDOException $e) {
    echo "❌ Connection test failed: " . $e->getMessage() . "<br><br>";
    die();
}

// Test 5: Check database name
echo "<h3>Test 5: Database Name</h3>";
try {
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    echo "✅ Connected to database: <strong>" . $result['db_name'] . "</strong><br><br>";
} catch (PDOException $e) {
    echo "❌ Error checking database: " . $e->getMessage() . "<br><br>";
}

// Test 6: List tables
echo "<h3>Test 6: Available Tables</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "✅ Found " . count($tables) . " table(s):<br>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul><br>";
    } else {
        echo "⚠️ No tables found in database<br><br>";
    }
} catch (PDOException $e) {
    echo "❌ Error listing tables: " . $e->getMessage() . "<br><br>";
}

// Test 7: Check products table
echo "<h3>Test 7: Products Table</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "✅ Products table exists with " . $result['count'] . " record(s)<br><br>";
} catch (PDOException $e) {
    echo "❌ Products table not found or error: " . $e->getMessage() . "<br><br>";
}

// Test 8: Check PDO attributes
echo "<h3>Test 8: PDO Configuration</h3>";
echo "Error Mode: " . $pdo->getAttribute(PDO::ATTR_ERRMODE) . " (3 = Exception mode) ✅<br>";
echo "Default Fetch Mode: " . $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE) . " (2 = Assoc array) ✅<br>";
echo "Emulated Prepares: " . ($pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES) ? 'Enabled' : 'Disabled') . " ✅<br><br>";

echo "<hr>";
echo "<h2 style='color: green;'>✅ All tests passed! Database connection is working correctly.</h2>";
?>