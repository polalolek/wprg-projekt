<nav class="admin-sidebar">
    <div class="admin-sidebar__title"><?= Icons::settings(16) ?> Admin</div>
    <ul>
        <li><a href="/admin/index.php" <?= basename($_SERVER['PHP_SELF'])==='index.php'?'class="active"':'' ?>><?= Icons::dashboard(16) ?> Dashboard</a></li>
        <li><a href="/admin/produkty.php" <?= basename($_SERVER['PHP_SELF'])==='produkty.php'?'class="active"':'' ?>><?= Icons::package(16) ?> Produkty</a></li>
        <li><a href="/admin/kategorie.php" <?= basename($_SERVER['PHP_SELF'])==='kategorie.php'?'class="active"':'' ?>><?= Icons::folder(16) ?> Kategorie</a></li>
        <li><a href="/admin/zamowienia.php" <?= basename($_SERVER['PHP_SELF'])==='zamowienia.php'?'class="active"':'' ?>><?= Icons::cart(16) ?> Zamówienia</a></li>
        <li><a href="/admin/uzytkownicy.php" <?= basename($_SERVER['PHP_SELF'])==='uzytkownicy.php'?'class="active"':'' ?>><?= Icons::users(16) ?> Użytkownicy</a></li>
        <li><a href="/admin/promocje.php" <?= basename($_SERVER['PHP_SELF'])==='promocje.php'?'class="active"':'' ?>><?= Icons::tag(16) ?> Promocje</a></li>
        <li><a href="/admin/recenzje.php" <?= basename($_SERVER['PHP_SELF'])==='recenzje.php'?'class="active"':'' ?>><?= Icons::star(16) ?> Recenzje</a></li>
        <hr>
        <li><a href="/index.php">← Wróć do sklepu</a></li>
    </ul>
</nav>
