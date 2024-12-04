<?php
session_start();
require 'db.php';

$database = new Database();
$db = $database->getConnection();


class User
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function login($email, $password)
    {
        $sql = "SELECT * FROM employees WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['isAdmin'] = $user['isAdmin'];
                header('Location: admin.php');
                exit;
            } else {
                return "Nesprávné heslo.<br>";
            }
        } else {
            return "Uživatel s tímto emailem nebyl nalezen.<br>";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $user = new User($db);
    $error = $user->login($email, $password);
    if (isset($error)) {
        echo $error;
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
                        <h1 class="card-title text-center mb-4">Přihlášení do EMS</h1>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Heslo</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Přihlásit</button>
                            </div>
                        </form>
                        <a href="pass_forgot.php">Zapomněli jste heslo?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>