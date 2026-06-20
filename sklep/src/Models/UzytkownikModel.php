<?php

declare(strict_types=1);

class UzytkownikModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Wyszukuje konto użytkownika na podstawie podanego adresu e-mail
    public function znajdzPoEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM uzytkownicy WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Pobiera dane konta użytkownika na podstawie jego id.
    // Zwraca null jeśli konto nie istnieje.
    public function znajdzPoId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM uzytkownicy WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Tworzy nowe konto użytkownika, hasło jest
    // bezpiecznie zaszyfrowane przed zapisem,
    // "haslo123" => "$2a$12$Ey4Iy3Gi8oIdATlHOX5XGuWn.RKiyTNybVlOY9MOzDVP/5Mpb63q." przyklad
    // nigdy nie trafia do bazy w oryginalnej formie.
    public function zarejestruj(string $imie, string $nazwisko, string $email, string $haslo): int
    {
        $hash = password_hash($haslo, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare(
            "INSERT INTO uzytkownicy (imie, nazwisko, email, haslo) VALUES (?,?,?,?)"
        );
        $stmt->execute([$imie, $nazwisko, $email, $hash]);
        return (int)$this->db->lastInsertId();
    }

    // Sprawdza czy hasło wpisane przez użytkownika przy logowaniu zgadza
    // się z zaszyfrowaną wersją zapisaną w bazie danych.
    public function weryfikujHaslo(string $haslo, string $hash): bool
    {
        return password_verify($haslo, $hash);
    }

    // Pozwala użytkownikowi zaktualizować swoje dane osobowe (imię, nazwisko, telefon)
    public function aktualizujProfil(int $id, array $dane): bool
    {
        $dozwolone = ['imie', 'nazwisko', 'telefon'];
        $dane = array_intersect_key($dane, array_flip($dozwolone));
        if (empty($dane)) return false;

        $pola = array_map(fn($k) => "$k = :$k", array_keys($dane));
        $dane['id'] = $id;
        return $this->db->prepare(
            "UPDATE uzytkownicy SET " . implode(', ', $pola) . " WHERE id = :id"
        )->execute($dane);
    }

    // Zmienia hasło użytkownika nowe hasło jest zaszyfrowane przed zapisem, tak samo jak przy rejestracji.
    public function zmienHaslo(int $id, string $noweHaslo): bool
    {
        $hash = password_hash($noweHaslo, PASSWORD_BCRYPT, ['cost' => 12]);
        return $this->db->prepare("UPDATE uzytkownicy SET haslo = ? WHERE id = ?")->execute([$hash, $id]);
    }
    // Dodaje punkty lojalnościowe do konta użytkownika
    public function dodajPunkty(int $userId, int $punkty): void
    {
        $this->db->prepare(
            "UPDATE uzytkownicy SET punkty_lojalnosciowe = punkty_lojalnosciowe + ? WHERE id = ?"
        )->execute([$punkty, $userId]);
    }

    // Zwraca listę wszystkich adresów dostawy zapisanych na koncie danego użytkownika.
    public function adresy(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM adresy WHERE uzytkownik_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Dodaje nowy adres dostawy do konta użytkownika
    public function dodajAdres(int $userId, array $dane): int
    {
        if (!empty($dane['domyslny'])) {
            $this->db->prepare("UPDATE adresy SET domyslny = 0 WHERE uzytkownik_id = ?")->execute([$userId]);
        }
        $stmt = $this->db->prepare(
            "INSERT INTO adresy (uzytkownik_id, imie, nazwisko, ulica, nr_domu, kod_pocztowy, miasto, kraj, domyslny)
             VALUES (:uzytkownik_id,:imie,:nazwisko,:ulica,:nr_domu,:kod_pocztowy,:miasto,:kraj,:domyslny)"
        );
        $dane['uzytkownik_id'] = $userId;
        $stmt->execute($dane);
        return (int)$this->db->lastInsertId();
    }

    // Pobiera listę wszystkich zarejestrowanych użytkowników sklepu tylko dla administratora.
    public function wszyscy(): array
    {
        return $this->db->query(
            "SELECT id, imie, nazwisko, email, rola, aktywny, punkty_lojalnosciowe, data_rejestracji FROM uzytkownicy ORDER BY data_rejestracji DESC"
        )->fetchAll();
    }

    // Sprawdza czy podany adres e-mail ma poprawny format
    // (np. czy zawiera "@" i domenę). Zwraca true jeśli jest prawidłowy.
    public function walidujEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // Sprawdza czy hasło spełnia wymagania bezpieczeństwa (min. 8 znaków, wielka litera, cyfra)
    // i zwraca listę błędów jeśli coś jest nie tak.
    public function walidujHaslo(string $haslo): array
    {
        $bledy = [];
        if (strlen($haslo) < 8) $bledy[] = 'Hasło musi mieć co najmniej 8 znaków.';
        if (!preg_match('/[A-Z]/', $haslo)) $bledy[] = 'Hasło musi zawierać wielką literę.';
        if (!preg_match('/[0-9]/', $haslo)) $bledy[] = 'Hasło musi zawierać cyfrę.';
        return $bledy;
    }
}
