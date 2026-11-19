<?php
require_once __DIR__ . '/../includes/client_init.php';

$konto = wymagaj_roli(['client']);
$id = parametr_int($_GET, 'id');

$stmt = $pdo->prepare(
    "SELECT r.id, r.reservation_date, r.start_time, r.end_time, r.status, r.comment, r.created_at,
            s.name AS usluga, s.duration, s.price, c.name AS kategoria,
            eu.name AS prac_imie, eu.surname AS prac_nazwisko
     FROM reservations r
     JOIN services s ON s.id = r.service_id
     JOIN service_categories c ON c.id = s.category_id
     JOIN employees e ON e.id = r.employee_id
     JOIN users eu ON eu.id = e.user_id
     WHERE r.id = ? AND r.user_id = ?"
);
$stmt->execute([$id, $konto['id']]);
$r = $stmt->fetch();

if (!$r) {
    nie_znaleziono('Nie znaleziono takiej rezerwacji.');
}

$tytul = 'Rezerwacja nr ' . $r['id'];
$aktywna = 'moje_rezerwacje';
require __DIR__ . '/../includes/client_header.php';
?>

<div class="karta">
    <dl>
        <dt>Usługa</dt><dd><?= h($r['usluga']) ?> (<?= h($r['kategoria']) ?>)</dd>
        <dt>Pracownik</dt><dd><?= h($r['prac_imie']) ?> <?= h($r['prac_nazwisko']) ?></dd>
        <dt>Data</dt><dd><?= h(DNI_TYGODNIA[dzien_tygodnia($r['reservation_date'])]) ?>, <?= h($r['reservation_date']) ?></dd>
        <dt>Godzina</dt><dd><?= h(godzina($r['start_time'])) ?>-<?= h(godzina($r['end_time'])) ?> (<?= (int) $r['duration'] ?> min)</dd>
        <dt>Cena</dt><dd><?= h(cena($r['price'])) ?></dd>
        <dt>Status</dt><dd><span class="<?= klasa_statusu($r['status']) ?>"><?= h($r['status']) ?></span></dd>
        <dt>Komentarz</dt><dd><?= h($r['comment'] ?: '-') ?></dd>
        <dt>Utworzona</dt><dd><?= h($r['created_at']) ?></dd>
    </dl>
</div>

<div class="przyciski" style="margin-top: 14px;">
    <?php if (czy_mozna_anulowac($r)): ?>
        <form method="POST" action="my_reservations.php" data-potwierdz="Czy na pewno anulować tę rezerwację?">
            <?= csrf_pole() ?>
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="btn btn-czerwony">Anuluj rezerwację</button>
        </form>
    <?php endif; ?>
    <a class="btn btn-szary" href="my_reservations.php">Wróć do listy</a>
</div>

<?php require __DIR__ . '/../includes/client_footer.php'; ?>
