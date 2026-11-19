<?php
 
 

const KROK_MINUT = 30;             
const MAX_DNI_NAPRZOD = 90;        
const MAX_DLUGOSC_KOMENTARZA = 500;

 
const STATUSY_OTWARTE = ['oczekująca', 'potwierdzona'];

 
const PRZEJSCIA_STATUSOW = [
    'oczekująca' => ['potwierdzona', 'anulowana'],
    'potwierdzona' => ['zrealizowana', 'anulowana'],
    'zrealizowana' => [],
    'anulowana' => [],
];

 

 
function parametr($zrodlo, $klucz) {
    $wartosc = $zrodlo[$klucz] ?? '';
    return is_string($wartosc) ? trim($wartosc) : '';
}

 
function parametr_int($zrodlo, $klucz) {
    $wartosc = $zrodlo[$klucz] ?? '';
    return (is_string($wartosc) && ctype_digit($wartosc)) ? (int) $wartosc : 0;
}

 
function na_minuty($czas) {
    $czesci = explode(':', (string) $czas);
    return ((int) $czesci[0]) * 60 + (int) ($czesci[1] ?? 0);
}

 
function z_minut($minuty) {
    return sprintf('%02d:%02d', intdiv($minuty, 60), $minuty % 60);
}

 
function dzien_tygodnia($data) {
    return (int) (new DateTime($data))->format('N');
}

 
function sprawdz_zakres_daty($data) {
    if (!poprawna_data($data)) {
        return 'Wybierz poprawną datę.';
    }
    $dzis = new DateTime('today');
    $wybrana = new DateTime($data);
    if ($wybrana < $dzis) {
        return 'Nie można rezerwować terminów z przeszłości.';
    }
    $maksimum = (clone $dzis)->modify('+' . MAX_DNI_NAPRZOD . ' days');
    if ($wybrana > $maksimum) {
        return 'Rezerwacji można dokonać maksymalnie ' . MAX_DNI_NAPRZOD . ' dni do przodu.';
    }
    return null;
}

 

 
function pobierz_oferte($pdo, $kategoria = 0, $szukaj = '', $tylko_dostepne = false) {
    $sql = "SELECT s.id, s.name, s.description, s.duration, s.price,
                   c.id AS kategoria_id, c.name AS kategoria,
                   (SELECT COUNT(*)
                      FROM employee_services es
                      JOIN employees e ON e.id = es.employee_id
                      JOIN users u ON u.id = e.user_id
                     WHERE es.service_id = s.id AND e.active = 1 AND u.active = 1) AS liczba_pracownikow
            FROM services s
            JOIN service_categories c ON c.id = s.category_id
            WHERE s.active = 1";
    $parametry = [];
    if ($kategoria > 0) {
        $sql .= " AND c.id = ?";
        $parametry[] = $kategoria;
    }
    if ($szukaj !== '') {
        $sql .= " AND (s.name LIKE ? OR s.description LIKE ?)";
        $wzor = '%' . addcslashes($szukaj, '%_\\') . '%';
        $parametry[] = $wzor;
        $parametry[] = $wzor;
    }
    if ($tylko_dostepne) {
        $sql .= " AND EXISTS (SELECT 1
                               FROM employee_services es2
                               JOIN employees e2 ON e2.id = es2.employee_id
                               JOIN users u2 ON u2.id = e2.user_id
                              WHERE es2.service_id = s.id AND e2.active = 1 AND u2.active = 1)";
    }
    $sql .= " ORDER BY c.name, s.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametry);
    return $stmt->fetchAll();
}

 
function pobierz_usluge($pdo, $id) {
    $stmt = $pdo->prepare(
        "SELECT s.id, s.name, s.description, s.duration, s.price,
                c.id AS kategoria_id, c.name AS kategoria
         FROM services s
         JOIN service_categories c ON c.id = s.category_id
         WHERE s.id = ? AND s.active = 1"
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

 
function pobierz_pracownikow_uslugi($pdo, $id_uslugi) {
    $stmt = $pdo->prepare(
        "SELECT e.id, e.description, u.name, u.surname
         FROM employee_services es
         JOIN employees e ON e.id = es.employee_id
         JOIN users u ON u.id = e.user_id
         WHERE es.service_id = ? AND e.active = 1 AND u.active = 1
         ORDER BY u.surname, u.name"
    );
    $stmt->execute([$id_uslugi]);
    return $stmt->fetchAll();
}

 
function pobierz_pracownika_uslugi($pdo, $id_pracownika, $id_uslugi) {
    $stmt = $pdo->prepare(
        "SELECT e.id, e.description, u.name, u.surname
         FROM employee_services es
         JOIN employees e ON e.id = es.employee_id
         JOIN users u ON u.id = e.user_id
         WHERE es.service_id = ? AND e.id = ? AND e.active = 1 AND u.active = 1"
    );
    $stmt->execute([$id_uslugi, $id_pracownika]);
    return $stmt->fetch() ?: null;
}

 
function godziny_tygodniowe($pdo, $id_pracownika) {
    $stmt = $pdo->prepare(
        "SELECT day_of_week, start_time, end_time
         FROM employee_availability
         WHERE employee_id = ?
         ORDER BY day_of_week, start_time"
    );
    $stmt->execute([$id_pracownika]);
    $grafik = [];
    foreach ($stmt->fetchAll() as $w) {
        $grafik[(int) $w['day_of_week']][] = godzina($w['start_time']) . '-' . godzina($w['end_time']);
    }
    return $grafik;
}

 

 
function pobierz_godziny_pracy($pdo, $id_pracownika, $data) {
    $stmt = $pdo->prepare(
        "SELECT start_time, end_time
         FROM employee_availability
         WHERE employee_id = ? AND day_of_week = ?
         ORDER BY start_time"
    );
    $stmt->execute([$id_pracownika, dzien_tygodnia($data)]);
    $przedzialy = [];
    foreach ($stmt->fetchAll() as $w) {
        $przedzialy[] = [na_minuty($w['start_time']), na_minuty($w['end_time'])];
    }
    return $przedzialy;
}

 
function pobierz_zajete_terminy($pdo, $id_pracownika, $data) {
    $stmt = $pdo->prepare(
        "SELECT start_time, end_time
         FROM reservations
         WHERE employee_id = ? AND reservation_date = ? AND status <> 'anulowana'"
    );
    $stmt->execute([$id_pracownika, $data]);
    $zajete = [];
    foreach ($stmt->fetchAll() as $w) {
        $zajete[] = [na_minuty($w['start_time']), na_minuty($w['end_time'])];
    }
    return $zajete;
}

 
 
 
function terminy_sie_nakladaja($nowy_start, $nowy_koniec, $stary_start, $stary_koniec) {
    return $nowy_start < $stary_koniec && $nowy_koniec > $stary_start;
}

 
 
 
function sloty_dnia($pdo, $id_pracownika, $data, $czas_trwania) {
    $godziny_pracy = pobierz_godziny_pracy($pdo, $id_pracownika, $data);
    $zajete = pobierz_zajete_terminy($pdo, $id_pracownika, $data);

    $teraz = new DateTime();
    $jest_dzis = $teraz->format('Y-m-d') === $data;
    $teraz_minuty = na_minuty($teraz->format('H:i'));

    $sloty = [];
    foreach ($godziny_pracy as [$od, $do]) {
        for ($start = $od; $start + $czas_trwania <= $do; $start += KROK_MINUT) {
            $koniec = $start + $czas_trwania;
            $powod = null;

            if ($jest_dzis && $start <= $teraz_minuty) {
                $powod = 'minęła';
            } else {
                foreach ($zajete as [$z_start, $z_koniec]) {
                    if (terminy_sie_nakladaja($start, $koniec, $z_start, $z_koniec)) {
                        $powod = 'zajęta';
                        break;
                    }
                }
            }

             
            $klucz = z_minut($start);
            if (!array_key_exists($klucz, $sloty) || $powod === null) {
                $sloty[$klucz] = $powod;
            }
        }
    }
    ksort($sloty);
    return $sloty;
}

 
 
function sprawdz_termin($pdo, $id_pracownika, $usluga, $data, $godzina) {
    $blad = sprawdz_zakres_daty($data);
    if ($blad !== null) {
        return $blad;
    }
    if (!poprawna_godzina($godzina)) {
        return 'Wybierz poprawną godzinę.';
    }

    $poczatek = DateTime::createFromFormat('Y-m-d H:i', $data . ' ' . $godzina);
    if (!$poczatek || $poczatek <= new DateTime()) {
        return 'Nie można rezerwować terminów z przeszłości.';
    }

    $start = na_minuty($godzina);
    $koniec = $start + (int) $usluga['duration'];

    $w_godzinach_pracy = false;
    foreach (pobierz_godziny_pracy($pdo, $id_pracownika, $data) as [$od, $do]) {
        if ($start >= $od && $koniec <= $do) {
            $w_godzinach_pracy = true;
            break;
        }
    }
    if (!$w_godzinach_pracy) {
        return 'Wybrany termin wykracza poza godziny pracy pracownika.';
    }

    foreach (pobierz_zajete_terminy($pdo, $id_pracownika, $data) as [$z_start, $z_koniec]) {
        if (terminy_sie_nakladaja($start, $koniec, $z_start, $z_koniec)) {
            return 'Ten termin jest już zajęty. Wybierz inną godzinę.';
        }
    }
    return null;
}

 

 
 
function utworz_rezerwacje($pdo, $id_klienta, $id_uslugi, $id_pracownika, $data, $godzina, $komentarz) {
    $usluga = pobierz_usluge($pdo, $id_uslugi);
    if (!$usluga) {
        return ['blad' => 'Wybrana usługa nie jest już dostępna.'];
    }
    if (!pobierz_pracownika_uslugi($pdo, $id_pracownika, $id_uslugi)) {
        return ['blad' => 'Wybrany pracownik nie wykonuje tej usługi.'];
    }
    $komentarz = trim($komentarz);
    if (mb_strlen($komentarz) > MAX_DLUGOSC_KOMENTARZA) {
        return ['blad' => 'Komentarz może mieć maksymalnie ' . MAX_DLUGOSC_KOMENTARZA . ' znaków.'];
    }

    try {
        $pdo->beginTransaction();

         
         
         
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? AND active = 1 FOR UPDATE");
        $stmt->execute([$id_pracownika]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            return ['blad' => 'Wybrany pracownik nie jest już dostępny.'];
        }

         
        $blad = sprawdz_termin($pdo, $id_pracownika, $usluga, $data, $godzina);
        if ($blad !== null) {
            $pdo->rollBack();
            return ['blad' => $blad];
        }

        $koniec = z_minut(na_minuty($godzina) + (int) $usluga['duration']);
        $stmt = $pdo->prepare(
            "INSERT INTO reservations
                (user_id, employee_id, service_id, reservation_date, start_time, end_time, status, comment)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $id_klienta, $id_pracownika, $id_uslugi, $data, $godzina, $koniec,
            STATUSY[0], $komentarz === '' ? null : $komentarz,
        ]);
        $id = (int) $pdo->lastInsertId();

        $pdo->commit();
        return ['id' => $id];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log($e->getMessage());
        return ['blad' => 'Nie udało się zapisać rezerwacji. Spróbuj ponownie później.'];
    }
}

 
function czy_przyszla($rezerwacja) {
    return in_array($rezerwacja['status'], STATUSY_OTWARTE, true)
        && $rezerwacja['reservation_date'] . ' ' . $rezerwacja['end_time'] > date('Y-m-d H:i:s');
}

 
function czy_mozna_anulowac($rezerwacja) {
    return in_array($rezerwacja['status'], STATUSY_OTWARTE, true)
        && $rezerwacja['reservation_date'] . ' ' . $rezerwacja['start_time'] > date('Y-m-d H:i:s');
}

 
 
function anuluj_rezerwacje_klienta($pdo, $id_rezerwacji, $id_klienta) {
    $teraz = new DateTime();
    $stmt = $pdo->prepare(
        "UPDATE reservations SET status = ?
         WHERE id = ? AND user_id = ? AND status IN (?, ?)
           AND (reservation_date > ? OR (reservation_date = ? AND start_time > ?))"
    );
    $stmt->execute([
        'anulowana', $id_rezerwacji, $id_klienta, STATUSY_OTWARTE[0], STATUSY_OTWARTE[1],
        $teraz->format('Y-m-d'), $teraz->format('Y-m-d'), $teraz->format('H:i:s'),
    ]);
    return $stmt->rowCount() === 1;
}

 

 
function pobierz_id_pracownika($pdo, $id_uzytkownika) {
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ? AND active = 1");
    $stmt->execute([$id_uzytkownika]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

 
 
function zmien_status_pracownika($pdo, $id_rezerwacji, $id_pracownika, $stary_status, $nowy_status) {
    if (!in_array($nowy_status, PRZEJSCIA_STATUSOW[$stary_status] ?? [], true)) {
        return false;
    }
    $stmt = $pdo->prepare(
        "UPDATE reservations SET status = ? WHERE id = ? AND employee_id = ? AND status = ?"
    );
    $stmt->execute([$nowy_status, $id_rezerwacji, $id_pracownika, $stary_status]);
    return $stmt->rowCount() === 1;
}
