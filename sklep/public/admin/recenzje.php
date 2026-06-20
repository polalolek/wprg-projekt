<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

Auth::start();
Auth::requireAdmin();

$db          = Database::getInstance();
$koszykModel = new KoszykModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
    $akcja = $_POST['akcja'] ?? '';
    $rid   = (int)$_POST['recenzja_id'];

    if ($akcja === 'zatwierdz') {
        $db->prepare("UPDATE recenzje SET zatwierdzona = 1 WHERE id = ?")->execute([$rid]);
        Auth::flash('success', 'Recenzja zatwierdzona.');
    } elseif ($akcja === 'usun') {
        $db->prepare("DELETE FROM recenzje WHERE id = ?")->execute([$rid]);
        Auth::flash('success', 'Recenzja usunięta.');
    }
    header('Location: recenzje.php');
    exit;
}

$filtr = $_GET['filtr'] ?? 'oczekujace';
$where = $filtr === 'wszystkie' ? '' : 'WHERE r.zatwierdzona = 0';

$recenzje = $db->query(
    "SELECT r.*, p.nazwa AS produkt_nazwa, p.slug AS produkt_slug,
        u.imie, u.nazwisko
     FROM recenzje r
     LEFT JOIN produkty p ON p.id = r.produkt_id
     LEFT JOIN uzytkownicy u ON u.id = r.uzytkownik_id
     $where
     ORDER BY r.data_dodania DESC"
)->fetchAll();

$liczbawKoszyku = $koszykModel->liczbaPozycji();
$tytul = 'Recenzje — Admin — ' . SITE_NAME;
require_once __DIR__ . '/../../templates/partials/header.php';
?>
<div class="container">
    <div class="admin-layout">
        <?php include __DIR__ . '/../../templates/admin/sidebar.php'; ?>
        <main class="admin-main">
            <?php $flash = Auth::getFlash('success'); if ($flash): ?><div class="alert alert--success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
            <div class="admin-header">
                <h1>Recenzje</h1>
                <div>
                    <a href="?filtr=oczekujace" class="btn btn--sm <?= $filtr==='oczekujace'?'btn--primary':'btn--ghost' ?>">Oczekujące</a>
                    <a href="?filtr=wszystkie"  class="btn btn--sm <?= $filtr==='wszystkie' ?'btn--primary':'btn--ghost' ?>">Wszystkie</a>
                </div>
            </div>

            <?php if (empty($recenzje)): ?>
            <div class="empty-state"><p>Brak recenzji do wyświetlenia.</p></div>
            <?php else: ?>
            <?php foreach ($recenzje as $r): ?>
            <div class="admin-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap">
                    <div>
                        <div style="display:flex;gap:12px;align-items:center;margin-bottom:8px">
                            <strong><?= htmlspecialchars($r['imie'].' '.($r['nazwisko'] ?? '')) ?></strong>
                            <span style="display:flex;gap:2px">
                                <?php for ($i=1;$i<=5;$i++): ?>
                                <span style="color:<?= $i<=$r['ocena']?'var(--amber)':'var(--gray-300)' ?>">★</span>
                                <?php endfor; ?>
                            </span>
                            <small style="color:var(--gray-500)"><?= date('d.m.Y H:i', strtotime($r['data_dodania'])) ?></small>
                            <?php if ($r['zatwierdzona']): ?><span class="status-badge status-dostarczone">Zatwierdzona</span><?php else: ?><span class="status-badge status-nowe">Oczekuje</span><?php endif; ?>
                        </div>
                        <div style="margin-bottom:6px">
                            Produkt: <a href="../../public/produkt.php?slug=<?= htmlspecialchars($r['produkt_slug'] ?? '') ?>"><?= htmlspecialchars($r['produkt_nazwa'] ?? 'Usunięty') ?></a>
                        </div>
                        <?php if ($r['tytul']): ?><strong><?= htmlspecialchars($r['tytul']) ?></strong><br><?php endif; ?>
                        <p style="color:var(--gray-700);margin-top:6px"><?= htmlspecialchars($r['tresc']) ?></p>
                    </div>
                    <div style="display:flex;gap:8px;flex-shrink:0">
                        <?php if (!$r['zatwierdzona']): ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                            <input type="hidden" name="akcja" value="zatwierdz">
                            <input type="hidden" name="recenzja_id" value="<?= $r['id'] ?>">
                            <button class="btn btn--sm btn--outline"><?= Icons::check(14) ?> Zatwierdź</button>
                        </form>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                            <input type="hidden" name="akcja" value="usun">
                            <input type="hidden" name="recenzja_id" value="<?= $r['id'] ?>">
                            <button class="btn btn--sm btn--danger" onclick="return confirm('Usunąć recenzję?')">Usuń</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../../templates/partials/footer.php'; ?>
