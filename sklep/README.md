# Sklep Online — PHP 8+ / SQLite

Kompletna platforma e-commerce napisana w czystym PHP 8+, bez zewnętrznych frameworków. Baza danych SQLite (zero konfiguracji serwera).

---

## Wymagania

- PHP 8.1 lub nowszy
- Rozszerzenie `pdo_sqlite` (domyślnie aktywne)
- Rozszerzenie `gd` lub `imagick` (opcjonalnie, do przetwarzania zdjęć)

---

## Szybki start

```bash
# 1. Przejdź do katalogu projektu
cd sklep

# 2. Zainicjalizuj bazę danych
php setup.php

# 3. Uruchom wbudowany serwer PHP
php -S localhost:8080 -t public

# 4. Otwórz w przeglądarce
# http://localhost:8080
```

---

## Konta testowe

| Rola   | E-mail              | Hasło     |
|--------|---------------------|-----------|
| Admin  | admin@sklep.pl      | Admin123! |
| Klient | anna@example.com    | Admin123! |

---

## Kody rabatowe (przykładowe)

| Kod       | Typ       | Wartość | Min. zamówienie |
|-----------|-----------|---------|-----------------|
| WITAJ10   | Procentowy| 10%     | 100 zł          |
| LATO50    | Kwotowy   | 50 zł   | 200 zł          |

---

## Struktura projektu

```
sklep/
├── config/
│   ├── config.php          # Konfiguracja, autoloader
│   └── Database.php        # Singleton PDO / SQLite
├── database/
│   ├── schema.sql          # Schemat + dane przykładowe
│   └── sklep.db            # Plik bazy (tworzony przez setup)
├── src/
│   ├── Models/
│   │   ├── ProduktModel.php
│   │   ├── KoszykModel.php
│   │   ├── ZamowienieModel.php
│   │   ├── UzytkownikModel.php
│   │   └── KategoriaModel.php
│   └── Middleware/
│       └── Auth.php        # Sesje, CSRF, autoryzacja
├── templates/
│   ├── partials/
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── karta_produktu.php
│   └── admin/
│       └── sidebar.php
├── public/                 # Document root serwera WWW
│   ├── index.php           # Strona główna
│   ├── sklep.php           # Lista produktów + filtry
│   ├── produkt.php         # Szczegół produktu + recenzje
│   ├── koszyk.php          # Koszyk zakupów
│   ├── zamowienie.php      # Checkout
│   ├── potwierdzenie.php   # Potwierdzenie zamówienia
│   ├── logowanie.php
│   ├── rejestracja.php
│   ├── konto.php           # Panel klienta
│   ├── css/style.css       # Pełny design system
│   ├── images/produkty/    # Upload zdjęć produktów
│   └── admin/
│       ├── index.php       # Dashboard
│       ├── produkty.php    # CRUD produktów
│       ├── kategorie.php   # CRUD kategorii
│       ├── zamowienia.php  # Zarządzanie zamówieniami
│       ├── uzytkownicy.php # Zarządzanie użytkownikami
│       ├── promocje.php    # Kody rabatowe
│       └── recenzje.php    # Moderacja recenzji
└── setup.php               # Skrypt inicjalizacji bazy
```

---

## Funkcje

### Sklep
- Przeglądanie produktów z filtrowaniem (kategoria, cena, szukaj)
- Sortowanie (cena, nazwa, data)
- Paginacja
- Szczegóły produktu z recenzjami i oceną gwiazdkową
- Produkty wyróżnione i z promocją

### Koszyk
- Dla zalogowanych i gości (sesja)
- Scalanie koszyka po zalogowaniu
- Kody rabatowe (procentowe i kwotowe)
- Darmowa dostawa od 199 zł

### Zamówienia
- Checkout z walidacją danych
- 4 metody płatności (przelew, BLIK, karta, pobranie)
- 3 metody dostawy (kurier, paczkomat, odbiór osobisty)
- Historia statusów zamówienia
- Powiadomienie po złożeniu zamówienia

### Konto klienta
- Edycja profilu i zmiana hasła
- Historia zamówień
- Program lojalnościowy (1 pkt za 10 zł)
- Zapisane adresy dostawy

### Panel administratora
- Dashboard ze statystykami i wykresem
- Zarządzanie produktami (CRUD + upload zdjęć)
- Zarządzanie kategoriami i podkategoriami
- Zarządzanie zamówieniami + zmiana statusu
- Zarządzanie użytkownikami (blokowanie, zmiana roli)
- Kody rabatowe (% i kwota, daty ważności, limity)
- Moderacja recenzji

### Bezpieczeństwo
- Tokeny CSRF na wszystkich formularzach POST
- Hasła hashowane bcrypt (cost=12)
- PDO prepared statements (ochrona przed SQL injection)
- `htmlspecialchars` na każdym wyjściu
- Walidacja MIME type przy uploadzie zdjęć
- `session_regenerate_id` po zalogowaniu

---

## Konfiguracja na serwerze Apache/Nginx

### Apache (`.htaccess` w katalogu `public/`)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

### Nginx
```nginx
root /var/www/sklep/public;
index index.php;
location / { try_files $uri $uri/ /index.php; }
location ~ \.php$ { fastcgi_pass unix:/run/php/php8.1-fpm.sock; include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; }
```

Ustaw `document_root` na katalog `public/`.

---

## Licencja

MIT — używaj swobodnie w projektach komercyjnych i prywatnych.
