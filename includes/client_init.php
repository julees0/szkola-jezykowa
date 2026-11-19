<?php
 
 

date_default_timezone_set('Europe/Warsaw');

set_exception_handler(function ($e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo 'Wystąpił błąd aplikacji. Spróbuj ponownie później.';
});

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/reservation_helpers.php';

 
function brak_dostepu($tekst, $prefiks = '') {
    http_response_code(403);
    $tytul = 'Brak dostępu';
    $aktywna = '';
    require __DIR__ . '/client_header.php';
    echo '<div class="alert alert-blad">' . h($tekst) . '</div>';
    require __DIR__ . '/client_footer.php';
    exit;
}

 
 
function nie_znaleziono($tekst, $prefiks = '') {
    http_response_code(404);
    $tytul = 'Nie znaleziono';
    $aktywna = '';
    require __DIR__ . '/client_header.php';
    echo '<div class="alert alert-blad">' . h($tekst) . '</div>';
    require __DIR__ . '/client_footer.php';
    exit;
}

 
 
 
function wymagaj_roli(array $role, $prefiks = '') {
    global $pdo;

    if (!isset($_SESSION['user_id'])) {
        $adres = $prefiks . 'login.php';
         
        if ($prefiks === '' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $cel = basename($_SERVER['SCRIPT_NAME']);
            if (!empty($_SERVER['QUERY_STRING'])) {
                $cel .= '?' . $_SERVER['QUERY_STRING'];
            }
            $adres .= '?next=' . urlencode($cel);
        }
        header('Location: ' . $adres);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name, surname, role, active FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $konto = $stmt->fetch();

    if (!$konto || (int) $konto['active'] !== 1) {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . $prefiks . 'login.php');
        exit;
    }

    $_SESSION['role'] = $konto['role'];
    if (!in_array($konto['role'], $role, true)) {
        brak_dostepu('Nie masz uprawnień do tej strony.', $prefiks);
    }
    return $konto;
}

 
 
function bezpieczny_adres_next($adres) {
    if (is_string($adres) && preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9=&_.:%+-]*)?$/', $adres) === 1) {
        return $adres;
    }
    return '';
}
