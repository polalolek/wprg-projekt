<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

Auth::start();
Auth::requireAdmin();

$db          = Database::getInstance();
$koszykModel = new KoszykModel();

$akcja = $_GET['akcja'] ?? 'lista';
$id    = (int)($_GET['id'] ?? 0);

if ($akcja === 'usun' && $id) {
    if (Auth::verifyCsrf($_GET['csrf'] ?? '')) {
        $db->prepare("DELETE FROM promocje WHERE id = ?")->execute([$id]);
        Auth::flash('success', 'Kod rabatowy usunięty.');
    }
    header('Location: promocje.php');
    exit;
}

if ($akcja === 'toggle' && $id) {
    if (Auth::verifyCsrf($_GET['csrf'] ?? '')) {
        $db->prepare("UPDATE promocje SET aktywna = 1 - aktywna WHERE id = ?")->execute([$id]);
        Auth::flash('success', 'Status promocji zmieniony.');
    }
    header('Location: promocje.php');
    exit;
}

$bledy = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bledy[] = 'Błąd bezpieczeństwa.';
    } else {
        $kod      = strtoupper(trim($_POST['kod'] ?? ''));
        $typ      = $_POST['typ'] ?? 'procent';
        $wartosc  = (float)$_POST['wartosc'];
        $minWart  = (float)($_POST['min_wartosc'] ?? 0);
        $maxUzyc  = ($_POST['max_uzyc'] ?? '') !== '' ? (int)$_POST['max_uzyc'] : null;
        $dataOd   = $_POST['data_od'] ?: null;
        $dataDo   = $_POST['data_do'] ?: null;

        if (!$kod)   $bledy[] = 'Podaj kod.';
        if ($wartosc <= 0) $bledy[] = 'Podaj wartość rabatu.';
        if (!in_array($typ, ['procent','kwota'])) $bledy[] = 'Nieprawidłowy typ.';

        if (empty($bledy)) {
            if ($id) {
                $db->prepare("UPDATE promocje SET kod=?,typ=?,wartosc=?,min_wartosc_zamowienia=?,max_uzyc=?,data_od=?,data_do=? WHERE id=?")
                   ->execute([$kod,$typ,$wartosc,$minWart,$maxUzyc,$dataOd,$dataDo,$id]);
            } else {
                $db->prepare("INSERT INTO promocje (kod,typ,wartosc,min_wartosc_zamowienia,max_uzyc,data_od,data_do) VALUES(?,?,?,?,?,?,?)")
                   ->execute([$kod,$typ,$wartosc,$minWart,$maxUzyc,$dataOd,$dataDo]);
            }
            Auth::flash('success', 'Kod rabatowy zapisany.');
            header('Location: promocje.php');
            exit;
        }
    }
}

$promocje       = $db->query("SELECT * FROM promocje ORDER BY id DESC")->fetchAll();
$edytowana      = null;
if ($id) {
    $s = $db->prepare("SELECT * FROM promocje WHERE id = ?");
    $s->execute([$id]);
    $edytowana = $s->fetch() ?: null;
}
$liczbawKoszyku = $koszykModel->liczbaPozycji();

$tytul = 'Promocje — Admin — ' . SITE_NAME;
require_once __DIR__ . '/../../templates/partials/header.php';
?>
<div class="container">
    <div class="admin-layout">
        <?php include __DIR__ . '/../../templates/admin/sidebar.php'; ?>
        <main class="admin-main">
            <?php $flash = Auth::getFlash('success'); if ($flash): ?><div class="alert alert--success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

            <?php if ($akcja === 'lista'): ?>
            <div class="admin-header">
                <h1>Kody rabatowe</h1>
                <a href="?akcja=nowy" class="btn btn--primary">+ Dodaj kod</a>
            </div>
            <table class="data-table">
                <thead><tr><th>Kod</th><th>Typ</th><th>Wartość</th><th>Min. zamówienie</th><th>Użyto</th><th>Ważny do</th><th>Aktywna</th><th>Akcje</th></tr></thead>
                <tbody>
                <?php foreach ($promocje as $p): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['kod']) ?></strong></td>
                    <td><?= $p['typ'] === 'procent' ? 'Procentowy' : 'Kwotowy' ?></td>
                    <td><?= $p['typ'] === 'procent' ? $p['wartosc'].'%' : number_format($p['wartosc'],2,',','').' zł' ?></td>
                    <td><?= $p['min_wartosc_zamowienia'] > 0 ? number_format($p['min_wartosc_zamowienia'],2,',','').' zł' : '—' ?></td>
                    <td><?= $p['uzyto'] ?><?= $p['max_uzyc'] ? '/'.$p['max_uzyc'] : '' ?></td>
                    <td><?= $p['data_do'] ? date('d.m.Y', strtotime($p['data_do'])) : '—' ?></td>
                    <td><?= $p['aktywna'] ? '<span style="color:var(--success)">' . Icons::check(15) . '</span>' : '<span style="color:var(--error)">' . Icons::cross(15) . '</span>' ?></td>
                    <td class="actions">
                        <a href="?akcja=edytuj&id=<?= $p['id'] ?>" class="btn btn--sm btn--outline">Edytuj</a>
                        <a href="?akcja=toggle&id=<?= $p['id'] ?>&csrf=<?= Auth::csrfToken() ?>" class="btn btn--sm btn--ghost"><?= $p['aktywna'] ? 'Wyłącz' : 'Włącz' ?></a>
                        <a href="?akcja=usun&id=<?= $p['id'] ?>&csrf=<?= Auth::csrfToken() ?>" class="btn btn--sm btn--danger" onclick="return confirm('Usunąć kod?')">Usuń</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php else: ?>
            <h1><?= $edytowana ? 'Edytuj kod' : 'Dodaj kod rabatowy' ?></h1>
            <?php if (!empty($bledy)): ?><div class="alert alert--error"><ul><?php foreach ($bledy as $b): ?><li><?= htmlspecialchars($b) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" action="promocje.php?akcja=<?= $id ? 'edytuj&id='.$id : 'nowy' ?>" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <div class="form-grid">
                    <div class="form-row"><label>Kod *</label><input type="text" name="kod" value="<?= htmlspecialchars(strtoupper($edytowana['kod'] ?? '')) ?>" required style="text-transform:uppercase"></div>
                    <div class="form-row">
                        <label>Typ *</label>
                        <select name="typ">
                            <option value="procent" <?= ($edytowana['typ'] ?? 'procent')==='procent'?'selected':'' ?>>Procentowy (%)</option>
                            <option value="kwota"   <?= ($edytowana['typ'] ?? '')==='kwota'?'selected':'' ?>>Kwotowy (zł)</option>
                        </select>
                    </div>
                    <div class="form-row"><label>Wartość *</label><input type="number" name="wartosc" step="0.01" min="0" value="<?= $edytowana['wartosc'] ?? '' ?>" required></div>
                    <div class="form-row"><label>Min. wartość zamówienia (zł)</label><input type="number" name="min_wartosc" step="0.01" min="0" value="<?= $edytowana['min_wartosc_zamowienia'] ?? 0 ?>"></div>
                    <div class="form-row"><label>Maks. liczba użyć</label><input type="number" name="max_uzyc" min="1" value="<?= $edytowana['max_uzyc'] ?? '' ?>" placeholder="Bez limitu"></div>
                    <div class="form-row"><label>Ważny od</label><input type="date" name="data_od" value="<?= $edytowana['data_od'] ?? '' ?>"></div>
                    <div class="form-row"><label>Ważny do</label><input type="date" name="data_do" value="<?= $edytowana['data_do'] ?? '' ?>"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">Zapisz</button>
                    <a href="promocje.php" class="btn btn--ghost">Anuluj</a>
                </div>
            </form>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../../templates/partials/footer.php'; ?>
