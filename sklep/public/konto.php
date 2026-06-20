<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

Auth::start();
Auth::requireLogin();

$uzytModel       = new UzytkownikModel();
$zamowienieModel = new ZamowienieModel();
$koszykModel     = new KoszykModel();

$user     = $uzytModel->znajdzPoId(Auth::userId());
$zamow    = $zamowienieModel->znajdzUzytkownika(Auth::userId());
$bledy    = [];

// Wylogowanie
if (isset($_GET['wyloguj'])) {
    Auth::logout();
    header('Location: index.php');
    exit;
}

// Aktualizacja profilu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aktualizuj_profil'])) {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bledy[] = 'Błąd bezpieczeństwa.';
    } else {
        $uzytModel->aktualizujProfil(Auth::userId(), [
            'imie'     => trim($_POST['imie'] ?? ''),
            'nazwisko' => trim($_POST['nazwisko'] ?? ''),
            'telefon'  => trim($_POST['telefon'] ?? ''),
        ]);
        Auth::flash('success', 'Profil zaktualizowany.');
        $user = $uzytModel->znajdzPoId(Auth::userId());
        header('Location: konto.php');
        exit;
    }
}

// Zmiana hasła
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zmien_haslo'])) {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $bledy[] = 'Błąd bezpieczeństwa.';
    } else {
        $stare = $_POST['stare_haslo'] ?? '';
        $nowe  = $_POST['nowe_haslo'] ?? '';
        $nowe2 = $_POST['nowe_haslo2'] ?? '';

        if (!$uzytModel->weryfikujHaslo($stare, $user['haslo'])) {
            $bledy[] = 'Stare hasło jest nieprawidłowe.';
        } else {
            $bledy = array_merge($bledy, $uzytModel->walidujHaslo($nowe));
            if ($nowe !== $nowe2) $bledy[] = 'Hasła nie są zgodne.';

            if (empty($bledy)) {
                $uzytModel->zmienHaslo(Auth::userId(), $nowe);
                Auth::flash('success', 'Hasło zostało zmienione.');
                header('Location: konto.php');
                exit;
            }
        }
    }
}

$zakl             = $_GET['zakladka'] ?? 'profil';
$liczbawKoszyku   = $koszykModel->liczbaPozycji();
$flash            = Auth::getFlash('success');
$tytul = 'Moje konto — ' . SITE_NAME;
require_once __DIR__ . '/../templates/partials/header.php';
?>

<div class="container">
    <div class="account-layout">
        <nav class="account-nav">
            <div class="account-nav__user">
                <span class="account-avatar"><?= strtoupper($user['imie'][0]) ?></span>
                <div>
                    <strong><?= htmlspecialchars($user['imie'] . ' ' . $user['nazwisko']) ?></strong>
                    <small><?= htmlspecialchars($user['email']) ?></small>
                </div>
            </div>
            <ul>
                <li><a href="?zakladka=profil" class="<?= $zakl==='profil'?'active':'' ?>"><?= Icons::user(15) ?> Profil</a></li>
                <li><a href="?zakladka=zamowienia" class="<?= $zakl==='zamowienia'?'active':'' ?>"><?= Icons::package(15) ?> Zamówienia</a></li>
                <li><a href="?zakladka=haslo" class="<?= $zakl==='haslo'?'active':'' ?>"><?= Icons::key(15) ?> Hasło</a></li>
                <li><a href="?zakladka=lojalnosc" class="<?= $zakl==='lojalnosc'?'active':'' ?>"><?= Icons::star(15) ?> Program lojalnościowy</a></li>
                <?php if (Auth::isAdmin()): ?>
                <li><a href="admin/index.php"><?= Icons::settings(15) ?> Panel admina</a></li>
                <?php endif; ?>
                <li><a href="?wyloguj=1" class="text-danger"><?= Icons::logout(15) ?> Wyloguj się</a></li>
            </ul>
        </nav>

        <main class="account-main">
            <?php if ($flash): ?><div class="alert alert--success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
            <?php if (!empty($bledy)): ?><div class="alert alert--error"><ul><?php foreach ($bledy as $b): ?><li><?= htmlspecialchars($b) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

            <?php if ($zakl === 'profil'): ?>
            <h1>Mój profil</h1>
            <form method="post" action="konto.php?zakladka=profil">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <input type="hidden" name="aktualizuj_profil" value="1">
                <div class="form-grid">
                    <div class="form-row"><label>Imię</label><input type="text" name="imie" value="<?= htmlspecialchars($user['imie']) ?>" required></div>
                    <div class="form-row"><label>Nazwisko</label><input type="text" name="nazwisko" value="<?= htmlspecialchars($user['nazwisko']) ?>" required></div>
                    <div class="form-row form-row--full"><label>E-mail (nie można zmienić)</label><input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
                    <div class="form-row form-row--full"><label>Telefon</label><input type="tel" name="telefon" value="<?= htmlspecialchars($user['telefon'] ?? '') ?>"></div>
                </div>
                <button type="submit" class="btn btn--primary">Zapisz zmiany</button>
            </form>

            <?php elseif ($zakl === 'zamowienia'): ?>
            <h1>Moje zamówienia</h1>
            <?php if (empty($zamow)): ?>
                <div class="empty-state"><p>Nie masz jeszcze żadnych zamówień.</p><a href="sklep.php" class="btn btn--primary">Przejdź do sklepu</a></div>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Nr zamówienia</th><th>Data</th><th>Wartość</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($zamow as $z): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($z['numer']) ?></strong></td>
                    <td><?= date('d.m.Y', strtotime($z['data_zamowienia'])) ?></td>
                    <td><?= number_format($z['wartosc_laczna'], 2, ',', ' ') ?> zł</td>
                    <td><span class="status-badge status-<?= $z['status'] ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $z['status']))) ?></span></td>
                    <td><a href="potwierdzenie.php?id=<?= $z['id'] ?>" class="btn btn--sm btn--outline">Szczegóły</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php elseif ($zakl === 'haslo'): ?>
            <h1>Zmiana hasła</h1>
            <form method="post" action="konto.php?zakladka=haslo" class="form-narrow">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <input type="hidden" name="zmien_haslo" value="1">
                <div class="form-row"><label>Aktualne hasło</label><input type="password" name="stare_haslo" required></div>
                <div class="form-row"><label>Nowe hasło</label><input type="password" name="nowe_haslo" required></div>
                <div class="form-row"><label>Powtórz nowe hasło</label><input type="password" name="nowe_haslo2" required></div>
                <button type="submit" class="btn btn--primary">Zmień hasło</button>
            </form>

            <?php elseif ($zakl === 'lojalnosc'): ?>
            <h1>Program lojalnościowy</h1>
            <div class="loyalty-box">
                <div class="loyalty-score">
                    <span class="loyalty-pts"><?= $user['punkty_lojalnosciowe'] ?></span>
                    <span>punktów</span>
                </div>
                <p>Za każde wydane 10 zł otrzymujesz 1 punkt. Punkty możesz wymieniać na zniżki.</p>
                <div class="loyalty-tiers">
                    <div class="tier <?= $user['punkty_lojalnosciowe'] >= 0 ? 'tier--active' : '' ?>"><strong>Brąz</strong><small>0–99 pkt</small></div>
                    <div class="tier <?= $user['punkty_lojalnosciowe'] >= 100 ? 'tier--active' : '' ?>"><strong>Srebro</strong><small>100–499 pkt</small></div>
                    <div class="tier <?= $user['punkty_lojalnosciowe'] >= 500 ? 'tier--active' : '' ?>"><strong>Złoto</strong><small>500+ pkt</small></div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/partials/footer.php'; ?>
