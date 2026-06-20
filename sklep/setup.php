#!/usr/bin/env php
<?php

declare(strict_types=1);

echo "=== Sklep Online — Setup ===\n\n";

$dbDir    = __DIR__ . '/database';
$dbFile   = $dbDir . '/sklep.db';
$schema   = $dbDir . '/schema.sql';
$imgDir   = __DIR__ . '/public/images/produkty';

// Utwórz katalog bazy danych
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
    echo "✓ Katalog database/ utworzony\n";
}

// Katalog uploadów
if (!is_dir($imgDir)) {
    mkdir($imgDir, 0755, true);
    echo "✓ Katalog public/images/produkty/ utworzony\n";
}

// Usuń starą bazę jeśli istnieje (opcjonalnie)
if (file_exists($dbFile)) {
    $ans = readline("Baza danych już istnieje. Czy chcesz ją zresetować? [t/N]: ");
    if (strtolower(trim($ans)) !== 't') {
        echo "Anulowano. Baza nie została zmieniona.\n";
        exit(0);
    }
    unlink($dbFile);
    echo "✓ Stara baza usunięta\n";
}

// Utwórz bazę
try {
    $pdo = new PDO('sqlite:' . $dbFile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    $sql = file_get_contents($schema);
    $pdo->exec($sql);

    // Upewnij się, że konta testowe mają poprawne hasło (Admin123!)
    $hashTest = password_hash('Admin123!', PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare(
        "UPDATE uzytkownicy SET haslo = ? WHERE email IN ('admin@sklep.pl', 'anna@example.com')"
    )->execute([$hashTest]);

    echo "✓ Baza danych utworzona: database/sklep.db\n";
    echo "✓ Schema załadowana\n";
    echo "✓ Dane przykładowe załadowane\n\n";

    // Weryfikacja
    $produkty = $pdo->query("SELECT COUNT(*) FROM produkty")->fetchColumn();
    $kategorie = $pdo->query("SELECT COUNT(*) FROM kategorie")->fetchColumn();
    $uzytkownicy = $pdo->query("SELECT COUNT(*) FROM uzytkownicy")->fetchColumn();

    echo "Produkty:    $produkty\n";
    echo "Kategorie:   $kategorie\n";
    echo "Użytkownicy: $uzytkownicy\n\n";

    echo "=== Konta testowe ===\n";
    echo "Admin:  admin@sklep.pl  / Admin123!\n";
    echo "Klient: anna@example.com / Admin123!\n\n";

    echo "=== Kody rabatowe ===\n";
    echo "WITAJ10 — 10% zniżki (min. 100 zł)\n";
    echo "LATO50  — 50 zł zniżki (min. 200 zł)\n\n";

    echo "=== Uruchomienie ===\n";
    echo "php -S localhost:8080 -t public\n";
    echo "Otwórz: http://localhost:8080\n\n";
    echo "✓ Setup zakończony pomyślnie!\n";

} catch (PDOException $e) {
    echo "✗ Błąd: " . $e->getMessage() . "\n";
    exit(1);
}
