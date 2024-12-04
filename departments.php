<?php
session_start();

include 'db.php';


class Department
{

    private $db;


    public function __construct($db)
    {

        $this->db = $db;
    }


    public function createDepartment($departmentName)
    {

        $insert_department = $this->db->prepare("INSERT INTO departments (departmentName) VALUES (?)");

        $insert_department->execute([$departmentName]);
    }


    public function updateDepartment($idDepartment, $departmentName)
    {

        $update_department = $this->db->prepare("UPDATE departments SET departmentName = ? WHERE idDepartment = ?");

        $update_department->execute([$departmentName, $idDepartment]);
    }


    public function deleteDepartment($departmentId)
    {

        $check_role_query = $this->db->prepare("SELECT * FROM employees WHERE department_id = ?");

        $check_role_query->execute([$departmentId]);


        if ($check_role_query->rowCount() > 0) {

            echo '<script>alert("This role is assigned to a user. Please remove the role from the user before deleting the role.")</script>';
        } else {

            $delete_role = $this->db->prepare("DELETE FROM departments WHERE idDepartment = ?");

            $delete_role->execute([$departmentId]);
        }
    }


    public function getAllDepartments()
    {

        $departments_query = $this->db->query("SELECT * FROM departments");

        return $departments_query->fetchAll(PDO::FETCH_ASSOC);
    }
}


$db = (new Database())->getConnection();

$department = new Department($db);


if (!isset($_SESSION['id']) || !$_SESSION['isAdmin']) {

    header('Location: index.php');

    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['create_department'])) {

        $department->createDepartment($_POST['departmentName']);

        header('Location: departments.php');

        exit();
    } elseif (isset($_POST['update_department'])) {

        $department->updateDepartment($_POST['idDepartment'], $_POST['departmentName']);

        header('Location: departments.php');

        exit();
    } elseif (isset($_POST['delete_department'])) {

        $department->deleteDepartment($_POST['department_id']);

        header('Location: departments.php');

        exit();
    }
}


$departments = $department->getAllDepartments();

include 'head.php';

include 'adminheader.php';
?>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Správa oddělení</h1>

        <h2 class="mb-4">Vytvoř nové oddělení</h2>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createDepartmentModal">
            Vytvoř nové oddělení
        </button>

        <div class="modal fade" id="createDepartmentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="createRoleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="createDepartmentModalLabel">Vytvoř nové oddělení</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="departmentName" class="form-label">Název oddělení</label>
                                <input type="text" class="form-control" id="deparmentName" name="departmentName" required>
                            </div>
                            <button type="submit" name="create_department" class="btn btn-success">Vytvoř nové oddělení</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="modal fade" id="editDepartmentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="editDepartmentModalLabel">Uprav oddělení</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" id="editDepartmentForm">
                            <input type="hidden" name="idDepartment" id="editDepartmentId">
                            <div class="mb-3">
                                <label for="editDepartmentName" class="form-label">Název oddělení</label>
                                <input type="text" class="form-control" id="editDepartmentName" name="departmentName" required>
                            </div>
                            <button type="submit" name="update_department" class="btn btn-primary">Uprav oddělení</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="mt-5">Seznam oddělení</h2>
        <table class="table ">
            <thead>
                <tr>
                    <th>Název oddělení</th>
                    <th class="d-flex justify-content-end">Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $department): ?>
                    <tr>
                        <td><?= htmlspecialchars($department['departmentName']) ?></td>
                        <td class="d-flex flex-row justify-content-end aling-items-center">
                            <button class="btn btn-warning m-1" data-bs-toggle="modal" data-bs-target="#editDepartmentModal"
                                onclick="populateEditRoleModal(<?= htmlspecialchars(json_encode($department)) ?>)">Edit</button>
                            <form method="POST" action="departments.php" style="display:inline;" class="m-1">
                                <input type="hidden" name="department_id" value="<?= htmlspecialchars($department['idDepartment']) ?>"> <!-- Opraveno -->
                                <button type="submit" name="delete_department" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this role?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function populateEditRoleModal(department) {
            document.getElementById('editDepartmentId').value = department.idDepartment; 
            document.getElementById('editDepartmentName').value = department.departmentName; 
        }
    </script>
</body>