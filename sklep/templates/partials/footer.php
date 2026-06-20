</main>

<footer class="site-footer">
    <div class="container site-footer__inner">
        <div class="footer-col">
            <div class="site-logo"><span class="site-logo__icon">●</span><?= SITE_NAME ?></div>
            <p>Twój zaufany sklep internetowy z produktami najwyższej jakości.</p>
        </div>
        <div class="footer-col">
            <h4>Sklep</h4>
            <ul>
                <li><a href="sklep.php">Wszystkie produkty</a></li>
                <li><a href="sklep.php?kategoria=elektronika">Elektronika</a></li>
                <li><a href="sklep.php?kategoria=odziez">Odzież</a></li>
                <li><a href="sklep.php?kategoria=sport">Sport</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Konto</h4>
            <ul>
                <?php if (Auth::isLoggedIn()): ?>
                <li><a href="/konto.php">Moje konto</a></li>
                <li><a href="/konto.php?zakladka=zamowienia">Zamówienia</a></li>
                <li><a href="/konto.php?wyloguj=1">Wyloguj się</a></li>
                <?php else: ?>
                <li><a href="/logowanie.php">Logowanie</a></li>
                <li><a href="/rejestracja.php">Rejestracja</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="footer-col">
            <ul>
                <li><a href="/regulamin.php">Regulamin</a></li>
                <li><a href="/polityka-prywatnosci.php">Polityka prywatności</a></li>
                <li><a href="/dostawa-zwroty.php">Dostawa i zwroty</a></li>
                <li><a href="/kontakt.php">Kontakt</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p>© <?= date('Y') ?> <?= SITE_NAME ?>. Wszelkie prawa zastrzeżone.</p>
            <p class="footer-pay">Płatności:&nbsp;<?= Icons::creditCard(15) ?> Karta &nbsp;<?= Icons::smartphone(15) ?> BLIK &nbsp;<?= Icons::bank(15) ?> Przelew</p>
        </div>
    </div>
</footer>

</body>
</html>
