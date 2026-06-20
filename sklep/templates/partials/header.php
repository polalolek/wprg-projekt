<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tytul ?? SITE_NAME) ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
</head>
<body>

<header class="site-header">
    <div class="container site-header__inner">
        <a href="/index.php" class="site-logo">
            <span class="site-logo__icon">●</span><?= SITE_NAME ?>
        </a>

        <!-- Wyszukiwarka -->
        <form action="/sklep.php" method="get" class="header-search">
            <input type="search" name="szukaj" placeholder="Szukaj produktów..." value="<?= htmlspecialchars($_GET['szukaj'] ?? '') ?>">
            <button type="submit"><?= Icons::search(18) ?></button>
        </form>

        <nav class="header-nav">
            <a href="/sklep.php" class="nav-link">Sklep</a>

            <?php if (Auth::isLoggedIn()): ?>
                <?php if (Auth::isAdmin()): ?>
                <a href="/admin/index.php" class="nav-link nav-link--admin">Admin</a>
                <?php endif; ?>
                <a href="/konto.php" class="nav-link">
                    <?= Icons::user(16) ?> <?= htmlspecialchars(Auth::userName()) ?>
                </a>
            <?php else: ?>
                <a href="/logowanie.php" class="nav-link">Zaloguj</a>
                <a href="/rejestracja.php" class="btn btn--sm btn--primary">Rejestracja</a>
            <?php endif; ?>

            <a href="/koszyk.php" class="cart-btn">
                <?= Icons::cart(18) ?> <span class="cart-count"><?= $liczbawKoszyku ?? 0 ?></span>
            </a>
        </nav>
    </div>
</header>

<main>
