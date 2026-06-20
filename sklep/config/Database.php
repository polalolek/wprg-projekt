<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dbPath = dirname(__DIR__) . '/database/sklep.db';
            $schemaPath = dirname(__DIR__) . '/database/schema.sql';

            $new = !file_exists($dbPath);

            try {
                self::$instance = new PDO(
                    'sqlite:' . $dbPath,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
                self::$instance->exec('PRAGMA foreign_keys = ON');
                self::$instance->exec('PRAGMA journal_mode = WAL');

                if ($new && file_exists($schemaPath)) {
                    $sql = file_get_contents($schemaPath);
                    self::$instance->exec($sql);
                }
            } catch (PDOException $e) {
                die('Błąd połączenia z bazą danych: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
