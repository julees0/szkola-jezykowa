<?php
require_once __DIR__ . '/../includes/client_init.php';

$konto = wymagaj_roli(array_keys(ROLE));

$stmt = $pdo->prepare("SELECT name, surname, email, phone, role, created_at FROM users WHERE id = ?");
$stmt->execute([$konto['id']]);
$user = $stmt->fetch();

$tytul = 'Moje konto';
$aktywna = 'profile';
require __DIR__ . '/../includes/client_header.php';
?>

<p>Witaj, <strong><?= h($user['name']) ?> <?= h($user['surname']) ?></strong>!</p>

<div class="karta">
    <dl>
        <dt>Imię i nazwisko</dt><dd><?= h($user['name']) ?> <?= h($user['surname']) ?></dd>
        <dt>E-mail</dt><dd><?= h($user['email']) ?></dd>
        <dt>Telefon</dt><dd><?= h($user['phone']) ?></dd>
        <dt>Rola w systemie</dt><dd><?= h(ROLE[$user['role']] ?? $user['role']) ?></dd>
        <dt>Konto założone</dt><dd><?= h($user['created_at']) ?></dd>
    </dl>
</div>

<div class="przyciski" style="margin-top: 14px;">
    <?php if ($user['role'] === 'client'): ?>
        <a class="btn" href="my_reservations.php">Moje rezerwacje</a>
        <a class="btn" href="reservation.php">Zarezerwuj termin</a>
    <?php elseif ($user['role'] === 'employee'): ?>
        <a class="btn" href="employee/index.php">Panel pracownika</a>
    <?php elseif ($user['role'] === 'admin'): ?>
        <a class="btn" href="admin/index.php">Panel administratora</a>
    <?php endif; ?>
    <a class="btn btn-szary" href="edit_profile.php">Edytuj profil</a>
    <a class="btn btn-szary" href="index.php">Strona główna</a>
</div>

<?php require __DIR__ . '/../includes/client_footer.php'; ?>
