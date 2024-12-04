<?php
session_start();

include 'db.php';

require 'mailer/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{

    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = "mail.jankarlik.cz";
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = "info@ninjicek.jankarlik.cz";
        $this->mailer->Password = "HovnoKleslo1234!";
        $this->mailer->Port = 465;

        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mailer->isHTML(true);

        $this->mailer->CharSet = 'UTF-8';
    }
    public function sendEmail($to, $name, $from, $subject, $message): void
    {

        try {
            $this->mailer->setFrom($from, 'EMS');
            $this->mailer->addAddress($to, $name);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $message;
            $this->mailer->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$this->mailer->ErrorInfo}";
        }
    }

    public function sendRegisterEmail($to, $name, $token)
    {
        $subject = 'EMS Registration';
        $message = "
            <p>Dear $name,</p>
            <p>Thank you for registering with EMS. Please click the link below to complete your registration:</p>
            <p><a href='http://localhost/EMS_WA/reset_password.php?token=$token'>Complete Registration</a></p>
            <p>Best regards,<br>EMS</p>";
        $this->sendEmail($to, $name, 'info@ninjicek.jankarlik.cz', $subject, $message);
    }
}


class Employee
{

    private $db;


    public function __construct($db)
    {

        $this->db = $db;
    }


    public function createEmployee($data)
    {

        $token = bin2hex(random_bytes(16));

        $tokenExpiry = date('Y-m-d H:i:s', strtotime('+2 hour'));


        $insert_user = $this->db->prepare("

            INSERT INTO employees (email, firstName, lastName, salary, start_date, department_id, position_id, password_reset_token, token_expiry, isAdmin) 

            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)

        ");

        $insert_user->execute([$data['email'], $data['firstName'], $data['lastName'], $data['salary'], $data['start'], $data['department'], $data['position'], $token, $tokenExpiry, $data['isAdmin']]);



        return $token; // Return token for email sending

    }


    public function updateEmployee($userId, $data)
    {

        $update_user = $this->db->prepare("UPDATE employees SET email = ?, firstName = ?, lastName = ?, salary = ?, start_date = ?, department_id = ?, position_id = ?, isAdmin = ? WHERE id = ?");

        $update_user->execute([$data['email'], $data['firstName'], $data['lastName'], $data['salary'], $data['start'], $data['department'], $data['position'], $data['isAdmin'], $userId]);
    }


    public function deleteEmployee($userId)
    {

        $delete_user = $this->db->prepare("DELETE FROM employees WHERE id = ?");

        $delete_user->execute([$userId]);
    }


    public function getAllEmployees($search = '', $orderColumn = 'firstName', $orderDirection = 'ASC', $limit = 20, $offset = 0)
    {

        $whereClause = $search ? "WHERE firstName LIKE :search" : '';

        $sql = "

            SELECT employees.id, employees.email, employees.firstName, employees.lastName, employees.salary, employees.start_date, 

                   employees.department_id, employees.position_id, 

                   departments.departmentName, positions.positionName

            FROM employees

            LEFT JOIN departments ON employees.department_id = departments.idDepartment

            LEFT JOIN positions ON employees.position_id = positions.idPosition

            $whereClause

            ORDER BY $orderColumn $orderDirection

            LIMIT :limit OFFSET :offset

        ";


        $employeesQuery = $this->db->prepare($sql);

        if ($search) {

            $employeesQuery->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        $employeesQuery->bindValue(':limit', $limit, PDO::PARAM_INT);

        $employeesQuery->bindValue(':offset', $offset, PDO::PARAM_INT);

        $employeesQuery->execute();

        return $employeesQuery->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getTotalEmployees($search = '')
    {

        $whereClause = $search ? "WHERE firstName LIKE :search" : '';

        $totalEmployeesQuery = $this->db->prepare("SELECT COUNT(*) as total FROM employees $whereClause");

        if ($search) {

            $totalEmployeesQuery->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        $totalEmployeesQuery->execute();

        return $totalEmployeesQuery->fetch(PDO::FETCH_ASSOC)['total'];
    }
}


$db = (new Database())->getConnection();

$employee = new Employee($db);


if (!isset($_SESSION['id'])) {

    header('Location: index.php');

    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['create_user'])) {

        $token = $employee->createEmployee($_POST);

        $mailer = new Mailer();

        $mailer->sendRegisterEmail($_POST['email'], $_POST['firstName'], $token);


        header('Location: employees.php');

        exit();
    } elseif (isset($_POST['update_user'])) {

        $employee->updateEmployee($_POST['userId'], $_POST);

        header('Location: employees.php');

        exit();
    } elseif (isset($_POST['delete_user'])) {

        $employee->deleteEmployee($_POST['user_id']);

        header('Location: employees.php');

        exit();
    }
}

class Department
{

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllDepartments()
    {
        $query = $this->db->prepare("SELECT idDepartment, departmentName FROM departments");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}

class Position
{

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllPositions()
    {
        $query = $this->db->prepare("SELECT idPosition, positionName FROM positions");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}


$itemsPerPage = 20;

$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$offset = ($currentPage - 1) * $itemsPerPage;

$search = $_GET['search'] ?? '';

$orderColumn = $_GET['orderColumn'] ?? 'firstName';

$orderDirection = $_GET['orderDirection'] ?? 'ASC';

$allowedColumns = ['firstName', 'lastName', 'email', 'salary', 'start_date', 'departmentName', 'positionName'];
$searchColumn = isset($_GET['searchColumn']) && in_array($_GET['searchColumn'], $allowedColumns) ? $_GET['searchColumn'] : 'firstName';

$departmentObj = new Department($db);
$departments = $departmentObj->getAllDepartments();

$positonObj = new Position($db);
$positions = $positonObj->getAllPositions();


$totalEmployees = $employee->getTotalEmployees($search);

$employees = $employee->getAllEmployees($search, $orderColumn, $orderDirection, $itemsPerPage, $offset);

$totalPages = ceil($totalEmployees / $itemsPerPage);


include 'head.php';

include 'adminheader.php';








?>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Správá zaměstanců</h1>
        <?php if ($_SESSION['isAdmin']): ?>
            <h2 class="mb-4">Vytvoř nového zaměstnance</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                Vytvoř nového zaměstnance
            </button>
        <?php endif; ?>

        <div class="modal fade" id="createUserModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="createUser  ModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="createUserModalLabel">Nový zaměstnanec</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="firstName" class="form-label">Jméno</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>
                            <div class="mb-3">
                                <label for="lastName" class="form-label">Příjmění</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="salary" class="form-label">Plat</label>
                                <input type="number" class="form-control" id="salary" name="salary" required>
                            </div>
                            <div class="mb-3">
                                <label for="start" class="form-label">Datum nástupu</label>
                                <input type="date" class="form-control" id="start" name="start" required>
                            </div>
                            <div class="mb-3">
                                <label for="isAdmin" class="form-label">Je admin?</label>
                                <input type="checkbox" id="isAdmin" name="isAdmin">
                            </div>
                            <div class="mb-3">
                                <label for="department" class="form-label">Oddělení</label>
                                <select class="form-select" id="department" name="department" required>
                                    <option value="">Vyber oddělení</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?= htmlspecialchars($department['idDepartment']) ?>"><?= htmlspecialchars($department['departmentName']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="position" class="form-label">Pozice</label>
                                <select class="form-select" id="position" name="position">
                                    <option value="">Vyber pozici</option>
                                    <?php foreach ($positions as $position): ?>
                                        <option value="<?= htmlspecialchars($position['idPosition']) ?>">
                                            <?= htmlspecialchars($position['positionName']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="create_user" class="btn btn-primary">Vytvoř zaměstnance</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editUserModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="editUserModalLabel">Úprava zaměstnance</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" id="editUserForm">
                            <input type="hidden" name="userId" id="editUserId">
                            <div class="mb-3">
                                <label for="firstName" class="form-label">Jméno</label>
                                <input type="text" class="form-control" id="firstNameEdit" name="firstName" required>
                            </div>
                            <div class="mb-3">
                                <label for="lastName" class="form-label">Příjmení</label>
                                <input type="text" class="form-control" id="lastNameEdit" name="lastName" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="emailEdit" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="salary" class="form-label">Plat</label>
                                <input type="number" class="form-control" id="salaryEdit" name="salary" required>
                            </div>
                            <div class="mb-3">
                                <label for="start" class="form-label">Datum nástupu</label>
                                <input type="date" class="form-control" id="startEdit" name="start" required>
                            </div>
                            <div class="mb-3">
                                <label for="isAdmin" class="form-label">Je admin?</label>
                                <input type="checkbox" id="isAdminEdit" name="isAdmin">
                            </div>
                            <div class="mb-3">
                                <label for="department" class="form-label">Oddělení</label>
                                <select class="form-select" id="departmentEdit" name="department" required>
                                    <option value="">Vyber oddělení</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?= htmlspecialchars($department['idDepartment']) ?>"><?= htmlspecialchars($department['departmentName']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="position" class="form-label">Pozice</label>
                                <select class="form-select" id="positionEdit" name="position">
                                    <option value="">Vyber pozici</option>
                                    <?php foreach ($positions as $position): ?>
                                        <option value="<?= htmlspecialchars($position['idPosition']) ?>">
                                            <?= htmlspecialchars($position['positionName']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="update_user" class="btn btn-primary">Uprav zaměstnance</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="mt-5">Seznam zaměstanců</h2>
        <div class="d-flex mb-3">
            <form method="GET" class="d-flex">
                <select name="searchColumn" class="form-select me-2">
                    <option value="firstName">Jméno</option>
                    <option value="lastName">Příjmení</option>
                    <option value="email">Email</option>
                    <option value="salary">Plat</option>
                    <option value="start_date">Datum nástupu</option>
                    <option value="departmentName">Oddělení</option>
                    <option value="positionName">Pozice</option>
                </select>
                <input type="text" name="search" class="form-control me-2" placeholder="Vyhledání" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Vyhledej</button>
            </form>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th><a href="?orderColumn=firstName&orderDirection=<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>" class="btn p-0">Jméno</a></th>
                    <th><a href="?orderColumn=lastName&orderDirection=<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>" class="btn p-0">Příjmení</a></th>
                    <th><a href="?orderColumn=email&orderDirection=<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>" class="btn p-0">Email</a></th>
                    <th><a href="?orderColumn=salary&orderDirection=<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>" class="btn p-0">Plat</a></th>
                    <th><a href="?orderColumn=start_date&orderDirection=<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>" class="btn p-0">Datum nástupu</a></th>
                    <th><a href="?orderColumn=departmentName&orderDirection=<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>" class="btn p-0">Oddělení</a></th>
                    <th><a href="?orderColumn=positionName&orderDirection=<?= $orderDirection === 'ASC' ? 'DESC' : 'ASC' ?>" class="btn p-0">Pozice</a></th>
                    <?php if ($_SESSION['isAdmin']): ?>
                        <th class="d-flex justify-content-end">Akce</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?= htmlspecialchars($employee['firstName']) ?></td>
                        <td><?= htmlspecialchars($employee['lastName']) ?></td>
                        <td><?= htmlspecialchars($employee['email']) ?></td>
                        <td><?= htmlspecialchars($employee['salary']) ?></td>
                        <td><?= htmlspecialchars($employee['start_date']) ?></td>
                        <td><?= htmlspecialchars($employee['departmentName']) ?></td>
                        <td><?= htmlspecialchars($employee['positionName']) ?></td>
                        <?php if ($_SESSION['isAdmin']): ?>
                            <td class="d-flex flex-row justify-content-end align-items-center">
                                <button class="btn btn-warning m-1" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                    onclick="populateEditModal(<?= htmlspecialchars(json_encode($employee)) ?>)">Edit</button>
                                <?php if ($employee['id'] != $_SESSION['id']): ?>
                                    <form method="POST" action="employees.php" style="display:inline;" class="m-1">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($employee['id']) ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="employees.php" style="display:inline;" class="m-1">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($employee['id']) ?>">
                                        <button disabled type="submit" name="delete_user" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-center mt-4">
            <nav>
                <ul class="pagination">
                    <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>&orderColumn=<?= $orderColumn ?>&orderDirection=<?= $orderDirection ?>&search=<?= $search ?>&searchColumn=<?= $searchColumn ?>">
                            << /a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&orderColumn=<?= $orderColumn ?>&orderDirection=<?= $orderDirection ?>&search=<?= $search ?>&searchColumn=<?= $searchColumn ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>&orderColumn=<?= $orderColumn ?>&orderDirection=<?= $orderDirection ?>&search=<?= $search ?>&searchColumn=<?= $searchColumn ?>">></a>
                    </li>
                </ul>
            </nav>
        </div>

    </div>


    <script>
        function populateEditModal(employee) {
            document.getElementById('editUserId').value = employee.id;
            document.getElementById('firstNameEdit').value = employee.firstName;
            document.getElementById('lastNameEdit').value = employee.lastName;
            document.getElementById('emailEdit').value = employee.email;
            document.getElementById('salaryEdit').value = employee.salary;
            document.getElementById('startEdit').value = employee.start_date;
            document.getElementById('isAdminEdit').checked = employee.isAdmin == 1;
            document.getElementById('departmentEdit').value = employee.department_id;
            document.getElementById('positionEdit').value = employee.position_id;
        }
    </script>
</body>