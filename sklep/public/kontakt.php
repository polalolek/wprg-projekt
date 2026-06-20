<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
Auth::start();

$wyslano = false;
$bledy   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bledy[] = 'Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.';
    } else {
        $imie    = trim($_POST['imie'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $temat   = trim($_POST['temat'] ?? '');
        $tresc   = trim($_POST['tresc'] ?? '');

        if (!$imie)  $bledy[] = 'Podaj imię i nazwisko.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $bledy[] = 'Podaj prawidłowy adres e-mail.';
        if (!$temat) $bledy[] = 'Podaj temat wiadomości.';
        if (strlen($tresc) < 10) $bledy[] = 'Wiadomość jest za krótka.';

        if (empty($bledy)) {
            // W produkcji tutaj byłoby wysłanie e-maila (mail() lub biblioteka)
            $wyslano = true;
        }
    }
}

$liczbawKoszyku = (new KoszykModel())->liczbaPozycji();
$tytul = 'Kontakt — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>
<div class="container">
    <div class="static-page">
        <h1><?= Icons::mail(28) ?> Kontakt</h1>

        <div class="contact-layout">
            <div class="contact-info">
                <h2>Dane kontaktowe</h2>
                <ul class="contact-list">
                    <li><?= Icons::mapPin(16) ?> ul. Przykładowa 1, 00-001 Warszawa</li>
                    <li><?= Icons::mail(16) ?> kontakt@<?= strtolower(SITE_NAME) ?>.pl</li>
                    <li><?= Icons::phone(16) ?> +48 123 456 789</li>
                </ul>
                <h3>Godziny obsługi</h3>
                <p>Poniedziałek – Piątek: 9:00 – 17:00<br>Sobota – Niedziela: Zamknięte</p>
                <h3>Czas odpowiedzi</h3>
                <p>Odpowiadamy na wiadomości w ciągu <strong>1 dnia roboczego</strong>.</p>
            </div>

            <div class="contact-form-wrap">
                <?php if ($wyslano): ?>
                <div class="alert alert--success">
                    <?= Icons::check(16) ?> Wiadomość została wysłana. Odpiszemy wkrótce.
                </div>
                <?php else: ?>
                <?php if (!empty($bledy)): ?>
                <div class="alert alert--error">
                    <ul><?php foreach ($bledy as $b): ?><li><?= htmlspecialchars($b) ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>
                <form method="post" action="kontakt.php">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    <div class="form-row">
                        <label>Imię i nazwisko *</label>
                        <input type="text" name="imie" value="<?= htmlspecialchars($_POST['imie'] ?? '') ?>" required>
                    </div>
                    <div class="form-row">
                        <label>E-mail *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-row">
                        <label>Temat *</label>
                        <select name="temat">
                            <option value="">— wybierz temat —</option>
                            <option value="zamowienie" <?= ($_POST['temat'] ?? '') === 'zamowienie' ? 'selected' : '' ?>>Pytanie o zamówienie</option>
                            <option value="zwrot" <?= ($_POST['temat'] ?? '') === 'zwrot' ? 'selected' : '' ?>>Zwrot / reklamacja</option>
                            <option value="dostawa" <?= ($_POST['temat'] ?? '') === 'dostawa' ? 'selected' : '' ?>>Dostawa</option>
                            <option value="inne" <?= ($_POST['temat'] ?? '') === 'inne' ? 'selected' : '' ?>>Inne</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label>Wiadomość *</label>
                        <textarea name="tresc" rows="5" required><?= htmlspecialchars($_POST['tresc'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn--primary"><?= Icons::mail(15) ?> Wyślij wiadomość</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
