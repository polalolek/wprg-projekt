<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

Auth::start();

if (Auth::isLoggedIn()) {
    header('Location: konto.php');
    exit;
}

$bledy    = [];
$redirect = $_GET['redirect'] ?? 'konto.php';
// Zezwalaj tylko na przekierowania wewnętrzne — blokuje open redirect na zewnętrzne domeny
if (!preg_match('/^[a-zA-Z0-9\/_\-\.?=&%]+$/', $redirect) || str_contains($redirect, '://') || str_starts_with($redirect, '//')) {
    $redirect = 'konto.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bledy[] = 'Błąd bezpieczeństwa.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $haslo = $_POST['haslo'] ?? '';
        $uzyt  = new UzytkownikModel();
        $user  = $uzyt->znajdzPoEmail($email);

        if ($user && $user['aktywny'] && $uzyt->weryfikujHaslo($haslo, $user['haslo'])) {
            Auth::login($user);
            (new KoszykModel())->przeniesDoUzytkownika($user['id']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $bledy[] = 'Nieprawidłowy e-mail lub hasło.';
        }
    }
}

$liczbawKoszyku = (new KoszykModel())->liczbaPozycji();
$tytul = 'Logowanie — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>
<div class="container">
    <div class="auth-box">
        <h1>Logowanie</h1>
        <?php if (!empty($bledy)): ?>
            <div class="alert alert--error"><?= htmlspecialchars($bledy[0]) ?></div>
        <?php endif; ?>
        <form method="post" action="logowanie.php?redirect=<?= urlencode($redirect) ?>">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <div class="form-row">
                <label>E-mail</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-row">
                <label>Hasło</label>
                <input type="password" name="haslo" required>
            </div>
            <button type="submit" class="btn btn--primary btn--full">Zaloguj się</button>
        </form>
        <p class="auth-switch">Nie masz konta? <a href="rejestracja.php">Zarejestruj się</a></p>
        <div class="auth-hint"><small>Konto testowe: admin@sklep.pl / Admin123!</small></div>
    </div>
</div>
<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
