<?php

declare(strict_types=1);

class KoszykModel
{
    private PDO $db;
    private ?int $userId;
    private string $sessionId;

    public function __construct()
    {
        $this->db        = Database::getInstance();
        $this->userId    = Auth::userId();
        $this->sessionId = session_id();
    }

    // Zwraca wszystkie produkty aktualnie dodane do koszyka
    // zarówno dla zalogowanego użytkownika, jak i dla gościa przeglądającego sklep bez konta.
    public function pozycje(): array
    {
        if ($this->userId) {
            $stmt = $this->db->prepare(
                "SELECT k.*, p.nazwa, p.slug, p.zdjecie_glowne, p.stan_magazynowy,
                    COALESCE(p.cena_promocyjna, p.cena) AS cena_aktualna
                 FROM koszyk k JOIN produkty p ON p.id = k.produkt_id
                 WHERE k.uzytkownik_id = ?"
            );
            $stmt->execute([$this->userId]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT k.*, p.nazwa, p.slug, p.zdjecie_glowne, p.stan_magazynowy,
                    COALESCE(p.cena_promocyjna, p.cena) AS cena_aktualna
                 FROM koszyk k JOIN produkty p ON p.id = k.produkt_id
                 WHERE k.sesja_id = ? AND k.uzytkownik_id IS NULL"
            );
            $stmt->execute([$this->sessionId]);
        }
        return $stmt->fetchAll();
    }

    // Zlicza łączną ilość sztuk produktów w koszyku to ta liczba wyświetlana zwykle przy ikonie koszyka w menu.
    public function liczbaPozycji(): int
    {
        if ($this->userId) {
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(ilosc),0) FROM koszyk WHERE uzytkownik_id = ?");
            $stmt->execute([$this->userId]);
        } else {
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(ilosc),0) FROM koszyk WHERE sesja_id = ? AND uzytkownik_id IS NULL");
            $stmt->execute([$this->sessionId]);
        }
        return (int)$stmt->fetchColumn();
    }

    // Dodaje produkt do koszyka; jeśli ten produkt jest już w koszyku,
    // po prostu zwiększa jego ilość zamiast dodawać duplikat.
    public function dodaj(int $produktId, int $ilosc = 1): bool
    {
        $stmtStan = $this->db->prepare("SELECT stan_magazynowy FROM produkty WHERE id = ? AND aktywny = 1");
        $stmtStan->execute([$produktId]);
        $stanMag = (int)$stmtStan->fetchColumn();
        if ($stanMag <= 0) return false;

        if ($this->userId) {
            $stmt = $this->db->prepare("SELECT id, ilosc FROM koszyk WHERE uzytkownik_id = ? AND produkt_id = ?");
            $stmt->execute([$this->userId, $produktId]);
        } else {
            $stmt = $this->db->prepare("SELECT id, ilosc FROM koszyk WHERE sesja_id = ? AND uzytkownik_id IS NULL AND produkt_id = ?");
            $stmt->execute([$this->sessionId, $produktId]);
        }
        $istniejacy = $stmt->fetch();

        if ($istniejacy) {
            $nowaIlosc = min($istniejacy['ilosc'] + $ilosc, $stanMag);
            return $this->db->prepare("UPDATE koszyk SET ilosc = ? WHERE id = ?")
                ->execute([$nowaIlosc, $istniejacy['id']]);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO koszyk (uzytkownik_id, sesja_id, produkt_id, ilosc) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$this->userId, $this->sessionId, $produktId, min($ilosc, $stanMag)]);
    }

    // Zmienia ilość danego produktu w koszyku; jeśli klient ustawi ilość na zero lub mniej,
    // produkt zostaje automatycznie usunięty z koszyka.
    public function aktualizujIlosc(int $koszykId, int $ilosc): bool
    {
        if ($ilosc <= 0) {
            return $this->usun($koszykId);
        }

        if ($this->userId) {
            $stmt = $this->db->prepare(
                "SELECT p.stan_magazynowy FROM koszyk k
                 JOIN produkty p ON p.id = k.produkt_id
                 WHERE k.id = ? AND k.uzytkownik_id = ?"
            );
            $stmt->execute([$koszykId, $this->userId]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT p.stan_magazynowy FROM koszyk k
                 JOIN produkty p ON p.id = k.produkt_id
                 WHERE k.id = ? AND k.sesja_id = ? AND k.uzytkownik_id IS NULL"
            );
            $stmt->execute([$koszykId, $this->sessionId]);
        }
        $stanMag = $stmt->fetchColumn();
        if ($stanMag === false) return false;

        $ilosc = min($ilosc, (int)$stanMag);
        if ($ilosc <= 0) {
            return $this->usun($koszykId);
        }

        return $this->db->prepare("UPDATE koszyk SET ilosc = ? WHERE id = ?")->execute([$ilosc, $koszykId]);
    }

    // Usuwa pojedynczy produkt z koszyka na podstawie jego pozycji w koszyku.
    public function usun(int $koszykId): bool
    {
        if ($this->userId) {
            return $this->db->prepare("DELETE FROM koszyk WHERE id = ? AND uzytkownik_id = ?")
                ->execute([$koszykId, $this->userId]);
        }
        return $this->db->prepare("DELETE FROM koszyk WHERE id = ? AND sesja_id = ? AND uzytkownik_id IS NULL")
            ->execute([$koszykId, $this->sessionId]);
    }

    // Usuwa wszystkie produkty z koszyka naraz
    public function wyczysc(): bool
    {
        if ($this->userId) {
            return $this->db->prepare("DELETE FROM koszyk WHERE uzytkownik_id = ?")->execute([$this->userId]);
        }
        return $this->db->prepare("DELETE FROM koszyk WHERE sesja_id = ? AND uzytkownik_id IS NULL")->execute([$this->sessionId]);
    }

    // Liczy łączną kwotę do zapłaty za wszystkie produkty w koszyku, uwzględniając ceny promocyjne.
    public function sumaWartosci(): float
    {
        $suma = 0.0;
        foreach ($this->pozycje() as $p) {
            $suma += $p['cena_aktualna'] * $p['ilosc'];
        }
        return $suma;
    }

    // Gdy gość się zaloguje, przenosi produkty które dodał
    // do koszyka przed zalogowaniem na jego konto żeby nic nie zginęło.
    public function przeniesDoUzytkownika(int $userId): void
    {
        // Po zalogowaniu przenieś koszyk sesji do konta
        $stmt = $this->db->prepare(
            "SELECT id, produkt_id, ilosc FROM koszyk WHERE sesja_id = ? AND uzytkownik_id IS NULL"
        );
        $stmt->execute([$this->sessionId]);
        $sesyjne = $stmt->fetchAll();

        foreach ($sesyjne as $poz) {
            $stmtStan = $this->db->prepare("SELECT stan_magazynowy FROM produkty WHERE id = ?");
            $stmtStan->execute([$poz['produkt_id']]);
            $stanMag = (int)$stmtStan->fetchColumn();

            $exist = $this->db->prepare("SELECT id, ilosc FROM koszyk WHERE uzytkownik_id = ? AND produkt_id = ?");
            $exist->execute([$userId, $poz['produkt_id']]);
            $istn = $exist->fetch();

            if ($istn) {
                $nowaIlosc = min($istn['ilosc'] + $poz['ilosc'], $stanMag);
                $this->db->prepare("UPDATE koszyk SET ilosc = ? WHERE id = ?")
                    ->execute([$nowaIlosc, $istn['id']]);
                $this->db->prepare("DELETE FROM koszyk WHERE id = ?")->execute([$poz['id']]);
            } else {
                $ilosc = min($poz['ilosc'], $stanMag);
                $this->db->prepare("UPDATE koszyk SET uzytkownik_id = ?, sesja_id = NULL, ilosc = ? WHERE id = ?")
                    ->execute([$userId, $ilosc, $poz['id']]);
            }
        }
    }
}
