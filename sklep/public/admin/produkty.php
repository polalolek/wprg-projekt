<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

Auth::start();
Auth::requireAdmin();

$produktModel   = new ProduktModel();
$kategoriaModel = new KategoriaModel();
$koszykModel    = new KoszykModel();

$akcja = $_GET['akcja'] ?? 'lista';
$id    = (int)($_GET['id'] ?? 0);

// Usuń produkt
if ($akcja === 'usun' && $id) {
    if (Auth::verifyCsrf($_GET['csrf'] ?? '')) {
        $produktModel->usun($id);
        Auth::flash('success', 'Produkt usunięty.');
    }
    header('Location: produkty.php');
    exit;
}

$bledy = [];

// Zapisz produkt (nowy lub edycja)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bledy[] = 'Błąd bezpieczeństwa.';
    } else {
        $nazwa    = trim($_POST['nazwa'] ?? '');
        $opis     = trim($_POST['opis'] ?? '');
        $opisKr   = trim($_POST['opis_krotki'] ?? '');
        $cena     = (float)str_replace(',', '.', $_POST['cena'] ?? '0');
        $cenaPr   = $_POST['cena_promocyjna'] !== '' ? (float)str_replace(',', '.', $_POST['cena_promocyjna']) : null;
        $stan     = (int)($_POST['stan_magazynowy'] ?? 0);
        $katId    = ($_POST['kategoria_id'] ?? '') !== '' ? (int)$_POST['kategoria_id'] : null;
        $sku      = trim($_POST['sku'] ?? '') ?: null;
        $aktywny  = isset($_POST['aktywny']) ? 1 : 0;
        $wyroz    = isset($_POST['wyrozniony']) ? 1 : 0;

        if (!$nazwa) $bledy[] = 'Podaj nazwę produktu.';
        if ($cena <= 0) $bledy[] = 'Podaj prawidłową cenę.';

        // Zdjęcie
        $zdjecieGl = null;
        if (!empty($_FILES['zdjecie']['name'])) {
            $mime = mime_content_type($_FILES['zdjecie']['tmp_name']);
            if (!in_array($mime, UPLOAD_ALLOWED)) {
                $bledy[] = 'Nieobsługiwany format zdjęcia.';
            } elseif ($_FILES['zdjecie']['size'] > UPLOAD_MAX_SIZE) {
                $bledy[] = 'Zdjęcie jest za duże (max 5 MB).';
            } else {
                $ext      = pathinfo($_FILES['zdjecie']['name'], PATHINFO_EXTENSION);
                $zdjecieGl = uniqid('prod_') . '.' . $ext;
                @mkdir(UPLOAD_DIR, 0755, true);
                move_uploaded_file($_FILES['zdjecie']['tmp_name'], UPLOAD_DIR . $zdjecieGl);
            }
        }

        if (empty($bledy)) {
            $dane = [
                'kategoria_id'      => $katId,
                'nazwa'             => $nazwa,
                'slug'              => ProduktModel::slugify($nazwa) . ($id ? '' : '-' . uniqid()),
                'opis'              => $opis,
                'opis_krotki'       => $opisKr,
                'cena'              => $cena,
                'cena_promocyjna'   => $cenaPr,
                'stan_magazynowy'   => $stan,
                'sku'               => $sku,
                'aktywny'           => $aktywny,
                'wyrozniony'        => $wyroz,
            ];
            if ($zdjecieGl) $dane['zdjecie_glowne'] = $zdjecieGl;

            if ($id) {
                unset($dane['slug']);
                $produktModel->aktualizuj($id, $dane);
                Auth::flash('success', 'Produkt zaktualizowany.');
            } else {
                $produktModel->dodaj($dane);
                Auth::flash('success', 'Produkt dodany.');
            }
            header('Location: produkty.php');
            exit;
        }
    }
}

$edytowany   = $id ? $produktModel->znajdzPoId($id) : null;
$wszystkieProd = $produktModel->znajdzWszystkie(1, 50);
$kategorie   = $kategoriaModel->wszystkie();
$liczbawKoszyku = $koszykModel->liczbaPozycji();

$tytul = 'Produkty — Admin — ' . SITE_NAME;
require_once __DIR__ . '/../../templates/partials/header.php';
?>

<div class="container">
    <div class="admin-layout">
        <?php include __DIR__ . '/../../templates/admin/sidebar.php'; ?>
        <main class="admin-main">
            <?php $flash = Auth::getFlash('success'); if ($flash): ?>
            <div class="alert alert--success"><?= htmlspecialchars($flash) ?></div>
            <?php endif; ?>

            <?php if ($akcja === 'lista'): ?>
            <div class="admin-header">
                <h1>Produkty</h1>
                <a href="?akcja=nowy" class="btn btn--primary">+ Dodaj produkt</a>
            </div>
            <table class="data-table">
                <thead><tr><th>ID</th><th>Nazwa</th><th>Kategoria</th><th>Cena</th><th>Stan</th><th>Aktywny</th><th>Akcje</th></tr></thead>
                <tbody>
                <?php foreach ($wszystkieProd as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['nazwa']) ?></td>
                    <td><?= htmlspecialchars($p['kategoria_nazwa'] ?? '—') ?></td>
                    <td><?= number_format($p['cena_aktualna'], 2, ',', ' ') ?> zł</td>
                    <td><?= $p['stan_magazynowy'] ?></td>
                    <td><?= $p['aktywny'] ? '<span style="color:var(--success)">' . Icons::check(15) . '</span>' : '<span style="color:var(--error)">' . Icons::cross(15) . '</span>' ?></td>
                    <td class="actions">
                        <a href="?akcja=edytuj&id=<?= $p['id'] ?>" class="btn btn--sm btn--outline">Edytuj</a>
                        <a href="?akcja=usun&id=<?= $p['id'] ?>&csrf=<?= Auth::csrfToken() ?>"
                           class="btn btn--sm btn--danger"
                           onclick="return confirm('Usunąć produkt?')">Usuń</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php else: // Formularz dodaj/edytuj ?>
            <h1><?= $edytowany ? 'Edytuj produkt' : 'Dodaj produkt' ?></h1>
            <?php if (!empty($bledy)): ?>
            <div class="alert alert--error"><ul><?php foreach ($bledy as $b): ?><li><?= htmlspecialchars($b) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <form method="post" action="produkty.php?akcja=<?= $id ? 'edytuj&id='.$id : 'nowy' ?>" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <div class="form-grid">
                    <div class="form-row form-row--full">
                        <label>Nazwa *</label>
                        <input type="text" name="nazwa" value="<?= htmlspecialchars($edytowany['nazwa'] ?? $_POST['nazwa'] ?? '') ?>" required>
                    </div>
                    <div class="form-row">
                        <label>Cena (zł) *</label>
                        <input type="number" name="cena" step="0.01" min="0" value="<?= $edytowany['cena'] ?? $_POST['cena'] ?? '' ?>" required>
                    </div>
                    <div class="form-row">
                        <label>Cena promocyjna (zł)</label>
                        <input type="number" name="cena_promocyjna" step="0.01" min="0" value="<?= $edytowany['cena_promocyjna'] ?? $_POST['cena_promocyjna'] ?? '' ?>">
                    </div>
                    <div class="form-row">
                        <label>Stan magazynowy</label>
                        <input type="number" name="stan_magazynowy" min="0" value="<?= $edytowany['stan_magazynowy'] ?? $_POST['stan_magazynowy'] ?? 0 ?>">
                    </div>
                    <div class="form-row">
                        <label>SKU</label>
                        <input type="text" name="sku" value="<?= htmlspecialchars($edytowany['sku'] ?? $_POST['sku'] ?? '') ?>">
                    </div>
                    <div class="form-row form-row--full">
                        <label>Kategoria</label>
                        <select name="kategoria_id">
                            <option value="">— Brak —</option>
                            <?php foreach ($kategorie as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= ($edytowany['kategoria_id'] ?? null) == $k['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(($k['rodzic_nazwa'] ? $k['rodzic_nazwa'].' / ' : '') . $k['nazwa']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row form-row--full">
                        <label>Krótki opis</label>
                        <input type="text" name="opis_krotki" value="<?= htmlspecialchars($edytowany['opis_krotki'] ?? '') ?>" maxlength="255">
                    </div>
                    <div class="form-row form-row--full">
                        <label>Pełny opis</label>
                        <textarea name="opis" rows="6"><?= htmlspecialchars($edytowany['opis'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row form-row--full">
                        <label>Zdjęcie główne</label>
                        <input type="file" name="zdjecie" accept="image/jpeg,image/png,image/webp">
                        <?php if (!empty($edytowany['zdjecie_glowne'])): ?>
                        <small>Aktualne: <?= htmlspecialchars($edytowany['zdjecie_glowne']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="form-row">
                        <label class="checkbox-label"><input type="checkbox" name="aktywny" value="1" <?= ($edytowany['aktywny'] ?? 1) ? 'checked' : '' ?>> Aktywny</label>
                    </div>
                    <div class="form-row">
                        <label class="checkbox-label"><input type="checkbox" name="wyrozniony" value="1" <?= ($edytowany['wyrozniony'] ?? 0) ? 'checked' : '' ?>> Wyróżniony</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Zapisz produkt</button>
                    <a href="produkty.php" class="btn btn--ghost">Anuluj</a>
                </div>
            </form>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/partials/footer.php'; ?>
