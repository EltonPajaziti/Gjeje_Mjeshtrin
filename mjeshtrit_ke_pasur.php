<?php
require_once 'dbconnection.php';
session_start();

// Sigurohu që përdoruesi është i kyçur
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // ID-ja e qytetarit të kyçur

// Shfaq rezervimet e përfunduara për qytetarin e kyçur
try {
    $stmt = $pdo->prepare("
        SELECT r.id AS rezervim_id, r.problemi, r.specifika, r.data, r.koha, r.created_at, r.menyra_pageses,
               u.first_name AS mjeshter_name, u.last_name AS mjeshter_lastname, u.profile_picture, 
               m.sherbimet, m.cmimi, s.status
        FROM rezervimet r
        INNER JOIN mjeshtrat m ON r.mjeshter_id = m.id
        INNER JOIN users u ON m.user_id = u.id
        INNER JOIN statuset_rezervime s ON r.id = s.rezervim_id
        WHERE r.user_id = :user_id AND s.status = 'Përfunduar'
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $rezervimet = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervimet e Përfunduara</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        .card {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .card img.profile {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 10px auto;
            display: block;
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
        .status {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #4caf50;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;">Punët e Përfunduara</h1>

    <?php if (!empty($rezervimet)): ?>
        <?php foreach ($rezervimet as $rezervim): ?>
            <div class="card">
                <div class="status">
                    <?= htmlspecialchars($rezervim['status']) ?>
                </div>
                <img src="<?= htmlspecialchars($rezervim['profile_picture'] ?? 'PROFILE/default_profile.png') ?>" alt="Foto e mjeshtrit" class="profile">
                <h2><?= htmlspecialchars($rezervim['mjeshter_name'] . ' ' . $rezervim['mjeshter_lastname']) ?></h2>
                <p><strong>Shërbimet:</strong> <?= htmlspecialchars($rezervim['sherbimet']) ?></p>
                <p><strong>Çmimi:</strong> <?= htmlspecialchars($rezervim['cmimi']) ?> €</p>
                <p><strong>Koha kur e ke rezervuar:</strong> <?= htmlspecialchars($rezervim['created_at']) ?></p>
                <p><strong>Problemi:</strong> <?= htmlspecialchars($rezervim['problemi']) ?></p>
                <p><strong>Specifika:</strong> <?= htmlspecialchars($rezervim['specifika']) ?></p>
                <p><strong>Data kur mjeshtri e përfundoi punën:</strong> <?= htmlspecialchars($rezervim['data']) ?></p>
                <p><strong>Koha kur mjeshtri e përfundoi punën:</strong> <?= htmlspecialchars($rezervim['koha']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align: center; color: #555;">Nuk ka punë të përfunduara për ju.</p>
    <?php endif; ?>
</body>
</html>
