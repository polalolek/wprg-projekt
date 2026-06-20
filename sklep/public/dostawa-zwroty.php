<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
Auth::start();
$liczbawKoszyku = (new KoszykModel())->liczbaPozycji();
$tytul = 'Dostawa i zwroty — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>
<div class="container">
    <div class="static-page">
        <h1><?= Icons::truck(28) ?> Dostawa i zwroty</h1>

        <div class="info-cards">
            <div class="info-card">
                <?= Icons::truck(32) ?>
                <h3>Kurier DPD</h3>
                <p>Dostawa w 1–2 dni robocze. Śledzenie przesyłki w czasie rzeczywistym.</p>
                <strong>19,99 zł</strong>
            </div>
            <div class="info-card">
                <?= Icons::package(32) ?>
                <h3>Paczkomat InPost</h3>
                <p>Dostawa w 1–2 dni robocze do wybranego paczkomatu.</p>
                <strong>19,99 zł</strong>
            </div>
            <div class="info-card">
                <?= Icons::mapPin(32) ?>
                <h3>Odbiór osobisty</h3>
                <p>Warszawa, ul. Przykładowa 1. Pon–Pt 9:00–17:00.</p>
                <strong>Bezpłatnie</strong>
            </div>
        </div>

        <section>
            <h2>Darmowa dostawa</h2>
            <p>Zamówienia powyżej <strong>199 zł</strong> są dostarczane bezpłatnie — niezależnie od wybranej metody dostawy (z wyłączeniem odbioru osobistego).</p>
        </section>

        <section>
            <h2>Czas realizacji</h2>
            <p>Zamówienia złożone w dni robocze do godziny <strong>14:00</strong> są wysyłane tego samego dnia. Zamówienia złożone po tej godzinie lub w weekendy są wysyłane następnego dnia roboczego.</p>
        </section>

        <section>
            <h2>Zwroty — 30 dni bez pytań</h2>
            <p>Masz <strong>30 dni</strong> na zwrot zakupionego towaru bez podawania przyczyny (licząc od dnia otrzymania przesyłki).</p>
            <h3>Jak złożyć zwrot?</h3>
            <ol>
                <li>Skontaktuj się z nami przez <a href="/kontakt.php">formularz kontaktowy</a> lub e-mail, podając numer zamówienia.</li>
                <li>Zapakuj produkt w oryginalne opakowanie (lub równoważne).</li>
                <li>Wyślij paczkę na adres: <?= SITE_NAME ?>, ul. Przykładowa 1, 00-001 Warszawa.</li>
                <li>Zwrot środków nastąpi w ciągu 5–7 dni roboczych od otrzymania paczki.</li>
            </ol>
            <p><strong>Koszt zwrotu</strong> ponosi kupujący, chyba że towar jest wadliwy — wówczas koszty pokrywa sklep.</p>
        </section>

        <section>
            <h2>Reklamacje</h2>
            <p>W przypadku produktu wadliwego skontaktuj się z nami niezwłocznie po stwierdzeniu wady. Rozpatrzymy reklamację w ciągu 14 dni roboczych. Szczegóły w <a href="/regulamin.php">regulaminie</a>.</p>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
