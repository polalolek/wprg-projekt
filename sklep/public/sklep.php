<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

Auth::start();

$produktModel   = new ProduktModel();
$kategoriaModel = new KategoriaModel();
$koszykModel    = new KoszykModel();

$strona     = max(1, (int)($_GET['strona'] ?? 1));
$katSlug    = trim($_GET['kategoria'] ?? '');
$szukaj     = trim($_GET['szukaj'] ?? '');
$sort       = $_GET['sort'] ?? 'data_dodania';
$kierunek   = $_GET['kier'] ?? 'DESC';
$cenaMin    = isset($_GET['cena_min']) && $_GET['cena_min'] !== '' ? (float)$_GET['cena_min'] : null;
$cenaMax    = isset($_GET['cena_max']) && $_GET['cena_max'] !== '' ? (float)$_GET['cena_max'] : null;

$kategoria    = $katSlug ? $kategoriaModel->znajdzPoSlug($katSlug) : null;
$kategoriaId  = $kategoria ? (int)$kategoria['id'] : null;

$liczbaProdukow = $produktModel->liczWszystkie($kategoriaId, $szukaj ?: null, $cenaMin, $cenaMax);
$stronyLacznie  = (int)ceil($liczbaProdukow / ITEMS_PER_PAGE);
$produkty       = $produktModel->znajdzWszystkie($strona, ITEMS_PER_PAGE, $kategoriaId, $szukaj ?: null, $sort, $kierunek, $cenaMin, $cenaMax);
$kategorie      = $kategoriaModel->glowne();
$liczbawKoszyku = $koszykModel->liczbaPozycji();

$tytul = ($kategoria ? $kategoria['nazwa'] . ' — ' : '') . 'Sklep — ' . SITE_NAME;

function buildUrl(array $merge = [], array $unset = []): string
{
    $params = $_GET;
    foreach ($unset as $k) unset($params[$k]);
    $params = array_merge($params, $merge);
    unset($params['strona']);
    return '?' . http_build_query($params);
}

require_once __DIR__ . '/../templates/partials/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php">Strona główna</a> /
        <?php if ($kategoria): ?>
            <a href="sklep.php">Sklep</a> / <span><?= htmlspecialchars($kategoria['nazwa']) ?></span>
        <?php elseif ($szukaj): ?>
            <a href="sklep.php">Sklep</a> / <span>Wyniki dla: "<?= htmlspecialchars($szukaj) ?>"</span>
        <?php else: ?>
            <span>Sklep</span>
        <?php endif; ?>
    </nav>

    <div class="shop-layout">
        <!-- Sidebar -->
        <aside class="shop-sidebar">
            <div class="sidebar-block">
                <h3>Kategorie</h3>
                <ul class="sidebar-list">
                    <li><a href="sklep.php" class="<?= !$kategoriaId ? 'active' : '' ?>">Wszystkie</a></li>
                    <?php foreach ($kategorie as $kat): ?>
                    <li><a href="sklep.php?kategoria=<?= $kat['slug'] ?>" class="<?= ($kat['id'] == $kategoriaId) ? 'active' : '' ?>">
                        <?= htmlspecialchars($kat['nazwa']) ?>
                    </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="sidebar-block">
                <h3>Cena</h3>
                <form method="get" action="sklep.php">
                    <?php if ($katSlug): ?><input type="hidden" name="kategoria" value="<?= htmlspecialchars($katSlug) ?>"><?php endif; ?>
                    <?php if ($szukaj): ?><input type="hidden" name="szukaj" value="<?= htmlspecialchars($szukaj) ?>"><?php endif; ?>
                    <div class="price-range">
                        <input type="number" name="cena_min" placeholder="Od" value="<?= htmlspecialchars((string)($cenaMin ?? '')) ?>" min="0" step="1">
                        <span>–</span>
                        <input type="number" name="cena_max" placeholder="Do" value="<?= htmlspecialchars((string)($cenaMax ?? '')) ?>" min="0" step="1">
                    </div>
                    <button type="submit" class="btn btn--sm btn--outline btn--full">Filtruj</button>
                </form>
            </div>
        </aside>

        <!-- Produkty -->
        <main class="shop-main">
            <div class="shop-toolbar">
                <p class="shop-count">Znaleziono <strong><?= $liczbaProdukow ?></strong> produktów</p>
                <div class="shop-sort">
                    <label for="sort">Sortuj:</label>
                    <select id="sort" onchange="location.href=this.value">
                        <option value="<?= buildUrl(['sort'=>'data_dodania','kier'=>'DESC']) ?>" <?= ($sort==='data_dodania'&&$kierunek==='DESC')?'selected':'' ?>>Najnowsze</option>
                        <option value="<?= buildUrl(['sort'=>'cena','kier'=>'ASC']) ?>" <?= ($sort==='cena'&&$kierunek==='ASC')?'selected':'' ?>>Cena rosnąco</option>
                        <option value="<?= buildUrl(['sort'=>'cena','kier'=>'DESC']) ?>" <?= ($sort==='cena'&&$kierunek==='DESC')?'selected':'' ?>>Cena malejąco</option>
                        <option value="<?= buildUrl(['sort'=>'nazwa','kier'=>'ASC']) ?>" <?= ($sort==='nazwa'&&$kierunek==='ASC')?'selected':'' ?>>Nazwa A–Z</option>
                    </select>
                </div>
            </div>

            <?php if (empty($produkty)): ?>
                <div class="empty-state">
                    <p>Brak produktów spełniających kryteria.</p>
                    <a href="sklep.php" class="btn btn--outline">Pokaż wszystkie</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($produkty as $p): ?>
                    <?php include __DIR__ . '/../templates/partials/karta_produktu.php'; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Paginacja -->
                <?php if ($stronyLacznie > 1): ?>
                <nav class="pagination">
                    <?php if ($strona > 1): ?>
                        <a href="<?= buildUrl(['strona' => $strona - 1]) ?>" class="page-btn">&laquo; Poprzednia</a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $strona-2); $i <= min($stronyLacznie, $strona+2); $i++): ?>
                        <a href="<?= buildUrl(['strona' => $i]) ?>" class="page-btn <?= $i===$strona?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($strona < $stronyLacznie): ?>
                        <a href="<?= buildUrl(['strona' => $strona + 1]) ?>" class="page-btn">Następna &raquo;</a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
