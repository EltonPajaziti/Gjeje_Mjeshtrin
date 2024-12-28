<?php
// Përfshi lidhjen me bazën e të dhënave
require_once 'dbconnection.php';

$error_message = ''; // Variabli për të ruajtur mesazhin e gabimit

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kontrollo nëse çelësat ekzistojnë në $_POST
    $email = $_POST['email'] ?? null;
    $role = $_POST['roli'] ?? null;
    $password = $_POST['password'] ?? null;

    if ($email && $role && $password) {
        try {
            // Kontrollo nëse përdoruesi ekziston dhe kredencialet janë të sakta
            $sql = "SELECT * FROM users WHERE email = :email AND role = :role";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email, ':role' => $role]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Nëse kredencialet përputhen
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];

                // Ridrejto përdoruesin bazuar në rolin e tij
                switch ($user['role']) {
                    case 'Admin':
                        header("Location: admin.php");
                        break;
                    case 'Mjeshter':
                        header("Location: mjeshter.php");
                        break;
                    case 'Qytetar':
                        header("Location: qytetari.php");
                        break;
                    default:
                        $error_message = "Roli nuk është i vlefshëm.";
                        break;
                }
                exit;
            } else {
                // Nëse kredencialet nuk përputhen
                $error_message = "Kredencialet nuk përputhen. Ju lutem provoni përsëri.";
            }
        } catch (PDOException $e) {
            $error_message = "Gabim gjatë hyrjes: " . $e->getMessage();
        }
    } else {
        $error_message = "Ju lutem plotësoni të gjitha fushat!";
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogIn</title>
    <link rel="icon" type="image/png" href="Images/FINAL_LOGO.png">
    <link href="CSS/signup.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <!-- Left Side: Form -->
    <div class="form-container">
        <h2>Hyr në platformë</h2>
        <form action="login.php" method="POST">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Shkruani email-in" required>

            <label for="roli">Roli</label>
            <select id="roli" name="roli" required>
                <option value="">Zgjidh Rolin</option>
                <option value="qytetar">Qytetar</option>
                <option value="mjeshter">Mjeshtër</option>
                <option value="admin">Admin</option>
            </select>

            <label for="password">Fjalëkalimi</label>
            <input type="password" id="password" name="password" placeholder="Shkruani fjalëkalimin" required>

            <button type="submit">Hyr</button>

            <!-- Shfaq mesazhin e gabimit poshtë butonit -->
            <?php if (!empty($error_message)): ?>
                <p style="color: red; text-align: center; margin-top: 10px;"><?php echo $error_message; ?></p>
            <?php endif; ?>
        </form>
    </div>

    <!-- Right Side: Info Section -->
    <div class="info-container">
        <img src="Images/FINAL_LOGO.png" alt="Logo">
        <div class="animated-text-container">
            <span class="animated-text" data-text="A ja ki numrin mjeshtrit?"></span>
            <span class="animated-text" data-text="Cili mjeshtër është ma i mirë?"></span>
            <span class="animated-text" data-text="Gjeje mjeshtrin që të duhet!"></span>
            <span class="animated-text" data-text="Kur është i lirë mjeshtri?"></span>
            <span class="animated-text" data-text="A është i shtrejtë?"></span>
            <span class="animated-text" data-text="Ku gjindet?"></span>
            <span class="animated-text" data-text="A e kryn punën mirë?"></span>
            <span class="animated-text" data-text="Ku me reklamu punen tem?"></span>
            <span class="animated-text" data-text="Nuk po e gjen shtëpinë!"></span>
            <span class="animated-text" data-text="Veq ki me zgjedh!"></span>
        </div>
    </div>
</div>

<script src="JS/animated.js"></script>
</body>
</html>
