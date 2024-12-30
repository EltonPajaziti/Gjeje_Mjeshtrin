<?php
require_once 'dbconnection.php';
session_start();

// Kontrollo nëse përdoruesi është i kyçur
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Merr mjeshtrat e preferuar nga tabela "mjeshtrat_favorit"
try {
    $stmt = $pdo->prepare("SELECT mf.mjeshter_id, u.first_name, u.last_name, u.profile_picture, u.municipality, u.contact_number, m.profesion, m.sherbimet
                           FROM mjeshtrat_favorit mf
                           INNER JOIN mjeshtrat m ON mf.mjeshter_id = m.id
                           INNER JOIN users u ON m.user_id = u.id
                           WHERE mf.user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $mjeshtrat_favorit = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mjeshtrit Favorit</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            color: #333;
        }
        .cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            width: 300px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }
        .card .no-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eee;
            color: #888;
            margin-bottom: 15px;
        }
        .card h3 {
            margin: 0 0 10px;
            color: #333;
            font-size: 20px;
        }
        .card p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
        }
        .card button {
            background-color: #fbc02d;
            border: none;
            color: #333;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .card button:hover {
            background-color: #e2964b;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Mjeshtrit Favorit</h1>
    </div>
    <div class="cards">
        <?php if ($mjeshtrat_favorit): ?>
            <?php foreach ($mjeshtrat_favorit as $mjeshter): ?>
                <div class="card">
                    <?php if (!empty($mjeshter['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($mjeshter['profile_picture']) ?>" alt="Foto e profilit">
                    <?php else: ?>
                        <div class="no-photo">Nuk ka foto profili</div>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($mjeshter['first_name'] . ' ' . $mjeshter['last_name']) ?></h3>
                    <p><strong>Profesioni:</strong> <?= htmlspecialchars($mjeshter['profesion']) ?></p>
                    <p><strong>Rajoni:</strong> <?= htmlspecialchars($mjeshter['municipality']) ?></p>
                    <p><strong>Numri Kontaktues:</strong> <?= htmlspecialchars($mjeshter['contact_number']) ?></p>
                    <p><strong>Shërbimet:</strong> <?= htmlspecialchars($mjeshter['sherbimet']) ?></p>
                    <a href="detajet.php?mjeshter_id=<?= $mjeshter['mjeshter_id'] ?>&source=favorites" class="btn-details">
    <button>Mëso më shumë detaje</button>
</a>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #888;">Nuk keni mjeshtra të preferuar.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
