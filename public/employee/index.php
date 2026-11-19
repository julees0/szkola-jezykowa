<?php
require_once __DIR__ . '/../../includes/client_init.php';

$prefiks = '../';
$konto = wymagaj_roli(['employee'], $prefiks);

$id_pracownika = pobierz_id_pracownika($pdo, $konto['id']);
if ($id_pracownika === null) {
    brak_dostepu('Twoje konto pracownika jest nieaktywne albo nie ma profilu pracownika.', $prefiks);
}

$dzisiaj = date('Y-m-d');
$na_strone = 15;
$strona = numer_strony();

$zapytanie = "SELECT r.id, r.reservation_date, r.start_time, r.end_time, r.status,
                     s.name AS usluga,
                     cu.name AS klient_imie, cu.surname AS klient_nazwisko, cu.phone AS klient_telefon
              FROM reservations r
              JOIN services s ON s.id = r.service_id
              JOIN users cu ON cu.id = r.user_id
              WHERE r.employee_id = ? AND ";

$stmt = $pdo->prepare($zapytanie . "r.reservation_date = ? ORDER BY r.start_time");
$stmt->execute([$id_pracownika, $dzisiaj]);
$dzis = $stmt->fetchAll();

$stmt = $pdo->prepare($zapytanie . "r.reservation_date > ? ORDER BY r.reservation_date, r.start_time");
$stmt->execute([$id_pracownika, $dzisiaj]);
$przyszle = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE employee_id = ? AND reservation_date < ?");
$stmt->execute([$id_pracownika, $dzisiaj]);
$liczba_historii = (int) $stmt->fetchColumn();

$przesuniecie = ($strona - 1) * $na_strone;
$stmt = $pdo->prepare(
    $zapytanie . "r.reservation_date < ? ORDER BY r.reservation_date DESC, r.start_time DESC
                  LIMIT " . (int) $na_strone . " OFFSET " . (int) $przesuniecie
);
$stmt->execute([$id_pracownika, $dzisiaj]);
$historia = $stmt->fetchAll();

function tabela_wizyt(array $wizyty, $z_data) {
    ?>
    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <?php if ($z_data): ?><th>Data</th><?php endif; ?>
                    <th>Godzina</th><th>Klient</th><th>Telefon</th><th>Usługa</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($wizyty as $w): ?>
                <tr>
                    <?php if ($z_data): ?><td><?= h($w['reservation_date']) ?></td><?php endif; ?>
                    <td><?= h(godzina($w['start_time'])) ?>-<?= h(godzina($w['end_time'])) ?></td>
                    <td><?= h($w['klient_imie']) ?> <?= h($w['klient_nazwisko']) ?></td>
                    <td><?= h($w['klient_telefon']) ?></td>
                    <td><?= h($w['usluga']) ?></td>
                    <td><span class="<?= klasa_statusu($w['status']) ?>"><?= h($w['status']) ?></span></td>
                    <td class="akcje"><a class="btn btn-maly" href="reservation.php?id=<?= (int) $w['id'] ?>">Szczegóły</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

$tytul = 'Panel pracownika';
$aktywna = 'pracownik';
require __DIR__ . '/../../includes/client_header.php';
?>

<h2>Wizyty na dziś (<?= h($dzisiaj) ?>)</h2>
<?php if (!$dzis): ?>
    <p>Na dziś nie masz zaplanowanych wizyt.</p>
<?php else: ?>
    <?php tabela_wizyt($dzis, false); ?>
<?php endif; ?>

<h2>Przyszłe wizyty</h2>
<?php if (!$przyszle): ?>
    <p>Brak przyszłych wizyt.</p>
<?php else: ?>
    <?php tabela_wizyt($przyszle, true); ?>
<?php endif; ?>

<h2>Historia</h2>
<?php if (!$historia): ?>
    <p>Brak wcześniejszych wizyt.</p>
<?php else: ?>
    <?php tabela_wizyt($historia, true); ?>
    <?= paginacja($liczba_historii, $na_strone, $strona) ?>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/client_footer.php'; ?>
