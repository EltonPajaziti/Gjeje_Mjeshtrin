<?php
require_once 'dbconnection.php';

try {
    // Krijo tabelën "statuset_rezervime"
    $sql = "CREATE TABLE IF NOT EXISTS statuset_rezervime (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rezervim_id INT NOT NULL,
        status ENUM('Në pritje', 'Anuluar', 'Aprovuar', 'Përfunduar') DEFAULT 'Në pritje',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (rezervim_id) REFERENCES rezervimet(id) ON DELETE CASCADE
    )";

    $pdo->exec($sql);

    // echo "Tabela 'statuset_rezervime' u krijua me sukses dhe u lidh me tabelën 'rezervimet'.";
} catch (PDOException $e) {
    die("Gabim gjatë krijimit të tabelës 'statuset_rezervime': " . $e->getMessage());
}

// Shfaqja e rezervimeve me status "Në pritje", "Anuluar", ose "Aprovuar"
try {
    $stmt = $pdo->prepare("SELECT r.id AS rezervim_id, r.problemi, r.specifika, r.data, r.koha, r.created_at, r.menyra_pageses,
                                  u.first_name, u.last_name, u.profile_picture, u.municipality, u.contact_number, u.email,
                                  m.sherbimet, m.cmimi, s.status
                           FROM rezervimet r
                           INNER JOIN mjeshtrat m ON r.mjeshter_id = m.id
                           INNER JOIN users u ON m.user_id = u.id
                           INNER JOIN statuset_rezervime s ON r.id = s.rezervim_id
                           WHERE s.status IN ('Në pritje', 'Anuluar', 'Aprovuar')
                           ORDER BY r.created_at DESC");
    $stmt->execute();
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
    <title>Rezervimet</title>
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
    </style>
</head>
<body>
    <h1 style="text-align: center;">Rezervimet Tuaja</h1>

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
                <div style="text-align: center;">
                    <?php if (!empty($rezervim['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($rezervim['profile_picture']) ?>" alt="Foto e profilit" class="profile">
                    <?php else: ?>
                        <img src="PROFILE/default_profile.png" alt="Foto e profilit" class="profile">
                    <?php endif; ?>
                </div>
                <h2><?= htmlspecialchars($rezervim['first_name'] . ' ' . $rezervim['last_name']) ?></h2>
                <p><strong>Adresa:</strong> <?= htmlspecialchars($rezervim['municipality']) ?></p>
                <p><strong>Numri kontaktues:</strong> <?= htmlspecialchars($rezervim['contact_number']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($rezervim['email']) ?></p>
                <p><strong>Shërbimet:</strong> <?= htmlspecialchars($rezervim['sherbimet']) ?></p>
                <p><strong>Çmimi:</strong> <?= htmlspecialchars($rezervim['cmimi']) ?> €</p>
                <p><strong>Koha kur e ke rezervuar:</strong> <?= htmlspecialchars($rezervim['created_at']) ?></p>
                <p><strong>Problemi:</strong> <?= htmlspecialchars($rezervim['problemi']) ?></p>
                <p><strong>Specifika:</strong> <?= htmlspecialchars($rezervim['specifika']) ?></p>
                <p><strong>Data kur dëshironi që mjeshtri të vie:</strong> <?= htmlspecialchars($rezervim['data']) ?></p>
                <p><strong>Koha kur dëshironi që mjeshtri të vie:</strong> <?= htmlspecialchars($rezervim['koha']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align: center; color: #555;">Nuk keni rezervime në pritje, të anuluara ose të aprovuara.</p>
    <?php endif; ?>
</body>
</html>
