<?php
require_once __DIR__ . '/../includes/client_init.php';

$konto = wymagaj_roli(['client']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sprawdz_csrf();

    $id_uslugi = parametr_int($_POST, 'usluga');
    $id_pracownika = parametr_int($_POST, 'pracownik');
    $data = parametr($_POST, 'data');
    $godzina = parametr($_POST, 'godzina');

    $wynik = utworz_rezerwacje(
        $pdo, $konto['id'], $id_uslugi, $id_pracownika, $data, $godzina, parametr($_POST, 'komentarz')
    );

    if (isset($wynik['id'])) {
        ustaw_komunikat('Rezerwacja nr ' . $wynik['id'] . ' została zapisana i oczekuje na potwierdzenie.');
        wroc('my_reservations.php');
    }

    ustaw_komunikat($wynik['blad'], 'blad');
    wroc('reservation.php', http_build_query([
        'usluga' => $id_uslugi, 'pracownik' => $id_pracownika, 'data' => $data,
    ]));
}

$id_uslugi = parametr_int($_GET, 'usluga');
$id_pracownika = parametr_int($_GET, 'pracownik');
$data = parametr($_GET, 'data');
$godzina = parametr($_GET, 'godzina');

$bledy = [];
$usluga = null;
$pracownik = null;
$data_ok = false;
$blad_terminu = null;

if ($id_uslugi > 0) {
    $usluga = pobierz_usluge($pdo, $id_uslugi);
    if (!$usluga) {
        $bledy[] = 'Wybrana usługa nie jest dostępna. Wybierz inną.';
    }
}
if ($usluga && $id_pracownika > 0) {
    $pracownik = pobierz_pracownika_uslugi($pdo, $id_pracownika, $id_uslugi);
    if (!$pracownik) {
        $bledy[] = 'Wybrany pracownik nie wykonuje tej usługi. Wybierz pracownika z listy.';
    }
}
if ($pracownik && $data !== '') {
    $blad_daty = sprawdz_zakres_daty($data);
    if ($blad_daty === null) {
        $data_ok = true;
    } else {
        $bledy[] = $blad_daty;
    }
}
if ($data_ok && $godzina !== '') {
    $blad_terminu = sprawdz_termin($pdo, $pracownik['id'], $usluga, $data, $godzina);
    if ($blad_terminu !== null) {
        $bledy[] = $blad_terminu;
    }
}

if (!$usluga) {
    $krok = 1;
} elseif (!$pracownik) {
    $krok = 2;
} elseif (!$data_ok) {
    $krok = 3;
} elseif ($godzina === '' || $blad_terminu !== null) {
    $krok = 4;
} else {
    $krok = 5;
}

function adres_rezerwacji(array $parametry = []) {
    return 'reservation.php' . ($parametry ? '?' . http_build_query($parametry) : '');
}

$nazwy_krokow = ['Usługa', 'Pracownik', 'Data', 'Godzina', 'Potwierdzenie'];

$tytul = 'Rezerwacja terminu';
$aktywna = 'rezerwacja';
require __DIR__ . '/../includes/client_header.php';
?>

<ol class="kroki">
    <?php foreach ($nazwy_krokow as $i => $nazwa): ?>
        <?php $numer = $i + 1; ?>
        <li class="<?= $numer < $krok ? 'zrobiony' : ($numer === $krok ? 'aktywny' : '') ?>"><?= h($nazwa) ?></li>
    <?php endforeach; ?>
</ol>

<?php foreach ($bledy as $blad): ?>
    <div class="alert alert-blad"><?= h($blad) ?></div>
<?php endforeach; ?>

<?php if ($krok > 1): ?>
    <div class="karta wybor">
        <dl>
            <dt>Usługa</dt>
            <dd><?= h($usluga['name']) ?> (<?= (int) $usluga['duration'] ?> min, <?= h(cena($usluga['price'])) ?>)
                <a href="<?= h(adres_rezerwacji()) ?>">zmień</a></dd>
            <?php if ($krok > 2): ?>
                <dt>Pracownik</dt>
                <dd><?= h($pracownik['name']) ?> <?= h($pracownik['surname']) ?>
                    <a href="<?= h(adres_rezerwacji(['usluga' => $usluga['id']])) ?>">zmień</a></dd>
            <?php endif; ?>
            <?php if ($krok > 3): ?>
                <dt>Data</dt>
                <dd><?= h(DNI_TYGODNIA[dzien_tygodnia($data)]) ?>, <?= h($data) ?>
                    <a href="<?= h(adres_rezerwacji(['usluga' => $usluga['id'], 'pracownik' => $pracownik['id']])) ?>">zmień</a></dd>
            <?php endif; ?>
            <?php if ($krok > 4): ?>
                <dt>Godzina</dt>
                <dd><?= h($godzina) ?>-<?= h(z_minut(na_minuty($godzina) + (int) $usluga['duration'])) ?>
                    <a href="<?= h(adres_rezerwacji(['usluga' => $usluga['id'], 'pracownik' => $pracownik['id'], 'data' => $data])) ?>">zmień</a></dd>
            <?php endif; ?>
        </dl>
    </div>
<?php endif; ?>

<?php if ($krok === 1): ?>
    <?php $oferta = pobierz_oferte($pdo, 0, '', true); ?>
    <h2>Wybierz usługę</h2>
    <?php if (!$oferta): ?>
        <p>Obecnie nie ma usług dostępnych do rezerwacji.</p>
    <?php else: ?>
        <div class="siatka">
            <?php foreach ($oferta as $u): ?>
                <div class="oferta-karta">
                    <span class="etykieta"><?= h($u['kategoria']) ?></span>
                    <h3><?= h($u['name']) ?></h3>
                    <p class="opis"><?= h($u['description'] ?: '') ?></p>
                    <div class="parametry">
                        <span><?= (int) $u['duration'] ?> min</span>
                        <span><strong><?= h(cena($u['price'])) ?></strong></span>
                    </div>
                    <div><a class="btn btn-maly" href="<?= h(adres_rezerwacji(['usluga' => $u['id']])) ?>">Wybierz</a></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php elseif ($krok === 2): ?>
    <?php $pracownicy = pobierz_pracownikow_uslugi($pdo, $usluga['id']); ?>
    <h2>Wybierz pracownika</h2>
    <?php if (!$pracownicy): ?>
        <p>Ta usługa nie ma obecnie dostępnych pracowników. <a href="<?= h(adres_rezerwacji()) ?>">Wybierz inną usługę</a>.</p>
    <?php else: ?>
        <div class="siatka">
            <?php foreach ($pracownicy as $p): ?>
                <div class="oferta-karta">
                    <h3><?= h($p['name']) ?> <?= h($p['surname']) ?></h3>
                    <p class="opis"><?= h($p['description'] ?: '') ?></p>
                    <div><a class="btn btn-maly" href="<?= h(adres_rezerwacji(['usluga' => $usluga['id'], 'pracownik' => $p['id']])) ?>">Wybierz</a></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php elseif ($krok === 3): ?>
    <?php $grafik = godziny_tygodniowe($pdo, $pracownik['id']); ?>
    <h2>Wybierz datę</h2>
    <form method="GET" action="reservation.php" class="formularz formularz-waski">
        <input type="hidden" name="usluga" value="<?= (int) $usluga['id'] ?>">
        <input type="hidden" name="pracownik" value="<?= (int) $pracownik['id'] ?>">
        <label>Data zajęć
            <input type="date" name="data" value="<?= h($data) ?>"
                   min="<?= h(date('Y-m-d')) ?>" max="<?= h(date('Y-m-d', strtotime('+' . MAX_DNI_NAPRZOD . ' days'))) ?>" required>
        </label>
        <div class="przyciski">
            <button type="submit" class="btn">Pokaż dostępne godziny</button>
        </div>
        <p class="podpowiedz">Pracownik przyjmuje:</p>
        <?php if ($grafik): ?>
            <ul class="grafik">
                <?php foreach ($grafik as $dzien => $przedzialy): ?>
                    <li><?= h(DNI_TYGODNIA[$dzien] ?? $dzien) ?>: <?= h(implode(', ', $przedzialy)) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="podpowiedz">Ten pracownik nie ma jeszcze ustawionych godzin pracy.</p>
        <?php endif; ?>
    </form>

<?php elseif ($krok === 4): ?>
    <?php
    $sloty = sloty_dnia($pdo, $pracownik['id'], $data, (int) $usluga['duration']);
    $liczba_wolnych = count(array_filter($sloty, fn($powod) => $powod === null));
    ?>
    <h2>Wybierz godzinę</h2>
    <?php if (!$sloty): ?>
        <p>Pracownik nie przyjmuje w tym dniu (lub usługa nie mieści się w jego godzinach pracy).</p>
    <?php elseif ($liczba_wolnych === 0): ?>
        <p>Brak wolnych terminów w tym dniu.</p>
    <?php else: ?>
        <p class="podpowiedz">Wolnych terminów: <?= $liczba_wolnych ?>. Przekreślone godziny są niedostępne (zajęte lub minione).</p>
    <?php endif; ?>

    <?php if ($sloty): ?>
        <div class="sloty">
            <?php foreach ($sloty as $g => $powod): ?>
                <?php if ($powod === null): ?>
                    <a class="slot" href="<?= h(adres_rezerwacji([
                        'usluga' => $usluga['id'], 'pracownik' => $pracownik['id'], 'data' => $data, 'godzina' => $g,
                    ])) ?>"><?= h($g) ?></a>
                <?php else: ?>
                    <span class="slot slot-zajety" title="<?= h('Termin ' . $powod) ?>"><?= h($g) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p><a class="btn btn-szary" href="<?= h(adres_rezerwacji(['usluga' => $usluga['id'], 'pracownik' => $pracownik['id']])) ?>">Wybierz inną datę</a></p>

<?php else: ?>
    <?php $koniec = z_minut(na_minuty($godzina) + (int) $usluga['duration']); ?>
    <h2>Potwierdź rezerwację</h2>
    <form method="POST" action="reservation.php" class="formularz">
        <?= csrf_pole() ?>
        <input type="hidden" name="usluga" value="<?= (int) $usluga['id'] ?>">
        <input type="hidden" name="pracownik" value="<?= (int) $pracownik['id'] ?>">
        <input type="hidden" name="data" value="<?= h($data) ?>">
        <input type="hidden" name="godzina" value="<?= h($godzina) ?>">

        <p>Sprawdź dane rezerwacji:
           <strong><?= h($usluga['name']) ?></strong> z lektorem
           <strong><?= h($pracownik['name']) ?> <?= h($pracownik['surname']) ?></strong>,
           <?= h($data) ?>, godz. <strong><?= h($godzina) ?>-<?= h($koniec) ?></strong>,
           cena <strong><?= h(cena($usluga['price'])) ?></strong>.</p>

        <label>Komentarz do rezerwacji (opcjonalnie)
            <textarea name="komentarz" rows="3" maxlength="<?= MAX_DLUGOSC_KOMENTARZA ?>"></textarea>
        </label>

        <div class="przyciski">
            <button type="submit" class="btn">Potwierdzam rezerwację</button>
            <a class="btn btn-szary" href="<?= h(adres_rezerwacji(['usluga' => $usluga['id'], 'pracownik' => $pracownik['id'], 'data' => $data])) ?>">Zmień godzinę</a>
        </div>
        <p class="podpowiedz">Nowa rezerwacja otrzymuje status „oczekująca” i czeka na potwierdzenie przez pracownika.</p>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../includes/client_footer.php'; ?>
