<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

Auth::start();
Auth::requireAdmin();

$katModel    = new KategoriaModel();
$koszykModel = new KoszykModel();

$akcja = $_GET['akcja'] ?? 'lista';
$id    = (int)($_GET['id'] ?? 0);

if ($akcja === 'usun' && $id) {
    if (Auth::verifyCsrf($_GET['csrf'] ?? '')) {
        $katModel->usun($id);
        Auth::flash('success', 'Kategoria usunięta.');
    }
    header('Location: kategorie.php');
    exit;
}

$bledy = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bledy[] = 'Błąd bezpieczeństwa.';
    } else {
        $nazwa    = trim($_POST['nazwa'] ?? '');
        $opis     = trim($_POST['opis'] ?? '');
        $rodzicId = ($_POST['rodzic_id'] ?? '') !== '' ? (int)$_POST['rodzic_id'] : null;
        $kolejnosc = (int)($_POST['kolejnosc'] ?? 0);

        if (!$nazwa) $bledy[] = 'Podaj nazwę kategorii.';

        if (empty($bledy)) {
            $slug = ProduktModel::slugify($nazwa);
            $dane = ['nazwa' => $nazwa, 'slug' => $slug, 'opis' => $opis ?: null, 'rodzic_id' => $rodzicId, 'kolejnosc' => $kolejnosc];
            if ($id) {
                unset($dane['slug']);
                $katModel->aktualizuj($id, $dane);
                Auth::flash('success', 'Kategoria zaktualizowana.');
            } else {
                $katModel->dodaj($dane);
                Auth::flash('success', 'Kategoria dodana.');
            }
            header('Location: kategorie.php');
            exit;
        }
    }
}

$edytowana  = $id ? $katModel->znajdzPoId($id) : null;
$wszystkie  = $katModel->wszystkie();
$glowne     = $katModel->glowne();
$liczbawKoszyku = $koszykModel->liczbaPozycji();

$tytul = 'Kategorie — Admin — ' . SITE_NAME;
require_once __DIR__ . '/../../templates/partials/header.php';
?>
<div class="container">
    <div class="admin-layout">
        <?php include __DIR__ . '/../../templates/admin/sidebar.php'; ?>
        <main class="admin-main">
            <?php $flash = Auth::getFlash('success'); if ($flash): ?><div class="alert alert--success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

            <?php if ($akcja === 'lista'): ?>
            <div class="admin-header">
                <h1>Kategorie</h1>
                <a href="?akcja=nowa" class="btn btn--primary">+ Dodaj kategorię</a>
            </div>
            <table class="data-table">
                <thead><tr><th>ID</th><th>Nazwa</th><th>Slug</th><th>Nadrzędna</th><th>Kolejność</th><th>Produkty</th><th>Akcje</th></tr></thead>
                <tbody>
                <?php foreach ($wszystkie as $k): ?>
                <tr>
                    <td><?= $k['id'] ?></td>
                    <td><?= htmlspecialchars(($k['rodzic_nazwa'] ? '↳ ' : '') . $k['nazwa']) ?></td>
                    <td><code><?= htmlspecialchars($k['slug']) ?></code></td>
                    <td><?= htmlspecialchars($k['rodzic_nazwa'] ?? '—') ?></td>
                    <td><?= $k['kolejnosc'] ?></td>
                    <td><?= $k['liczba_produktow'] ?></td>
                    <td class="actions">
                        <a href="?akcja=edytuj&id=<?= $k['id'] ?>" class="btn btn--sm btn--outline">Edytuj</a>
                        <a href="?akcja=usun&id=<?= $k['id'] ?>&csrf=<?= Auth::csrfToken() ?>"
                           class="btn btn--sm btn--danger"
                           onclick="return confirm('Usunąć kategorię?')">Usuń</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php else: ?>
            <h1><?= $edytowana ? 'Edytuj kategorię' : 'Dodaj kategorię' ?></h1>
            <?php if (!empty($bledy)): ?><div class="alert alert--error"><ul><?php foreach ($bledy as $b): ?><li><?= htmlspecialchars($b) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" action="kategorie.php?akcja=<?= $id ? 'edytuj&id='.$id : 'nowa' ?>" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <div class="form-row"><label>Nazwa *</label><input type="text" name="nazwa" value="<?= htmlspecialchars($edytowana['nazwa'] ?? $_POST['nazwa'] ?? '') ?>" required></div>
                <div class="form-row"><label>Opis</label><textarea name="opis" rows="3"><?= htmlspecialchars($edytowana['opis'] ?? '') ?></textarea></div>
                <div class="form-row">
                    <label>Kategoria nadrzędna</label>
                    <select name="rodzic_id">
                        <option value="">— Brak (kategoria główna) —</option>
                        <?php foreach ($glowne as $g): ?>
                        <?php if ($g['id'] !== $id): ?>
                        <option value="<?= $g['id'] ?>" <?= ($edytowana['rodzic_id'] ?? null) == $g['id'] ? 'selected' : '' ?>><?= htmlspecialchars($g['nazwa']) ?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row"><label>Kolejność</label><input type="number" name="kolejnosc" value="<?= $edytowana['kolejnosc'] ?? 0 ?>" min="0"></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Zapisz</button>
                    <a href="kategorie.php" class="btn btn--ghost">Anuluj</a>
                </div>
            </form>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../../templates/partials/footer.php'; ?>
