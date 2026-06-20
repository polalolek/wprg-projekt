<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
Auth::start();
$liczbawKoszyku = (new KoszykModel())->liczbaPozycji();
$tytul = 'Polityka prywatności — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>
<div class="container">
    <div class="static-page">
        <h1><?= Icons::shield(28) ?> Polityka prywatności</h1>
        <p class="static-page__updated">Ostatnia aktualizacja: 1 stycznia 2024 r.</p>

        <section>
            <h2>1. Administrator danych</h2>
            <p>Administratorem danych osobowych jest <?= SITE_NAME ?>, ul. Przykładowa 1, 00-001 Warszawa. Kontakt w sprawach ochrony danych: rodo@<?= strtolower(SITE_NAME) ?>.pl.</p>
        </section>

        <section>
            <h2>2. Jakie dane zbieramy</h2>
            <ul>
                <li>Dane identyfikacyjne: imię, nazwisko, adres e-mail.</li>
                <li>Dane adresowe: adres dostawy podany przy składaniu zamówienia.</li>
                <li>Dane kontaktowe: numer telefonu (opcjonalnie).</li>
                <li>Dane transakcyjne: historia zamówień, metody płatności (bez danych kart).</li>
            </ul>
        </section>

        <section>
            <h2>3. Cel i podstawa przetwarzania</h2>
            <ul>
                <li><strong>Realizacja zamówień</strong> — przetwarzanie niezbędne do wykonania umowy (art. 6 ust. 1 lit. b RODO).</li>
                <li><strong>Obsługa konta użytkownika</strong> — na podstawie zgody i umowy.</li>
                <li><strong>Marketing bezpośredni</strong> — wyłącznie po udzieleniu zgody.</li>
                <li><strong>Obowiązki prawne</strong> — przechowywanie dokumentów księgowych (art. 6 ust. 1 lit. c RODO).</li>
            </ul>
        </section>

        <section>
            <h2>4. Okres przechowywania danych</h2>
            <p>Dane przechowujemy przez czas niezbędny do realizacji celu, w jakim zostały zebrane, jednak nie krócej niż:</p>
            <ul>
                <li>Dane zamówień — 5 lat od zakończenia roku podatkowego.</li>
                <li>Dane konta użytkownika — do momentu usunięcia konta lub cofnięcia zgody.</li>
            </ul>
        </section>

        <section>
            <h2>5. Prawa użytkownika</h2>
            <p>Przysługuje Ci prawo do: dostępu do danych, ich sprostowania, usunięcia ("prawo do bycia zapomnianym"), ograniczenia przetwarzania, przenoszenia danych oraz wniesienia sprzeciwu. Skargę możesz złożyć do Prezesa UODO (uodo.gov.pl).</p>
        </section>

        <section>
            <h2>6. Pliki cookies</h2>
            <p>Strona wykorzystuje niezbędne pliki cookies do obsługi sesji (koszyk, logowanie). Nie stosujemy cookies śledzących ani reklamowych bez Twojej zgody.</p>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
