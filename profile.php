<?php
require_once 'dbconnection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Kontrollo dhe krijo direktoriumin PROFILE nëse nuk ekziston
$upload_dir = 'PROFILE/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Merr të dhënat e përdoruesit
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Gabim gjatë marrjes së të dhënave: " . $e->getMessage();
}

// Përpunimi i ndryshimeve
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $file_name = $user_id . '_' . basename($_FILES['profile_image']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :id");
                $stmt->execute([':profile_picture' => $target_file, ':id' => $user_id]);
                $success_message = "Fotoja e profilit u përditësua me sukses!";
                $user['profile_picture'] = $target_file;
            } catch (PDOException $e) {
                $error_message = "Gabim gjatë përditësimit të fotos: " . $e->getMessage();
            }
        } else {
            $error_message = "Gabim gjatë ngarkimit të fotos.";
        }
    }

    // Përditëso fushat e tjera
    foreach (['email', 'contact_number', 'municipality', 'address', 'gender', 'password'] as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== $user[$field]) {
            try {
                $value = $field === 'password' ? password_hash($_POST[$field], PASSWORD_BCRYPT) : $_POST[$field];
                $stmt = $pdo->prepare("UPDATE users SET $field = :value WHERE id = :id");
                $stmt->execute([':value' => $value, ':id' => $user_id]);
                $success_message = "Të dhënat u përditësuan me sukses!";
                $user[$field] = $value;
            } catch (PDOException $e) {
                $error_message = "Gabim gjatë përditësimit të të dhënave: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profili</title>
    <link rel="stylesheet" href="CSS/profile.css">
    <style>
        .profile-image-section img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-image-section {
    text-align: center; /* Center the content inside the section */
}

.profile-image-section div {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-color: #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto; /* Center the circle within its container */
}

.profile-text {
    margin-top: 10px; /* Add spacing below the circle */
    font-size: 14px; /* Adjust font size as needed */
    color: #333; /* Optional: Change the text color */
}


    </style>
</head>
<body>
    <div class="profile-container">
        <form action="profile.php" method="POST" enctype="multipart/form-data">
        <div class="profile-image-section">
    <label for="profile_image">
        <div>
            <?php if (!empty($user['profile_picture'])): ?>
                <img id="preview-image" src="<?= htmlspecialchars($user['profile_picture']); ?>" alt="Foto e Profilit">
            <?php else: ?>
                <img id="preview-image" src="default_profile.png" alt="">
            <?php endif; ?>
        </div>
        <p class="profile-text">Foto e Profilit</p>
    </label>
    <input type="file" id="profile_image" name="profile_image" style="display: none;" accept="image/*" onchange="previewImage(event)">
</div>

            <div class="profile-details">
                <p><strong>Emri dhe Mbiemri:</strong> <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <p><strong>Email:</strong> <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>"></p>
                <p><strong>Numri Kontaktues:</strong> <input type="text" name="contact_number" value="<?= htmlspecialchars($user['contact_number']); ?>"></p>
                <p><strong>Rajoni:</strong> <input type="text" name="municipality" value="<?= htmlspecialchars($user['municipality']); ?>"></p>
                <p><strong>Adresa:</strong> <input type="text" name="address" value="<?= htmlspecialchars($user['address']); ?>"></p>
                <p><strong>Gjinia:</strong> <input type="text" name="gender" value="<?= htmlspecialchars($user['gender']); ?>"></p>
                <p><strong>Fjalëkalimi:</strong> <input type="password" name="password" placeholder="Ndrysho fjalëkalimin"></p>
            </div>
            <button type="submit">Ruaj Ndryshimet</button>
        </form>
        <?php if (!empty($error_message)): ?>
            <p class="error-message" style="color: red;"><?= htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <p class="success-message" style="color: green;"><?= htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
    </div>
    <script>
        function previewImage(event) {
            const reader = new FileReader();
            const preview = document.getElementById('preview-image');

            reader.onload = function () {
                preview.src = reader.result;
            }

            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>
