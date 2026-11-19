<?php
require_once __DIR__ . '/../includes/client_init.php';

$stmt = $pdo->query(
    "SELECT c.id, c.name, c.description, COUNT(s.id) AS liczba_uslug
     FROM service_categories c
     LEFT JOIN services s ON s.category_id = c.id AND s.active = 1
     GROUP BY c.id, c.name, c.description
     ORDER BY c.name"
);
$kategorie = $stmt->fetchAll();

$rola = $_SESSION['role'] ?? null;
$tytul = 'Szkoła Językowa';
$aktywna = 'index';
require __DIR__ . '/../includes/client_header.php';
?>

<section class="hero">
    <p>Witaj w systemie rezerwacji zajęć naszej szkoły językowej. Wybierz kurs, lektora i dogodny termin,
       a lekcja zostanie zarezerwowana w kilku krokach.</p>
    <div class="przyciski">
        <a class="btn" href="offer.php">Zobacz ofertę</a>
        <?php if ($rola === 'client' || $rola === null): ?>
            <a class="btn" href="reservation.php">Zarezerwuj termin</a>
        <?php endif; ?>
        <?php if ($rola === null): ?>
            <a class="btn btn-szary" href="login.php">Zaloguj się</a>
            <a class="btn btn-szary" href="register.php">Utwórz konto</a>
        <?php endif; ?>
    </div>
</section>

<h2>Kategorie zajęć</h2>
<?php if (!$kategorie): ?>
    <p>Oferta jest w przygotowaniu.</p>
<?php else: ?>
    <div class="siatka">
        <?php foreach ($kategorie as $k): ?>
            <div class="oferta-karta">
                <h3><?= h($k['name']) ?></h3>
                <p class="opis"><?= h($k['description'] ?: 'Zajęcia z tego języka.') ?></p>
                <div class="parametry">
                    <span class="etykieta">Usług: <?= (int) $k['liczba_uslug'] ?></span>
                </div>
                <div><a class="btn btn-maly" href="offer.php?kategoria=<?= (int) $k['id'] ?>">Zobacz zajęcia</a></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/client_footer.php'; ?>
