<?php
session_start();
include 'db.php';

$database = new Database();
$db = $database->getConnection();

class PositionManager
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function createPosition($positionName)
    {
        $insert_position = $this->db->prepare("INSERT INTO positions (positionName) VALUES (?)");
        $insert_position->execute([$positionName]);
    }

    public function updatePosition($idPosition, $positionName)
    {
        $update_position = $this->db->prepare("UPDATE positions SET positionName = ? WHERE idPosition = ?");
        $update_position->execute([$positionName, $idPosition]);
    }

    public function deletePosition($positionId)
    {
        $delete_position = $this->db->prepare("DELETE FROM positions WHERE idPosition = ?");
        $delete_position->execute([$positionId]);
    }

    public function fetchAllPositions()
    {
        $positions_query = $this->db->query("SELECT * FROM positions");
        return $positions_query->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!isset($_SESSION['id']) || !$_SESSION['isAdmin']) {
    header('Location: index.php');
    exit();
}

$positionManager = new PositionManager($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_position'])) {
        $positionName = $_POST['positionName'] ?? '';
        $positionManager->createPosition($positionName);
        header('Location: positions.php');
        exit();
    }

    if (isset($_POST['update_position'])) {
        $idPosition = $_POST['idPosition'] ?? '';
        $positionName = $_POST['positionName'] ?? '';
        $positionManager->updatePosition($idPosition, $positionName);
        header('Location: positions.php');
        exit();
    }

    if (isset($_POST['delete_position'])) {
        $positionId = $_POST['position_id'] ?? '';
        $positionManager->deletePosition($positionId);
        header('Location: positions.php');
        exit();
    }
}

$positions = $positionManager->fetchAllPositions();
include 'head.php';
include 'adminheader.php';
?>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Správa pozicí</h1>

        <h2 class="mb-4">Vytvoř novou pozici</h2>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPositionModal">
            Vytvoř novou pozici
        </button>


        <div class="modal fade" id="createPositionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="createPositionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="createPositionModalLabel">Vytvoř novou pozici</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="positionName" class="form-label">Název pozice</label>
                                <input type="text" class="form-control" id="positionName" name="positionName" required>
                            </div>
                            <button type="submit" name="create_position" class="btn btn-success">Vytvoř pozici</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="editPositionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editPositionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="editPositionModalLabel">Uprav pozici</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" id="editPositionForm">
                            <input type="hidden" name="idPosition" id="editPositionId">
                            <div class="mb-3">
                                <label for="editPositionName" class="form-label">Název pozice</label>
                                <input type="text" class="form-control" id="editPositionName" name="positionName" required>
                            </div>
                            <button type="submit" name="update_position" class="btn btn-primary">Uprav pozici</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="mt-5">Seznam pozicí</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Název pozice</th>
                    <th class="d-flex justify-content-end">Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($positions as $position): ?>
                    <tr>
                        <td><?= htmlspecialchars($position['positionName']) ?></td>
                        <td class="d-flex flex-row justify-content-end align-items-center">
                            <button class="btn btn-warning m-1" data-bs-toggle="modal" data-bs-target="#editPositionModal"
                                onclick="populateEditPositionModal(<?= htmlspecialchars(json_encode($position)) ?>)">Edit</button>
                            <form method="POST" action="positions.php" style="display:inline;" class="m-1">
                                <input type="hidden" name="position_id" value="<?= htmlspecialchars($position['idPosition']) ?>">
                                <button type="submit" name="delete_position" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this position?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function populateEditPositionModal(position) {
            document.getElementById('editPositionId').value = position.idPosition;
            document.getElementById('editPositionName').value = position.positionName;
        }
    </script>
</body>