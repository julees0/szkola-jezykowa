<?php
require_once __DIR__ . '/../../includes/client_init.php';

$prefiks = '../';
$konto = wymagaj_roli(['employee'], $prefiks);

$id_pracownika = pobierz_id_pracownika($pdo, $konto['id']);
if ($id_pracownika === null) {
    brak_dostepu('Twoje konto pracownika jest nieaktywne albo nie ma profilu pracownika.', $prefiks);
}

$id = parametr_int($_GET, 'id') ?: parametr_int($_POST, 'id');

function pobierz_wizyte($pdo, $id, $id_pracownika) {
    $stmt = $pdo->prepare(
        "SELECT r.id, r.reservation_date, r.start_time, r.end_time, r.status, r.comment, r.created_at,
                s.name AS usluga, s.duration, s.price,
                cu.name AS klient_imie, cu.surname AS klient_nazwisko,
                cu.email AS klient_email, cu.phone AS klient_telefon
         FROM reservations r
         JOIN services s ON s.id = r.service_id
         JOIN users cu ON cu.id = r.user_id
         WHERE r.id = ? AND r.employee_id = ?"
    );
    $stmt->execute([$id, $id_pracownika]);
    return $stmt->fetch();
}

$r = pobierz_wizyte($pdo, $id, $id_pracownika);
if (!$r) {
    nie_znaleziono('Nie znaleziono takiej rezerwacji.', $prefiks);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sprawdz_csrf();
    $nowy = parametr($_POST, 'status');

    if (zmien_status_pracownika($pdo, $r['id'], $id_pracownika, $r['status'], $nowy)) {
        ustaw_komunikat('Status rezerwacji został zmieniony na: ' . $nowy . '.');
    } else {
        ustaw_komunikat('Nie można zmienić statusu na wybrany.', 'blad');
    }
    wroc('reservation.php', 'id=' . $r['id']);
}

$dozwolone = PRZEJSCIA_STATUSOW[$r['status']] ?? [];

$tytul = 'Rezerwacja nr ' . $r['id'];
$aktywna = 'pracownik';
require __DIR__ . '/../../includes/client_header.php';
?>

<div class="karta">
    <dl>
        <dt>Usługa</dt><dd><?= h($r['usluga']) ?> (<?= (int) $r['duration'] ?> min, <?= h(cena($r['price'])) ?>)</dd>
        <dt>Data</dt><dd><?= h(DNI_TYGODNIA[dzien_tygodnia($r['reservation_date'])]) ?>, <?= h($r['reservation_date']) ?></dd>
        <dt>Godzina</dt><dd><?= h(godzina($r['start_time'])) ?>-<?= h(godzina($r['end_time'])) ?></dd>
        <dt>Klient</dt><dd><?= h($r['klient_imie']) ?> <?= h($r['klient_nazwisko']) ?></dd>
        <dt>E-mail klienta</dt><dd><?= h($r['klient_email']) ?></dd>
        <dt>Telefon klienta</dt><dd><?= h($r['klient_telefon']) ?></dd>
        <dt>Komentarz</dt><dd><?= h($r['comment'] ?: '-') ?></dd>
        <dt>Utworzona</dt><dd><?= h($r['created_at']) ?></dd>
        <dt>Status</dt><dd><span class="<?= klasa_statusu($r['status']) ?>"><?= h($r['status']) ?></span></dd>
    </dl>
</div>

<h2>Zmiana statusu</h2>
<?php if (!$dozwolone): ?>
    <p>Ta rezerwacja ma status końcowy (<?= h($r['status']) ?>) - nie można go już zmienić.</p>
    <a class="btn btn-szary" href="index.php">Wróć do listy</a>
<?php else: ?>
    <form method="POST" action="reservation.php?id=<?= (int) $r['id'] ?>" class="filtry">
        <?= csrf_pole() ?>
        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
        <label>Nowy status
            <select name="status">
                <?php foreach ($dozwolone as $s): ?>
                    <option value="<?= h($s) ?>"><?= h($s) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn">Zapisz status</button>
        <a class="btn btn-szary" href="index.php">Wróć do listy</a>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/client_footer.php'; ?>
