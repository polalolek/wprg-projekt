<?php
// potwierdzenie.php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
Auth::start();

$id  = (int)($_GET['id'] ?? 0);
$zam = (new ZamowienieModel())->znajdzPoId($id);
if (!$zam) { header('Location: index.php'); exit; }

// Zabezpieczenie — tylko właściciel lub admin
if (!Auth::isAdmin() && $zam['uzytkownik_id'] !== Auth::userId()) {
    header('Location: index.php'); exit;
}

$liczbawKoszyku = (new KoszykModel())->liczbaPozycji();
$tytul = 'Potwierdzenie zamówienia — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>
<div class="container">
    <div class="confirm-box">
        <div class="confirm-icon"><?= Icons::checkCircle(36) ?></div>
        <h1>Zamówienie złożone!</h1>
        <p>Dziękujemy za zakupy. Twoje zamówienie numer <strong><?= htmlspecialchars($zam['numer']) ?></strong> zostało przyjęte.</p>
        <p>Potwierdzenie zostanie wysłane na adres <strong><?= htmlspecialchars($zam['email']) ?></strong>.</p>

        <div class="order-summary-box">
            <h2>Szczegóły zamówienia</h2>
            <table class="order-detail-table">
                <thead><tr><th>Produkt</th><th>Ilość</th><th>Wartość</th></tr></thead>
                <tbody>
                <?php foreach ($zam['pozycje'] as $poz): ?>
                <tr>
                    <td><?= htmlspecialchars($poz['nazwa_produktu']) ?></td>
                    <td><?= $poz['ilosc'] ?></td>
                    <td><?= number_format($poz['wartosc'], 2, ',', ' ') ?> zł</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr><td colspan="2">Dostawa</td><td><?= number_format($zam['koszt_dostawy'], 2, ',', ' ') ?> zł</td></tr>
                <?php if ($zam['rabat'] > 0): ?>
                <tr><td colspan="2">Rabat</td><td>-<?= number_format($zam['rabat'], 2, ',', ' ') ?> zł</td></tr>
                <?php endif; ?>
                <tr class="tfoot-total"><td colspan="2"><strong>Łącznie</strong></td><td><strong><?= number_format($zam['wartosc_laczna'], 2, ',', ' ') ?> zł</strong></td></tr>
                </tfoot>
            </table>
        </div>

        <div class="confirm-actions">
            <?php if (Auth::isLoggedIn()): ?>
            <a href="konto.php" class="btn btn--outline">Moje zamówienia</a>
            <?php endif; ?>
            <a href="sklep.php" class="btn btn--primary">Kontynuuj zakupy</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
