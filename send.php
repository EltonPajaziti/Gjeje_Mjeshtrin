<?php 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (isset($_POST["send"])){
    try {
        $mail = new PHPMailer(true);

        // Konfigurimi i SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'pajazitielton02@gmail.com';
        $mail->Password = 'hbjyofcysnhygpnd';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Vendosja e dërguesit (From) si adresa fikse
        $mail->setFrom('pajazitielton02@gmail.com', 'GJEJE MJESHTRIN');

        // Vendosja e 'Reply-To' si email-i i përdoruesit
        $mail->addReplyTo($_POST["email"], $_POST["name"]);

        // Vendosja e marrësit (To)
        $mail->addAddress('pajazitielton02@gmail.com');

        // Përmbajtja e email-it
        $mail->isHTML(true);
        $mail->Subject = htmlspecialchars($_POST["subject"], ENT_QUOTES, 'UTF-8');
        $mail->Body = "
            <h4>Mesazh nga: <strong>{$_POST['name']}</strong></h4>
            <p><strong>Email:</strong> {$_POST['email']}</p>
            <p><strong>Telefoni:</strong> {$_POST['phone']}</p>
            <p><strong>Mesazhi:</strong><br>{$_POST['message']}</p>
        ";

        $mail->send();
        echo "<script>alert('Mesazhi është dërguar me sukses!'); window.history.back();</script>";
    } catch (Exception $e) {
        echo "<script>alert('Mesazhi nuk mund të dërgohej. Gabim: {$mail->ErrorInfo}'); window.history.back();</script>";
    }
}
?>
