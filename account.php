<?php
session_start();
include 'db.php';


class User
{

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }


    public function getUserData($user_id)
    {

        $query = $this->db->prepare("SELECT firstName, lastName, email FROM employees WHERE id = ?");

        $query->execute([$user_id]);

        return $query->fetch(PDO::FETCH_ASSOC);
    }


    public function updateUserData($user_id, $firstName, $lastName, $email, $password = null)
    {

        $update = $this->db->prepare("UPDATE employees SET firstName = ?, lastName = ?, email = ? WHERE id = ?");

        $update->execute([$firstName, $lastName, $email, $user_id]);


        if (!empty($password)) {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $update_password = $this->db->prepare("UPDATE employees SET password = ? WHERE id = ?");

            $update_password->execute([$hashed_password, $user_id]);
        }
    }
}


$db = (new Database())->getConnection();

$user = new User($db);


if (!isset($_SESSION['id'])) {

    header('Location: index.php');

    exit();
}


$user_data = $user->getUserData($_SESSION['id']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user->updateUserData($_SESSION['id'], $_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['password']);

    header('Location: admin.php');

    exit();
}


include 'head.php';

include 'adminheader.php';
?>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Úprava údajů</h1>
        <form method="POST">
            <div class="mb-3">
                <label for="firstName" class="form-label">Jméno</label>
                <input type="text" class="form-control" id="firstName" name="firstName" value="<?= htmlspecialchars($user_data['firstName']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Přímení</label>
                <input type="text" class="form-control" id="lastName" name="lastName" value="<?= htmlspecialchars($user_data['lastName']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Nové heslo</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="mb-3">
                <label for="password2" class="form-label">Potvrďte nové heslo</label>
                <input type="password" class="form-control" id="password2" name="password2">
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal">
                Uložit
            </button>
            
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmModalLabel">Potvrzení změn</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Opravdu chcete uložit změny?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                            <button type="submit" class="btn btn-primary">Potvrdit</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

</body>