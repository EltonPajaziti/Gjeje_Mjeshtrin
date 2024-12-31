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

// Përditëso statusin në tabelën `statuset_rezervime` kur klikohen butonat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rezervim_id'], $_POST['status'])) {
    $rezervim_id = $_POST['rezervim_id'];
    $new_status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE statuset_rezervime SET status = :status WHERE rezervim_id = :rezervim_id");
        $stmt->execute([':status' => $new_status, ':rezervim_id' => $rezervim_id]);
        header("Location: klientet_ne_pritje.php"); // Rifresko faqen për të reflektuar ndryshimet
        exit;
    } catch (PDOException $e) {
        die("Gabim gjatë përditësimit të statusit: " . $e->getMessage());
    }
}

// Shfaq klientët që kanë bërë rezervim për mjeshtrin dhe kanë status "Në pritje", "Aprovuar" ose "Anuluar"
try {
    $stmt = $pdo->prepare("
        SELECT r.id AS rezervim_id, r.problemi, r.specifika, r.data, r.koha, r.created_at, r.menyra_pageses,
               u.first_name, u.last_name, u.profile_picture, u.municipality, u.contact_number, u.email,
               m.sherbimet, m.cmimi, s.status
        FROM rezervimet r
        INNER JOIN mjeshtrat m ON r.mjeshter_id = m.id
        INNER JOIN users u ON r.user_id = u.id
        INNER JOIN statuset_rezervime s ON r.id = s.rezervim_id
        WHERE r.mjeshter_id = :mjeshter_id AND s.status IN ('Në pritje', 'Aprovuar', 'Anuluar')
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([':mjeshter_id' => $mjeshter_id]);
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
    <title>Klientët në Pritje</title>
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
            background-color: #f0f0f0;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }
        .status.pending {
            background-color: #e2964b;
        }
        .status.cancelled {
            background-color: #f44336;
            color: white;
        }
        .status.approved {
            background-color: #4caf50;
            color: white;
        }
        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
            font-weight: bold;
        }
        .cancel { background-color: #f44336; }
        .approve { background-color: #4caf50; }
        .complete { background-color: #ffa500; }
    </style>
</head>
<body>
    <h1 style="text-align: center;">Klientët në Pritje</h1>

    <?php if (!empty($rezervimet)): ?>
        <?php foreach ($rezervimet as $rezervim): ?>
            <div class="card">
                <div class="status <?php
                    switch ($rezervim['status']) {
                        case 'Në pritje': echo 'pending'; break;
                        case 'Anuluar': echo 'cancelled'; break;
                        case 'Aprovuar': echo 'approved'; break;
                    }
                ?>">
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
                <p><strong>Data kur dëshironi që mjeshtri të vie:</strong> <?= htmlspecialchars($rezervim['data']) ?></p>
                <p><strong>Koha kur dëshironi që mjeshtri të vie:</strong> <?= htmlspecialchars($rezervim['koha']) ?></p>
                <div class="buttons">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="rezervim_id" value="<?= htmlspecialchars($rezervim['rezervim_id']) ?>">
                        <button type="submit" name="status" value="Anuluar" class="cancel">Anulo</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="rezervim_id" value="<?= htmlspecialchars($rezervim['rezervim_id']) ?>">
                        <button type="submit" name="status" value="Aprovuar" class="approve">Aprovo</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="rezervim_id" value="<?= htmlspecialchars($rezervim['rezervim_id']) ?>">
                        <button type="submit" name="status" value="Përfunduar" class="complete">Eshtë Përfunduar</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align: center; color: #555;">Nuk ka klientë në pritje.</p>
    <?php endif; ?>
</body>
</html>
