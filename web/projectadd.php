<?php 


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

    session_start();
    require_once __DIR__ . "/../includes/config.php";
    require_once __DIR__ . "/../includes/PVDatabase.php";
    require_once __DIR__ . "/../includes/permissions.php";


    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: projects.php');
        exit;
    }

    // only logged in users can add/edit/delete
    if (!isset($_SESSION['person_id'])) {
        header('Location: login.php');
        exit;
    }

    function redirect($query){
        header("Location: projects.php?" . $query);
        exit;
    }

    // replace a project's contributor list wiht the names typed in the form
    function saveContributors($projectId, $text) {
        // remove old list first, handle edits
        PVDatabase::executeSQL("DELETE FROM project_contributor WHERE project_id = ?", [$projectId]);

        $names = explode(',', $text); // split on commas
        foreach ($names as $n) {
            $n = trim($n); // remove extra spaces
            if ($n === '') continue; // skip empty pieces like "John,,Ana"
            PVDatabase::executeSQL(
                "INSERT INTO project_contributor (project_id, contributor_name) VALUES (?, ?)",
                [$projectId, mb_substr($n, 0, 100)] // column is VARCHAR(100)
            );
        }
    }

    /*
    function isTeamMember($personId, $projectId) {
        $personId = (int)$personId;
        $projectId = (int)$projectId;
        $rows = PVDatabase::getDataFromSQL(
            "SELECT 1 FROM projects p
             JOIN team_member tm ON tm.team_id = p.team_id
             WHERE p.project_id = $projectId AND tm.member_id = $personId"
        );
        return !empty($rows);
    }
    */

    $me = (int)$_SESSION['person_id'];
    $action = $_POST['action'] ?? '';
    $projectId = (int)($_POST['projectId'] ?? 0);

    // only team members can edit or delete a project
    if (($action == 'edit' || $action === 'delete') && !canManageProject($me, $projectId)) {
        redirect("error=forbidden");
    }

    // DELETE Function
    if($action === 'delete'){
        if($projectId <= 0) redirect("error=invalid");

        try{
            PVDatabase::executeSQL("DELETE FROM projects WHERE project_id = ?", [$projectId]);
        }catch (PDOException $e){
            error_log("Delete project failed: " . $e->getMessage());
            redirect("error=db");
        }
        redirect("deleted=1");
    }

// Add and Edit

    if($action !== 'add' && $action !== 'edit'){
        redirect("error=invalid");
    }

    $name = trim($_POST['projectName'] ?? '');
    $desc = trim($_POST['projectDescription'] ?? '');
    $reqs = trim($_POST['projectReqs'] ?? '');
    $status = $_POST['projectStatus'] ?? '';
    $dept = $_POST['department'] ?? '';
    $repo = trim($_POST['repoLink'] ?? '');
    $teamId = (int)($_POST['teamId'] ?? 0);
    $contributors = trim($_POST['contributors'] ?? '');

    $allowedStatus = ['not started', 'in progress', 'completed', 'discontinued'];
    $allowedDepts = ['Computer Science', 'Engineering'];

    if($name === '' || $teamId <= 0 
        || !in_array($status, $allowedStatus, true) 
        || ($dept !== '' && !in_array($dept, $allowedDepts, true))
        || ($action === 'edit' && $projectId <= 0)){
        redirect("error=invalid");
    }

    if ($repo !== '' && !filter_var($repo, FILTER_VALIDATE_URL)){
        redirect("error=invalid");
    }

    // you can only put a project on a team you are on (admin can pick any team)
    // (the dropdown already hides other teams, this stops a forged form)
    if (!isAdmin($me)) {
        $onTeam = PVDatabase::getDataFromSQL(
            "SELECT 1 FROM team_member WHERE member_id = ? AND team_id = ?",
            [$me, $teamId]
        );
        if (empty($onTeam)) {
            redirect("error=team");
        }
    }

    $desc = $desc !== '' ? $desc : null;
    $reqs = $reqs !== '' ? $reqs : null;
    // $teamId = $teamId > 0 ? $teamId : null;
    $dept = $dept !== '' ? $dept : null;
    $repo = $repo !== '' ? $repo : null;

    try{
        if($action === 'add'){
            PVDatabase::executeSQL(
            "INSERT INTO projects
                (project_name, project_description, project_requirements,
                project_status, department, team_id, repo_link)
            VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$name, $desc, $reqs, $status, $dept, $teamId, $repo]
            );
            // redirect('added=1');

            // find the id of the project added
            $newRows = PVDatabase::getDataFromSQL(
                "SELECT project_id FROM projects
                 WHERE project_name = ? AND team_id = ?
                 ORDER BY project_id DESC LIMIT 1",
                [$name, $teamId]
            );
            if (!empty($newRows)) {
                saveContributors((int)$newRows[0]['project_id'], $contributors);
            }
            redirect('added=1');
        }

        PVDatabase::executeSQL(
            "UPDATE projects
            SET project_name = ?, project_description = ?, project_requirements = ?, 
            project_status = ?, department = ?, team_id = ?, repo_link = ?
            WHERE project_id = ?;",
            [$name, $desc, $reqs, $status, $dept, $teamId, $repo, $projectId]
        );
        //redirect("updated=1");
        saveContributors($projectId, $contributors);
        redirect("updated=1");

    } catch (PDOException $e){
        error_log("Project $action failed: " . $e->getMessage());
        redirect("error=db");
    }
?>