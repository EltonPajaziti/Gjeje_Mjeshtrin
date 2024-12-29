<?php
require_once 'dbconnection.php';

// Merr profesionin nga parametri i URL-së
$profesion = $_GET['profesion'] ?? null;

if (!$profesion) {
    echo "<h2 style='text-align: center; color: red;'>Nuk u përcaktua profesioni!</h2>";
    exit;
}

try {
    // Merr të dhënat e mjeshtrave që kanë profesionin e zgjedhur
    $stmt = $pdo->prepare(
        "SELECT u.id AS user_id, m.id AS mjeshter_id, u.first_name, u.last_name, u.profile_picture, u.municipality, u.contact_number, m.sherbimet
         FROM users u
         INNER JOIN mjeshtrat m ON u.id = m.user_id
         WHERE m.profesion = :profesion"
    );
    $stmt->execute([':profesion' => $profesion]);
    $mjeshtrat = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profesionet</title>
    <link href="CSS/profesionet.css" rel="stylesheet">
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
        <h1>Mjeshtrat për profesionin: <?= htmlspecialchars($profesion) ?></h1>
    </div>
    <div class="cards">
        <?php if ($mjeshtrat): ?>
            <?php foreach ($mjeshtrat as $mjeshter): ?>
                <div class="card">
                    <?php if (!empty($mjeshter['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($mjeshter['profile_picture']) ?>" alt="Foto e profilit">
                    <?php else: ?>
                        <div class="no-photo">Nuk ka foto profili</div>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($mjeshter['first_name'] . ' ' . $mjeshter['last_name']) ?></h3>
                    <p><strong>Rajoni:</strong> <?= htmlspecialchars($mjeshter['municipality']) ?></p>
                    <p><strong>Numri Kontaktues:</strong> <?= htmlspecialchars($mjeshter['contact_number']) ?></p>
                    <p><strong>Shërbimet:</strong> <?= htmlspecialchars($mjeshter['sherbimet']) ?></p>
                    <a href="detajet.php?mjeshter_id=<?= $mjeshter['mjeshter_id'] ?>" class="btn-details">
                        <button>Mëso më shumë detaje</button>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #888;">Nuk u gjetën mjeshtra për këtë profesion.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
