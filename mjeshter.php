<?php
require_once 'dbconnection.php';
session_start();

// Kontrollo nëse përdoruesi është i kyçur
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Merr të dhënat e përdoruesit nga baza e të dhënave
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
    // Krijo tabelën 'mjeshtrat' nëse nuk ekziston
    $sql = "CREATE TABLE IF NOT EXISTS mjeshtrat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        profesion VARCHAR(100) NOT NULL,
        sherbimet TEXT NOT NULL,
        cmimi DECIMAL(10, 2) NOT NULL,
        orari_punes VARCHAR(255),
       
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    // echo "Tabela 'mjeshtrat' u krijua me sukses.";
} catch (PDOException $e) {
    die("Gabim gjatë krijimit të tabelës 'mjeshtrat': " . $e->getMessage());
}

try {
    // Krijo tabelën 'pune_foto' nëse nuk ekziston
    $sql = "CREATE TABLE IF NOT EXISTS foto_pune (
        id INT AUTO_INCREMENT PRIMARY KEY,
        mjeshter_id INT NOT NULL,
        foto_path VARCHAR(255) NOT NULL,
        FOREIGN KEY (mjeshter_id) REFERENCES mjeshtrat(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    // echo "Tabela 'foto_pune' u krijua me sukses.";
} catch (PDOException $e) {
    die("Gabim gjatë krijimit të tabelës 'foto_pune': " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar</title>
    <link rel="stylesheet" href="CSS/navbar.css">
    <link rel="stylesheet" href="CSS/mjeshter_form.css">
    <link href="CSS/footer.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

</head>
<body>
<nav class="navbar">
        <ul class="navbar-list">
            <li class="navbar-item profile">
                <a href="profile.php">
                    <div class="profile-image"></div>
                    <span><?= htmlspecialchars($user_name); ?></span> <!-- Vendoset emri i përdoruesit -->
                </a>
            </li>
            <li class="navbar-item">
                <a href="mjeshtrit_ke_pasur.php">Klientat që ke pasur</a>
            </li>
            <li class="navbar-item">
                <a href="mjeshtrit_pritje.php">Klientat që i ke në pritje</a>
            </li>
            <li class="navbar-item">
                <a href="index.php">Dil</a>
            </li>
        </ul>
    </nav>

    <?php
// Kontrollo nëse përdoruesi është i kyçur
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Kontrollo nëse forma është dërguar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profesion = $_POST['profesion'] ?? null;
    $sherbimet = $_POST['sherbimet'] ?? null;
    $cmimi = $_POST['cmimi'] ?? null;
    $orari_punes = $_POST['orari_punes'] ?? null;

    // Kontrollo që të dhënat e kërkuara janë plotësuar
    if ($profesion && $sherbimet && $cmimi) {
        try {
            // Ruaj të dhënat në tabelën 'mjeshtrat'
            $sql = "INSERT INTO mjeshtrat (user_id, profesion, sherbimet, cmimi, orari_punes) 
                    VALUES (:user_id, :profesion, :sherbimet, :cmimi, :orari_punes)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $user_id,
                ':profesion' => $profesion,
                ':sherbimet' => $sherbimet,
                ':cmimi' => $cmimi,
                ':orari_punes' => $orari_punes,
            ]);

            $mjeshter_id = $pdo->lastInsertId(); // Merr ID-në e fundit të shtuar

            // Kontrollo dhe ruaj fotot në tabelën 'foto_pune'
            if (!empty($_FILES['foto_pune']['name'][0])) {
                foreach ($_FILES['foto_pune']['tmp_name'] as $index => $tmp_name) {
                    $file_name = $_FILES['foto_pune']['name'][$index];
                    $file_path = 'UPLOADS/' . uniqid() . '_' . $file_name;

                    // Lëviz foton në dosjen 'uploads'
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $sql = "INSERT INTO foto_pune (mjeshter_id, foto_path) 
                                VALUES (:mjeshter_id, :foto_path)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':mjeshter_id' => $mjeshter_id,
                            ':foto_path' => $file_path,
                        ]);
                    }
                }
            }

            // echo "<p style='color: green; text-align: center;'>Të dhënat u ruajtën me sukses!</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red; text-align: center;'>Gabim gjatë ruajtjes së të dhënave: " . $e->getMessage() . "</p>";
        }
    } else {
        // echo "<p style='color: red; text-align: center;'>Ju lutem plotësoni të gjitha fushat e kërkuara!</p>";
    }
}
?>

    <div class="form-container">
    <h2>Informatat për profesionin tuaj</h2>
    <form method="POST" enctype="multipart/form-data">

        <label for="profesion">Profesion:</label>
        <select id="profesion" name="profesion" required>
            <option value="">Zgjidhni profesionin</option>
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

        <label for="sherbimet">Shërbimet që i ofroni:</label>
        <textarea id="sherbimet" name="sherbimet" placeholder="Shkruani shërbimet që ofroni" required></textarea>

        <label for="cmimi">Çmimi:</label>
        <input type="number" id="cmimi" name="cmimi" placeholder="Shkruani çmimin në €" step="0.01" required>

        <label for="orari_punes">Orari i punës:</label>
        <input type="text" id="orari_punes" name="orari_punes" placeholder="Shkruani orarin e punës">

        <label for="foto_pune">Foto të punës tuaj:</label>
        <input type="file" id="foto_pune" name="foto_pune[]" multiple accept="image/*">

        <button type="submit">Ruaj të dhënat</button>
    </form>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
