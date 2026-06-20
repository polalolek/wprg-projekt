<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

Auth::start();
Auth::requireAdmin();

$zamModel    = new ZamowienieModel();
$koszykModel = new KoszykModel();

$id     = (int)($_GET['id'] ?? 0);
$strona = max(1, (int)($_GET['strona'] ?? 1));
$status = $_GET['status'] ?? '';
$szukaj = trim($_GET['szukaj'] ?? '');

// Zmień status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zmien_status'])) {
    if (Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $zamModel->zmienStatus((int)$_POST['zam_id'], $_POST['nowy_status'], trim($_POST['komentarz'] ?? ''));
        Auth::flash('success', 'Status zamówienia zaktualizowany.');
    }
    header('Location: zamowienia.php?id=' . $_POST['zam_id']);
    exit;
}

$szczegoly = null;
if ($id) {
    $szczegoly = $zamModel->znajdzPoId($id);
    $historia  = $zamModel->historia($id);
}

$zamowienia     = $zamModel->wszystkie($strona, $status ?: null, $szukaj ?: null);
$liczbawKoszyku = $koszykModel->liczbaPozycji();

$statusy = ['nowe','oplacone','w_realizacji','wyslane','dostarczone','anulowane','zwrot'];
$tytul   = 'Zamówienia — Admin — ' . SITE_NAME;
require_once __DIR__ . '/../../templates/partials/header.php';
?>

<div class="container">
    <div class="admin-layout">
        <?php include __DIR__ . '/../../templates/admin/sidebar.php'; ?>
        <main class="admin-main">
            <?php $flash = Auth::getFlash('success'); if ($flash): ?>
            <div class="alert alert--success"><?= htmlspecialchars($flash) ?></div>
            <?php endif; ?>

            <?php if ($szczegoly): ?>
            <!-- Szczegóły zamówienia -->
            <div class="admin-header">
                <h1>Zamówienie <?= htmlspecialchars($szczegoly['numer']) ?></h1>
                <a href="zamowienia.php" class="btn btn--ghost">← Wróć</a>
            </div>

            <div class="order-detail-grid">
                <div class="admin-card">
                    <h2>Dane klienta</h2>
                    <p><?= htmlspecialchars($szczegoly['imie'] . ' ' . $szczegoly['nazwisko']) ?></p>
                    <p><?= htmlspecialchars($szczegoly['email']) ?></p>
                    <?php if ($szczegoly['telefon']): ?><p><?= htmlspecialchars($szczegoly['telefon']) ?></p><?php endif; ?>
                    <h3>Adres dostawy</h3>
                    <p><?= htmlspecialchars($szczegoly['ulica'] . ' ' . $szczegoly['nr_domu']) ?></p>
                    <p><?= htmlspecialchars($szczegoly['kod_pocztowy'] . ' ' . $szczegoly['miasto']) ?></p>
                </div>

                <div class="admin-card">
                    <h2>Zmień status</h2>
                    <p>Obecny: <span class="status-badge status-<?= $szczegoly['status'] ?>"><?= ucfirst(str_replace('_',' ',$szczegoly['status'])) ?></span></p>
                    <form method="post" action="zamowienia.php?id=<?= $id ?>">
                        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                        <input type="hidden" name="zmien_status" value="1">
                        <input type="hidden" name="zam_id" value="<?= $id ?>">
                        <div class="form-row">
                            <select name="nowy_status">
                                <?php foreach ($statusy as $s): ?>
                                <option value="<?= $s ?>" <?= $s === $szczegoly['status'] ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <input type="text" name="komentarz" placeholder="Komentarz (opcjonalnie)">
                        </div>
                        <button type="submit" class="btn btn--primary">Zaktualizuj status</button>
                    </form>
                </div>
            </div>

            <div class="admin-card">
                <h2>Produkty</h2>
                <table class="data-table">
                    <thead><tr><th>Produkt</th><th>SKU</th><th>Cena</th><th>Ilość</th><th>Wartość</th></tr></thead>
                    <tbody>
                    <?php foreach ($szczegoly['pozycje'] as $poz): ?>
                    <tr>
                        <td><?= htmlspecialchars($poz['nazwa_produktu']) ?></td>
                        <td><?= htmlspecialchars($poz['sku'] ?? '—') ?></td>
                        <td><?= number_format($poz['cena_jednostkowa'], 2, ',', ' ') ?> zł</td>
                        <td><?= $poz['ilosc'] ?></td>
                        <td><?= number_format($poz['wartosc'], 2, ',', ' ') ?> zł</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr><td colspan="4">Dostawa</td><td><?= number_format($szczegoly['koszt_dostawy'], 2, ',', ' ') ?> zł</td></tr>
                    <?php if ($szczegoly['rabat'] > 0): ?>
                    <tr><td colspan="4">Rabat</td><td>-<?= number_format($szczegoly['rabat'], 2, ',', ' ') ?> zł</td></tr>
                    <?php endif; ?>
                    <tr><td colspan="4"><strong>Łącznie</strong></td><td><strong><?= number_format($szczegoly['wartosc_laczna'], 2, ',', ' ') ?> zł</strong></td></tr>
                    </tfoot>
                </table>
            </div>

            <div class="admin-card">
                <h2>Historia statusów</h2>
                <div class="status-timeline">
                    <?php foreach ($historia as $h): ?>
                    <div class="timeline-item">
                        <span class="timeline-date"><?= date('d.m.Y H:i', strtotime($h['data'])) ?></span>
                        <span><?php if ($h['stary_status']): ?>
                            <span class="status-badge status-<?= $h['stary_status'] ?>"><?= ucfirst(str_replace('_',' ',$h['stary_status'])) ?></span> →
                        <?php endif; ?>
                        <span class="status-badge status-<?= $h['nowy_status'] ?>"><?= ucfirst(str_replace('_',' ',$h['nowy_status'])) ?></span>
                        <?php if ($h['komentarz']): ?> — <?= htmlspecialchars($h['komentarz']) ?><?php endif; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- Lista zamówień -->
            <div class="admin-header">
                <h1>Zamówienia</h1>
            </div>

            <!-- Filtry -->
            <form method="get" action="zamowienia.php" class="filter-bar">
                <select name="status" onchange="this.form.submit()">
                    <option value="">Wszystkie statusy</option>
                    <?php foreach ($statusy as $s): ?>
                    <option value="<?= $s ?>" <?= $s === $status ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="szukaj" value="<?= htmlspecialchars($szukaj) ?>" placeholder="Szukaj po nr, email, nazwisku">
                <button type="submit" class="btn btn--sm btn--outline">Szukaj</button>
                <?php if ($status || $szukaj): ?><a href="zamowienia.php" class="btn btn--sm btn--ghost">Wyczyść</a><?php endif; ?>
            </form>

            <table class="data-table">
                <thead><tr><th>Nr zamówienia</th><th>Data</th><th>Klient</th><th>Wartość</th><th>Płatność</th><th>Status</th><th>Akcje</th></tr></thead>
                <tbody>
                <?php foreach ($zamowienia as $z): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($z['numer']) ?></strong></td>
                    <td><?= date('d.m.Y H:i', strtotime($z['data_zamowienia'])) ?></td>
                    <td><?= htmlspecialchars($z['imie'] . ' ' . $z['nazwisko']) ?><br><small><?= htmlspecialchars($z['email']) ?></small></td>
                    <td><?= number_format($z['wartosc_laczna'], 2, ',', ' ') ?> zł</td>
                    <td><?= htmlspecialchars($z['metoda_platnosci']) ?></td>
                    <td><span class="status-badge status-<?= $z['status'] ?>"><?= ucfirst(str_replace('_',' ',$z['status'])) ?></span></td>
                    <td><a href="zamowienia.php?id=<?= $z['id'] ?>" class="btn btn--sm btn--outline">Szczegóły</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/partials/footer.php'; ?>
