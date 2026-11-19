<?php
 
 
$prefiks = $prefiks ?? '';
$aktywna = $aktywna ?? '';
$rola = $_SESSION['role'] ?? null;

$menu = [
    'index' => ['Strona główna', $prefiks . 'index.php'],
    'oferta' => ['Oferta', $prefiks . 'offer.php'],
];
if ($rola === 'client') {
    $menu['rezerwacja'] = ['Zarezerwuj', $prefiks . 'reservation.php'];
    $menu['moje_rezerwacje'] = ['Moje rezerwacje', $prefiks . 'my_reservations.php'];
    $menu['profile'] = ['Moje konto', $prefiks . 'profile.php'];
} elseif ($rola === 'employee') {
    $menu['pracownik'] = ['Panel pracownika', $prefiks . 'employee/index.php'];
    $menu['profile'] = ['Moje konto', $prefiks . 'profile.php'];
} elseif ($rola === 'admin') {
    $menu['admin'] = ['Panel administratora', $prefiks . 'admin/index.php'];
    $menu['profile'] = ['Moje konto', $prefiks . 'profile.php'];
} else {
    $menu['login'] = ['Zaloguj się', $prefiks . 'login.php'];
    $menu['register'] = ['Utwórz konto', $prefiks . 'register.php'];
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($tytul) ?> - Szkoła Językowa</title>
    <link rel="stylesheet" href="<?= h($prefiks) ?>css/admin.css">
    <link rel="stylesheet" href="<?= h($prefiks) ?>css/client.css">
</head>
<body>
<header class="pasek">
    <div class="pasek-wnetrze">
        <span class="logo">Szkoła Językowa</span>
        <nav>
            <?php foreach ($menu as $klucz => [$nazwa, $adres]): ?>
                <a href="<?= h($adres) ?>"<?= $klucz === $aktywna ? ' class="aktywny"' : '' ?>><?= h($nazwa) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="konto">
                <?= h($_SESSION['name'] ?? '') ?> <?= h($_SESSION['surname'] ?? '') ?>
                | <a href="<?= h($prefiks) ?>logout.php">Wyloguj</a>
            </div>
        <?php endif; ?>
    </div>
</header>
<main class="tresc">
    <h1><?= h($tytul) ?></h1>
    <?php pokaz_komunikat(); ?>
