<?php

declare(strict_types=1);

class ProduktModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Pobiera listę produktów ze sklepu z możliwością filtrowania
    // (kategoria, cena, szukana fraza), sortowania i podziału na strony.
    public function znajdzWszystkie(
        int $strona = 1,
        int $naStronie = ITEMS_PER_PAGE,
        ?int $kategoriaId = null,
        ?string $szukaj = null,
        string $sortowanie = 'data_dodania',
        string $kierunek = 'DESC',
        ?float $cenaMin = null,
        ?float $cenaMax = null
    ): array {
        $offset = ($strona - 1) * $naStronie;
        $params = [];
        $warunki = ['p.aktywny = 1'];

        if ($kategoriaId) {
            // Uwzględnij podkategorie
            $warunki[] = '(p.kategoria_id = :kat OR k.rodzic_id = :kat2)';
            $params[':kat']  = $kategoriaId;
            $params[':kat2'] = $kategoriaId;
        }
        if ($szukaj) {
            $warunki[] = '(p.nazwa LIKE :szukaj OR p.opis_krotki LIKE :szukaj2)';
            $params[':szukaj']  = '%' . $szukaj . '%';
            $params[':szukaj2'] = '%' . $szukaj . '%';
        }
        if ($cenaMin !== null) {
            $warunki[] = 'COALESCE(p.cena_promocyjna, p.cena) >= :cena_min';
            $params[':cena_min'] = $cenaMin;
        }
        if ($cenaMax !== null) {
            $warunki[] = 'COALESCE(p.cena_promocyjna, p.cena) <= :cena_max';
            $params[':cena_max'] = $cenaMax;
        }

        $dozwoloneSortowania = ['cena', 'nazwa', 'data_dodania', 'stan_magazynowy'];
        if (!in_array($sortowanie, $dozwoloneSortowania, true)) {
            $sortowanie = 'data_dodania';
        }
        $kierunek = strtoupper($kierunek) === 'ASC' ? 'ASC' : 'DESC';

        $where = implode(' AND ', $warunki);

        $sql = "SELECT p.*, k.nazwa AS kategoria_nazwa,
                    COALESCE(p.cena_promocyjna, p.cena) AS cena_aktualna,
                    COALESCE(AVG(r.ocena), 0) AS srednia_ocena,
                    COUNT(DISTINCT r.id) AS liczba_ocen
                FROM produkty p
                LEFT JOIN kategorie k ON k.id = p.kategoria_id
                LEFT JOIN recenzje r ON r.produkt_id = p.id AND r.zatwierdzona = 1
                WHERE {$where}
                GROUP BY p.id
                ORDER BY p.{$sortowanie} {$kierunek}
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit',  $naStronie, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Zlicza ile produktów spełnia podane kryteria wyszukiwania
    public function liczWszystkie(
        ?int $kategoriaId = null,
        ?string $szukaj = null,
        ?float $cenaMin = null,
        ?float $cenaMax = null
    ): int {
        $params = [];
        $warunki = ['p.aktywny = 1'];

        if ($kategoriaId) {
            $warunki[] = '(p.kategoria_id = :kat OR k.rodzic_id = :kat2)';
            $params[':kat']  = $kategoriaId;
            $params[':kat2'] = $kategoriaId;
        }
        if ($szukaj) {
            $warunki[] = '(p.nazwa LIKE :szukaj OR p.opis_krotki LIKE :szukaj2)';
            $params[':szukaj']  = '%' . $szukaj . '%';
            $params[':szukaj2'] = '%' . $szukaj . '%';
        }
        if ($cenaMin !== null) {
            $warunki[] = 'COALESCE(p.cena_promocyjna, p.cena) >= :cena_min';
            $params[':cena_min'] = $cenaMin;
        }
        if ($cenaMax !== null) {
            $warunki[] = 'COALESCE(p.cena_promocyjna, p.cena) <= :cena_max';
            $params[':cena_max'] = $cenaMax;
        }

        $where = implode(' AND ', $warunki);
        $sql = "SELECT COUNT(DISTINCT p.id) FROM produkty p LEFT JOIN kategorie k ON k.id = p.kategoria_id WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Wyszukuje jeden konkretny produkt po jego adresie URL np. "laptop hp", razem ze średnią ocen i liczbą recenzji.
    public function znajdzPoSlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, k.nazwa AS kategoria_nazwa, k.slug AS kategoria_slug,
                COALESCE(p.cena_promocyjna, p.cena) AS cena_aktualna,
                COALESCE(AVG(r.ocena), 0) AS srednia_ocena,
                COUNT(DISTINCT r.id) AS liczba_ocen
             FROM produkty p
             LEFT JOIN kategorie k ON k.id = p.kategoria_id
             LEFT JOIN recenzje r ON r.produkt_id = p.id AND r.zatwierdzona = 1
             WHERE p.slug = ? AND p.aktywny = 1
             GROUP BY p.id"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Pobiera dane produktu na podstawie jego wewnętrznego id.
    // Zwraca null jeśli produkt nie istnieje.
    public function znajdzPoId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM produkty WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Zwraca produkty oznaczone jako wyróżnione — używane np. do sekcji "Polecamy" na stronie głównej.
    public function wyroznione(int $limit = 4): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, COALESCE(p.cena_promocyjna, p.cena) AS cena_aktualna
             FROM produkty p WHERE p.aktywny = 1 AND p.wyrozniony = 1 LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    // Pobiera inne produkty z tej samej kategorii, które można zaproponować
    // klientowi jako "Podobne produkty" na stronie produktu.
    public function podobne(int $produktId, int $kategoriaId, int $limit = 4): array
    {
        $stmt = $this->db->prepare(
            "SELECT *, COALESCE(cena_promocyjna, cena) AS cena_aktualna
             FROM produkty WHERE aktywny = 1 AND kategoria_id = ? AND id != ? LIMIT ?"
        );
        $stmt->execute([$kategoriaId, $produktId, $limit]);
        return $stmt->fetchAll();
    }

    // Zapisuje nowy produkt w bazie danych sklepu i zwraca nadany mu id.
    public function dodaj(array $dane): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO produkty (kategoria_id, nazwa, slug, opis, opis_krotki, cena, cena_promocyjna,
             stan_magazynowy, sku, zdjecie_glowne, aktywny, wyrozniony)
             VALUES (:kategoria_id, :nazwa, :slug, :opis, :opis_krotki, :cena, :cena_promocyjna,
             :stan_magazynowy, :sku, :zdjecie_glowne, :aktywny, :wyrozniony)"
        );
        $stmt->execute($dane);
        return (int)$this->db->lastInsertId();
    }

    // Aktualizuje wybrane dane produktu np. cenę lub opis bez potrzeby wpisywania wszystkich informacji od nowa
    public function aktualizuj(int $id, array $dane): bool
    {
        $pola = array_map(fn($k) => "$k = :$k", array_keys($dane));
        $sql = "UPDATE produkty SET " . implode(', ', $pola) . " WHERE id = :id";
        $dane['id'] = $id;
        return $this->db->prepare($sql)->execute($dane);
    }

    // Trwale usuwa produkt ze sklepu na podstawie jego id.
    // Zwraca true jeśli usunięcie się powiodło.
    public function usun(int $id): bool
    {
        return $this->db->prepare("DELETE FROM produkty WHERE id = ?")->execute([$id]);
    }

    // Pobiera wszystkie zatwierdzone recenzje danego produktu wraz z imieniem i nazwiskiem
    // autora, posortowane od najnowszych.
    public function recenzje(int $produktId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.imie, u.nazwisko FROM recenzje r
             LEFT JOIN uzytkownicy u ON u.id = r.uzytkownik_id
             WHERE r.produkt_id = ? AND r.zatwierdzona = 1 ORDER BY r.data_dodania DESC"
        );
        $stmt->execute([$produktId]);
        return $stmt->fetchAll();
    }

    // Zamienia dowolny tekst np. nazwę produktu na przyjazny adres URL
    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $map  = ['ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ź'=>'z','ż'=>'z'];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', trim($text));
        return $text;
    }
}
