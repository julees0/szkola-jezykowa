<?php
require_once __DIR__ . '/../includes/client_init.php';

$komunikat = "";

$next = bezpieczny_adres_next($_POST['next'] ?? $_GET['next'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $komunikat = "Wypełnij wszystkie pola!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, surname, password, role, active FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {

                if ((int) $user['active'] !== 1) {
                    $komunikat = "To konto zostało zablokowane.";
                } else {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['surname'] = $user['surname'];
                    $_SESSION['role'] = $user['role'];

                     
                    if ($user['role'] === 'admin') {
                        header("Location: admin/index.php");
                    } elseif ($user['role'] === 'employee') {
                        header("Location: employee/index.php");
                    } elseif ($next !== '') {
                        header("Location: " . $next);
                    } else {
                        header("Location: profile.php");
                    }
                    exit;
                }
            } else {
                $komunikat = "Nieprawidłowy e-mail lub hasło!";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $komunikat = "Nie udało się zalogować. Spróbuj ponownie później.";
        }
    }
}

$tytul = 'Logowanie do systemu';
$aktywna = 'login';
require __DIR__ . '/../includes/client_header.php';
?>

<?php if (!empty($komunikat)): ?>
    <div class="alert alert-blad"><?php echo htmlspecialchars($komunikat, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<?php if ($next !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
    <div class="alert alert-ok">Zaloguj się, aby kontynuować rezerwację.</div>
<?php endif; ?>

<form action="login.php" method="POST" class="formularz formularz-waski">
    <input type="hidden" name="next" value="<?= h($next) ?>">
    <label for="email">E-mail
        <input type="email" id="email" name="email" required>
    </label>
    <label for="password">Hasło
        <input type="password" id="password" name="password" required>
    </label>
    <div class="przyciski">
        <button type="submit" class="btn">Zaloguj się</button>
    </div>
    <p>Nie masz konta? <a href="register.php">Zarejestruj się</a></p>
</form>

<?php require __DIR__ . '/../includes/client_footer.php'; ?>
