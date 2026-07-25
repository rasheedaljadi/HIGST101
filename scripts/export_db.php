<?php

/**
 * Standalone Database Exporter for Bagisto/Laravel.
 * Auto-detects MySQL port and host if default fails.
 */

$envFile = __DIR__ . '/../.env';
$outputFile = __DIR__ . '/../higest_database.sql';

echo "----------------------------------------\n";
echo "Starting Standalone Database Export...\n";
echo "----------------------------------------\n";

if (!file_exists($envFile)) {
    echo "ERROR: .env file not found at {$envFile}\n";
    exit(1);
}

// Parse .env manually
$env = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if (strpos($line, '#') === 0) continue;
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $env[$key] = $value;
    }
}

$defaultHost = $env['DB_HOST'] ?? '127.0.0.1';
$defaultPort = $env['DB_PORT'] ?? '3306';
$database    = $env['DB_DATABASE'] ?? 'higest';
$username    = $env['DB_USERNAME'] ?? 'root';
$password    = $env['DB_PASSWORD'] ?? '';

$hostsToTry = array_unique([$defaultHost, '127.0.0.1', 'localhost']);
$portsToTry = array_unique([(int)$defaultPort, 3306, 3307, 3308, 33060]);

$pdo = null;
$connectedHost = null;
$connectedPort = null;

echo "Searching for active MySQL connection...\n";

foreach ($hostsToTry as $h) {
    foreach ($portsToTry as $p) {
        try {
            $dsn = "mysql:host={$h};port={$p};dbname={$database};charset=utf8mb4";
            $testPdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 2,
            ]);
            $pdo = $testPdo;
            $connectedHost = $h;
            $connectedPort = $p;
            break 2;
        } catch (\Throwable $e) {
            // continue trying
        }
    }
}

if (!$pdo) {
    // If database 'higest' does not exist yet, try connecting without dbname to list databases
    foreach ($hostsToTry as $h) {
        foreach ($portsToTry as $p) {
            try {
                $dsn = "mysql:host={$h};port={$p};charset=utf8mb4";
                $testPdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 2,
                ]);
                $dbs = $testPdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
                echo "Connected to MySQL at {$h}:{$p}, but database '{$database}' was not found.\n";
                echo "Available databases: " . implode(", ", $dbs) . "\n";
                exit(1);
            } catch (\Throwable $e) {
                // continue trying
            }
        }
    }

    echo "ERROR: Could not connect to MySQL server on any of the standard ports (3306, 3307, 3308).\n";
    echo "Please check if MySQL in XAMPP is running on a custom port.\n";
    exit(1);
}

echo "SUCCESS: Connected to MySQL at {$connectedHost}:{$connectedPort} (Database: {$database})\n";

try {
    $tablesStmt = $pdo->query('SHOW TABLES');
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    $handle = fopen($outputFile, 'w');
    if (!$handle) {
        throw new Exception("Could not open file for writing: " . $outputFile);
    }

    fwrite($handle, "-- ==============================================\n");
    fwrite($handle, "-- Bagisto Standalone Database Dump: {$database}\n");
    fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
    fwrite($handle, "-- ==============================================\n\n");
    fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
    fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($handle, "SET TIME_ZONE = \"+00:00\";\n\n");

    $count = 0;
    foreach ($tables as $table) {
        echo "[+] Exporting table: {$table}...\n";

        fwrite($handle, "-- ----------------------------------------------\n");
        fwrite($handle, "-- Table structure for `{$table}`\n");
        fwrite($handle, "-- ----------------------------------------------\n");
        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

        $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $createRow = $createStmt->fetch();
        $createSql = $createRow['Create Table'] ?? reset($createRow);
        fwrite($handle, $createSql . ";\n\n");

        $rowsStmt = $pdo->query("SELECT * FROM `{$table}`");
        $rows = $rowsStmt->fetchAll();

        if (count($rows) > 0) {
            fwrite($handle, "-- Data for `{$table}` (" . count($rows) . " rows)\n");
            $chunks = array_chunk($rows, 100);
            foreach ($chunks as $chunk) {
                $insertValues = [];
                foreach ($chunk as $row) {
                    $vals = array_map(function ($val) use ($pdo) {
                        if (is_null($val)) return 'NULL';
                        return $pdo->quote($val);
                    }, $row);
                    $insertValues[] = "(" . implode(", ", $vals) . ")";
                }

                if (!empty($insertValues)) {
                    $colsStr = implode("`, `", array_keys($chunk[0]));
                    $insertSql = "INSERT INTO `{$table}` (`{$colsStr}`) VALUES\n" . implode(",\n", $insertValues) . ";\n";
                    fwrite($handle, $insertSql);
                }
            }
            fwrite($handle, "\n");
        }
        $count++;
    }

    fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($handle);

    $fileSizeMB = round(filesize($outputFile) / (1024 * 1024), 2);
    echo "\n========================================\n";
    echo "SUCCESS: Exported {$count} tables to:\n";
    echo " -> " . realpath($outputFile) . "\n";
    echo " -> File Size: {$fileSizeMB} MB\n";
    echo "========================================\n";
} catch (\Throwable $e) {
    echo "\nERROR during export: " . $e->getMessage() . "\n";
    exit(1);
}
