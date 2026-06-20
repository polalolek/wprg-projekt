<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

Auth::start();
Auth::requireAdmin();

$uzytModel   = new UzytkownikModel();
$koszykModel = new KoszykModel();
$db          = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
    $akcja = $_POST['akcja'] ?? '';
    $uid   = (int)$_POST['user_id'];

    if ($akcja === 'toggle_aktywny') {
        $db->prepare("UPDATE uzytkownicy SET aktywny = 1 - aktywny WHERE id = ?")->execute([$uid]);
        Auth::flash('success', 'Status użytkownika zmieniony.');
    } elseif ($akcja === 'zmien_role') {
        $rola = in_array($_POST['rola'], ['klient','admin']) ? $_POST['rola'] : 'klient';
        $db->prepare("UPDATE uzytkownicy SET rola = ? WHERE id = ?")->execute([$rola, $uid]);
        Auth::flash('success', 'Rola zmieniona.');
    }
    header('Location: uzytkownicy.php');
    exit;
}

$uzytkownicy    = $uzytModel->wszyscy();
$liczbawKoszyku = $koszykModel->liczbaPozycji();

$tytul = 'Użytkownicy — Admin — ' . SITE_NAME;
require_once __DIR__ . '/../../templates/partials/header.php';
?>
<div class="container">
    <div class="admin-layout">
        <?php include __DIR__ . '/../../templates/admin/sidebar.php'; ?>
        <main class="admin-main">
            <?php $flash = Auth::getFlash('success'); if ($flash): ?><div class="alert alert--success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
            <h1>Użytkownicy</h1>
            <table class="data-table">
                <thead><tr><th>ID</th><th>Imię i nazwisko</th><th>E-mail</th><th>Rola</th><th>Punkty</th><th>Aktywny</th><th>Rejestracja</th><th>Akcje</th></tr></thead>
                <tbody>
                <?php foreach ($uzytkownicy as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['imie'] . ' ' . $u['nazwisko']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                            <input type="hidden" name="akcja" value="zmien_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="rola" onchange="this.form.submit()" <?= $u['id'] === Auth::userId() ? 'disabled' : '' ?>>
                                <option value="klient" <?= $u['rola']==='klient'?'selected':'' ?>>Klient</option>
                                <option value="admin"  <?= $u['rola']==='admin' ?'selected':'' ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                    <td><?= $u['punkty_lojalnosciowe'] ?></td>
                    <td><?= $u['aktywny'] ? '<span style="color:var(--success)">' . Icons::check(15) . '</span>' : '<span style="color:var(--error)">' . Icons::cross(15) . '</span>' ?></td>
                    <td><?= date('d.m.Y', strtotime($u['data_rejestracji'])) ?></td>
                    <td>
                        <?php if ($u['id'] !== Auth::userId()): ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                            <input type="hidden" name="akcja" value="toggle_aktywny">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn--sm <?= $u['aktywny'] ? 'btn--danger' : 'btn--outline' ?>">
                                <?= $u['aktywny'] ? 'Zablokuj' : 'Odblokuj' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../../templates/partials/footer.php'; ?>
