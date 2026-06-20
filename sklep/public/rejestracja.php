<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

Auth::start();

if (Auth::isLoggedIn()) { header('Location: konto.php'); exit; }

$bledy = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bledy[] = 'Błąd bezpieczeństwa.';
    } else {
        $imie    = trim($_POST['imie'] ?? '');
        $nazwisko = trim($_POST['nazwisko'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $haslo   = $_POST['haslo'] ?? '';
        $haslo2  = $_POST['haslo2'] ?? '';
        $uzyt    = new UzytkownikModel();

        if (!$imie)    $bledy[] = 'Podaj imię.';
        if (!$nazwisko) $bledy[] = 'Podaj nazwisko.';
        if (!$uzyt->walidujEmail($email)) $bledy[] = 'Podaj prawidłowy e-mail.';
        if ($uzyt->znajdzPoEmail($email)) $bledy[] = 'Ten e-mail jest już zarejestrowany.';
        $bledy = array_merge($bledy, $uzyt->walidujHaslo($haslo));
        if ($haslo !== $haslo2) $bledy[] = 'Hasła nie są zgodne.';
        if (!isset($_POST['regulamin'])) $bledy[] = 'Zaakceptuj regulamin.';

        if (empty($bledy)) {
            $id   = $uzyt->zarejestruj($imie, $nazwisko, $email, $haslo);
            $user = $uzyt->znajdzPoId($id);
            Auth::login($user);
            Auth::flash('success', 'Konto zostało utworzone. Witamy w sklepie!');
            header('Location: konto.php');
            exit;
        }
    }
}

$liczbawKoszyku = (new KoszykModel())->liczbaPozycji();
$tytul = 'Rejestracja — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>
<div class="container">
    <div class="auth-box auth-box--wide">
        <h1>Utwórz konto</h1>
        <?php if (!empty($bledy)): ?>
        <div class="alert alert--error"><ul><?php foreach ($bledy as $b): ?><li><?= htmlspecialchars($b) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="rejestracja.php">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <div class="form-grid">
                <div class="form-row">
                    <label>Imię *</label>
                    <input type="text" name="imie" value="<?= htmlspecialchars($_POST['imie'] ?? '') ?>" required>
                </div>
                <div class="form-row">
                    <label>Nazwisko *</label>
                    <input type="text" name="nazwisko" value="<?= htmlspecialchars($_POST['nazwisko'] ?? '') ?>" required>
                </div>
                <div class="form-row form-row--full">
                    <label>E-mail *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-row">
                    <label>Hasło *</label>
                    <input type="password" name="haslo" required>
                    <small>Min. 8 znaków, wielka litera i cyfra</small>
                </div>
                <div class="form-row">
                    <label>Powtórz hasło *</label>
                    <input type="password" name="haslo2" required>
                </div>
            </div>
            <label class="checkbox-label">
                <input type="checkbox" name="regulamin" required>
                Akceptuję <a href="#">regulamin</a> i <a href="#">politykę prywatności</a>
            </label>
            <button type="submit" class="btn btn--primary btn--full">Zarejestruj się</button>
        </form>
        <p class="auth-switch">Masz już konto? <a href="logowanie.php">Zaloguj się</a></p>
    </div>
</div>
<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
