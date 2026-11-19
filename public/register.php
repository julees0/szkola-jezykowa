<?php
require_once __DIR__ . '/../includes/client_init.php';

$komunikat = "";
$sukces = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '' || $surname === '' || $email === '' || $phone === '' || $password === '' || $confirm_password === '') {
        $komunikat = "Wypełnij wszystkie pola!";
    } elseif (mb_strlen($name) > 50 || mb_strlen($surname) > 50 || mb_strlen($email) > 100 || mb_strlen($phone) > 20) {
        $komunikat = "Któreś z pól jest za długie (imię i nazwisko do 50 znaków, e-mail do 100, telefon do 20).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $komunikat = "Niepoprawny format adresu e-mail!";
    } elseif ($password !== $confirm_password) {
        $komunikat = "Hasła muszą być takie same!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);

            if ($stmt->fetchColumn() !== false) {
                $komunikat = "Ten adres e-mail jest już zarejestrowany!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                
                $stmt = $pdo->prepare("INSERT INTO users (name, surname, email, phone, password, role, active) VALUES (?, ?, ?, ?, ?, 'client', 1)");
                $stmt->execute([$name, $surname, $email, $phone, $hashed_password]);

                $komunikat = "Rejestracja zakończona sukcesem! Możesz się teraz zalogować.";
                $sukces = true;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $komunikat = "Nie udało się utworzyć konta. Spróbuj ponownie później.";
        }
    }
}

$tytul = 'Rejestracja użytkownika';
$aktywna = 'register';
require __DIR__ . '/../includes/client_header.php';
?>

<?php if (!empty($komunikat)): ?>
    <div class="alert <?= $sukces ? 'alert-ok' : 'alert-blad' ?>"><?php echo htmlspecialchars($komunikat, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<form action="register.php" method="POST" class="formularz formularz-waski">
    <label for="name">Imię
        <input type="text" id="name" name="name" maxlength="50" required>
    </label>
    <label for="surname">Nazwisko
        <input type="text" id="surname" name="surname" maxlength="50" required>
    </label>
    <label for="email">E-mail
        <input type="email" id="email" name="email" maxlength="100" required>
    </label>
    <label for="phone">Telefon
        <input type="tel" id="phone" name="phone" maxlength="20" required>
    </label>
    <label for="password">Hasło
        <input type="password" id="password" name="password" required>
    </label>
    <label for="confirm_password">Powtórz hasło
        <input type="password" id="confirm_password" name="confirm_password" required>
    </label>
    <div class="przyciski">
        <button type="submit" class="btn">Zarejestruj się</button>
    </div>
    <p>Masz już konto? <a href="login.php">Zaloguj się</a></p>
</form>

<?php require __DIR__ . '/../includes/client_footer.php'; ?>
