<?php
require_once 'dbconnection.php';
session_start();

// Sigurohu që përdoruesi është i kyçur
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // ID-ja e qytetarit të kyçur

// Trajto formularin për vlerësim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rezervim_id'])) {
    $rezervim_id = $_POST['rezervim_id'];
    $comment = $_POST['comment'];
    $rating = $_POST['rating'];

    try {
        // Kontrollo nëse vlerësimi tashmë ekziston
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM vleresimet WHERE rezervim_id = :rezervim_id AND qytetar_id = :qytetar_id");
        $stmt->execute([
            ':rezervim_id' => $rezervim_id,
            ':qytetar_id' => $user_id
        ]);
        $already_rated = $stmt->fetchColumn() > 0;

        if ($already_rated) {
            $error = "Ju tashmë e keni vlerësuar këtë punë.";
        } else {
            // Merr mjeshter_id nga rezervimi përkatës
            $stmt = $pdo->prepare("SELECT mjeshter_id FROM rezervimet WHERE id = :rezervim_id AND user_id = :user_id");
            $stmt->execute([
                ':rezervim_id' => $rezervim_id,
                ':user_id' => $user_id
            ]);
            $rezervim = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rezervim) {
                $error = "Rezervimi nuk ekziston ose nuk është i lidhur me këtë përdorues.";
            } else {
                $mjeshter_id = $rezervim['mjeshter_id'];

                // Shto të dhënat në tabelën "vleresimet"
                $stmt = $pdo->prepare("
                    INSERT INTO vleresimet (qytetar_id, mjeshter_id, rezervim_id, koment, vleresim)
                    VALUES (:qytetar_id, :mjeshter_id, :rezervim_id, :koment, :vleresim)
                ");
                $stmt->execute([
                    ':qytetar_id' => $user_id,
                    ':mjeshter_id' => $mjeshter_id,
                    ':rezervim_id' => $rezervim_id,
                    ':koment' => $comment,
                    ':vleresim' => $rating
                ]);
                $success = "Vlerësimi u ruajt me sukses!";
            }
        }
    } catch (PDOException $e) {
        $error = "Gabim gjatë shtimit të vlerësimit: " . $e->getMessage();
    }
}

// Shfaq rezervimet e përfunduara për qytetarin e kyçur dhe kontrollo nëse janë vlerësuar
try {
    $stmt = $pdo->prepare("
        SELECT r.id AS rezervim_id, r.problemi, r.specifika, r.data, r.koha, r.created_at, r.menyra_pageses,
               u.first_name AS mjeshter_name, u.last_name AS mjeshter_lastname, u.profile_picture, 
               m.sherbimet, m.cmimi, s.status,
               (SELECT COUNT(*) FROM vleresimet v WHERE v.rezervim_id = r.id AND v.qytetar_id = :user_id) AS rated
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

// Krijo tabelën "vleresimet" nëse nuk ekziston
try {
    $sql = "CREATE TABLE IF NOT EXISTS vleresimet (
        id INT AUTO_INCREMENT PRIMARY KEY,
        qytetar_id INT NOT NULL,
        mjeshter_id INT NOT NULL,
        rezervim_id INT NOT NULL,
        koment TEXT,
        vleresim INT NOT NULL CHECK (vleresim BETWEEN 1 AND 10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (qytetar_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (mjeshter_id) REFERENCES mjeshtrat(id) ON DELETE CASCADE,
        FOREIGN KEY (rezervim_id) REFERENCES rezervimet(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
} catch (PDOException $e) {
    die("Gabim gjatë krijimit të tabelës 'vleresimet': " . $e->getMessage());
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
        .review-button {
            display: block;
            margin: 20px auto 0;
            padding: 10px 20px;
            background-color: #e2964b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
        }
        .review-button:hover {
            background-color: #531f11;
        }
        .review-form {
            display: none;
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fefefe;
        }
        .review-form textarea {
            width: 100%;
            height: 100px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 14px;
        }
        .review-form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        .submit-review {
            display: block;
            margin: 0 auto;
            padding: 10px 20px;
            background-color: #e2964b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
        }
        .submit-review:hover {
            background-color: #531f11;
        }
    </style>
    <script>
        function toggleReviewForm(id) {
            const form = document.getElementById(`review-form-${id}`);
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</head>
<body>
    <h1 style="text-align: center;">Punët e Përfunduara</h1>

    <?php if (!empty($rezervimet)): ?>
        <?php if (isset($success)) echo "<p style='color: green; text-align: center;'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p style='color: red; text-align: center;'>$error</p>"; ?>
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
                <?php if (!$rezervim['rated']): ?>
                    <button class="review-button" onclick="toggleReviewForm(<?= $rezervim['rezervim_id'] ?>)">Dëshiron që ta vlerësosh punën e mjeshtrit!</button>
                    <div class="review-form" id="review-form-<?= $rezervim['rezervim_id'] ?>">
                        <form method="POST">
                            <textarea name="comment" placeholder="Shkruani komentin tuaj për mjeshtrin..." required></textarea>
                            <select name="rating" required>
                                <option value="">Zgjidh një vlerësim</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                            <input type="hidden" name="rezervim_id" value="<?= $rezervim['rezervim_id'] ?>">
                            <button type="submit" class="submit-review">Vlerëso</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align: center; color: #555;">Nuk ka punë të përfunduara për ju.</p>
    <?php endif; ?>
</body>
</html>
