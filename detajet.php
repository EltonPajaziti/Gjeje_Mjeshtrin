<?php
require_once 'dbconnection.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null;

// Kontrolloni nëse navigimi është bërë nga faqja "mjeshtrit_favorit.php"
$source = $_GET['source'] ?? null;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_favorites']) && $source !== 'favorites') {
    $mjeshter_id = $_POST['mjeshter_id'] ?? null;

    if ($user_id && $mjeshter_id) {
        try {
            // Kontrollo nëse mjeshtri është tashmë në të preferuarat
            $stmt = $pdo->prepare("SELECT 1 FROM mjeshtrat_favorit WHERE user_id = :user_id AND mjeshter_id = :mjeshter_id");
            $stmt->execute([':user_id' => $user_id, ':mjeshter_id' => $mjeshter_id]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                // Nëse ekziston, shfaq mesazhin dhe ndal ekzekutimin
                echo "<script>
                        alert('Ky mjeshtër tashmë është në listën tuaj të preferuar!');
                        window.history.back();
                      </script>";
                exit; // Ndalo më tej ekzekutimin për të shmangur shtimin dhe alerte të tjera
            } else {
                // Nëse nuk ekziston, shto mjeshtrin në të preferuarat
                $stmt = $pdo->prepare("INSERT INTO mjeshtrat_favorit (user_id, mjeshter_id) VALUES (:user_id, :mjeshter_id)");
                $stmt->execute([':user_id' => $user_id, ':mjeshter_id' => $mjeshter_id]);
                echo "<script>
                        alert('Mjeshtri u shtua në të preferuarat me sukses!');
                        window.history.back();
                      </script>";
                exit;
            }
        } catch (PDOException $e) {
            // Regjistro gabimet e tjera për qëllime debug-u
            error_log("Gabim gjatë shtimit në të preferuarat: " . $e->getMessage());
        }
    } else {
        echo "<script>
                alert('Të dhënat e paplota! Përdoruesi ose mjeshtri nuk u përcaktua.');
                window.history.back();
              </script>";
    }
}


try {
    // Pyetja SQL për të shtuar UNIQUE constraint
    $sql = "ALTER TABLE mjeshtrat_favorit
            ADD CONSTRAINT unique_user_mjeshter UNIQUE (user_id, mjeshter_id)";
    
    // Ekzekuto pyetjen
    $pdo->exec($sql);
    // echo "Constraint-i UNIQUE u shtua me sukses!";
} catch (PDOException $e) {
    // Kap gabimet nëse diçka shkon keq
    // echo "Gabim gjatë shtimit të constraint-it UNIQUE: " . $e->getMessage();
}
$user_id = $_SESSION['user_id'] ?? null;

// Shtimi i mjeshtrit në të preferuarat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_favorites'])) {
    $mjeshter_id = $_POST['mjeshter_id'] ?? null;

    if ($user_id && $mjeshter_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO mjeshtrat_favorit (user_id, mjeshter_id) VALUES (:user_id, :mjeshter_id)");
            $stmt->execute([':user_id' => $user_id, ':mjeshter_id' => $mjeshter_id]);
            echo "<script>alert('Mjeshtri u shtua në të preferuarat me sukses!');</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Gabim gjatë shtimit në të preferuarat: " . htmlspecialchars($e->getMessage()) . "');</script>";
        }
    } else {
        echo "<script>alert('Të dhënat e paplota! Përdoruesi ose mjeshtri nuk u përcaktua.');</script>";
    }
}

// Ruajtja e të dhënave në tabelën rezervimet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rezervo_mjeshtrin'])) {
    $mjeshter_id = $_POST['mjeshter_id'] ?? null;
    $problemi = $_POST['problemi'] ?? null;
    $specifika = $_POST['specifika'] ?? null;
    $data = $_POST['data'] ?? null;
    $koha = $_POST['koha'] ?? null;
    $menyra_pageses = $_POST['menyra_pageses'] ?? null;

    if ($user_id && $mjeshter_id && $problemi && $specifika && $data && $koha && $menyra_pageses) {
        try {
            $pdo->beginTransaction();

            // Ruaj rezervimin
            $stmt = $pdo->prepare("INSERT INTO rezervimet (user_id, mjeshter_id, problemi, specifika, data, koha, menyra_pageses)
                                   VALUES (:user_id, :mjeshter_id, :problemi, :specifika, :data, :koha, :menyra_pageses)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':mjeshter_id' => $mjeshter_id,
                ':problemi' => $problemi,
                ':specifika' => $specifika,
                ':data' => $data,
                ':koha' => $koha,
                ':menyra_pageses' => $menyra_pageses
            ]);

            // Merr ID-në e fundit të rezervimit
            $rezervim_id = $pdo->lastInsertId();

            // Shto statusin "Në pritje" për rezervimin
            $stmt = $pdo->prepare("INSERT INTO statuset_rezervime (rezervim_id, status) VALUES (:rezervim_id, 'Në pritje')");
            $stmt->execute([':rezervim_id' => $rezervim_id]);

            $pdo->commit();

            echo "<script>
                    alert('Rezervimi u krye me sukses!');
                    window.location.href = 'qytetari.php';
                  </script>";
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "<script>
                    alert('Gabim gjatë ruajtjes së rezervimit: " . htmlspecialchars($e->getMessage()) . "');
                  </script>";
        }
    } else {
        echo "<script>
                alert('Ju lutemi plotësoni të gjitha fushat!');
              </script>";
    }
}

try {
    // Krijo tabelën "rezervimet"
    $sql = "CREATE TABLE IF NOT EXISTS rezervimet (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        mjeshter_id INT NOT NULL,
        problemi TEXT NOT NULL,
        specifika TEXT NOT NULL,
        data DATE NOT NULL,
        koha TIME NOT NULL,
        menyra_pageses ENUM('Para në dorë', 'Kartelë bankare') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (mjeshter_id) REFERENCES mjeshtrat(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
} catch (PDOException $e) {
    die("Gabim gjatë krijimit të tabelës: " . $e->getMessage());
}

try {
    $sql = "CREATE TABLE IF NOT EXISTS mjeshtrat_favorit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        mjeshter_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (mjeshter_id) REFERENCES mjeshtrat(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
} catch (PDOException $e) {
    die("Gabim gjatë krijimit të tabelës: " . $e->getMessage());
}

$mjeshter_id = $_GET['mjeshter_id'] ?? null;

if (!$mjeshter_id) {
    echo "<h2 style='text-align: center; color: red;'>Mjeshtri nuk u përcaktua!</h2>";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT u.first_name, u.last_name, u.profile_picture, u.municipality, u.contact_number, u.email,
                           m.sherbimet, m.cmimi, m.orari_punes
                           FROM mjeshtrat m
                           INNER JOIN users u ON m.user_id = u.id
                           WHERE m.id = :mjeshter_id");
    $stmt->execute([':mjeshter_id' => $mjeshter_id]);
    $mjeshter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mjeshter) {
        echo "<h2 style='text-align: center; color: red;'>Mjeshtri nuk u gjet!</h2>";
        exit;
    }

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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
            position: relative;
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
        .favorite-icon {
            position: absolute;
            top: 10px;
            left: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fbc02d;
            border: none;
            padding: 10px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            color: #333;
        }
        .favorite-icon:hover {
            background-color: #e2964b;
        }
        .favorite-icon i {
            color: #e2964b;
            transition: color 0.3s ease;
        }
        .favorite-icon:hover i {
            color: red;
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
        .form-container {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 20px auto;
            display: none;
        }
        .form-container h3 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-container label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        .form-container textarea,
        .form-container input[type="date"],
        .form-container input[type="radio"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-container .radio-group {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
        }
        .form-container button {
            width: 100%;
            background-color: #fbc02d;
            color: #333;
            border: none;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #e2964b;
        }
    </style>
</head>
<body>
    <div class="card">
    <?php if ($source !== 'favorites'): ?>
    <form method="POST" style="display: inline;">
        <input type="hidden" name="mjeshter_id" value="<?= htmlspecialchars($mjeshter_id) ?>">
        <button type="submit" name="add_to_favorites" class="favorite-icon">
            <i class="fa-regular fa-heart"></i> Shto në të preferuarat
        </button>
    </form>
<?php endif; ?>

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
        <p><strong>Shërimet:</strong> <?= htmlspecialchars($mjeshter['sherbimet']) ?></p>
        <p><strong>Çmimi:</strong> <?= htmlspecialchars($mjeshter['cmimi']) ?> €</p>
        <p><strong>Orari i punës:</strong> <?= htmlspecialchars($mjeshter['orari_punes']) ?></p>
        <div class="photos">
            <h3>Foto të punës:</h3>
            <?php foreach ($foto_pune as $foto): ?>
                <img src="<?= htmlspecialchars($foto['foto_path']) ?>" alt="Foto e punës">
            <?php endforeach; ?>
        </div>
        <button onclick="showReservationForm()">Dua ta rezervoj!</button>
    </div>

    <div class="form-container" id="reservation-form">
        <h3>Rezervo Mjeshtrin Tuaj!</h3>
        <form action="" method="POST">
            <input type="hidden" name="mjeshter_id" value="<?= htmlspecialchars($mjeshter_id) ?>">

            <label for="problemi">Problemi:</label>
            <textarea id="problemi" name="problemi" rows="4" required></textarea>

            <label for="specifika">Specifika:</label>
            <textarea id="specifika" name="specifika" rows="4" required></textarea>

            <label for="data">Data kur dëshironi që të vie Mjeshtri:</label>
            <input type="date" id="data" name="data" required>

            <label for="koha">Koha kur dëshironi që të vie Mjeshtri:</label>
            <input type="time" id="koha" name="koha" required>

            <label>Mënyra e Pagesës:</label>
            <div class="radio-group">
                <label><input type="radio" name="menyra_pageses" value="Para në dorë" required> Me para në dorë</label>
                <label><input type="radio" name="menyra_pageses" value="Kartelë bankare" required> Me kartelë bankare</label>
            </div>

            <button type="submit" name="rezervo_mjeshtrin">Rezervo</button>
        </form>
    </div>

    <script>
        function showReservationForm() {
            document.getElementById('reservation-form').style.display = 'block';
        }
    </script>
</body>
</html>
