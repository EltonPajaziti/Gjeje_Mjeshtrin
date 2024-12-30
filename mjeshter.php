<?php
require_once 'dbconnection.php';
session_start();

$upload_dir = 'PUNA/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $profile_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : 'PROFILE/default_profile.png';
    $user_name = $user['first_name'] . ' ' . $user['last_name'];
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave: " . $e->getMessage());
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS mjeshtrat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        profesion VARCHAR(100) NOT NULL,
        sherbimet TEXT NOT NULL,
        cmimi DECIMAL(10, 2) NOT NULL,
        orari_punes VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS foto_pune (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mjeshter_id INT NOT NULL,
        foto_path VARCHAR(255) NOT NULL,
        FOREIGN KEY (mjeshter_id) REFERENCES mjeshtrat(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    die("Gabim gjatë krijimit të tabelave: " . $e->getMessage());
}

// Kontrollo nëse përdoruesi ka të dhëna të ruajtura në tabelën mjeshtrat
try {
    $stmt = $pdo->prepare("SELECT * FROM mjeshtrat WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $mjeshter = $stmt->fetch(PDO::FETCH_ASSOC);

    $fotos = [];
    if ($mjeshter) {
        $stmt = $pdo->prepare("SELECT id, foto_path FROM foto_pune WHERE mjeshter_id = :mjeshter_id");
        $stmt->execute([':mjeshter_id' => $mjeshter['id']]);
        $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave të mjeshtrit: " . $e->getMessage());
}

// Kontrollo nëse të dhënat mungojnë dhe shfaq formularin
$showForm = !$mjeshter;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profesion = $_POST['profesion'] ?? null;
    $sherbimet = $_POST['sherbimet'] ?? null;
    $cmimi = $_POST['cmimi'] ?? null;
    $orari_punes = $_POST['orari_punes'] ?? null;

    if ($profesion && $sherbimet && $cmimi) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM mjeshtrat WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            $mjeshter = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($mjeshter) {
                $sql = "UPDATE mjeshtrat SET profesion = :profesion, sherbimet = :sherbimet, cmimi = :cmimi, orari_punes = :orari_punes WHERE user_id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':profesion' => $profesion,
                    ':sherbimet' => $sherbimet,
                    ':cmimi' => $cmimi,
                    ':orari_punes' => $orari_punes
                ]);
                $mjeshter_id = $mjeshter['id'];
            } else {
                $sql = "INSERT INTO mjeshtrat (user_id, profesion, sherbimet, cmimi, orari_punes) VALUES (:user_id, :profesion, :sherbimet, :cmimi, :orari_punes)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':profesion' => $profesion,
                    ':sherbimet' => $sherbimet,
                    ':cmimi' => $cmimi,
                    ':orari_punes' => $orari_punes
                ]);
                $mjeshter_id = $pdo->lastInsertId();
            }

            if (!empty($_FILES['foto_pune']['name'][0])) {
                foreach ($_FILES['foto_pune']['tmp_name'] as $index => $tmp_name) {
                    $file_name = $_FILES['foto_pune']['name'][$index];
                    $unique_name = uniqid() . '_' . basename($file_name);
                    $target_path = $upload_dir . $unique_name;

                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $sql = "INSERT INTO foto_pune (mjeshter_id, foto_path) VALUES (:mjeshter_id, :foto_path)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':mjeshter_id' => $mjeshter_id,
                            ':foto_path' => $target_path
                        ]);
                    } else {
                        echo "<p style='color: red;'>Gabim gjatë ngarkimit të fotos: $file_name</p>";
                    }
                }
            }

            if (!empty($_POST['delete_foto'])) {
                foreach ($_POST['delete_foto'] as $foto_id) {
                    $stmt = $pdo->prepare("SELECT foto_path FROM foto_pune WHERE id = :id");
                    $stmt->execute([':id' => $foto_id]);
                    $foto = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($foto) {
                        unlink($foto['foto_path']);
                        $stmt = $pdo->prepare("DELETE FROM foto_pune WHERE id = :id");
                        $stmt->execute([':id' => $foto_id]);
                    }
                }
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Gabim gjatë ruajtjes së të dhënave: " . $e->getMessage() . "</p>";
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mjeshtri</title>
    <link rel="stylesheet" href="CSS/navbar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/mjeshter_form.css">
    <link href="CSS/footer.css" rel="stylesheet">
    <style>
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px auto;
            max-width: 600px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card img {
            max-width: 100px;
            height: auto;
            margin-right: 5px;
        }
        .photos {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        button {
            background-color: rgba(251, 232, 176, 1);
            color: #333;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: #e2964b;
        }
        #edit-form {
            max-width: 600px;
            margin: 0 auto;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        form button {
            align-self: flex-start;
        }
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        .container {
            flex: 1;
        }

        #ruaj-btn{
            margin-bottom: 45px;
        }
    </style>
</head>
<body>
<nav class="navbar">
    <ul class="navbar-list">
        <li class="navbar-item profile">
            <a href="profile.php">
                <div class="profile-image" style="background-image: url('<?= htmlspecialchars($profile_picture) ?>');"></div>
                <span><?= htmlspecialchars($user_name) ?></span>
            </a>
        </li>
        <li class="navbar-item">
            <a href="mjeshtrit_ke_pasur.php">Klientët që ke pasur</a>
        </li>
        <li class="navbar-item">
            <a href="mjeshtrit_pritje.php">Klientët që i ke në pritje</a>
        </li>
        <li class="navbar-item">
            <a href="index.php">Dil</a>
        </li>
    </ul>
</nav>

<div class="container">
    <?php if (!$mjeshter): ?>
        <div id="edit-form">
            <h2>Ju lutemi plotësoni të dhënat për profesionin tuaj</h2>
            <form method="POST" enctype="multipart/form-data">
                <label for="profesion">Profesion:</label>
                <select id="profesion" name="profesion" required>
                    <option value="">Zgjidhni profesionin</option>
                    <option value="Elektricist">Elektricist</option>
                    <option value="Moler">Moler</option>
                    <!-- Shto opsionet tjera -->
                </select>

                <label for="sherbimet">Shërbimet që ofroni:</label>
                <textarea id="sherbimet" name="sherbimet" placeholder="Shkruani shërbimet që ofroni" required></textarea>

                <label for="cmimi">Çmimi:</label>
                <input type="number" id="cmimi" name="cmimi" placeholder="Shkruani çmimin në €" step="0.01" required>

                <label for="orari_punes">Orari i punës:</label>
                <input type="text" id="orari_punes" name="orari_punes" placeholder="Shkruani orarin e punës">

                <label for="foto_pune">Shto foto të punës:</label>
                <input type="file" id="foto_pune" name="foto_pune[]" multiple accept="image/*">

                <button style="margin-bottom: 50px;" type="submit">Ruaj të dhënat</button>
            </form>
        </div>
    <?php else: ?>
        <div class="card" id="mjeshter-card">
            <h3>Profesion: <?= htmlspecialchars($mjeshter['profesion']) ?></h3>
            <p>Shërbimet: <?= htmlspecialchars($mjeshter['sherbimet']) ?></p>
            <p>Çmimi: <?= htmlspecialchars($mjeshter['cmimi']) ?> €</p>
            <p>Orari i punës: <?= htmlspecialchars($mjeshter['orari_punes']) ?></p>
            <div class="photos">
                <?php foreach ($fotos as $foto): ?>
                    <img src="<?= htmlspecialchars($foto['foto_path']) ?>" alt="Foto pune">
                <?php endforeach; ?>
            </div>
            <button id="ndrysho-btn" onclick="shfaqFormen()">Ndrysho të dhënat</button>
        </div>
    <?php endif; ?>
</div>


    <div id="edit-form" style="display:none;">
        <h2>Informatat për profesionin tuaj</h2>
        <form method="POST" enctype="multipart/form-data">
            <label for="profesion">Profesion:</label>
            <select id="profesion" name="profesion" required>
                <option value="<?= htmlspecialchars($mjeshter['profesion']) ?>" selected><?= htmlspecialchars($mjeshter['profesion']) ?></option>
                <option value="Elektricist">Elektricist</option>
                <option value="Moler">Moler</option>
                <option value="Mekanik">Mekanik</option>
                <option value="Kopshtar">Kopshtar</option>
                <option value="Mirëmbajtës">Mirëmbajtës i shtëpisë</option>
                <option value="Hidraulik">Hidraulik</option>
                <option value="Pllakaxhi">Pllakaxhi</option>
                <option value="Murator">Murator</option>
                <option value="Zdrukthtar">Zdrukthtar</option>
                <option value="Kondicioner">Mjeshtër për ngrohje dhe kondicioner</option>
                <option value="Oxhakpastrues">Oxhakpastrues</option>
                <option value="Izolues">Izolues</option>
            </select>

            <label for="sherbimet">Shërbimet që ofroni:</label>
            <textarea id="sherbimet" name="sherbimet" placeholder="Shkruani shërbimet që ofroni" required><?= htmlspecialchars($mjeshter['sherbimet']) ?></textarea>

            <label for="cmimi">Çmimi:</label>
            <input type="number" id="cmimi" name="cmimi" placeholder="Shkruani çmimin në €" step="0.01" value="<?= htmlspecialchars($mjeshter['cmimi']) ?>" required>

            <label for="orari_punes">Orari i punës:</label>
            <input type="text" id="orari_punes" name="orari_punes" placeholder="Shkruani orarin e punës" value="<?= htmlspecialchars($mjeshter['orari_punes']) ?>">

            <label for="foto_pune">Fotot ekzistuese:</label>
            <?php foreach ($fotos as $foto): ?>
                <div>
                    <img src="<?= htmlspecialchars($foto['foto_path']) ?>" alt="Foto" style="max-width: 100px;">
                    <label>
                        <input type="checkbox" name="delete_foto[]" value="<?= $foto['id'] ?>"> Fshi këtë foto
                    </label>
                </div>
            <?php endforeach; ?>

            <label for="foto_pune">Shto foto të reja:</label>
            <input type="file" id="foto_pune" name="foto_pune[]" multiple accept="image/*">

            <button type="submit" id="ruaj-btn">Ruaj të dhënat</button>
        </form>
    </div>

</div>

<?php include 'footer.php'; ?>
<script>
    function shfaqFormen() {
        document.getElementById('ndrysho-btn').style.display = 'none';
        document.getElementById('mjeshter-card').style.display = 'none';
        document.getElementById('edit-form').style.display = 'block';
    }
</script>

</body>
</html>
