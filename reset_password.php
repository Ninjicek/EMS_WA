<?php
require 'db.php';

$database = new Database();
$db = $database->getConnection();

class PasswordResetHandler
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function validateToken($token)
    {
        $query = $this->db->prepare("SELECT * FROM employees WHERE password_reset_token = ? AND token_expiry > NOW()");
        $query->execute([$token]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function resetPassword($token, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $this->db->prepare("UPDATE employees SET password = ?, password_reset_token = NULL, token_expiry = NULL WHERE password_reset_token = ?");
        $update->execute([$hashedPassword, $token]);
        return $update->rowCount() > 0;
    }
}

$token = $_GET['token'] ?? null;
$passwordResetHandler = new PasswordResetHandler($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $token) {
    $user = $passwordResetHandler->validateToken($token);
    if (!$user) {
        die('Invalid or expired token.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass'], $_POST['pass2'], $_POST['token'])) {
    $pass = $_POST['pass'];
    $pass2 = $_POST['pass2'];
    $token = $_POST['token'];

    if ($pass === $pass2) {
        if ($passwordResetHandler->resetPassword($token, $pass)) {
            header('Location: index.php');
            exit;
        } else {
            die('Chyba při aktualizaci hesla nebo token je neplatný.');
        }
    } else {
        die('Passwords do not match.');
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
                        <h1 class="card-title text-center mb-4">Změna hesla</h1>
                        <form method="post" action="">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                            <div class="mb-3">
                                <label for="pass" class="form-label">Změnit heslo</label>
                                <input type="password" name="pass" id="pass" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="pass2" class="form-label">Heslo znovu</label>
                                <input type="password" name="pass2" id="pass2" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Změnit heslo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>