<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="icon" type="image/png" href="Images/FINAL_LOGO.png">
   <link href="CSS/signup.css" rel="stylesheet">
</head>
<body>

<?php
// Përfshi lidhjen me bazën e të dhënave
require_once 'dbconnection.php';

try {
    // Krijo tabelën nëse nuk ekziston
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL,
        contact_number VARCHAR(15) NOT NULL,
        municipality VARCHAR(50) NOT NULL,
        address VARCHAR(255) NOT NULL,
        gender ENUM('Mashkull', 'Femër') NOT NULL,
        role ENUM('Qytetar', 'Mjeshter', 'Admin') NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
} catch (PDOException $e) {
    die("Gabim gjatë krijimit të tabelës: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifiko nëse çelësat ekzistojnë në $_POST për të parandaluar gabimet
    $first_name = $_POST['emri'] ?? null;
    $last_name = $_POST['mbiemri'] ?? null;
    $email = $_POST['email'] ?? null;
    $contact_number = $_POST['numri'] ?? null;
    $municipality = $_POST['rajoni'] ?? null;
    $address = $_POST['adresa'] ?? null;
    $gender = $_POST['gjinia'] ?? null;
    $role = $_POST['roli'] ?? null;
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    // Kontrollo që të gjitha të dhënat të jenë të pranishme
    if ($first_name && $last_name && $email && $contact_number && $municipality && $address && $gender && $role && $password) {
        try {
            // Shto të dhënat në tabelën 'users'
            $sql = "INSERT INTO users (first_name, last_name, email, contact_number, municipality, address, gender, role, password)
                    VALUES (:first_name, :last_name, :email, :contact_number, :municipality, :address, :gender, :role, :password)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':contact_number' => $contact_number,
                ':municipality' => $municipality,
                ':address' => $address,
                ':gender' => $gender,
                ':role' => $role,
                ':password' => $password,
            ]);

            // Ridrejto në faqen e hyrjes
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            echo "Gabim gjatë regjistrimit: " . $e->getMessage();
        }
    } else {
        echo "<p style='color: red; text-align: center;'>Ju lutem plotësoni të gjitha fushat!</p>";
    }
}
?>



<div class="container">
        <!-- Left Side: Form -->
        <div class="form-container">
            <h2>Krijo një llogari</h2>
            <form action="signup.php" method="POST">
                <label for="emri">Emri</label>
                <input type="text" id="emri" name="emri" placeholder="Shkruani emrin" required>

                <label for="mbiemri">Mbiemri</label>
                <input type="text" id="mbiemri" name="mbiemri" placeholder="Shkruani mbiemrin" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Shkruani email-in" required>

                <label for="numri">Numri Kontaktues</label>
                <input type="text" id="numri" name="numri" placeholder="Shkruani numrin tuaj" required>

                <label for="rajoni">Rajoni</label>
                <select id="rajoni" name="rajoni" required>
                    <option value="">Zgjidh Rajonin</option>
                    <option value="Prishtina">Prishtina</option>
                    <option value="Mitrovica">Mitrovica</option>
                    <option value="Peja">Peja</option>
                    <option value="Prizreni">Prizreni</option>
                    <option value="Ferizaji">Ferizaji</option>
                    <option value="Gjilani">Gjilani</option>
                    <option value="Gjakova">Gjakova</option> 
                </select>

                <label for="adresa">Adresa</label>
                <input type="text" id="adresa" name="adresa" placeholder="Shkruani adresën" required>

                <label for="gjinia">Gjinia</label>
                <select id="gjinia" name="gjinia" required>
                    <option value="">Zgjidh Gjininë</option>
                    <option value="mashkull">Mashkull</option>
                    <option value="femer">Femër</option>
                </select>

                <label for="roli">Roli</label>
                <select id="roli" name="roli" required>
                    <option  value="">Zgjidh Rolin</option>
                    <option value="qytetar">Qytetar</option>
                    <option value="mjeshter">Mjeshtër</option>
                    <option value="admin">Admin</option>
                </select>

                <label for="password">Fjalëkalimi</label>
                <input type="password" id="password" name="password" placeholder="Shkruani fjalëkalimin" required>

                <button type="submit">Regjistrohu</button>
            </form>
        </div>

        <!-- Right Side: Info Section -->
        <div class="info-container">
    <img src="Images/FINAL_LOGO.png" alt="Logo">
    <div class="animated-text-container">
        <span class="animated-text" data-text="A ja ki numrin mjeshtrit?"></span>
        <span class="animated-text" data-text="Cili mjeshtër është ma i mirë?"></span>
        <span class="animated-text" data-text="Gjeje mjeshtrin që të duhet!"></span>
        <span class="animated-text" data-text="Kur është i lirë mjeshtri?"></span>
        <span class="animated-text" data-text="A është i shtrejtë?"></span>
        <span class="animated-text" data-text="Ku gjindet?"></span>
        <span class="animated-text" data-text="A e kryn punën mirë?"></span>
        <span class="animated-text" data-text="Ku me reklamu punen tem?"></span>
        <span class="animated-text" data-text="Nuk po e gjen shtëpinë!"></span>
        <span class="animated-text" data-text="Veq ki me zgjedh!"></span>
    </div>
</div>



    </div>

    <script src="JS/animated.js"></script>
</body>
</html>
