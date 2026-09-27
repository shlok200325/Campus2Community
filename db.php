<?php
/**
 * Campus2Community Database Connection (PDO)
 * Automatically ensures database and tables are provisioned.
 */

require_once __DIR__ . '/config.php';

function getDbConnection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = DB_HOST;
    $port = DB_PORT;
    $dbName = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;

    try {
        // Connect to server (without DB first) to ensure DB exists
        $dsnServer = "mysql:host={$host};port={$port};charset=utf8mb4";
        $serverPdo = new PDO($dsnServer, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Create DB if not exists
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Now connect to the database
        $dsnDb = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsnDb, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // Check if tables exist, if not initialize schema from database.sql
        $stmt = $pdo->query("SHOW TABLES LIKE 'citizens'");
        if ($stmt->rowCount() === 0) {
            initializeDatabaseSchema($pdo);
        }

        return $pdo;
    } catch (PDOException $e) {
        // If MySQL fails, log and output structured error
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

function initializeDatabaseSchema($pdo) {
    $sqlFile = __DIR__ . '/database.sql';
    if (file_exists($sqlFile)) {
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        } catch (Exception $e) {}

        $sql = file_get_contents($sqlFile);
        
        // Remove CREATE DATABASE and USE statements from execution block if any
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmtSql) {
            if (!empty($stmtSql) && 
                stripos($stmtSql, 'CREATE DATABASE') === false && 
                stripos($stmtSql, 'USE `c2c_portal`') === false) {
                try {
                    $pdo->exec($stmtSql);
                } catch (PDOException $ex) {
                    // Ignore minor drop table errors during init
                }
            }
        }

        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        } catch (Exception $e) {}
    }
}
