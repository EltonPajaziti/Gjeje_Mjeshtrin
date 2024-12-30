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
                <a href="mjeshtrit_ke_pasur.php">Menaxho Klientët</a>
            </li>
            <li class="navbar-item">
                <a href="mjeshtrit_pritje.php">Menaxho Mjeshtrit</a>
            </li>
            <li class="navbar-item">
                <a href="index.php">Dil</a>
            </li>
        </ul>
    </nav>

    <div class="container custom-table-container">
        <h2 class="mb-4">Menaxhimi i Qytetarëve</h2>
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
