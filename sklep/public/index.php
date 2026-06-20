<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

Auth::start();

$produktModel   = new ProduktModel();
$kategoriaModel = new KategoriaModel();
$koszykModel    = new KoszykModel();

$wyroznione  = $produktModel->wyroznione(8);
$kategorie   = $kategoriaModel->glowne();
$liczbawKoszyku = $koszykModel->liczbaPozycji();

$tytul = 'Strona główna — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>

<!-- Hero -->
<section class="hero">
    <div class="hero__inner container">
        <div class="hero__text">
            <span class="hero__eyebrow">Nowe kolekcje</span>
            <h1 class="hero__title">Odkryj świat<br><em>wyjątkowych produktów</em></h1>
            <p class="hero__sub">Tysiące produktów w najlepszych cenach. Darmowa dostawa od 199&nbsp;zł.</p>
            <div class="hero__actions">
                <a href="sklep.php" class="btn btn--primary btn--lg">Przejdź do sklepu</a>
                <a href="sklep.php?wyroznione=1" class="btn btn--ghost btn--lg">Bestsellery</a>
            </div>
        </div>
        <div class="hero__badges">
            <div class="badge-card"><span class="badge-card__icon"><?= Icons::truck(22) ?></span><strong>Darmowa dostawa</strong><small>od 199 zł</small></div>
            <div class="badge-card"><span class="badge-card__icon"><?= Icons::returnArrow(22) ?></span><strong>Zwrot 30 dni</strong><small>bez pytań</small></div>
            <div class="badge-card"><span class="badge-card__icon"><?= Icons::lock(22) ?></span><strong>Bezpieczne płatności</strong><small>SSL / 3D Secure</small></div>
            <div class="badge-card"><span class="badge-card__icon"><?= Icons::star(22) ?></span><strong>Program lojalnościowy</strong><small>Zbieraj punkty</small></div>
        </div>
    </div>
</section>

<!-- Kategorie -->
<section class="section">
    <div class="container">
        <h2 class="section__title">Kategorie</h2>
        <div class="categories-grid">
            <?php foreach ($kategorie as $kat): ?>
            <a href="sklep.php?kategoria=<?= htmlspecialchars($kat['slug']) ?>" class="category-card">
                <span class="category-card__name"><?= htmlspecialchars($kat['nazwa']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Wyróżnione produkty -->
<?php if (!empty($wyroznione)): ?>
<section class="section section--alt">
    <div class="container">
        <h2 class="section__title">Polecane produkty</h2>
        <div class="products-grid">
            <?php foreach ($wyroznione as $p): ?>
            <?php include __DIR__ . '/../templates/partials/karta_produktu.php'; ?>
            <?php endforeach; ?>
        </div>
        <div class="section__cta">
            <a href="sklep.php" class="btn btn--outline">Zobacz wszystkie produkty</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Baner promocji -->
<section class="promo-banner">
    <div class="container">
        <div class="promo-banner__inner">
            <div>
                <h2>Skorzystaj z kodu promocyjnego</h2>
                <p>Użyj kodu <strong>WITAJ10</strong> i zyskaj <strong>10% zniżki</strong> na pierwsze zamówienie!</p>
            </div>
            <a href="sklep.php" class="btn btn--white">Skorzystaj teraz</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
