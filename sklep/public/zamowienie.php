<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

Auth::start();

$koszykModel    = new KoszykModel();
$zamowienieModel = new ZamowienieModel();

$pozycje = $koszykModel->pozycje();
if (empty($pozycje)) {
    header('Location: koszyk.php');
    exit;
}

$suma         = $koszykModel->sumaWartosci();
$dostawaKoszt = $suma >= 199 ? 0 : 19.99;
$rabat        = 0.0;
$kodRabatowy  = null;
$promocja     = null;

if (!empty($_SESSION['kod_rabatowy'])) {
    $promocja = $_SESSION['kod_rabatowy'];
    $rabat    = $promocja['typ'] === 'procent' ? $suma * ($promocja['wartosc'] / 100) : min($promocja['wartosc'], $suma);
    $kodRabatowy = $promocja['kod'];
}

$lacznie = max(0, $suma - $rabat + $dostawaKoszt);

$user   = null;
$adresy = [];
if (Auth::isLoggedIn()) {
    $uzytModel = new UzytkownikModel();
    $user      = $uzytModel->znajdzPoId(Auth::userId());
    $adresy    = $uzytModel->adresy(Auth::userId());
}

$bledy = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bledy[] = 'Błąd bezpieczeństwa.';
    } else {
        $imie        = trim($_POST['imie'] ?? '');
        $nazwisko    = trim($_POST['nazwisko'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $telefon     = trim($_POST['telefon'] ?? '');
        $ulica       = trim($_POST['ulica'] ?? '');
        $nr_domu     = trim($_POST['nr_domu'] ?? '');
        $kodPoczt    = trim($_POST['kod_pocztowy'] ?? '');
        $miasto      = trim($_POST['miasto'] ?? '');
        $metoda_pl   = $_POST['metoda_platnosci'] ?? 'przelew';
        $metoda_d    = $_POST['metoda_dostawy'] ?? 'kurier';
        $uwagi       = trim($_POST['uwagi'] ?? '');

        if (!$imie)     $bledy[] = 'Podaj imię.';
        if (!$nazwisko) $bledy[] = 'Podaj nazwisko.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $bledy[] = 'Podaj prawidłowy e-mail.';
        if (!$ulica)    $bledy[] = 'Podaj ulicę.';
        if (!$nr_domu)  $bledy[] = 'Podaj numer domu.';
        if (!preg_match('/^\d{2}-\d{3}$/', $kodPoczt)) $bledy[] = 'Podaj kod pocztowy w formacie 00-000.';
        if (!$miasto)   $bledy[] = 'Podaj miasto.';

        if (empty($bledy)) {
            $db = Database::getInstance();
            foreach ($pozycje as $p) {
                $stmtStan = $db->prepare("SELECT stan_magazynowy FROM produkty WHERE id = ? AND aktywny = 1");
                $stmtStan->execute([$p['produkt_id']]);
                $stanMag = (int)$stmtStan->fetchColumn();
                if ($stanMag < $p['ilosc']) {
                    $bledy[] = 'Produkt „' . $p['nazwa'] . '" jest niedostępny w wymaganej ilości (dostępnych: ' . $stanMag . ' szt.).';
                }
            }
        }

        if (empty($bledy)) {
            $danePozycji = [];
            foreach ($pozycje as $p) {
                $danePozycji[] = [
                    'produkt_id' => $p['produkt_id'],
                    'nazwa'      => $p['nazwa'],
                    'sku'        => $p['sku'] ?? null,
                    'cena'       => $p['cena_aktualna'],
                    'ilosc'      => $p['ilosc'],
                    'wartosc'    => $p['cena_aktualna'] * $p['ilosc'],
                ];
            }

            $daneZam = [
                'numer'              => $zamowienieModel->generujNumer(),
                'uzytkownik_id'      => Auth::userId(),
                'status'             => 'nowe',
                'metoda_platnosci'   => $metoda_pl,
                'metoda_dostawy'     => $metoda_d,
                'koszt_dostawy'      => $dostawaKoszt,
                'wartosc_produktow'  => $suma,
                'rabat'              => $rabat,
                'wartosc_laczna'     => $lacznie,
                'imie'               => $imie,
                'nazwisko'           => $nazwisko,
                'email'              => $email,
                'telefon'            => $telefon ?: null,
                'ulica'              => $ulica,
                'nr_domu'            => $nr_domu,
                'kod_pocztowy'       => $kodPoczt,
                'miasto'             => $miasto,
                'kraj'               => 'Polska',
                'uwagi'              => $uwagi ?: null,
                'kod_rabatowy'       => $kodRabatowy,
            ];

            $zamId = $zamowienieModel->utworz($daneZam, $danePozycji);

            // Punkty lojalnościowe (1 pkt za każde 10 zł)
            if (Auth::isLoggedIn()) {
                $uzytModel = new UzytkownikModel();
                $uzytModel->dodajPunkty(Auth::userId(), (int)floor($lacznie / 10));
            }

            // Oznacz kod rabatowy jako użyty
            if ($promocja) {
                $db = Database::getInstance();
                $db->prepare("UPDATE promocje SET uzyto = uzyto + 1 WHERE id = ?")->execute([$promocja['id']]);
                unset($_SESSION['kod_rabatowy']);
            }

            $koszykModel->wyczysc();

            header('Location: potwierdzenie.php?id=' . $zamId);
            exit;
        }
    }
}

$liczbawKoszyku = $koszykModel->liczbaPozycji();
$tytul = 'Składanie zamówienia — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>

<div class="container">
    <h1 class="page-title">Składanie zamówienia</h1>

    <?php if (!empty($bledy)): ?>
    <div class="alert alert--error">
        <ul><?php foreach ($bledy as $b): ?><li><?= htmlspecialchars($b) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post" action="zamowienie.php" class="checkout-form">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

        <div class="checkout-layout">
            <div class="checkout-main">
                <!-- Dane dostawy -->
                <section class="checkout-section">
                    <h2>Dane dostawy</h2>
                    <?php if (!empty($adresy)): ?>
                    <div class="saved-addresses">
                        <label>Użyj zapisanego adresu:</label>
                        <select id="saved-addr" onchange="fillAddress(this)">
                            <option value="">— wybierz —</option>
                            <?php foreach ($adresy as $a): ?>
                            <option value='<?= json_encode($a) ?>'>
                                <?= htmlspecialchars("{$a['ulica']} {$a['nr_domu']}, {$a['kod_pocztowy']} {$a['miasto']}") ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-row">
                            <label>Imię *</label>
                            <input type="text" name="imie" value="<?= htmlspecialchars($_POST['imie'] ?? $user['imie'] ?? '') ?>" required>
                        </div>
                        <div class="form-row">
                            <label>Nazwisko *</label>
                            <input type="text" name="nazwisko" value="<?= htmlspecialchars($_POST['nazwisko'] ?? $user['nazwisko'] ?? '') ?>" required>
                        </div>
                        <div class="form-row form-row--full">
                            <label>E-mail *</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $user['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-row form-row--full">
                            <label>Telefon</label>
                            <input type="tel" name="telefon" value="<?= htmlspecialchars($_POST['telefon'] ?? $user['telefon'] ?? '') ?>">
                        </div>
                        <div class="form-row">
                            <label>Ulica *</label>
                            <input type="text" name="ulica" id="f-ulica" value="<?= htmlspecialchars($_POST['ulica'] ?? '') ?>" required>
                        </div>
                        <div class="form-row">
                            <label>Nr domu/lokalu *</label>
                            <input type="text" name="nr_domu" id="f-nr" value="<?= htmlspecialchars($_POST['nr_domu'] ?? '') ?>" required>
                        </div>
                        <div class="form-row">
                            <label>Kod pocztowy *</label>
                            <input type="text" name="kod_pocztowy" id="f-kod" value="<?= htmlspecialchars($_POST['kod_pocztowy'] ?? '') ?>" placeholder="00-000" required>
                        </div>
                        <div class="form-row">
                            <label>Miasto *</label>
                            <input type="text" name="miasto" id="f-miasto" value="<?= htmlspecialchars($_POST['miasto'] ?? '') ?>" required>
                        </div>
                    </div>
                </section>

                <!-- Dostawa -->
                <section class="checkout-section">
                    <h2>Metoda dostawy</h2>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="metoda_dostawy" value="kurier" checked>
                            <span><strong>Kurier DPD</strong><small>1–2 dni robocze</small></span>
                            <span class="radio-price"><?= $dostawaKoszt > 0 ? number_format($dostawaKoszt, 2, ',', ' ') . ' zł' : 'GRATIS' ?></span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="metoda_dostawy" value="paczkomat">
                            <span><strong>Paczkomat InPost</strong><small>1–2 dni robocze</small></span>
                            <span class="radio-price"><?= $dostawaKoszt > 0 ? number_format($dostawaKoszt, 2, ',', ' ') . ' zł' : 'GRATIS' ?></span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="metoda_dostawy" value="odbior">
                            <span><strong>Odbiór osobisty</strong><small>Warszawa, ul. Przykładowa 1</small></span>
                            <span class="radio-price">GRATIS</span>
                        </label>
                    </div>
                </section>

                <!-- Płatność -->
                <section class="checkout-section">
                    <h2>Metoda płatności</h2>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="metoda_platnosci" value="przelew" checked>
                            <span><strong>Przelew bankowy</strong></span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="metoda_platnosci" value="blik">
                            <span><strong>BLIK</strong></span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="metoda_platnosci" value="karta">
                            <span><strong>Karta płatnicza</strong></span>
                        </label>
                        <label class="radio-card">
                            <input type="radio" name="metoda_platnosci" value="pobranie">
                            <span><strong>Płatność przy odbiorze</strong></span>
                        </label>
                    </div>
                </section>

                <!-- Uwagi -->
                <section class="checkout-section">
                    <label>Uwagi do zamówienia (opcjonalnie)</label>
                    <textarea name="uwagi" rows="3" placeholder="Dodatkowe informacje dla sprzedawcy..."><?= htmlspecialchars($_POST['uwagi'] ?? '') ?></textarea>
                </section>
            </div>

            <!-- Podsumowanie -->
            <aside class="checkout-summary">
                <h2>Twoje zamówienie</h2>
                <div class="order-items">
                    <?php foreach ($pozycje as $p): ?>
                    <div class="order-item">
                        <span><?= htmlspecialchars($p['nazwa']) ?> × <?= $p['ilosc'] ?></span>
                        <span><?= number_format($p['cena_aktualna'] * $p['ilosc'], 2, ',', ' ') ?> <?= CURRENCY_SYMBOL ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <hr>
                <div class="summary-rows">
                    <div class="summary-row"><span>Produkty:</span><span><?= number_format($suma, 2, ',', ' ') ?> zł</span></div>
                    <?php if ($rabat > 0): ?>
                    <div class="summary-row summary-row--discount"><span>Rabat:</span><span>-<?= number_format($rabat, 2, ',', ' ') ?> zł</span></div>
                    <?php endif; ?>
                    <div class="summary-row"><span>Dostawa:</span><span><?= $dostawaKoszt > 0 ? number_format($dostawaKoszt, 2, ',', ' ') . ' zł' : 'GRATIS' ?></span></div>
                    <div class="summary-row summary-row--total"><span><strong>Łącznie:</strong></span><span><strong><?= number_format($lacznie, 2, ',', ' ') ?> zł</strong></span></div>
                </div>

                <button type="submit" class="btn btn--primary btn--full btn--lg">Złóż zamówienie</button>
                <p class="checkout-note">Klikając "Złóż zamówienie" akceptujesz <a href="#">regulamin</a> sklepu.</p>
            </aside>
        </div>
    </form>
</div>

<script>
function fillAddress(sel) {
    if (!sel.value) return;
    const a = JSON.parse(sel.value);
    document.getElementById('f-ulica').value  = a.ulica  || '';
    document.getElementById('f-nr').value     = a.nr_domu || '';
    document.getElementById('f-kod').value    = a.kod_pocztowy || '';
    document.getElementById('f-miasto').value = a.miasto || '';
}
</script>

<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
