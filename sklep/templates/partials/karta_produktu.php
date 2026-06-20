<article class="product-card">
    <?php if (!empty($p['cena_promocyjna'])): ?>
    <span class="product-card__badge">-<?= round((1 - $p['cena_promocyjna'] / $p['cena']) * 100) ?>%</span>
    <?php endif; ?>
    <?php if (!empty($p['wyrozniony'])): ?>
    <span class="product-card__featured">★ Polecany</span>
    <?php endif; ?>

    <a href="produkt.php?slug=<?= htmlspecialchars($p['slug']) ?>" class="product-card__img-link">
        <?php if (!empty($p['zdjecie_glowne'])): ?>
            <img src="images/produkty/<?= htmlspecialchars($p['zdjecie_glowne']) ?>"
                 alt="<?= htmlspecialchars($p['nazwa']) ?>" loading="lazy">
        <?php else: ?>
            <div class="product-card__img-placeholder"><?= Icons::package(36) ?></div>
        <?php endif; ?>
    </a>

    <div class="product-card__body">
        <?php if (!empty($p['kategoria_nazwa'])): ?>
        <span class="product-card__cat"><?= htmlspecialchars($p['kategoria_nazwa']) ?></span>
        <?php endif; ?>
        <h3 class="product-card__name">
            <a href="produkt.php?slug=<?= htmlspecialchars($p['slug']) ?>"><?= htmlspecialchars($p['nazwa']) ?></a>
        </h3>
        <?php if (!empty($p['opis_krotki'])): ?>
        <p class="product-card__desc"><?= htmlspecialchars(mb_substr($p['opis_krotki'], 0, 80)) ?>…</p>
        <?php endif; ?>

        <div class="product-card__footer">
            <div class="product-card__price">
                <?php if (!empty($p['cena_promocyjna'])): ?>
                    <span class="price--old"><?= number_format($p['cena'], 2, ',', ' ') ?> zł</span>
                    <span class="price--main"><?= number_format($p['cena_promocyjna'], 2, ',', ' ') ?> zł</span>
                <?php else: ?>
                    <span class="price--main"><?= number_format($p['cena'], 2, ',', ' ') ?> zł</span>
                <?php endif; ?>
            </div>

            <?php if (($p['stan_magazynowy'] ?? 0) > 0): ?>
            <form method="post" action="produkt.php?slug=<?= htmlspecialchars($p['slug']) ?>">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                <input type="hidden" name="dodaj_do_koszyka" value="1">
                <input type="hidden" name="ilosc" value="1">
                <button type="submit" class="btn btn--sm btn--primary">Do koszyka</button>
            </form>
            <?php else: ?>
            <span class="stock-out">Brak</span>
            <?php endif; ?>
        </div>
    </div>
</article>
