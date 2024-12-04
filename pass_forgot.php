<?php
session_start();
require 'db.php';
require 'mailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$database = new Database();
$db = $database->getConnection();

class PasswordReset
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function requestReset($email)
    {
        $query = $this->db->prepare("SELECT * FROM employees WHERE email = ?");
        $query->execute([$email]);
        $user = $query->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(16));
            $tokenExpiry = date('Y-m-d H:i:s', strtotime('+2 hour'));

            $update = $this->db->prepare("UPDATE employees SET password_reset_token = ?, token_expiry = ? WHERE email = ?");
            $update->execute([$token, $tokenExpiry, $email]);

            return $token;
        } else {
            return false;
        }
    }

    public function sendEmail($email, $token, $user)
    {
        $mailer = new PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->Host = "mail.jankarlik.cz";
            $mailer->SMTPAuth = true;
            $mailer->Username = "info@ninjicek.jankarlik.cz";
            $mailer->Password = "HovnoKleslo1234!";
            $mailer->Port = 465;
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mailer->isHTML(true);
            $mailer->CharSet = 'UTF-8';

            $mailer->setFrom('info@ninjicek.jankarlik.cz', 'EMS');
            $mailer->addAddress($email, $user['firstName'] . ' ' . $user['lastName']);
            $mailer->Subject = 'Password Reset Request';
            $mailer->Body = "Click the link to reset your password: <a href='http://localhost/EMS_WA/reset_password.php?token=$token'>Reset Password</a>";

            $mailer->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];
    $passwordReset = new PasswordReset($db);
    $user = $db->prepare("SELECT * FROM employees WHERE email = ?");
    $user->execute([$email]);
    $userData = $user->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $token = $passwordReset->requestReset($email);
        if ($token) {
            $passwordReset->sendEmail($email, $token, $userData);
            echo "Email has been sent.";
        } else {
            echo "Error generating token.";
        }
    } else {
        echo "Email not found.";
    }
}

include 'head.php';
?>

<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4">Zapomenuté heslo</h1>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Odeslat email k restetu</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>