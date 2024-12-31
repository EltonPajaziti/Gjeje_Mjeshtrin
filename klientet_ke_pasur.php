<?php
require_once 'dbconnection.php';
session_start();

// Sigurohu që përdoruesi është i kyçur
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // ID-ja e mjeshtrit të kyçur

// Kontrollo nëse ky përdorues është mjeshtër
try {
    $stmt = $pdo->prepare("SELECT id FROM mjeshtrat WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $mjeshter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mjeshter) {
        die("Ky përdorues nuk i ka plotësuar ende të dhënat që duhet t'i plotësoj një mjeshtër! Plotësoji ato, CKA PO PRET!?");
    }
    $mjeshter_id = $mjeshter['id'];
} catch (PDOException $e) {
    die("Gabim gjatë verifikimit të mjeshtrit: " . $e->getMessage());
}

// Shfaq klientët që kanë bërë rezervim për mjeshtrin dhe kanë status "Përfunduar"
try {
    $stmt = $pdo->prepare("
        SELECT r.id AS rezervim_id, r.problemi, r.specifika, r.data, r.koha, r.created_at, r.menyra_pageses,
               u.first_name, u.last_name, u.profile_picture, u.municipality, u.contact_number, u.email,
               m.sherbimet, m.cmimi, s.status, v.koment, v.vleresim
        FROM rezervimet r
        INNER JOIN mjeshtrat m ON r.mjeshter_id = m.id
        INNER JOIN users u ON r.user_id = u.id
        INNER JOIN statuset_rezervime s ON r.id = s.rezervim_id
        LEFT JOIN vleresimet v ON r.id = v.rezervim_id
        WHERE r.mjeshter_id = :mjeshter_id AND s.status = 'Përfunduar'
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([':mjeshter_id' => $mjeshter_id]);
    $rezervimet_perfunduara = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .view-review-button {
            display: block;
            margin: 20px auto 0;
            padding: 10px 20px;
            background-color: #e2964b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
        }
        .view-review-button:hover {
            background-color: #531f11;
        }
        .review-content {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
    <script>
        function toggleReviewContent(id) {
            const content = document.getElementById(`review-content-${id}`);
            content.style.display = content.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</head>
<body>
    <h1 style="text-align: center;">Rezervimet e Përfunduara</h1>

    <?php if (!empty($rezervimet_perfunduara)): ?>
        <?php foreach ($rezervimet_perfunduara as $rezervim): ?>
            <div class="card">
                <div class="status">
                    <?= htmlspecialchars($rezervim['status']) ?>
                </div>
                <img src="<?= htmlspecialchars($rezervim['profile_picture'] ?? 'PROFILE/default_profile.png') ?>" alt="Foto e profilit" class="profile">
                <h2><?= htmlspecialchars($rezervim['first_name'] . ' ' . $rezervim['last_name']) ?></h2>
                <p><strong>Rajoni:</strong> <?= htmlspecialchars($rezervim['municipality']) ?></p>
                <p><strong>Adresa:</strong> <?= htmlspecialchars($rezervim['municipality']) ?></p>
                <p><strong>Numri Kontaktues:</strong> <?= htmlspecialchars($rezervim['contact_number']) ?></p>
                <p><strong>Koha kur e ke rezervuar:</strong> <?= htmlspecialchars($rezervim['created_at']) ?></p>
                <p><strong>Problemi:</strong> <?= htmlspecialchars($rezervim['problemi']) ?></p>
                <p><strong>Specifika:</strong> <?= htmlspecialchars($rezervim['specifika']) ?></p>
                <p><strong>Data kur mjeshtri e përfundoi punën:</strong> <?= htmlspecialchars($rezervim['data']) ?></p>
                <p><strong>Koha kur mjeshtri e përfundoi punën:</strong> <?= htmlspecialchars($rezervim['koha']) ?></p>

                <?php if (!empty($rezervim['koment']) || !empty($rezervim['vleresim'])): ?>
                    <button class="view-review-button" onclick="toggleReviewContent(<?= $rezervim['rezervim_id'] ?>)">Shiko vlerësimin e klientit</button>
                    <div class="review-content" id="review-content-<?= $rezervim['rezervim_id'] ?>">
                        <p><strong>Vlerësimi:</strong> <?= htmlspecialchars($rezervim['vleresim']) ?>/10</p>
                        <p><strong>Komenti:</strong> <?= htmlspecialchars($rezervim['koment']) ?></p>
                    </div>
                <?php else: ?>
                    <p style="color: #888; text-align: center;">Nuk ka ende vlerësim nga klienti.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align: center; color: #555;">Nuk ka rezervime të përfunduara për këtë mjeshtër.</p>
    <?php endif; ?>
</body>
</html>
