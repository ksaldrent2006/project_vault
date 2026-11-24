<?php
    session_start();
    require_once __DIR__ . "/../includes/config.php";
    require_once __DIR__ . "/../includes/PVDatabase.php";
    require_once __DIR__ . "/../includes/permissions.php";

    // only loggined in users can use this page
    if (!isset($_SESSION['person_id'])) {
        header('Location: login.php');
        exit;
    }

    $me = (int)$_SESSION['person_id'];
    $isAdmin = isAdmin($me);
    $message = '';

    function esc($value){
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    $requestId = (int)($_POST['requestId'] ?? 0);

    //handle creating requests
    if($action === "create") {
        $projectId = (int)($_POST['projectId'] ?? 0);
        $desc = trim($_POST['requestDescription'] ?? '');
        $desc = $desc !== '' ? $desc : null;

        if($projectId <= 0){
            header("Location: requests.php?error=invalid");
            exit;
        }

        try {
            PVDatabase::executeSQL(
                "INSERT INTO request (requestor_id, project_id, request_description, date_requested)
                VALUES (?, ?, ?, CURDATE())",
                [$me, $projectId, $desc]
            );
        } catch (PDOException $e) {
            if($e->getCode() === '23000'){
                header("Location: requests.php?error=duplicate");
                exit;
            }
            error_log("Request create failed: " . $e->getMessage());
            header("Location: requests.php?error=db");
            exit;
        }
        header("Location: requests.php?requested=1");
        exit;
    }

    //handle form approval and revoking
    if($requestId <= 0 || !in_array($action, ['approve', 'deny', 'revoke'], true)) {
        header("Location: requests.php?error=invalid");
        exit;
    }
    try {
        if ($action === 'approve' || $action === 'deny') {
            // look up the request and the team that owns it project
            $rows = PVDatabase::getDataFromSQL(
                "SELECT r.requestor_id, r.project_id, r.acceptance_status, p.team_id
                FROM request r
                JOIN projects p ON p.project_id = r.project_id
                WHERE r.request_id = ?",
                [$requestId]
            );
            $req = $rows[0] ?? null;

            // must exist, still be pending, not my own request
            // i need to be a team member or an admin
            if (!$req || $req['acceptance_status'] !== 'pending'
                || (int)$req['requestor_id'] === $me
                || !canManageProject($me, $req['project_id'])) {
                header("Location: requests.php?error=forbidden");
                exit;
            }

            if ($action === 'approve') {
                PVDatabase::executeSQL(
                    "UPDATE request SET acceptance_status = 'approved' WHERE request_id = ?",
                    [$requestId]
                );

                // add the requestor the the project's team
                PVDatabase::executeSQL(
                    "INSERT IGNORE INTO team_member (member_id, team_id) VALUES (?, ?)",
                    [$req['requestor_id'], $req['team_id']]
                );
                $result = "approved=1";
            } else {
                PVDatabase::executeSQL(
                    "UPDATE request SET acceptance_status = 'denied' WHERE request_id = ?",
                    [$requestId]
                );
                $result = "denied=1";
            }
        /*
        if ($action === 'approve'){
            PVDatabase::executeSQL(
                "UPDATE request r
                JOIN projects p ON p.project_id = r.project_id
                JOIN team_member tm ON tm.team_id = p.team_id
                SET r.acceptance_status = 'approved'
                WHERE r.request_id = ? and tm.member_id = ?
                AND r.requestor_id <> ? AND r.acceptance_status = 'pending'",
                [$requestId, $me, $me]
            );
            $result = "approved=1";
        */
        } else {
            PVDatabase::executeSQL(
                "DELETE FROM request
                WHERE request_id = ? AND requestor_id = ? AND acceptance_status = 'pending'",
                [$requestId, $me]
            );
            $result = "revoked=1";
        }
    } catch (PDOException $e){
        error_log("Request $action failed: " . $e->getMessage());
        header("Location: requests.php?error=db");
        exit;
    }
    header("Location: requests.php?" . $result);
    exit;
}

    $allRequests = PVDatabase::getDataFromSQL(
        //request_id, requestor_id, project_id, request_description, date_requested, acceptance_status
        "SELECT requestor_id, project_id, request_description, date_requested, acceptance_status
        FROM request;"
    );

    if ($isAdmin) {
        // admin seeing all pending requests
        $pendingRequests = PVDatabase::getDataFromSQL(
            "SELECT r.request_id, r.request_description, r.date_requested, p.project_name, per.first_name, per.last_name
            FROM request r
            JOIN projects p ON p.project_id = r.project_id
            JOIN person per ON per.person_id = r.requestor_id
            WHERE r.requestor_id <> ? AND r.acceptance_status = 'pending'
            ORDER BY r.date_requested", [$me]
        );
    } else {
        // team members see their own project's requests
        $pendingRequests = PVDatabase::getDataFromSQL(
            "SELECT r.request_id, r.request_description, r.date_requested, p.project_name, per.first_name, per.last_name
            FROM request r
            JOIN projects p ON p.project_id = r.project_id
            JOIN person per ON per.person_id = r.requestor_id
            JOIN team_member tm ON tm.team_id = p.team_id
            WHERE tm.member_id = ? AND r.requestor_id <> ? AND r.acceptance_status = 'pending'
            ORDER BY r.date_requested", [$me, $me]
        );        
    }

    /*
    $pendingRequests = PVDatabase::getDataFromSQL(
        "SELECT r.request_id, r.request_description, r.date_requested, p.project_name, per.first_name, per.last_name
        FROM request r
        JOIN projects p ON p.project_id = r.project_id
        JOIN person per ON per.person_id = r.requestor_id
        JOIN team_member tm ON tm.team_id = p.team_id
        WHERE tm.member_id = ? AND r.requestor_id <> ? AND r.acceptance_status = 'pending'
        ORDER BY r.date_requested", [$me, $me]
    );
    */

    $myRequests = PVDatabase::getDataFromSQL(
        "SELECT r.request_id, r.request_description, r.date_requested, r.acceptance_status, p.project_name
        FROM request r
        LEFT JOIN projects p ON p.project_id = r.project_id
        WHERE r.requestor_id = ?
        ORDER BY r.date_requested DESC",
        [$me]
    );

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Vault | Requests</title>

    <link rel="stylesheet" href="style.css">
</head>
<body class="projects-page">
    <div id="navBar"></div>
    <h1>Requests</h1>
    <?php if (isset($_GET['requested'])): ?>
        <p class="note">Request sent.</p>
    <?php elseif (($_GET['error'] ?? '') === 'duplicate'): ?>
        <p class="note">You've already requested that project.</p>
    <?php elseif (isset($_GET['approved'])): ?>
        <p class="note">Request approved.</p>
    <?php elseif (isset($_GET['denied'])): ?>
        <p class="note">Request denied.</p>
    <?php elseif (($_GET['error'] ?? '') === 'forbidden'): ?>
        <p class="note">You can only answer requests for your own team's projects.</p>
    <?php elseif (isset($_GET['revoked'])): ?>
        <p class="note">Request revoked.</p>
    <?php elseif (($_GET['error'] ?? '') === 'invalid'): ?>
        <p class="note">Something was wrong with that request.</p>
    <?php elseif (($_GET['error'] ?? '') === 'db'): ?>
        <p class="note">Could not complete that action. Please try again later.</p>
    <?php endif; ?>

    <h2>Pending Requests</h2>
    <div class="projects">
        <!-- For each loop here to go through all the projects that you can approve requests for -->
         <?php if (empty($pendingRequests)): ?>
            <p class="note">No requests currently awaiting approval.</p>
        <?php endif; ?>
        <?php foreach ($pendingRequests as $r): ?>
        <div class="card project-card">
            <div class="project-title">
                <h3><?= esc($r['project_name']) ?></h3>
            </div>
            <div class="project-details">
                <p>Requested by: <?= esc($r['first_name'] . ' ' . $r['last_name']) ?></p>
                <p>Requested on: <?= esc($r['date_requested']) ?></p>
                <p>Description: <?= esc($r['request_description']) ?></p>
                <form action="requests.php" method="POST">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="requestId" value="<?= (int)$r['request_id'] ?>">
                    <button type="submit" class="btn">Approve?</button>
                </form>
                <br>
                <form action="requests.php" method="POST" onsubmit="return confirm('Deny this request?');">
                    <input type="hidden" name="action" value="deny">
                    <input type="hidden" name="requestId" value="<?= (int)$r['request_id'] ?>">
                    <button type="submit" class="btn btn-danger">Deny</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <h2>My Requests</h2>
    <div class="projects">
        <!-- For each loop here to go through all the projects that you sent requests for -->
         <?php if (empty($myRequests)): ?>
            <p class="note">You haven't requested any projects.</p>
        <?php endif; ?>
        <?php foreach ($myRequests as $r): ?>
        <div class="card project-card">
            <div class="project-title">
                <h3><?= esc($r['project_name']) ?></h3>
            </div>
            <div class="project-details">
                <p>Requested on: <?= esc($r['date_requested']) ?></p>
                <p>Description: <?= esc($r['request_description']) ?></p>
                <p>Approval status: <?= esc($r['acceptance_status']) ?></p>
                <?php if ($r['acceptance_status'] === 'pending'): ?>
                    <form action="requests.php" method="POST">
                        <input type="hidden" name="action" value="revoke">
                        <input type="hidden" name="requestId" value="<?= (int)$r['request_id'] ?>">
                        <button type="submit" class="btn btn-danger">Revoke request?</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        window.addEventListener('load', init);

        function init(){
            fetch("navbar.php")
                    .then(res => res.text())
                    .then(data => {
                        document.getElementById("navBar").innerHTML = data;
                    })
                    .catch(error => console.error("Error loading navbar: ", error));
        
        }
    </script>
</body>
</html>

