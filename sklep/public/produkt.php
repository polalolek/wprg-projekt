<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

Auth::start();

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: sklep.php'); exit; }

$produktModel   = new ProduktModel();
$koszykModel    = new KoszykModel();

$produkt = $produktModel->znajdzPoSlug($slug);
if (!$produkt) { header('Location: sklep.php'); exit; }

// Dodaj do koszyka
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dodaj_do_koszyka'])) {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        Auth::flash('error', 'Błąd bezpieczeństwa. Spróbuj ponownie.');
    } else {
        $ilosc = max(1, min((int)$_POST['ilosc'], $produkt['stan_magazynowy']));
        $koszykModel->dodaj($produkt['id'], $ilosc);
        Auth::flash('success', 'Produkt dodany do koszyka!');
    }
    header('Location: produkt.php?slug=' . $slug);
    exit;
}

// Dodaj recenzję
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dodaj_recenzje'])) {
    Auth::requireLogin();
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $ocena = min(5, max(1, (int)$_POST['ocena']));
        $trescRec = trim($_POST['tresc'] ?? '');
        $tytulRec = trim($_POST['tytul'] ?? '');
        $db = Database::getInstance();
        $db->prepare(
            "INSERT INTO recenzje (produkt_id, uzytkownik_id, ocena, tytul, tresc) VALUES (?,?,?,?,?)"
        )->execute([$produkt['id'], Auth::userId(), $ocena, $tytulRec, $trescRec]);
        Auth::flash('success', 'Recenzja wysłana — oczekuje na zatwierdzenie.');
    }
    header('Location: produkt.php?slug=' . $slug);
    exit;
}

$recenzje       = $produktModel->recenzje($produkt['id']);
$podobne        = $produktModel->podobne($produkt['id'], (int)$produkt['kategoria_id']);
$liczbawKoszyku = $koszykModel->liczbaPozycji();

$tytul = htmlspecialchars($produkt['nazwa']) . ' — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>

<div class="container">
    <nav class="breadcrumb">
        <a href="index.php">Strona główna</a> /
        <a href="sklep.php">Sklep</a> /
        <?php if ($produkt['kategoria_slug']): ?>
        <a href="sklep.php?kategoria=<?= $produkt['kategoria_slug'] ?>"><?= htmlspecialchars($produkt['kategoria_nazwa']) ?></a> /
        <?php endif; ?>
        <span><?= htmlspecialchars($produkt['nazwa']) ?></span>
    </nav>

    <?php $flash = Auth::getFlash('success'); if ($flash): ?>
        <div class="alert alert--success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php $flash = Auth::getFlash('error'); if ($flash): ?>
        <div class="alert alert--error"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="product-detail">
        <!-- Zdjęcie -->
        <div class="product-detail__image">
            <div class="product-img-wrap">
                <?php if ($produkt['zdjecie_glowne']): ?>
                    <img src="images/produkty/<?= htmlspecialchars($produkt['zdjecie_glowne']) ?>" alt="<?= htmlspecialchars($produkt['nazwa']) ?>">
                <?php else: ?>
                    <div class="product-img-placeholder"><?= Icons::package(48) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info -->
        <div class="product-detail__info">
            <?php if ($produkt['kategoria_nazwa']): ?>
            <span class="product-cat"><?= htmlspecialchars($produkt['kategoria_nazwa']) ?></span>
            <?php endif; ?>
            <h1><?= htmlspecialchars($produkt['nazwa']) ?></h1>

            <div class="product-meta">
                <span class="product-sku">SKU: <?= htmlspecialchars($produkt['sku'] ?? 'N/D') ?></span>
                <?php if ($produkt['liczba_ocen'] > 0): ?>
                <span class="product-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?= $i <= round($produkt['srednia_ocena']) ? 'star--on' : '' ?>">★</span>
                    <?php endfor; ?>
                    (<?= $produkt['liczba_ocen'] ?> ocen)
                </span>
                <?php endif; ?>
            </div>

            <div class="product-price-block">
                <?php if ($produkt['cena_promocyjna']): ?>
                    <span class="price price--old"><?= number_format($produkt['cena'], 2, ',', ' ') ?> <?= CURRENCY_SYMBOL ?></span>
                    <span class="price price--promo"><?= number_format($produkt['cena_promocyjna'], 2, ',', ' ') ?> <?= CURRENCY_SYMBOL ?></span>
                    <span class="badge-promo">-<?= round((1 - $produkt['cena_promocyjna'] / $produkt['cena']) * 100) ?>%</span>
                <?php else: ?>
                    <span class="price"><?= number_format($produkt['cena'], 2, ',', ' ') ?> <?= CURRENCY_SYMBOL ?></span>
                <?php endif; ?>
            </div>

            <p class="product-short-desc"><?= htmlspecialchars($produkt['opis_krotki'] ?? '') ?></p>

            <?php if ($produkt['stan_magazynowy'] > 0): ?>
                <p class="stock stock--in"><?= Icons::check(14) ?> Na stanie (<?= $produkt['stan_magazynowy'] ?> <?= $produkt['jednostka'] ?>)</p>
            <?php else: ?>
                <p class="stock stock--out"><?= Icons::cross(14) ?> Brak w magazynie</p>
            <?php endif; ?>

            <?php if ($produkt['stan_magazynowy'] > 0): ?>
            <form method="post" action="produkt.php?slug=<?= htmlspecialchars($slug) ?>" class="add-to-cart-form">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <input type="hidden" name="dodaj_do_koszyka" value="1">
                <div class="qty-row">
                    <label>Ilość:</label>
                    <div class="qty-control">
                        <button type="button" onclick="this.nextElementSibling.stepDown()">−</button>
                        <input type="number" name="ilosc" value="1" min="1" max="<?= $produkt['stan_magazynowy'] ?>">
                        <button type="button" onclick="this.previousElementSibling.stepUp()">+</button>
                    </div>
                </div>
                <button type="submit" class="btn btn--primary btn--lg btn--full"><?= Icons::cart(16) ?> Dodaj do koszyka</button>
            </form>
            <?php endif; ?>

            <div class="product-perks">
                <span><?= Icons::truck(14) ?> Darmowa dostawa od 199 zł</span>
                <span><?= Icons::returnArrow(14) ?> Zwrot w ciągu 30 dni</span>
                <span><?= Icons::lock(14) ?> Bezpieczna płatność</span>
            </div>
        </div>
    </div>

    <!-- Opis -->
    <div class="product-tabs">
        <div class="tab-content">
            <h2>Opis produktu</h2>
            <div class="product-desc"><?= nl2br(htmlspecialchars($produkt['opis'] ?? '')) ?></div>
        </div>
    </div>

    <!-- Recenzje -->
    <div class="product-reviews">
        <h2>Recenzje (<?= count($recenzje) ?>)</h2>

        <?php foreach ($recenzje as $rec): ?>
        <div class="review-card">
            <div class="review-header">
                <strong><?= htmlspecialchars($rec['imie'] . ' ' . ($rec['nazwisko'][0] ?? '') . '.') ?></strong>
                <span class="review-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?= $i <= $rec['ocena'] ? 'star--on' : '' ?>">★</span>
                    <?php endfor; ?>
                </span>
                <small><?= date('d.m.Y', strtotime($rec['data_dodania'])) ?></small>
            </div>
            <?php if ($rec['tytul']): ?><strong class="review-title"><?= htmlspecialchars($rec['tytul']) ?></strong><?php endif; ?>
            <p><?= htmlspecialchars($rec['tresc']) ?></p>
        </div>
        <?php endforeach; ?>

        <?php if (Auth::isLoggedIn()): ?>
        <div class="review-form-wrap">
            <h3>Dodaj recenzję</h3>
            <form method="post" action="produkt.php?slug=<?= htmlspecialchars($slug) ?>" class="review-form">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <input type="hidden" name="dodaj_recenzje" value="1">
                <div class="form-row">
                    <label>Ocena:</label>
                    <div class="star-picker">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label><input type="radio" name="ocena" value="<?= $i ?>" <?= $i===5?'checked':'' ?>> <?= $i ?>★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-row">
                    <label>Tytuł:</label>
                    <input type="text" name="tytul" placeholder="Krótki tytuł recenzji" maxlength="100">
                </div>
                <div class="form-row">
                    <label>Treść:</label>
                    <textarea name="tresc" rows="4" placeholder="Opisz swoje doświadczenie z produktem..." required></textarea>
                </div>
                <button type="submit" class="btn btn--primary">Wyślij recenzję</button>
            </form>
        </div>
        <?php else: ?>
        <p><a href="logowanie.php">Zaloguj się</a>, aby dodać recenzję.</p>
        <?php endif; ?>
    </div>

    <!-- Podobne -->
    <?php if (!empty($podobne)): ?>
    <section class="section">
        <h2 class="section__title">Podobne produkty</h2>
        <div class="products-grid">
            <?php foreach ($podobne as $p): ?>
            <?php include __DIR__ . '/../templates/partials/karta_produktu.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
