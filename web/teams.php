<?php
/**
 * teams.php
 *  - My Teams: see the teams i'm on, leave one
 *  - Create a Team: make a new team and join it automatically
 *  - All Teams: search/sort every team and join one
 *  - Admin only: add anyone to a team, remove anyone from a team
 */

session_start();
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/PVDatabase.php";
require_once __DIR__ . "/../includes/permissions.php";

// only loggined in users can use this page
if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$me = $_SESSION['person_id'];
$isAdmin = isAdmin($me);
$message = '';
$messageType = 'success';

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

        // NOTE: there is no "join" action. students join a team by requesting a project.
        /*
        if ($action === 'join') {
            PVDatabase::executeSQL("INSERT INTO team_member (member_id, team_id) VALUES (?, ?)",
            [$me, $teamId]);
            $message = 'You joined the team!';
        }
        */

        if ($action === 'leave') {
            PVDatabase::executeSQL("DELETE FROM team_member WHERE member_id = ? AND team_id = ?",
            [$me, $teamId]);
            $message = 'You left the team.';
        }

        // admin only: add person
        if ($action === 'admin_add' && $isAdmin) {
            $personId = (int)($_POST['person_id'] ?? 0);
            if ($personId <= 0 || $teamId <= 0) {
                $message = 'Please pick a person and a team.';
                $messageType = 'error';
            } else {
                PVDatabase::executeSQL(
                    "INSERT IGNORE INTO team_member (member_id, team_id) VALUES (?, ?)",
                    [$personId, $teamId]
                );
                $message = 'Member added to team.';
            }
        }

        // admin only: remove person
        if ($action === 'admin_remove' && $isAdmin) {
            PVDatabase::executeSQL("DELETE FROM team_member WHERE member_id = ? AND team_id = ?",
            [(int)($_POST['person_id'] ?? 0), $teamId]);
            $message = 'Member removed from team.';
        }

        } catch (PDOException $e) {
            error_log("Teams action failed: " . $e->getMessage());
            $message = 'Something went wrong.';
            $messageType = 'error';
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

// team members
$memberRows = PVDatabase::getDataFromSQL(
    "SELECT tm.team_id, p.person_id, p.first_name, p.last_name, p.email_address
    FROM team_member tm
    JOIN person p ON p.person_id = tm.member_id
    ORDER BY p.first_name, p.last_name"
);

$membersByTeam = [];
foreach ($memberRows as $row) {
    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    if ($name === '') {
        $name = $row['email_address']; 
    }
    $membersByTeam[$row['team_id']][] = ['id' => (int)$row['person_id'], 'name' => $name];
}

function memberNames($members) {
    return implode(', ', array_column($members, 'name'));
}

$allTeams = PVDatabase::getDataFromSQL(
    "SELECT t.*,
        EXISTS (SELECT 1 FROM team_member tm
                WHERE tm.team_id = t.team_id AND tm.member_id = ?) AS
        is_member 
        FROM team t
        ORDER BY t.team_id DESC",
        [$me]
);

// admin only: everyone for dropdown
$people = [];
if ($isAdmin) {
    $people = PVDatabase::getDataFromSQL(
        "SELECT person_id, first_name, last_name, email_address, role
         FROM person
         WHERE currently_active = 1
         ORDER BY last_name, first_name"
    );
}

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
        <div class="message <?= $messageType ?>"><?= esc($message) ?></div>
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
                    <p>Members: <?= esc(memberNames($membersByTeam[$t['team_id']] ?? [])) ?></p>
                    <span>Start Date: <?= esc($t['start_date']) ?></span>
                    <span>End Date: <?= esc($t['end_date']) ?></span>
                </div>
                <div class="project-links">
                    <form method="post" onsubmit="return confirm('Leave this team?');">
                        <input type="hidden" name="action" value="leave">
                        <input type="hidden" name="team_id" value="<?= $t['team_id'] ?>">
                        <button type="submit" class="btn btn-danger">Leave team</button>
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
            <button type="submit" class="btn">Create Team</button>
        </form>
    </div>

    <!-- admin only: add anyone to any team -->
    <?php if ($isAdmin): ?>
        <h2>Add a Member <span class="admin-tag">(Admin)</span></h2>
        <div class="card create-team">
            <form method="post" class="auth-form">
                <input type="hidden" name="action" value="admin_add">
                <label>Person
                    <select name="person_id" required>
                        <option value="">Select a person</option>
                        <?php foreach ($people as $p): ?>
                            <option value="<?= (int)$p['person_id'] ?>">
                                <?= esc($p['first_name'] . ' ' . $p['last_name']) ?> (<?= esc($p['email_address']) ?>, <?= esc($p['role']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Team
                    <select name="team_id" required>
                        <option value="">Select a team</option>
                        <?php foreach ($allTeams as $t): ?>
                            <option value="<?= (int)$t['team_id'] ?>"><?= esc($t['team_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="btn">Add to team</button>
            </form>
        </div>
    <?php endif; ?>
 
    <!-- all teams -->
    <h2>All Teams</h2>
    <div class="search-sort">
        <label for="searchBar">Search:</label>
        <input type="text" id="searchBar" placeholder="Search teams...">
        <label for="sort">Sort by</label>
        <select id="sort">
            <option value="newest" selected>Newest</option>
            <option value="start">Start Date</option>
            <option value="name">Name (A-Z)</option>
        </select>
    </div>
 
    <p id="noResults" <?= $allTeams ? 'style="display:none"' : '' ?>>No teams found.</p>

    <div class="projects" id="allTeamsList">
        <?php foreach ($allTeams as $t):
            $members = $membersByTeam[$t['team_id']] ?? [];
            $searchText = strtolower(($t['team_name'] ?? '') . ' ' . ($t['advisor'] ?? ''));
        ?>
 
    <div class="card project-card team-card"
        data-search="<?= esc($searchText) ?>"
        data-id="<?= (int)$t['team_id'] ?>"
        data-name="<?= esc(strtolower($t['team_name'] ?? '')) ?>"
        data-start="<?= esc($t['start_date']) ?>">
    <div class="project-title">
        <h3><?= esc($t['team_name']) ?></h3>
        </div>
            <div class="project-details">
                <p>Advisor: <?= esc($t['advisor']) ?></p>
                <span>Start Date: <?= esc($t['start_date']) ?></span>
                <br>
                <span>End Date: <?= esc($t['end_date']) ?></span>
            </div>
            <?php if ($isAdmin): ?>
                <!-- admin: every member with a remove button -->
                <?php if (!$members): ?>
                    <p class="note">No members yet. Add someone with the form above.</p>
                <?php else: ?>
                    <ul class="member-list">
                        <?php foreach ($members as $m): ?>
                            <li>
                                <span><?= esc($m['name']) ?></span>
                                <form method="post" class="inline-form" onsubmit="return confirm('Remove this person from the team?');">
                                    <input type="hidden" name="action" value="admin_remove">
                                    <input type="hidden" name="team_id" value="<?= (int)$t['team_id'] ?>">
                                    <input type="hidden" name="person_id" value="<?= (int)$m['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Remove</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
            <div class="project-links">
                <?php if ($t['is_member']): ?>
                    <span class="note">Joined ✓</span>
                <?php elseif (!$isAdmin): ?>
                    <span class="note">To join, request one of this team's projects on the <a href="projects.php">Projects</a> page.</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
 
    <script>
        window.addEventListener('load', init);

        function init() {
            // load the navbar, same as projects.php
            fetch("navbar.php")
                .then(res => res.text())
                .then(data => { document.getElementById("navBar").innerHTML = data; })
                .catch(error => console.error("Error loading navbar: ", error));

                // input happens on every key press, so the list update as you type
                document.getElementById("searchBar").addEventListener('input', updateSearch);
                document.getElementById("sort").addEventListener('change', updateSort);
        }

        function updateSearch() {
            const query = document.getElementById("searchBar").value.toLowerCase().trim();
            let shown = 0;

            document.querySelectorAll(".team-card").forEach(card => {
                const match = card.dataset.search.includes(query);
                card.style.display = match ? "" : "none";
                if (match) shown++;
            });

            document.getElementById("noResults").style.display = (shown === 0) ? "" : "none";
        }

        function updateSort() {
            const sort = document.getElementById("sort").value;
            const list = document.getElementById("allTeamsList");
            const cards = Array.from(list.querySelectorAll(".team-card"));

            cards.sort((a, b) => {
                if (sort === "name") {
                    return a.dataset.name.localeCompare(b.dataset.name);
                }
                if (sort === "start") {
                    if (!a.dataset.start) return 1;
                    if (!b.dataset.start) return -1;
                    return a.dataset.start.localeCompare(b.dataset.start);
                }
                return b.dataset.id - a.dataset.id;
            });

            cards.forEach(card => list.appendChild(card));
        }
    </script>
</body>
</html>
