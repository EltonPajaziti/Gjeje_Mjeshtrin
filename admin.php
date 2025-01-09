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

// Merr të dhënat e qytetarëve
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, profile_picture, created_at FROM users WHERE role = 'Qytetar'");
    $stmt->execute();
    $qytetaret = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave për qytetarët: " . $e->getMessage());
}

// Merr të dhënat e mjeshtrave
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, profile_picture, created_at FROM users WHERE role = 'Mjeshter'");
    $stmt->execute();
    $mjeshtrat = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave për mjeshtrat: " . $e->getMessage());
}


// Numri total i përdoruesve (pa përfshirë adminët)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users WHERE role != 'Admin'");
    $stmt->execute();
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së numrit të përdoruesve: " . $e->getMessage());
}

// Numri i përdoruesve meshkuj (pa përfshirë adminët)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as male_users FROM users WHERE gender = 'Mashkull' AND role != 'Admin'");
    $stmt->execute();
    $male_users = $stmt->fetch(PDO::FETCH_ASSOC)['male_users'];
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së numrit të meshkujve: " . $e->getMessage());
}

// Numri i përdoruesve femra (pa përfshirë adminët)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as female_users FROM users WHERE gender = 'Femer' AND role != 'Admin'");
    $stmt->execute();
    $female_users = $stmt->fetch(PDO::FETCH_ASSOC)['female_users'];
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së numrit të femrave: " . $e->getMessage());
}


// Numri i përdoruesve për secilin rajon (pa përfshirë adminët)
try {
    $stmt = $pdo->prepare("
        SELECT municipality, COUNT(*) as total 
        FROM users 
        WHERE role != 'Admin' 
        GROUP BY municipality
    ");
    $stmt->execute();
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Inicioni një array për të garantuar që të gjitha rajonet të përfshihen
    $all_regions = [
        "Prishtina" => 0,
        "Mitrovica" => 0,
        "Peja" => 0,
        "Prizreni" => 0,
        "Ferizaji" => 0,
        "Gjilani" => 0,
        "Gjakova" => 0
    ];

    // Mbushni array-n me të dhëna reale
    foreach ($regions as $region) {
        if (array_key_exists($region['municipality'], $all_regions)) {
            $all_regions[$region['municipality']] = (int)$region['total'];
        }
    }
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave: " . $e->getMessage());
}

// Konverto të dhënat për JavaScript
$regions_json = json_encode($all_regions);


// Llogarit numrin e qytetarëve
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS qytetare_count FROM users WHERE role = 'Qytetar'");
    $stmt->execute();
    $qytetare_count = $stmt->fetch(PDO::FETCH_ASSOC)['qytetare_count'];
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave për qytetarët: " . $e->getMessage());
}

// Llogarit numrin e mjeshtrave
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS mjeshter_count FROM users WHERE role = 'Mjeshter'");
    $stmt->execute();
    $mjeshter_count = $stmt->fetch(PDO::FETCH_ASSOC)['mjeshter_count'];
} catch (PDOException $e) {
    die("Gabim gjatë marrjes së të dhënave për mjeshtrat: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="CSS/adminnavbar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-image: url('<?= htmlspecialchars($profile_picture); ?>');
            background-size: cover;
            background-position: center;
        }
        .custom-table-container {
            margin-top: 20px;
        }
        .custom-table {
            border: 1px solid #ddd;
        }
        .custom-table thead {
            background-color: #e2964b;
            color: black;
        }
        .custom-table thead th {
            text-align: center;
        }
        .custom-table tbody tr td {
            vertical-align: middle;
            text-align: center;
        }
        .custom-table tbody tr td img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .card {
    border-radius: 10px;
    background: #fff;
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: scale(1.05);
}

.card-img-top {
    border-radius: 50%;
    background-color: #f4f4f4;
    padding: 10px;
}

#special-cards-container {
    display: flex;
    justify-content: space-around;
    align-items: center;
    margin: 20px 0;
}

.special-card {
    width: 300px;
    padding: 20px;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease-in-out;
}

.special-card:hover {
    transform: scale(1.05);
}

.special-card-img {
    width: 80px;
    height: 80px;
    margin-bottom: 15px;
}

.special-card h3 {
    font-size: 16px;
    color: #333;
    margin-bottom: 10px;
}

.special-card-count {
    font-size: 24px;
    font-weight: bold;
    color: black;
}


    </style>
</head>
<body>
    <nav id="custom-navbar">
        <ul class="navbar-list">
            <li class="navbar-item profile">
                <a href="profile.php">
                    <div class="profile-image"></div>
                    <span><?= htmlspecialchars($user_name); ?></span>
                </a>
            </li>
            <li class="navbar-item">
                <a href="#qytetari">Menaxho Qytetarët</a>
            </li>
            <li class="navbar-item">
                <a href="#mjeshtrat">Menaxho Mjeshtrit</a>
            </li>
            <li class="navbar-item">
                <a href="index.php">Dil</a>
            </li>
        </ul>
    </nav>


    <div class="container my-4">
    <div class="row text-center">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <img src="Images/uuu.png" class="card-img-top mx-auto mt-3" style="width: 100px;" alt="Total Users">
                <div class="card-body">
                    <h5 class="card-title">Numri i përdoruesve</h5>
                    <p class="card-text display-4"><?= htmlspecialchars($total_users); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <img src="Images/male.png" class="card-img-top mx-auto mt-3" style="width: 100px;" alt="Male Users">
                <div class="card-body">
                    <h5 class="card-title">Numri i meshkujve</h5>
                    <p class="card-text display-4"><?= htmlspecialchars($male_users); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <img src="Images/female.png" class="card-img-top mx-auto mt-3" style="width: 100px;" alt="Female Users">
                <div class="card-body">
                    <h5 class="card-title">Numri i femrave</h5>
                    <p class="card-text display-4"><?= htmlspecialchars($female_users); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="width: 80%; margin: auto;">
        <canvas id="regionChart"></canvas>
    </div>

    <script>
    // Të dhënat e rajoneve nga PHP
    const regionData = <?= $regions_json; ?>;

    // Ekstrakto etiketat dhe vlerat nga të dhënat
    const labels = Object.keys(regionData);
    const values = Object.values(regionData);

    // Krijimi i grafikut
    const ctx = document.getElementById('regionChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Numri i përdoruesve',
                data: values,
                backgroundColor: 'rgba(251, 232, 176, 1)',
                borderColor: '#531f11',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1, // Hapi i numrave (vetëm numra të plotë)
                        callback: function(value) {
                            if (value % 1 === 0) {
                                return value; // Shfaq vetëm numra të plotë
                            }
                        }
                    },
                    grid: {
                        drawTicks: true, // Shfaq numrat në boshtin vertikal
                        drawBorder: true, // Kufiri i grafikut
                        drawOnChartArea: false // Mos vizato vijat horizontale
                    }
                }
            }
        }
    });
</script>

<div id="special-cards-container">
    <div class="special-card">
        <img src="Images/qytetar-removebg-preview.png" alt="Qytetarët" class="special-card-img">
        <h3>Numri i Qytetarëve të regjistruar në platformë</h3>
        <div class="special-card-count"><?= htmlspecialchars($qytetare_count) ?></div>
    </div>
    <div class="special-card">
        <img src="Images/mje.jpeg" alt="Mjeshtrat" class="special-card-img">
        <h3>Numri i Mjeshtrave të regjistruar në platformë</h3>
        <div class="special-card-count"><?= htmlspecialchars($mjeshter_count) ?></div>
    </div>
</div>


    <div class="container custom-table-container">
        <h2 class="mb-4" id="qytetari">Menaxhimi i Qytetarëve</h2>
        <table class="table custom-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Emri dhe Mbiemri</th>
                    <th>Foto e Profilit</th>
                    <th>Data e Krijimit</th>
                    <th>Roli</th>
                    <th>Veprimi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($qytetaret as $index => $qytetar): ?>
                    <tr id="row-<?= $qytetar['id'] ?>">
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($qytetar['first_name'] . ' ' . $qytetar['last_name']) ?></td>
                        <td>
                            <img src="<?= htmlspecialchars($qytetar['profile_picture'] ?? 'PROFILE/default_profile.png') ?>" alt="Foto e profilit">
                        </td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($qytetar['created_at']))) ?></td>
                        <td>Qytetar</td>
                        <td>
                            <a href="modify_user.php?id=<?= htmlspecialchars($qytetar['id']) ?>" class="btn btn-success btn-sm">Modifiko</a>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $qytetar['id'] ?>">Fshije</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2 class="mb-4" id="mjeshtrat">Menaxhimi i Mjeshtrave</h2>
        <table class="table custom-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Emri dhe Mbiemri</th>
                    <th>Foto e Profilit</th>
                    <th>Data e Krijimit</th>
                    <th>Roli</th>
                    <th>Veprimi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mjeshtrat as $index => $mjeshter): ?>
                    <tr id="row-mjeshter-<?= $mjeshter['id'] ?>">
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($mjeshter['first_name'] . ' ' . $mjeshter['last_name']) ?></td>
                        <td>
                            <img src="<?= htmlspecialchars($mjeshter['profile_picture'] ?? 'PROFILE/default_profile.png') ?>" alt="Foto e profilit">
                        </td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($mjeshter['created_at']))) ?></td>
                        <td>Mjeshter</td>
                        <td>
                            <a href="modify_user.php?id=<?= htmlspecialchars($mjeshter['id']) ?>" class="btn btn-success btn-sm">Modifiko</a>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $mjeshter['id'] ?>">Fshije</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        $(document).on('click', '.delete-btn', function () {
            const userId = $(this).data('id');
            const row = $(`#row-${userId}`);
            if (confirm('A jeni të sigurt që dëshironi ta fshini këtë përdorues?')) {
                $.ajax({
                    url: 'delete_user_ajax.php',
                    type: 'POST',
                    data: { user_id: userId },
                    success: function (response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            row.remove();
                            alert('Përdoruesi u fshi me sukses!');
                        } else {
                            alert('Gabim gjatë fshirjes së përdoruesit!');
                        }
                    },
                    error: function () {
                        alert('Gabim në server!');
                    }
                });
            }
        });
    </script>
    
</body>
</html>
