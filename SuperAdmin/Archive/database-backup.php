<?php
session_start();

// --- Super Admin Check ---
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    die("Access Denied!");
}

// --- Database Connection ---
require_once '../../1database_conn/connection.php';
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "adminevalsys";

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) die("Failed to connect: " . mysqli_connect_error());

// --- Backup/Archive Message ---
$backupMessage = "";

// --- Handle Database Backup safely ---
if (isset($_POST['backup_db'])) {
    $backupDir = "../../backups/";
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

    $filename = $backupDir . "adminevalsys.sql";

    // mysqldump command (Windows)
    $mysqldump = '"C:\\xampp\\mysql\\bin\\mysqldump.exe"';
    $command = "{$mysqldump} --user={$user} --password={$pass} --host={$host} {$dbname} > {$filename}";
    exec($command, $output, $returnVar);

    if ($returnVar === 0) {
        // Archive record in DB
        $stmt = $conn->prepare("
            INSERT INTO evaluation_archives 
            (file_name, department_id, school_year, semester, notes, is_archived) 
            VALUES (?, ?, ?, ?, ?, 0)
        ");
        $null = null;
        $emptyNotes = "Auto backup generated";
        $fileNameOnly = basename($filename);
        $stmt->bind_param("sisss", $fileNameOnly, $null, $null, $null, $emptyNotes);
        $stmt->execute();
        $stmt->close();

        // Redirect to prevent infinite POST loop
        header("Location: " . $_SERVER['PHP_SELF'] . "?backup=success");
        exit();
    } else {
        $backupMessage = "Database backup failed!";
    }
}

// --- Show message if redirected ---
if (isset($_GET['backup']) && $_GET['backup'] === 'success') {
    $backupMessage = "Database backup successful and archived!";
}

// --- Fetch all archives ---
$archives = [];
$result = $conn->query("SELECT * FROM evaluation_archives ORDER BY archive_date DESC");
if ($result) while ($row = $result->fetch_assoc()) $archives[] = $row;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
<title>Database Backup & Archive</title>
<link rel="shortcut icon" href="../../assets/img/logo-small.png">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="main-wrapper">
<?php
include '../../Layout Components/header.php';
include '../../Layout Components/sidebar.php';
?>
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Database Backup & Archiving</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../home.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Archive</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Backup Button & Message -->
        <div class="row mb-3">
            <div class="col-md-12">
                <form method="post">
                    <div class="mb-2">
                        <label>Backup Notes (optional):</label>
                        <textarea name="notes" class="form-control" placeholder="Describe this backup..."></textarea>
                    </div>
                    <button type="submit" name="backup_db" class="btn btn-success">
                        <i class="fas fa-database"></i> Backup Database
                    </button>
                </form>
                <?php if($backupMessage): ?>
                    <div class="alert alert-info mt-2"><?= htmlspecialchars($backupMessage) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Archives Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Archived Backups</h5>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>File Name</th>
                            <th>Department</th>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Notes</th>
                            <th>Date Archived</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(count($archives) > 0): ?>
                        <?php foreach($archives as $index => $archive): ?>
                            <?php
                            $archived = isset($archive['is_archived']) ? $archive['is_archived'] : 0;
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($archive['file_name']) ?></td>
                                <td><?= htmlspecialchars($archive['department_id'] ?? 'All') ?></td>
                                <td><?= htmlspecialchars($archive['school_year'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($archive['semester'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($archive['notes'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($archive['archive_date']) ?></td>
                                <td>
                                    <!-- Always show Download -->
                                    <a href="../../backups/<?= urlencode($archive['file_name']) ?>" 
                                    class="btn btn-primary btn-sm" download>
                                        <i class="fas fa-download"></i>
                                    </a>

                                <?php if ($archived == 0): ?>

                                    <!-- Edit Notes button -->
                                    <button class="btn btn-info btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editNotesModal"
                                            data-id="<?= $archive['id'] ?>"
                                            data-notes="<?= htmlspecialchars($archive['notes']) ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button class="btn btn-secondary btn-sm open-archive-modal"
                                            data-id="<?= $archive['id'] ?>"
                                            data-filename="<?= htmlspecialchars($archive['file_name']) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#archiveConfirmModal">
                                        <i class="fas fa-archive"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-warning btn-sm open-restore-modal"
                                            data-id="<?= $archive['id'] ?>"
                                            data-filename="<?= htmlspecialchars($archive['file_name']) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#restoreConfirmModal">
                                        <i class="fas fa-undo"></i>
                                    </button>

                                    <button class="btn btn-danger btn-sm open-delete-modal"
                                            data-id="<?= $archive['id'] ?>"
                                            data-filename="<?= htmlspecialchars($archive['file_name']) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteConfirmModal">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">No archives found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    <?php include '../../Layout Components/footer.php'; ?>
</div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>

<!-- Edit Notes Modal -->
<div class="modal fade" id="editNotesModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="update_notes.php" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Edit Backup Notes</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="id" id="editNotesId">

        <label>Notes</label>
        <textarea name="notes" id="editNotesText" class="form-control" rows="4"></textarea>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>

    </form>
  </div>
</div>

<script>
document.getElementById('editNotesModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    document.getElementById('editNotesId').value = button.getAttribute('data-id');
    document.getElementById('editNotesText').value = button.getAttribute('data-notes');
});
</script>

<!-- Archive Confirmation Modal -->
<div class="modal fade" id="archiveConfirmModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Confirm Archive</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p>Are you sure you want to archive:</p>
        <h6 id="archiveFileName"></h6>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a id="confirmArchiveBtn" class="btn btn-secondary">Archive</a>
      </div>

    </div>
  </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreConfirmModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Confirm Restore</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p>This will restore the selected backup:</p>
        <h6 id="restoreFileName"></h6>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a id="confirmRestoreBtn" class="btn btn-warning">Restore</a>
      </div>

    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Delete Backup Permanently?</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p>This action cannot be undone. Delete backup:</p>
        <h6 id="deleteFileName"></h6>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
      </div>

    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    // ARCHIVE MODAL
    document.querySelectorAll(".open-archive-modal").forEach(btn => {
        btn.addEventListener("click", () => {
            let id = btn.dataset.id;
            let file = btn.dataset.filename;

            document.getElementById("archiveFileName").textContent = file;
            document.getElementById("confirmArchiveBtn").href = "db_archive.php?id=" + id;
        });
    });

    // RESTORE MODAL
    document.querySelectorAll(".open-restore-modal").forEach(btn => {
        btn.addEventListener("click", () => {
            let id = btn.dataset.id;
            let file = btn.dataset.filename;

            document.getElementById("restoreFileName").textContent = file;
            document.getElementById("confirmRestoreBtn").href = "restore.php?id=" + id;
        });
    });

    // DELETE MODAL
    document.querySelectorAll(".open-delete-modal").forEach(btn => {
        btn.addEventListener("click", () => {
            let id = btn.dataset.id;
            let file = btn.dataset.filename;

            document.getElementById("deleteFileName").textContent = file;
            document.getElementById("confirmDeleteBtn").href = "delete_archive.php?id=" + id;
        });
    });

});
</script>


</body>
</html>
