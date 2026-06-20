<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

Auth::start();

$koszykModel = new KoszykModel();

// Akcje POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
    $akcja = $_POST['akcja'] ?? '';

    if ($akcja === 'aktualizuj') {
        foreach ($_POST['ilosc'] as $id => $ilosc) {
            $koszykModel->aktualizujIlosc((int)$id, (int)$ilosc);
        }
        Auth::flash('success', 'Koszyk zaktualizowany.');
    } elseif ($akcja === 'usun') {
        $koszykModel->usun((int)$_POST['koszyk_id']);
    } elseif ($akcja === 'wyczysc') {
        $koszykModel->wyczysc();
        Auth::flash('success', 'Koszyk wyczyszczony.');
    }
    header('Location: koszyk.php');
    exit;
}

// Kod rabatowy (GET / SESSION)
$kodRabatowy = null;
$rabat        = 0.0;
$kodBlad      = '';

if (!empty($_GET['kod'])) {
    $kod  = strtoupper(trim($_GET['kod']));
    $db   = Database::getInstance();
    $sumaDoWalidacji = $koszykModel->sumaWartosci();
    $stmt = $db->prepare(
        "SELECT * FROM promocje WHERE kod = ? AND aktywna = 1
         AND (data_od IS NULL OR data_od <= date('now'))
         AND (data_do IS NULL OR data_do >= date('now'))
         AND (max_uzyc IS NULL OR uzyto < max_uzyc)
         AND (min_wartosc_zamowienia IS NULL OR min_wartosc_zamowienia <= ?)"
    );
    $stmt->execute([$kod, $sumaDoWalidacji]);
    $promocja = $stmt->fetch();

    if ($promocja) {
        $_SESSION['kod_rabatowy'] = $promocja;
    } else {
        $kodBlad = 'Nieprawidłowy lub nieaktywny kod rabatowy.';
        unset($_SESSION['kod_rabatowy']);
    }
    header('Location: koszyk.php' . ($kodBlad ? '?kod_blad=1' : ''));
    exit;
}

$pozycje    = $koszykModel->pozycje();
$suma       = $koszykModel->sumaWartosci();
$dostawaKoszt = $suma >= 199 ? 0 : 19.99;

if (!empty($_SESSION['kod_rabatowy'])) {
    $prom = $_SESSION['kod_rabatowy'];
    if ($prom['typ'] === 'procent') {
        $rabat = $suma * ($prom['wartosc'] / 100);
    } else {
        $rabat = min($prom['wartosc'], $suma);
    }
    $kodRabatowy = $prom['kod'];
}

$lacznie      = max(0, $suma - $rabat + $dostawaKoszt);
$liczbawKoszyku = $koszykModel->liczbaPozycji();

$tytul = 'Koszyk — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>

<div class="container">
    <h1 class="page-title">Twój koszyk</h1>

    <?php $flash = Auth::getFlash('success'); if ($flash): ?>
        <div class="alert alert--success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['kod_blad'])): ?>
        <div class="alert alert--error">Nieprawidłowy lub nieaktywny kod rabatowy.</div>
    <?php endif; ?>

    <?php if (empty($pozycje)): ?>
        <div class="empty-state">
            <p>Twój koszyk jest pusty.</p>
            <a href="sklep.php" class="btn btn--primary">Przejdź do sklepu</a>
        </div>
    <?php else: ?>
    <div class="cart-layout">
        <div class="cart-items">
            <form method="post" action="koszyk.php">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <input type="hidden" name="akcja" value="aktualizuj">
                <table class="cart-table">
                    <thead>
                        <tr><th>Produkt</th><th>Cena</th><th>Ilość</th><th>Wartość</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pozycje as $p): ?>
                    <tr>
                        <td class="cart-product">
                            <div class="cart-product__img">
                                <?php if ($p['zdjecie_glowne']): ?>
                                    <img src="images/produkty/<?= htmlspecialchars($p['zdjecie_glowne']) ?>" alt="">
                                <?php else: ?>
                                    <?= Icons::package(28) ?>
                                <?php endif; ?>
                            </div>
                            <a href="produkt.php?slug=<?= htmlspecialchars($p['slug']) ?>"><?= htmlspecialchars($p['nazwa']) ?></a>
                        </td>
                        <td class="cart-price"><?= number_format($p['cena_aktualna'], 2, ',', ' ') ?> <?= CURRENCY_SYMBOL ?></td>
                        <td>
                            <input type="number" name="ilosc[<?= $p['id'] ?>]" value="<?= $p['ilosc'] ?>"
                                   min="1" max="<?= $p['stan_magazynowy'] ?>" class="qty-input">
                        </td>
                        <td class="cart-total"><?= number_format($p['cena_aktualna'] * $p['ilosc'], 2, ',', ' ') ?> <?= CURRENCY_SYMBOL ?></td>
                        <td>
                            <button type="button" class="btn-remove"
                                onclick="if(confirm('Usunąć produkt?')) { document.getElementById('usun-<?= $p['id'] ?>').submit(); }">✕</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="cart-actions">
                    <button type="submit" class="btn btn--outline">Zaktualizuj koszyk</button>
                </div>
            </form>

            
            <?php foreach ($pozycje as $p): ?>
            <form id="usun-<?= $p['id'] ?>" method="post" action="koszyk.php" style="display:none">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <input type="hidden" name="akcja" value="usun">
                <input type="hidden" name="koszyk_id" value="<?= $p['id'] ?>">
            </form>
            <?php endforeach; ?>
        </div>


        <aside class="cart-summary">
            <h2>Podsumowanie</h2>
            <table class="summary-table">
                <tr><td>Wartość produktów:</td><td><?= number_format($suma, 2, ',', ' ') ?> <?= CURRENCY_SYMBOL ?></td></tr>
                <?php if ($rabat > 0): ?>
                <tr class="summary-discount"><td>Rabat (<?= htmlspecialchars($kodRabatowy) ?>):</td><td>-<?= number_format($rabat, 2, ',', ' ') ?> <?= CURRENCY_SYMBOL ?></td></tr>
                <?php endif; ?>
                <tr><td>Dostawa:</td><td><?= $dostawaKoszt > 0 ? number_format($dostawaKoszt, 2, ',', ' ') . ' ' . CURRENCY_SYMBOL : '<strong>GRATIS</strong>' ?></td></tr>
                <tr class="summary-total"><td><strong>Łącznie:</strong></td><td><strong><?= number_format($lacznie, 2, ',', ' ') ?> <?= CURRENCY_SYMBOL ?></strong></td></tr>
            </table>

            <form action="koszyk.php" method="get" class="promo-form">
                <input type="text" name="kod" placeholder="Kod rabatowy"
                       value="<?= htmlspecialchars($kodRabatowy ?? '') ?>">
                <button type="submit" class="btn btn--sm btn--outline">Zastosuj</button>
                <?php if ($kodRabatowy): ?><span class="promo-ok"><?= Icons::check(14) ?> Rabat aktywny</span><?php endif; ?>
            </form>

            <a href="zamowienie.php" class="btn btn--primary btn--full btn--lg">Przejdź do kasy</a>
            <a href="sklep.php" class="btn btn--ghost btn--full">Kontynuuj zakupy</a>
        </aside>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
