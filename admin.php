<?php
require_once 'dbconnection.php';
session_start();

// Kontrollo nëse përdoruesi është i kyçur
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Merr të dhënat e përdoruesit nga baza e të dhënave
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $profile_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : 'PROFILE/default_profile.png';
    $user_name = $user['first_name'] . ' ' . $user['last_name'];
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar</title>
    <link rel="stylesheet" href="CSS/navbar.css">
    <style>
        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-image: url('<?= htmlspecialchars($profile_picture); ?>');
            background-size: cover;
            background-position: center;
            border: none; /* Largon kufirin */
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <ul class="navbar-list">
            <li class="navbar-item profile">
                <a href="profile.php">
                    <div class="profile-image"></div>
                    <span><?= htmlspecialchars($user_name); ?></span> <!-- Vendoset emri i përdoruesit -->
                </a>
            </li>
            <li class="navbar-item">
                <a href="mjeshtrit_ke_pasur.php">Menaxho Klientët</a>
            </li>
            <li class="navbar-item">
                <a href="mjeshtrit_pritje.php">Menaxho Mjeshtrit</a>
            </li>
           
            <li class="navbar-item">
                <a href="index.php">Dil</a>
            </li>
        </ul>
    </nav>
</body>
</html>
