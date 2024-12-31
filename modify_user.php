<?php
require_once 'dbconnection.php';
session_start();

// Kontrollo nëse përdoruesi është i kyçur
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Merr ID-në e përdoruesit për të cilin do të bëhet modifikimi
if (!isset($_GET['id'])) {
    die("ID e përdoruesit nuk është specifikuar.");
}

$perdorues_id = $_GET['id'];

// Merr të dhënat e përdoruesit nga databaza
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $perdorues_id]);
    $perdorues = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$perdorues) {
        die("Përdoruesi nuk ekziston.");
    }
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave të përdoruesit: " . $e->getMessage());
}

// Përditëso të dhënat e përdoruesit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields_to_update = [];
    $params = [':id' => $perdorues_id];

    // Check every field for updates
    foreach (['email', 'contact_number', 'municipality', 'address', 'gender'] as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== $perdorues[$field]) {
            $fields_to_update[] = "$field = :$field";
            $params[":$field"] = $_POST[$field];
        }
    }

    // Check if password is updated
    if (!empty($_POST['password'])) {
        $fields_to_update[] = "password = :password";
        $params[':password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }

    // Update profile picture if uploaded
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $upload_dir = 'PROFILE/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = $perdorues_id . '_' . basename($_FILES['profile_image']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            $fields_to_update[] = "profile_picture = :profile_picture";
            $params[':profile_picture'] = $target_file;
        }
    }

    // Execute update if fields exist
    if (!empty($fields_to_update)) {
        $update_query = "UPDATE users SET " . implode(", ", $fields_to_update) . " WHERE id = :id";
        try {
            $stmt = $pdo->prepare($update_query);
            $stmt->execute($params);
            header("Location: admin.php?success=1");
            exit;
        } catch (PDOException $e) {
            die("Gabim gjatë përditësimit të të dhënave: " . $e->getMessage());
        }
    }
}

?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifiko Përdoruesin</title>
    <link rel="stylesheet" href="CSS/profile.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .profile-image-section img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        .profile-image-section div {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        .profile-text {
            text-align: center;
            margin-top: 10px;
        }
        button {
            margin-top: 10px;
        }
        .butoni{
            background-color: #e2964b;
        }
        .butoni:hover{
            background-color: #531f11;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <form action="modify_user.php?id=<?= htmlspecialchars($perdorues_id); ?>" method="POST" enctype="multipart/form-data">
            <div class="profile-image-section">
                <label for="profile_image">
                    <div>
                        <?php if (!empty($perdorues['profile_picture'])): ?>
                            <img id="preview-image" src="<?= htmlspecialchars($perdorues['profile_picture']); ?>" alt="Foto e Profilit">
                        <?php else: ?>
                            <img id="preview-image" src="PROFILE/default_profile.png" alt="Foto e Profilit">
                        <?php endif; ?>
                    </div>
                    <p class="profile-text">Foto e Profilit</p>
                </label>
                <input type="file" id="profile_image" name="profile_image" style="display: none;" accept="image/*" onchange="previewImage(event)">
            </div>

            <div class="profile-details">
                <p><strong>Emri dhe Mbiemri:</strong> <?= htmlspecialchars($perdorues['first_name'] . ' ' . $perdorues['last_name']); ?></p>
                <p><strong>Email:</strong> <input type="email" name="email" value="<?= htmlspecialchars($perdorues['email']); ?>"></p>
                <p><strong>Numri Kontaktues:</strong> <input type="text" name="contact_number" value="<?= htmlspecialchars($perdorues['contact_number']); ?>"></p>
                <p><strong>Rajoni:</strong> <input type="text" name="municipality" value="<?= htmlspecialchars($perdorues['municipality']); ?>"></p>
                <p><strong>Adresa:</strong> <input type="text" name="address" value="<?= htmlspecialchars($perdorues['address']); ?>"></p>
                <p><strong>Gjinia:</strong> <input type="text" name="gender" value="<?= htmlspecialchars($perdorues['gender']); ?>"></p>
                <p><strong>Fjalëkalimi:</strong> <input type="password" name="password" placeholder="Ndrysho fjalëkalimin"></p>
            </div>
            <button type="submit" class="butoni">Ruaj Ndryshimet</button>
        </form>
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
