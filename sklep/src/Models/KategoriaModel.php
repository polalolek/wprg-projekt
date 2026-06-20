<?php

declare(strict_types=1);

class KategoriaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Zwraca listę wszystkich aktywnych kategorii w sklepie,
    public function wszystkie(): array
    {
        return $this->db->query(
            "SELECT k.*, parent.nazwa AS rodzic_nazwa,
                (SELECT COUNT(*) FROM produkty p WHERE p.kategoria_id = k.id AND p.aktywny = 1) AS liczba_produktow
             FROM kategorie k LEFT JOIN kategorie parent ON parent.id = k.rodzic_id
             WHERE k.aktywna = 1 ORDER BY k.kolejnosc, k.nazwa"
        )->fetchAll();
    }

    // Pobiera tylko kategorie główne, czyli te które nie są podkategorią czegoś innego
    public function glowne(): array
    {
        return $this->db->query(
            "SELECT * FROM kategorie WHERE aktywna = 1 AND rodzic_id IS NULL ORDER BY kolejnosc, nazwa"
        )->fetchAll();
    }

    // Zwraca podkategorie należące do wskazanej kategorii nadrzędnej, przyklad: "Koszulki" w kategorii "Odzież".
    public function podkategorie(int $rodzicId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM kategorie WHERE aktywna = 1 AND rodzic_id = ? ORDER BY kolejnosc, nazwa"
        );
        $stmt->execute([$rodzicId]);
        return $stmt->fetchAll();
    }

    // Wyszukuje kategorię na podstawie jej przyjaznego adresu URL (slug), np. "elektronika" zamiast id: 105.
    public function znajdzPoSlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM kategorie WHERE slug = ? AND aktywna = 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Pobiera dane jednej kategorii na podstawie jej id.
    // Zwraca null jeśli kategoria nie istnieje
    public function znajdzPoId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM kategorie WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Zapisuje nową kategorię w bazie danych i zwraca jej id.
    public function dodaj(array $dane): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO kategorie (nazwa, slug, opis, rodzic_id, kolejnosc) VALUES (:nazwa,:slug,:opis,:rodzic_id,:kolejnosc)"
        );
        $stmt->execute($dane);
        return (int)$this->db->lastInsertId();
    }

    // Aktualizuje wybrane informacje o kategorii (np. nazwę lub opis)
    // bez konieczności podawania wszystkich danych
    public function aktualizuj(int $id, array $dane): bool
    {
        $pola = array_map(fn($k) => "$k = :$k", array_keys($dane));
        $dane['id'] = $id;
        return $this->db->prepare(
            "UPDATE kategorie SET " . implode(', ', $pola) . " WHERE id = :id"
        )->execute($dane);
    }

    // Trwale usuwa kategorię z bazy danych na podstawie jej id. Zwraca true jeśli usunięcie się powiodło.
    public function usun(int $id): bool
    {
        return $this->db->prepare("DELETE FROM kategorie WHERE id = ?")->execute([$id]);
    }
}
