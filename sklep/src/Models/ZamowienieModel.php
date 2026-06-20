<?php

declare(strict_types=1);

class ZamowienieModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Tworzy unikalny numer zamówienia w formacie "ZAM-YYYYMMDD-XXXXXX",
    public function generujNumer(): string
    {
        return 'ZAM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    // Składa nowe zamówienie w bazie zapisuje dane klienta,
    // listę produktów, zmniejsza stany magazynowe i tworzy historię.
    // Jeśli cokolwiek się nie uda, wszystkie zmiany są cofane.
    public function utworz(array $dane, array $pozycje): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO zamowienia (numer, uzytkownik_id, status, metoda_platnosci, metoda_dostawy,
                 koszt_dostawy, wartosc_produktow, rabat, wartosc_laczna,
                 imie, nazwisko, email, telefon, ulica, nr_domu, kod_pocztowy, miasto, kraj, uwagi, kod_rabatowy)
                 VALUES (:numer,:uzytkownik_id,:status,:metoda_platnosci,:metoda_dostawy,
                 :koszt_dostawy,:wartosc_produktow,:rabat,:wartosc_laczna,
                 :imie,:nazwisko,:email,:telefon,:ulica,:nr_domu,:kod_pocztowy,:miasto,:kraj,:uwagi,:kod_rabatowy)"
            );
            $stmt->execute($dane);
            $zamId = (int)$this->db->lastInsertId();

            foreach ($pozycje as $poz) {
                $this->db->prepare(
                    "INSERT INTO pozycje_zamowien (zamowienie_id, produkt_id, nazwa_produktu, sku, cena_jednostkowa, ilosc, wartosc)
                     VALUES (?,?,?,?,?,?,?)"
                )->execute([$zamId, $poz['produkt_id'], $poz['nazwa'], $poz['sku'], $poz['cena'], $poz['ilosc'], $poz['wartosc']]);

                // Zmniejsz stan magazynowy
                $this->db->prepare("UPDATE produkty SET stan_magazynowy = stan_magazynowy - ? WHERE id = ?")
                    ->execute([$poz['ilosc'], $poz['produkt_id']]);
            }

            // Dodaj wpis do historii
            $this->db->prepare(
                "INSERT INTO historia_zamowien (zamowienie_id, nowy_status, komentarz) VALUES (?, 'nowe', 'Zamówienie złożone')"
            )->execute([$zamId]);

            $this->db->commit();
            return $zamId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    // Pobiera pełne dane jednego zamówienia łącznie z listą zamawianych produktów na podstawie jego id
    public function znajdzPoId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM zamowienia WHERE id = ?");
        $stmt->execute([$id]);
        $zam = $stmt->fetch();
        if (!$zam) return null;

        $stmt2 = $this->db->prepare("SELECT * FROM pozycje_zamowien WHERE zamowienie_id = ?");
        $stmt2->execute([$id]);
        $zam['pozycje'] = $stmt2->fetchAll();

        return $zam;
    }

    // Zwraca wszystkie zamówienia złożone przez danego użytkownika,
    // posortowane od najnowszego
    public function znajdzUzytkownika(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM zamowienia WHERE uzytkownik_id = ? ORDER BY data_zamowienia DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Pobiera listę zamówień dla panelu administratora z możliwością
    // filtrowania po statusie lub wyszukiwania po numerze, e-mailu lub nazwisku.
    public function wszystkie(int $strona = 1, ?string $status = null, ?string $szukaj = null): array
    {
        $offset = ($strona - 1) * 20;
        $warunki = [];
        $params  = [];

        if ($status) {
            $warunki[] = 'status = :status';
            $params[':status'] = $status;
        }
        if ($szukaj) {
            $warunki[] = '(numer LIKE :s OR email LIKE :s2 OR nazwisko LIKE :s3)';
            $params[':s']  = "%$szukaj%";
            $params[':s2'] = "%$szukaj%";
            $params[':s3'] = "%$szukaj%";
        }

        $where = $warunki ? 'WHERE ' . implode(' AND ', $warunki) : '';
        $sql   = "SELECT * FROM zamowienia $where ORDER BY data_zamowienia DESC LIMIT 20 OFFSET :offset";
        $stmt  = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Zmienia status zamówienia np z "nowe" na "wysłane" i
    // automatycznie zapisuje tę zmianę w historii zamówienia.
    public function zmienStatus(int $id, string $status, string $komentarz = ''): bool
    {
        $stare = $this->db->prepare("SELECT status FROM zamowienia WHERE id = ?");
        $stare->execute([$id]);
        $staryStatus = $stare->fetchColumn();

        $ok = $this->db->prepare(
            "UPDATE zamowienia SET status = ?, data_aktualizacji = datetime('now') WHERE id = ?"
        )->execute([$status, $id]);

        if ($ok) {
            $this->db->prepare(
                "INSERT INTO historia_zamowien (zamowienie_id, stary_status, nowy_status, komentarz) VALUES (?,?,?,?)"
            )->execute([$id, $staryStatus, $status, $komentarz]);
        }

        return $ok;
    }

    // Pobiera pełną historię zmian statusu danego zamówienia
    public function historia(int $zamId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM historia_zamowien WHERE zamowienie_id = ? ORDER BY data ASC"
        );
        $stmt->execute([$zamId]);
        return $stmt->fetchAll();
    }

    // Generuje podsumowanie sprzedaży dla admina
    public function statystyki(): array
    {
        $stats = [];

        $stmt = $this->db->query(
            "SELECT status, COUNT(*) AS liczba, COALESCE(SUM(wartosc_laczna),0) AS suma
             FROM zamowienia GROUP BY status"
        );
        $stats['statusy'] = $stmt->fetchAll();

        $stmt = $this->db->query(
            "SELECT strftime('%Y-%m', data_zamowienia) AS miesiac,
                COUNT(*) AS liczba, COALESCE(SUM(wartosc_laczna),0) AS suma
             FROM zamowienia WHERE data_zamowienia >= date('now','-6 months')
             GROUP BY miesiac ORDER BY miesiac"
        );
        $stats['miesiace'] = $stmt->fetchAll();

        $stats['lacznie'] = (float)$this->db->query(
            "SELECT COALESCE(SUM(wartosc_laczna),0) FROM zamowienia WHERE status NOT IN ('anulowane','zwrot')"
        )->fetchColumn();

        return $stats;
    }
}
