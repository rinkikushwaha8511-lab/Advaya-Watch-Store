<?php
/**
 * Advaya Watch Store - Database Connection Configuration
 * 
 * Uses PDO for database connectivity with strict error mode.
 */

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'advaya_db');
define('DB_USER', 'root');
define('DB_PASS', '1234');
define('DB_CHARSET', 'utf8mb4');

/**
 * Establishes and returns a PDO database connection.
 * 
 * @return PDO
 * @throws PDOException if connection fails
 */
function getDBConnection(): PDO {
    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // In production, log the error and display a user-friendly message.
        // For development/verification, we throw the exception or handle it locally.
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
}
