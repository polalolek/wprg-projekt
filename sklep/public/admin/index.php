<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

Auth::start();
Auth::requireAdmin();

$db              = Database::getInstance();
$zamowModel      = new ZamowienieModel();
$koszykModel     = new KoszykModel();

$statZam         = $zamowModel->statystyki();

$liczbaProd      = (int)$db->query("SELECT COUNT(*) FROM produkty WHERE aktywny = 1")->fetchColumn();
$liczbaUzyt      = (int)$db->query("SELECT COUNT(*) FROM uzytkownicy WHERE rola = 'klient'")->fetchColumn();
$noweZam         = (int)$db->query("SELECT COUNT(*) FROM zamowienia WHERE status = 'nowe'")->fetchColumn();
$recenzje        = (int)$db->query("SELECT COUNT(*) FROM recenzje WHERE zatwierdzona = 0")->fetchColumn();

$ostatnieZam     = $db->query("SELECT * FROM zamowienia ORDER BY data_zamowienia DESC LIMIT 5")->fetchAll();

$liczbawKoszyku  = $koszykModel->liczbaPozycji();
$tytul = 'Panel administracyjny — ' . SITE_NAME;
require_once __DIR__ . '/../../templates/partials/header.php';
?>

<div class="container">
    <div class="admin-layout">
        <?php include __DIR__ . '/../../templates/admin/sidebar.php'; ?>
        <main class="admin-main">
            <h1>Panel administracyjny</h1>

            
            <div class="stats-grid">
                <div class="stat-card stat-card--blue">
                    <span class="stat-card__val"><?= $liczbaProd ?></span>
                    <span class="stat-card__label">Aktywnych produktów</span>
                </div>
                <div class="stat-card stat-card--green">
                    <span class="stat-card__val"><?= number_format($statZam['lacznie'], 2, ',', ' ') ?> zł</span>
                    <span class="stat-card__label">Łączny przychód</span>
                </div>
                <div class="stat-card stat-card--orange">
                    <span class="stat-card__val"><?= $noweZam ?></span>
                    <span class="stat-card__label">Nowych zamówień</span>
                </div>
                <div class="stat-card stat-card--purple">
                    <span class="stat-card__val"><?= $liczbaUzyt ?></span>
                    <span class="stat-card__label">Klientów</span>
                </div>
            </div>

            <div class="admin-grid-2">
                <div class="admin-card">
                    <h2>Zamówienia wg statusu</h2>
                    <table class="data-table">
                        <thead><tr><th>Status</th><th>Liczba</th><th>Wartość</th></tr></thead>
                        <tbody>
                        <?php foreach ($statZam['statusy'] as $s): ?>
                        <tr>
                            <td><span class="status-badge status-<?= $s['status'] ?>"><?= ucfirst(str_replace('_',' ',$s['status'])) ?></span></td>
                            <td><?= $s['liczba'] ?></td>
                            <td><?= number_format($s['suma'], 2, ',', ' ') ?> zł</td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-card">
                    <h2>Ostatnie zamówienia</h2>
                    <table class="data-table">
                        <thead><tr><th>Nr</th><th>Klient</th><th>Wartość</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($ostatnieZam as $z): ?>
                        <tr>
                            <td><a href="zamowienia.php?id=<?= $z['id'] ?>"><?= htmlspecialchars($z['numer']) ?></a></td>
                            <td><?= htmlspecialchars($z['imie'] . ' ' . $z['nazwisko']) ?></td>
                            <td><?= number_format($z['wartosc_laczna'], 2, ',', ' ') ?> zł</td>
                            <td><span class="status-badge status-<?= $z['status'] ?>"><?= ucfirst(str_replace('_',' ',$z['status'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="zamowienia.php" class="btn btn--sm btn--outline">Wszystkie zamówienia</a>
                </div>
            </div>

            <?php if ($recenzje > 0): ?>
            <div class="alert alert--info">
                ⚠️ Masz <strong><?= $recenzje ?></strong> recenzji oczekujących na zatwierdzenie.
                <a href="recenzje.php">Przejdź do recenzji</a>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/partials/footer.php'; ?>
