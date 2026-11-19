<?php
require_once __DIR__ . '/../includes/client_init.php';

$konto = wymagaj_roli(['client']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sprawdz_csrf();
    $id = parametr_int($_POST, 'id');

    if ($id > 0 && anuluj_rezerwacje_klienta($pdo, $id, $konto['id'])) {
        ustaw_komunikat('Rezerwacja nr ' . $id . ' została anulowana.');
    } else {
        ustaw_komunikat('Nie można anulować tej rezerwacji. Mogą to być tylko Twoje przyszłe, aktywne rezerwacje.', 'blad');
    }
    wroc('my_reservations.php');
}

$stmt = $pdo->prepare(
    "SELECT r.id, r.reservation_date, r.start_time, r.end_time, r.status,
            s.name AS usluga, s.price,
            eu.name AS prac_imie, eu.surname AS prac_nazwisko
     FROM reservations r
     JOIN services s ON s.id = r.service_id
     JOIN employees e ON e.id = r.employee_id
     JOIN users eu ON eu.id = e.user_id
     WHERE r.user_id = ?
     ORDER BY r.reservation_date, r.start_time"
);
$stmt->execute([$konto['id']]);

$przyszle = [];
$zakonczone = [];
foreach ($stmt->fetchAll() as $r) {
    if (czy_przyszla($r)) {
        $przyszle[] = $r;
    } else {
        $zakonczone[] = $r;
    }
}
$zakonczone = array_reverse($zakonczone);

function tabela_rezerwacji(array $rezerwacje, $z_anulowaniem) {
    ?>
    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>Usługa</th><th>Pracownik</th><th>Data</th><th>Godzina</th>
                    <th>Cena</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rezerwacje as $r): ?>
                <tr>
                    <td><?= h($r['usluga']) ?></td>
                    <td><?= h($r['prac_imie']) ?> <?= h($r['prac_nazwisko']) ?></td>
                    <td><?= h($r['reservation_date']) ?></td>
                    <td><?= h(godzina($r['start_time'])) ?>-<?= h(godzina($r['end_time'])) ?></td>
                    <td><?= h(cena($r['price'])) ?></td>
                    <td><span class="<?= klasa_statusu($r['status']) ?>"><?= h($r['status']) ?></span></td>
                    <td class="akcje">
                        <a class="btn btn-maly btn-szary" href="reservation_details.php?id=<?= (int) $r['id'] ?>">Szczegóły</a>
                        <?php if ($z_anulowaniem && czy_mozna_anulowac($r)): ?>
                            <form method="POST" action="my_reservations.php"
                                  data-potwierdz="Czy na pewno anulować tę rezerwację?">
                                <?= csrf_pole() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button type="submit" class="btn btn-maly btn-czerwony">Anuluj</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

$tytul = 'Moje rezerwacje';
$aktywna = 'moje_rezerwacje';
require __DIR__ . '/../includes/client_header.php';
?>

<p><a class="btn" href="reservation.php">Zarezerwuj nowy termin</a></p>

<h2>Przyszłe rezerwacje</h2>
<?php if (!$przyszle): ?>
    <p>Nie masz przyszłych rezerwacji.</p>
<?php else: ?>
    <?php tabela_rezerwacji($przyszle, true); ?>
<?php endif; ?>

<h2>Zakończone i anulowane</h2>
<?php if (!$zakonczone): ?>
    <p>Brak zakończonych lub anulowanych rezerwacji.</p>
<?php else: ?>
    <?php tabela_rezerwacji($zakonczone, false); ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/client_footer.php'; ?>
