<?php
/**
 * progress.php
 * - opened from the "See project documentation button: progress.php?project_id=...
 * - shows project info at the top
 * - shows all progress updates (newest first)
 * - you can add a new update or delete one
 */

session_start();
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/PVDatabase.php";
 
// only logged in users can use this page
if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}
 
function esc($value){
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// project id
$projectId = (int)($_GET['project_id'] ?? 0);

// status
$statuses = ['not started', 'in progress', 'completed', 'discontinued'];

/**
 * handle form - add an update or delete one
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $status = $_POST['status'] ?? '';
        $link = trim($_POST['visual_materials'] ?? '');

        // allow statuses from list
        if (!in_array($status, $statuses)) {
            $status = 'in progress';
        }

        // check link
        if ($link !== '' && !preg_match('#^https?://#i', $link)) {
            header("Location: progress.php?project_id=$projectId&error=link");
            exit;
        }

        PVDatabase::executeSQL(
            "INSERT INTO progress (project_id, status, challenges, task_steps,
            visual_materials, project_start_date, project_end_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $projectId,
                $status,
                trim($_POST['challenges'] ?? ''),
                trim($_POST['task_steps'] ?? ''),
                $link ?: null,
                $_POST['start_date'] ?: null,
                $_POST['end_date'] ?: null
            ]
        );

        // keep project's status in sync wiht the updates
        PVDatabase::executeSQL(
            "UPDATE projects SET project_status = ? WHERE project_id = ?",
            [$status, $projectId]
        );

        header("Location: progress.php?project_id=$projectId&added=1");
        exit;      
    }

    if ($action === 'delete') {
        PVDatabase::executeSQL(
            "DELETE FROM progress WHERE progress_id = ? AND project_id = ?",
            [(int)($_POST['progress_id'] ?? 0), $projectId]
        );
        header("Location: progress.php?project_id=$projectId&deleted=1");
        exit;
    }
}

// project and team
$projectRows = PVDatabase::getDataFromSQL(
    "SELECT p.*, t.team_name, t.advisor
     FROM projects p
     LEFT JOIN team t ON t.team_id = p.team_id
     WHERE p.project_id = ?",
    [$projectId]
);
$project = $projectRows[0] ?? null;

// all updates
$updates = PVDatabase::getDataFromSQL(
    "SELECT * FROM progress WHERE project_id = ? ORDER BY progress_id DESC",
    [$projectId]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Vault | Progress</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="projects-page">
    <div id="navBar"></div>

<?php if (!$project): ?>
    <h1>Project not found</h1>
    <p><a href="projects.php">Back to projects</a></p>
<?php else: ?>

    <main class="progress-page">
        <a href="projects.php">Back to projects</a>

    <!-- project info -->
        <div class="card project-card">
            <h1><?= esc($project['project_name'] ?: 'No Title Given') ?></h1>
            <p><strong>Department:</strong> <?= esc($project['department']) ?></p>
            <p><strong>Team:</strong> <?= esc($project['team_name']) ?> (<?= esc($project['advisor']) ?>)</p>
            <p><strong>Description:</strong> <?= esc($project['project_description']) ?></p>
            <!-- current status -->
            <p><strong>Current status:</strong> <?= esc(ucfirst($updates[0]['status'] ?? $project['project_status'] ?? 'none')) ?></p>
            <p><strong>Repository:</strong>
                <?php if ($project['repo_link']): ?>
                    <a href="<?= esc($project['repo_link']) ?>" target="_blank" rel="noopener">View repository</a>
                <?php else: ?>
                    No repository link given.
                <?php endif; ?>
            </p>
            <button type="button" class="btn" id="openAdd">+ Add update</button>
        </div>

        <?php if (isset($_GET['added'])): ?> <p class="note">Update added.</p>
        <?php elseif (isset($_GET['deleted'])): ?> <p class="note">Update deleted.</p>
        <?php elseif (isset($_GET['error'])): ?> <p class="note">The link has to start with http:// or https://</p>
        <?php endif; ?>

        <h2>Progress Updates</h2>
        <?php if (!$updates): ?>
            <p class="note">No updates yet.</p>
        <?php endif; ?>
 
        <!-- one card per update -->
        <?php foreach ($updates as $u): ?>
            <div class="card project-card">
                <h3><?= esc(ucfirst($u['status'])) ?></h3>
                <p class="note"><?= esc($u['project_start_date'] ?: 'no start date') ?> to <?= esc($u['project_end_date'] ?: 'no end date') ?></p>
 
                <p><strong>Task steps:</strong><br><?= nl2br(esc($u['task_steps'])) ?></p>
                <p><strong>Challenges:</strong><br><?= nl2br(esc($u['challenges'])) ?></p>
 
                <?php if ($u['visual_materials']): ?>
                    <p><a href="<?= esc($u['visual_materials']) ?>" target="_blank" rel="noopener">Visual materials</a></p>
                <?php endif; ?>
 
                <form method="POST" onsubmit="return confirm('Delete this update?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="progress_id" value="<?= (int)$u['progress_id'] ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        <?php endforeach; ?>
    </main>

    <!-- popup form like projects.php -->
    <dialog id="addForm">
        <h3>Add progress update</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <label>Status
                <select name="status">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= esc($s) ?>"><?= esc(ucfirst($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Task steps <textarea name="task_steps" rows="4"></textarea></label>
            <label>Challenges <textarea name="challenges" rows="3"></textarea></label>
            <label>Visual materials link <input type="url" name="visual_materials" placeholder="https://..."></label>
            <label>Start date <input type="date" name="start_date"></label>
            <label>End date <input type="date" name="end_date"></label>
            <div class="actions">
                <button type="button" id="closeBtn">Close</button>
                <button type="submit" id="submitBtn">Add update</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>

    <script>
        window.addEventListener('load', init);

        function init() {
            fetch("navbar.php")
            .then(res => res.text())
            .then(data => { document.getElementById("navBar").innerHTML = data; })
            .catch(error => console.error("Error loading navbar: ", error));

            const popup = document.getElementById("addForm");
            if (popup) {
                document.getElementById("openAdd").addEventListener('click', () => popup.showModal());
                document.getElementById("closeBtn").addEventListener('click', () => popup.close());
            }
        }
    </script>
</body>
</html>