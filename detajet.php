<?php
require_once 'dbconnection.php';

try {
    // Krijo tabelën mjeshtrat_favorit
    $sql = "CREATE TABLE IF NOT EXISTS mjeshtrat_favorit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        mjeshter_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (mjeshter_id) REFERENCES mjeshtrat(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    // echo "Tabela 'mjeshtrat_favorit' u krijua me sukses!";
} catch (PDOException $e) {
    die("Gabim gjatë krijimit të tabelës: " . $e->getMessage());
}
$mjeshter_id = $_GET['mjeshter_id'] ?? null;

if (!$mjeshter_id) {
    echo "<h2 style='text-align: center; color: red;'>Mjeshtri nuk u përcaktua!</h2>";
    exit;
}

try {
    // Merr të dhënat e mjeshtrit nga tabela 'mjeshtrat'
    $stmt = $pdo->prepare("
        SELECT u.first_name, u.last_name, u.profile_picture, u.municipality, u.contact_number, u.email,
               m.sherbimet, m.cmimi, m.orari_punes
        FROM mjeshtrat m
        INNER JOIN users u ON m.user_id = u.id
        WHERE m.id = :mjeshter_id
    ");
    $stmt->execute([':mjeshter_id' => $mjeshter_id]);
    $mjeshter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mjeshter) {
        echo "<h2 style='text-align: center; color: red;'>Mjeshtri nuk u gjet!</h2>";
        exit;
    }

    // Merr fotot e punës nga tabela 'foto_pune'
    $stmt = $pdo->prepare("SELECT foto_path FROM foto_pune WHERE mjeshter_id = :mjeshter_id");
    $stmt->execute([':mjeshter_id' => $mjeshter_id]);
    $foto_pune = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detajet e Mjeshtrit</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        .card {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card img.profile {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
        }
        .card h2 {
            margin: 0;
            color: #333;
            text-align: center;
        }
        .card p {
            margin: 10px 0;
            color: #555;
        }
        .photos {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .photos img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        button {
            background-color: #fbc02d;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: #333;
            font-weight: bold;
            cursor: pointer;
            display: block;
            margin: 20px auto 0;
            text-align: center;
        }
        button:hover {
            background-color: #e2964b;
        }
    </style>
</head>
<body>
    <div class="card">
        <div style="text-align: center;">
            <?php if (!empty($mjeshter['profile_picture'])): ?>
                <img src="<?= htmlspecialchars($mjeshter['profile_picture']) ?>" alt="Foto e profilit" class="profile">
            <?php else: ?>
                <img src="PROFILE/default_profile.png" alt="Foto e profilit" class="profile">
            <?php endif; ?>
        </div>
        <h2><?= htmlspecialchars($mjeshter['first_name'] . ' ' . $mjeshter['last_name']) ?></h2>
        <p><strong>Adresa:</strong> <?= htmlspecialchars($mjeshter['municipality']) ?></p>
        <p><strong>Numri kontaktues:</strong> <?= htmlspecialchars($mjeshter['contact_number']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($mjeshter['email']) ?></p>
        <p><strong>Shërbimet:</strong> <?= htmlspecialchars($mjeshter['sherbimet']) ?></p>
        <p><strong>Çmimi:</strong> <?= htmlspecialchars($mjeshter['cmimi']) ?> €</p>
        <p><strong>Orari i punës:</strong> <?= htmlspecialchars($mjeshter['orari_punes']) ?></p>
        <div class="photos">
            <h3>Foto të punës:</h3>
            <?php foreach ($foto_pune as $foto): ?>
                <img src="<?= htmlspecialchars($foto['foto_path']) ?>" alt="Foto e punës">
            <?php endforeach; ?>
        </div>
        <button>Rezervo</button>
    </div>
</body>
</html>
