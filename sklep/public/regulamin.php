<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
Auth::start();
$liczbawKoszyku = (new KoszykModel())->liczbaPozycji();
$tytul = 'Regulamin — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>
<div class="container">
    <div class="static-page">
        <h1><?= Icons::fileText(28) ?> Regulamin sklepu</h1>
        <p class="static-page__updated">Obowiązuje od 1 stycznia 2024 r.</p>

        <section>
            <h2>§ 1. Postanowienia ogólne</h2>
            <p>Niniejszy regulamin określa zasady korzystania ze sklepu internetowego <?= SITE_NAME ?>, dostępnego pod adresem <?= BASE_URL ?: 'localhost' ?>.</p>
            <p>Właścicielem sklepu jest firma <?= SITE_NAME ?> z siedzibą w Warszawie, ul. Przykładowa 1, 00-001 Warszawa.</p>
            <p>Kontakt z obsługą sklepu możliwy jest za pośrednictwem formularza kontaktowego lub adresu e-mail: kontakt@<?= strtolower(SITE_NAME) ?>.pl.</p>
        </section>

        <section>
            <h2>§ 2. Składanie zamówień</h2>
            <ol>
                <li>Zamówienia można składać przez całą dobę, 7 dni w tygodniu.</li>
                <li>Warunkiem realizacji zamówienia jest prawidłowe wypełnienie formularza zamówienia.</li>
                <li>Po złożeniu zamówienia klient otrzymuje potwierdzenie na podany adres e-mail.</li>
                <li>Sklep zastrzega sobie prawo do anulowania zamówień w przypadku braku dostępności towaru.</li>
            </ol>
        </section>

        <section>
            <h2>§ 3. Ceny i płatności</h2>
            <ol>
                <li>Wszystkie ceny podane w sklepie są cenami brutto (zawierają podatek VAT) i wyrażone są w złotych polskich (PLN).</li>
                <li>Sklep akceptuje następujące formy płatności: przelew bankowy, BLIK, karta płatnicza, płatność przy odbiorze.</li>
                <li>W przypadku płatności przelewem, zamówienie jest realizowane po zaksięgowaniu wpłaty na koncie sklepu.</li>
            </ol>
        </section>

        <section>
            <h2>§ 4. Dostawa</h2>
            <ol>
                <li>Zamówienia są realizowane w terminie 1–5 dni roboczych od momentu potwierdzenia zamówienia.</li>
                <li>Koszt dostawy wynosi 19,99 zł. Zamówienia powyżej 199 zł są dostarczane bezpłatnie.</li>
                <li>Sklep nie ponosi odpowiedzialności za opóźnienia wynikające z działania firm kurierskich.</li>
            </ol>
        </section>

        <section>
            <h2>§ 5. Reklamacje</h2>
            <ol>
                <li>Klient ma prawo do złożenia reklamacji w przypadku stwierdzenia wady towaru.</li>
                <li>Reklamacje należy zgłaszać drogą e-mailową lub przez formularz kontaktowy.</li>
                <li>Sklep rozpatruje reklamacje w terminie 14 dni roboczych od ich otrzymania.</li>
            </ol>
        </section>

        <section>
            <h2>§ 6. Postanowienia końcowe</h2>
            <p>W sprawach nieuregulowanych niniejszym regulaminem zastosowanie mają przepisy Kodeksu Cywilnego oraz ustawy o prawach konsumenta.</p>
            <p>Sklep zastrzega sobie prawo do zmiany regulaminu. O wszelkich zmianach klienci zostaną poinformowani z co najmniej 14-dniowym wyprzedzeniem.</p>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
