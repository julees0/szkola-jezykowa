<?php
require_once __DIR__ . '/../includes/client_init.php';

$usluga = pobierz_usluge($pdo, parametr_int($_GET, 'id'));
if (!$usluga) {
    nie_znaleziono('Nie znaleziono takiej usługi albo nie jest już dostępna.');
}

$pracownicy = pobierz_pracownikow_uslugi($pdo, $usluga['id']);

$tytul = $usluga['name'];
$aktywna = 'oferta';
require __DIR__ . '/../includes/client_header.php';
?>

<div class="karta">
    <dl>
        <dt>Kategoria</dt><dd><?= h($usluga['kategoria']) ?></dd>
        <dt>Opis</dt><dd><?= h($usluga['description'] ?: 'Brak opisu.') ?></dd>
        <dt>Czas trwania</dt><dd><?= (int) $usluga['duration'] ?> min</dd>
        <dt>Cena</dt><dd><?= h(cena($usluga['price'])) ?></dd>
        <dt>Dostępność</dt>
        <dd>
            <?php if ($pracownicy): ?>
                Dostępni lektorzy: <?= count($pracownicy) ?>
            <?php else: ?>
                Chwilowo brak dostępnych lektorów.
            <?php endif; ?>
        </dd>
    </dl>
</div>

<?php if ($pracownicy): ?>
    <h2>Lektorzy prowadzący te zajęcia</h2>
    <div class="siatka">
        <?php foreach ($pracownicy as $p): ?>
            <?php $grafik = godziny_tygodniowe($pdo, $p['id']); ?>
            <div class="oferta-karta">
                <h3><?= h($p['name']) ?> <?= h($p['surname']) ?></h3>
                <p class="opis"><?= h($p['description'] ?: '') ?></p>
                <div>
                    <strong>Godziny pracy:</strong>
                    <?php if ($grafik): ?>
                        <ul class="grafik">
                            <?php foreach ($grafik as $dzien => $przedzialy): ?>
                                <li><?= h(DNI_TYGODNIA[$dzien] ?? $dzien) ?>: <?= h(implode(', ', $przedzialy)) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span class="podpowiedz">brak zdefiniowanych godzin</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="przyciski" style="margin-top: 16px;">
        <a class="btn" href="reservation.php?usluga=<?= (int) $usluga['id'] ?>">Zarezerwuj termin</a>
        <a class="btn btn-szary" href="offer.php">Wróć do oferty</a>
    </div>
<?php else: ?>
    <p><a class="btn btn-szary" href="offer.php">Wróć do oferty</a></p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/client_footer.php'; ?>
