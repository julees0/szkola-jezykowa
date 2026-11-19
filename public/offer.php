<?php
require_once __DIR__ . '/../includes/client_init.php';

$kategoria = parametr_int($_GET, 'kategoria');
$szukaj = parametr($_GET, 'q');

$kategorie = $pdo->query("SELECT id, name FROM service_categories ORDER BY name")->fetchAll();
$uslugi = pobierz_oferte($pdo, $kategoria, $szukaj);

$tytul = 'Oferta zajęć';
$aktywna = 'oferta';
require __DIR__ . '/../includes/client_header.php';
?>

<form method="GET" action="offer.php" class="filtry">
    <label>Kategoria
        <select name="kategoria">
            <option value="0">Wszystkie</option>
            <?php foreach ($kategorie as $k): ?>
                <option value="<?= (int) $k['id'] ?>"<?= $kategoria === (int) $k['id'] ? ' selected' : '' ?>><?= h($k['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Szukaj
        <input type="text" name="q" value="<?= h($szukaj) ?>" placeholder="np. angielski" maxlength="100">
    </label>
    <button type="submit" class="btn">Filtruj</button>
    <?php if ($kategoria > 0 || $szukaj !== ''): ?>
        <a href="offer.php" class="btn btn-szary">Wyczyść</a>
    <?php endif; ?>
</form>

<?php if (!$uslugi): ?>
    <p>Nie znaleziono zajęć spełniających podane kryteria.</p>
<?php else: ?>
    <?php $biezaca_kategoria = null; ?>
    <?php foreach ($uslugi as $u): ?>
        <?php if ($biezaca_kategoria !== $u['kategoria_id']): ?>
            <?php if ($biezaca_kategoria !== null): ?></div><?php endif; ?>
            <?php $biezaca_kategoria = $u['kategoria_id']; ?>
            <h2><?= h($u['kategoria']) ?></h2>
            <div class="siatka">
        <?php endif; ?>

        <div class="oferta-karta">
            <h3><?= h($u['name']) ?></h3>
            <p class="opis"><?= h($u['description'] ?: 'Brak opisu.') ?></p>
            <div class="parametry">
                <span>Czas: <strong><?= (int) $u['duration'] ?> min</strong></span>
                <span>Cena: <strong><?= h(cena($u['price'])) ?></strong></span>
            </div>
            <div>
                <?php if ((int) $u['liczba_pracownikow'] > 0): ?>
                    <span class="etykieta">Dostępni lektorzy: <?= (int) $u['liczba_pracownikow'] ?></span>
                <?php else: ?>
                    <span class="etykieta etykieta-szara">Chwilowo niedostępna</span>
                <?php endif; ?>
            </div>
            <div class="przyciski">
                <a class="btn btn-maly btn-szary" href="service.php?id=<?= (int) $u['id'] ?>">Szczegóły</a>
                <?php if ((int) $u['liczba_pracownikow'] > 0): ?>
                    <a class="btn btn-maly" href="reservation.php?usluga=<?= (int) $u['id'] ?>">Zarezerwuj</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/client_footer.php'; ?>
