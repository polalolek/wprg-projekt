-- =============================================
-- SKLEP INTERNETOWY — Schemat bazy danych SQLite
-- =============================================

PRAGMA foreign_keys = ON;

-- Użytkownicy
CREATE TABLE IF NOT EXISTS uzytkownicy (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    imie TEXT NOT NULL,
    nazwisko TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    haslo TEXT NOT NULL,
    rola TEXT NOT NULL DEFAULT 'klient' CHECK(rola IN ('klient','admin')),
    telefon TEXT,
    aktywny INTEGER NOT NULL DEFAULT 1,
    punkty_lojalnosciowe INTEGER NOT NULL DEFAULT 0,
    data_rejestracji TEXT NOT NULL DEFAULT (datetime('now')),
    ostatnie_logowanie TEXT
);

-- Adresy
CREATE TABLE IF NOT EXISTS adresy (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uzytkownik_id INTEGER NOT NULL REFERENCES uzytkownicy(id) ON DELETE CASCADE,
    imie TEXT NOT NULL,
    nazwisko TEXT NOT NULL,
    ulica TEXT NOT NULL,
    nr_domu TEXT NOT NULL,
    kod_pocztowy TEXT NOT NULL,
    miasto TEXT NOT NULL,
    kraj TEXT NOT NULL DEFAULT 'Polska',
    domyslny INTEGER NOT NULL DEFAULT 0
);

-- Kategorie
CREATE TABLE IF NOT EXISTS kategorie (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nazwa TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    opis TEXT,
    rodzic_id INTEGER REFERENCES kategorie(id) ON DELETE SET NULL,
    kolejnosc INTEGER NOT NULL DEFAULT 0,
    aktywna INTEGER NOT NULL DEFAULT 1
);

-- Produkty
CREATE TABLE IF NOT EXISTS produkty (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kategoria_id INTEGER REFERENCES kategorie(id) ON DELETE SET NULL,
    nazwa TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    opis TEXT,
    opis_krotki TEXT,
    cena REAL NOT NULL,
    cena_promocyjna REAL,
    stan_magazynowy INTEGER NOT NULL DEFAULT 0,
    jednostka TEXT NOT NULL DEFAULT 'szt.',
    sku TEXT UNIQUE,
    zdjecie_glowne TEXT,
    aktywny INTEGER NOT NULL DEFAULT 1,
    wyrozniony INTEGER NOT NULL DEFAULT 0,
    data_dodania TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Zdjęcia produktów
CREATE TABLE IF NOT EXISTS zdjecia_produktow (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    produkt_id INTEGER NOT NULL REFERENCES produkty(id) ON DELETE CASCADE,
    sciezka TEXT NOT NULL,
    alt TEXT,
    kolejnosc INTEGER NOT NULL DEFAULT 0
);

-- Koszyk
CREATE TABLE IF NOT EXISTS koszyk (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uzytkownik_id INTEGER REFERENCES uzytkownicy(id) ON DELETE CASCADE,
    sesja_id TEXT,
    produkt_id INTEGER NOT NULL REFERENCES produkty(id) ON DELETE CASCADE,
    ilosc INTEGER NOT NULL DEFAULT 1,
    data_dodania TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Zamówienia
CREATE TABLE IF NOT EXISTS zamowienia (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numer TEXT UNIQUE NOT NULL,
    uzytkownik_id INTEGER REFERENCES uzytkownicy(id) ON DELETE SET NULL,
    status TEXT NOT NULL DEFAULT 'nowe' CHECK(status IN ('nowe','oplacone','w_realizacji','wyslane','dostarczone','anulowane','zwrot')),
    metoda_platnosci TEXT NOT NULL DEFAULT 'przelew',
    metoda_dostawy TEXT NOT NULL DEFAULT 'kurier',
    koszt_dostawy REAL NOT NULL DEFAULT 0,
    wartosc_produktow REAL NOT NULL DEFAULT 0,
    rabat REAL NOT NULL DEFAULT 0,
    wartosc_laczna REAL NOT NULL DEFAULT 0,
    imie TEXT NOT NULL,
    nazwisko TEXT NOT NULL,
    email TEXT NOT NULL,
    telefon TEXT,
    ulica TEXT NOT NULL,
    nr_domu TEXT NOT NULL,
    kod_pocztowy TEXT NOT NULL,
    miasto TEXT NOT NULL,
    kraj TEXT NOT NULL DEFAULT 'Polska',
    uwagi TEXT,
    kod_rabatowy TEXT,
    data_zamowienia TEXT NOT NULL DEFAULT (datetime('now')),
    data_aktualizacji TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Pozycje zamówień
CREATE TABLE IF NOT EXISTS pozycje_zamowien (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    zamowienie_id INTEGER NOT NULL REFERENCES zamowienia(id) ON DELETE CASCADE,
    produkt_id INTEGER REFERENCES produkty(id) ON DELETE SET NULL,
    nazwa_produktu TEXT NOT NULL,
    sku TEXT,
    cena_jednostkowa REAL NOT NULL,
    ilosc INTEGER NOT NULL,
    wartosc REAL NOT NULL
);

-- Promocje / kody rabatowe
CREATE TABLE IF NOT EXISTS promocje (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kod TEXT UNIQUE NOT NULL,
    typ TEXT NOT NULL DEFAULT 'procent' CHECK(typ IN ('procent','kwota')),
    wartosc REAL NOT NULL,
    min_wartosc_zamowienia REAL NOT NULL DEFAULT 0,
    max_uzyc INTEGER,
    uzyto INTEGER NOT NULL DEFAULT 0,
    data_od TEXT,
    data_do TEXT,
    aktywna INTEGER NOT NULL DEFAULT 1
);

-- Recenzje produktów
CREATE TABLE IF NOT EXISTS recenzje (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    produkt_id INTEGER NOT NULL REFERENCES produkty(id) ON DELETE CASCADE,
    uzytkownik_id INTEGER REFERENCES uzytkownicy(id) ON DELETE SET NULL,
    ocena INTEGER NOT NULL CHECK(ocena BETWEEN 1 AND 5),
    tytul TEXT,
    tresc TEXT,
    zatwierdzona INTEGER NOT NULL DEFAULT 0,
    data_dodania TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Historia zamówień (logi statusów)
CREATE TABLE IF NOT EXISTS historia_zamowien (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    zamowienie_id INTEGER NOT NULL REFERENCES zamowienia(id) ON DELETE CASCADE,
    stary_status TEXT,
    nowy_status TEXT NOT NULL,
    komentarz TEXT,
    data TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Indeksy
CREATE INDEX IF NOT EXISTS idx_produkty_kategoria ON produkty(kategoria_id);
CREATE INDEX IF NOT EXISTS idx_produkty_slug ON produkty(slug);
CREATE INDEX IF NOT EXISTS idx_zamowienia_uzytkownik ON zamowienia(uzytkownik_id);
CREATE INDEX IF NOT EXISTS idx_zamowienia_status ON zamowienia(status);
CREATE INDEX IF NOT EXISTS idx_koszyk_uzytkownik ON koszyk(uzytkownik_id);
CREATE INDEX IF NOT EXISTS idx_koszyk_sesja ON koszyk(sesja_id);

-- =============================================
-- DANE PRZYKŁADOWE
-- =============================================

-- Admin (hasło: Admin123!)
INSERT OR IGNORE INTO uzytkownicy (imie, nazwisko, email, haslo, rola) VALUES
('Jan', 'Kowalski', 'admin@sklep.pl', '$2y$12$U7J0/29FdmUxykQ.EPt4DOVGaLvN1JJrPDEHMgeaS1jDciNBJSwkO', 'admin');

-- Klient testowy (hasło: Admin123!)
INSERT OR IGNORE INTO uzytkownicy (imie, nazwisko, email, haslo, rola, punkty_lojalnosciowe) VALUES
('Anna', 'Nowak', 'anna@example.com', '$2y$12$U7J0/29FdmUxykQ.EPt4DOVGaLvN1JJrPDEHMgeaS1jDciNBJSwkO', 'klient', 150);

-- Kategorie
INSERT OR IGNORE INTO kategorie (nazwa, slug, opis, kolejnosc) VALUES
('Elektronika', 'elektronika', 'Telefony, laptopy, akcesoria', 1),
('Odzież', 'odziez', 'Ubrania dla każdego', 2),
('Dom i Ogród', 'dom-ogrod', 'Wyposażenie domu i ogrodu', 3),
('Sport', 'sport', 'Sprzęt sportowy i fitness', 4),
('Książki', 'ksiazki', 'Książki, e-booki, audiobooki', 5);

-- Podkategorie
INSERT OR IGNORE INTO kategorie (nazwa, slug, rodzic_id, kolejnosc) VALUES
('Smartfony', 'smartfony', 1, 1),
('Laptopy', 'laptopy', 1, 2),
('Akcesoria GSM', 'akcesoria-gsm', 1, 3),
('Meble', 'meble', 3, 1),
('Oświetlenie', 'oswietlenie', 3, 2);

-- Produkty
INSERT OR IGNORE INTO produkty (kategoria_id, nazwa, slug, opis_krotki, opis, cena, cena_promocyjna, stan_magazynowy, sku, wyrozniony) VALUES
(6, 'Smartfon XPhone Pro 128GB', 'smartfon-xphone-pro-128gb',
 'Flagowy smartfon z aparatem 108 Mpx i baterią 5000 mAh.',
 'Smartfon XPhone Pro to flagowe urządzenie z najnowszym procesorem, doskonałym aparatem 108 Mpx i długo działającą baterią 5000 mAh. Ekran AMOLED 6.7" zapewnia niezrównane wrażenia wizualne.',
 2499.99, 2199.99, 25, 'SMART-001', 1),

(7, 'Laptop UltraBook 15"', 'laptop-ultrabook-15',
 'Ultralekki laptop biznesowy z procesorem Intel i7 i 16 GB RAM.',
 'UltraBook 15 to idealny laptop dla profesjonalistów. Intel Core i7 12. generacji, 16 GB RAM DDR5, dysk SSD 512 GB NVMe. Waga zaledwie 1.4 kg.',
 4299.00, NULL, 12, 'LAPT-001', 1),

(3, 'Kurtka Zimowa Unisex', 'kurtka-zimowa-unisex',
 'Ciepła kurtka zimowa z wypełnieniem puchowym.',
 'Kurtka zimowa z naturalnym wypełnieniem puchowym (90% puch, 10% pierze). Wodoodporna powłoka DWR, regulowany kaptur.',
 349.00, 279.00, 50, 'KURT-001', 0),

(4, 'Sofa Trzyosobowa "Comfort"', 'sofa-trzyosobowa-comfort',
 'Elegancka sofa trzyosobowa w tkaninie velvet.',
 'Sofa Comfort to połączenie stylu i wygody. Tapicerka velvet dostępna w 8 kolorach, nogi z litego drewna, wymiary 220x90x85 cm.',
 1899.00, NULL, 8, 'SOFA-001', 1),

(5, 'Rower Górski TrailMaster 29"', 'rower-gorski-trailmaster-29',
 'Aluminiowy rower górski z amortyzatorem i 21-biegową przekładnią.',
 'TrailMaster 29 to rower dla amatorów i zaawansowanych. Rama aluminiowa, amortyzator przedni 100 mm, hamulce tarczowe hydrauliczne.',
 1599.00, 1399.00, 15, 'ROWER-001', 0),

(8, 'Zestaw słuchawkowy BeatSound Pro', 'zestaw-sluchawkowy-beatsound-pro',
 'Bezprzewodowe słuchawki z ANC i 30-godzinną baterią.',
 'BeatSound Pro oferuje aktywną redukcję szumów, 30 h pracy na baterii, ładowanie USB-C. Składana konstrukcja idealna do podróży.',
 499.00, 399.00, 30, 'SLUCH-001', 0);

-- Promocja
INSERT OR IGNORE INTO promocje (kod, typ, wartosc, min_wartosc_zamowienia, max_uzyc, data_do) VALUES
('WITAJ10', 'procent', 10, 100, 100, date('now', '+30 days')),
('LATO50', 'kwota', 50, 200, 50, date('now', '+14 days'));
