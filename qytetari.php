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
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/navbar.css">
    <link rel="stylesheet" href="CSS/footer.css">
    <style>
        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-image: url('<?= htmlspecialchars($profile_picture); ?>');
            background-size: cover;
            background-position: center;
            border: none; /* Largon kufirin */
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 20px;
            max-width: 1200px;
            margin: auto;
        }

        .card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
        }

        .card a {
            text-decoration: none;
            color: #333;
        }

        .card img {
            width: 60px;
            height: 60px;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <a href="mjeshtrit_ke_pasur.php">Mjeshtrit që ke pasur</a>
            </li>
            <li class="navbar-item">
                <a href="mjeshtrit_pritje.php">Mjeshtrit që i ke në pritje</a>
            </li>
            <li class="navbar-item">
                <a href="mjeshtrit_favorit.php">Mjeshtrit Favorit</a>
            </li>
            <li class="navbar-item">
                <a href="index.php">Dil</a>
            </li>
        </ul>
    </nav>

    <div class="container">
    <div class="card">
        <a href="profesionet.php?profesion=Elektricist">
            <img src="Images/c1.jpg" alt="Electrician">
            <p>Elektricist</p>
        </a>
    </div>
    <div class="card">
        <a href="profesionet.php?profesion=Moler">
            <img src="Images/c3.jpg" alt="Moler">
            <p>Moler</p>
        </a>
    </div>
    <div class="card">
        <a href="profesionet.php?profesion=Mekanik">
            <img src="Images/c4.jpg" alt="Mekanik">
            <p>Mekanik</p>
        </a>
    </div>
    <div class="card">
        <a href="profesionet.php?profesion=Kopshtar">
            <img src="Images/c5.png" alt="Kopshtar">
            <p>Kopshtar</p>
        </a>
    </div>
    <div class="card">
        <a href="profesionet.php?profesion=Mirëmbajtës i shtëpisë">
            <img src="Images/c6.png" alt="Housekeeper">
            <p>Mirëmbajtës i shtëpisë</p>
        </a>
    </div>
    <div class="card">
        <a href="profesionet.php?profesion=Hidraulik">
            <img src="Images/c2.jpg" alt="Hidraulik">
            <p>Hidraulik</p>
        </a>
    </div>
    <div class="card">
        <a href="profesionet.php?profesion=Pllakaxhi">
            <img src="Images/c7.jpg" alt="Pllakaxhi">
            <p>Pllakaxhi</p>
        </a>
    </div>
    <div class="card">
        <a href="profesionet.php?profesion=Murator">
            <img src="Images/c8.png" alt="Murator">
            <p>Murator</p>
        </a>
    </div>
    <div class="card">
        <a href="profesionet.php?profesion=Zdrukthtar">
            <img src="Images/c9.png" alt="Zdrukthtar">
            <p>Zdrukthtar</p>
        </a>
    </div>
    <div class="card">
        <a href="profesionet.php?profesion=Mjeshtër për ngrohje dhe kondicioner">
            <img src="Images/c10.jpg" alt="kondicioner">
            <p>Mjeshtër për ngrohje dhe kondicioner</p>
        </a>
    </div>
    <div class="card">
        <a href="profesionet.php?profesion=Oxhakpastrues">
            <img src="Images/c11.jpg" alt="oxhak">
            <p>Oxhakpastrues</p>
        </a>
    </div>
    <div class="card">
        <a href="profesionet.php?profesion=Izolues">
            <img src="Images/c12.jpg" alt="Izolues">
            <p>Izolues</p>
        </a>
    </div>
</div>

<?php  include 'footer.php'; ?>
</body>
</html>
