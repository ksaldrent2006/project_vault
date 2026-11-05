<?php
/**
 * teams.php
 *  - My Teams: see the teams i'm on, leave one
 *  - Create a Team: make a new team and join it automatically
 *  - All Teams: search/sort every team and join one
 */

session_start();
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/PVDatabase.php";

// only loggined in users can use this page
if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$me = $_SESSION['person_id'];
$message = '';

function esc($value){
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
* handle forms - create team, join team, or leave team
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $teamId = (int)($_POST['team_id'] ?? 0);

    try {
        if ($action === 'create') {
            $newId = PVDatabase::executeSQL(
                "INSERT INTO team (team_name, advisor, start_date, end_date)
                VALUES (?, ?, ?, ?)",

                [trim($_POST['team_name']), trim($_POST['advisor']),
                 $_POST['start_date'] ?: null, $_POST['end_date'] ?: null], 
                true               
            );
            PVDatabase::executeSQL("INSERT INTO team_member (member_id, team_id) VALUES (?, ?)",
            [$me, $newId]);
            $message = 'Team created!';
        }

        if ($action === 'join') {
            PVDatabase::executeSQL("INSERT INTO team_member (member_id, team_id) VALUES (?, ?)",
            [$me, $teamId]);
            $message = 'You joined the team!';
        }

        if ($action === 'leave') {
            PVDatabase::executeSQL("DELETE FROM team_member WHERE member_id = ? AND team_id = ?",
            [$me, $teamId]);
            $message = 'You left the team.';
        }

        } catch (PDOException $e) {
            // duplicate entry
            $message = ($e->errorInfo[1] == 1062) ? "You're already on that team." : 'Something went wrong.';        
        }
}

// my teams
$myTeams = PVDatabase::getDataFromSQL(
    "SELECT t.* FROM team t
    JOIN team_member tm ON tm.team_id = t.team_id
    WHERE tm.member_id = ?
    ORDER BY t.team_name",
    [$me]
);

// all teams (search and sort)
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$sortOptions = [
    'newest' => 't.team_id DESC',
    'start' => 't.start_date ASC',
    'name' => 't.team_name ASC'
];
$orderBy = $sortOptions[$sort] ?? $sortOptions['newest'];

// is_member = 1 is for when i'm on the team (shows Joined instead of a Join button)
$allTeams = PVDatabase::getDataFromSQL(
    "SELECT t.*,
            EXISTS (SELECT 1 FROM team_member tm
                    WHERE tm.team_id = t.team_id AND tm.member_id = ?) AS is_member
     FROM team t
     WHERE t.team_name LIKE ? OR t.advisor LIKE ?
     ORDER BY $orderBy",
    [$me, "%$search%", "%$search%"]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Vault | Teams</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class='projects-page'>
    <div id="navBar"></div>
    <h1>Teams</h1>
    <!-- NOTE: this html reuse some of the stuff from projects.php for consistency -->

    <?php if ($message): ?>
        <div class="message"><?= esc($message) ?></div>
    <?php endif; ?>


    <!-- my teams -->
    <h2>My Teams</h2>
    <?php if (!$myTeams): ?>
        <p class="note">You're not on any teams yet.</p>
    <?php endif; ?>
 
    <div class="projects">
        <?php foreach ($myTeams as $t): ?>
            <div class="card project-card">
                <div class="project-title">
                    <h3><?= esc($t['team_name']) ?></h3>
                </div>
                <div class="project-details">
                    <p>Advisor: <?= esc($t['advisor']) ?></p>
                    <span>Start Date: <?= esc($t['start_date']) ?></span>
                    <span>End Date: <?= esc($t['end_date']) ?></span>
                </div>
                <div class="project-links">
                    <form method="post" onsubmit="return confirm('Leave this team?');">
                        <input type="hidden" name="action" value="leave">
                        <input type="hidden" name="team_id" value="<?= $t['team_id'] ?>">
                        <button type="submit" class="error">Leave team</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
 
    <!-- create a team -->
    <h2>Create a Team</h2>
    <div class="card create-team">
        <form method="post" class="auth-form">
            <input type="hidden" name="action" value="create">
            <label>Team Name <input type="text" name="team_name" required></label>
            <label>Advisor <input type="text" name="advisor"></label>
            <label>Start Date <input type="date" name="start_date"></label>
            <label>End Date <input type="date" name="end_date"></label>
            <button type="submit">Create Team</button>
        </form>
    </div>
 
    <!-- all teams -->
    <h2>All Teams</h2>
    <form method="get" class="search-sort">
        <label for="searchBar">Search:</label>
        <input type="text" id="searchBar" name="search" placeholder="Search teams..." value="<?= esc($search) ?>">
        <label for="sort">Sort by</label>
        <select id="sort" name="sort">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="start"  <?= $sort === 'start'  ? 'selected' : '' ?>>Start date</option>
            <option value="name"   <?= $sort === 'name'   ? 'selected' : '' ?>>Name (A-Z)</option>
        </select>
        <button type="submit">Search</button>
    </form>
 
    <?php if (!$allTeams): ?>
        <p>No teams found.</p>
    <?php endif; ?>
 
    <div class="projects">
        <?php foreach ($allTeams as $t): ?>
            <div class="card project-card">
                <div class="project-title">
                    <h3><?= esc($t['team_name']) ?></h3>
                </div>
                <div class="project-details">
                    <p>Advisor: <?= esc($t['advisor']) ?></p>
                    <span>Start Date: <?= esc($t['start_date']) ?></span>
                    <span>End Date: <?= esc($t['end_date']) ?></span>
                </div>
                <div class="project-links">
                    <?php if ($t['is_member']): ?>
                        <span class="note">Joined ✓</span>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="action" value="join">
                            <input type="hidden" name="team_id" value="<?= $t['team_id'] ?>">
                            <button type="submit">Join team</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
 
    <!-- load the navbar, same as projects.php -->
    <script>
        window.addEventListener('load', () => {
            fetch("navbar.php")
                .then(res => res.text())
                .then(data => { document.getElementById("navBar").innerHTML = data; })
                .catch(error => console.error("Error loading navbar: ", error));
        });
    </script>
</body>
</html>
