<?php
$host = 'localhost:4307'; // Adresa e serverit dhe porta
$dbname = 'Gjeje_Mjeshtrin'; // Emri i bazës së të dhënave që dëshiron të krijosh
$username = 'root'; // Përdoruesi i MySQL
$password = ''; // Fjalëkalimi i MySQL

try {
    // Lidhje me serverin pa specifikuar një bazë të dhënash
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Krijo bazën e të dhënave nëse nuk ekziston
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8 COLLATE utf8_general_ci");

    // Lidhje me bazën e sapo krijuar
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Opsionale: Printo një mesazh për sukses
    // echo "Lidhja me bazën e të dhënave '$dbname' u realizua me sukses!";
} catch (PDOException $e) {
    // die("Lidhja me bazën e të dhënave dështoi: " . $e->getMessage());
}
?>
