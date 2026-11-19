<?php 
    session_start();
    require_once __DIR__ . "/../includes/config.php";
    require_once __DIR__ . "/../includes/PVDatabase.php";

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: projects.php');
        exit;
    }

    // only logged in users can add/edit/delete
    if (!isset($_SESSION['person_id']) {
        header('Location: login.php');
        exit;
    })

    function redirect($query){
        header("Location: projects.php?" . $query);
        exit;
    }

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

    $me = (int)$_SESSION['person_id'];
    $action = $_POST['action'] ?? '';
    $projectId = (int)($_POST['projectId'] ?? 0);

    // only team members can edit or delete a project
    if (($action == 'edit' || $action === 'delete') && !isTeamMember($me, $projectId)) {
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
            redirect('added=1');
        }

        PVDatabase::executeSQL(
            "UPDATE projects
            SET project_name = ?, project_description = ?, project_requirements = ?, 
            project_status = ?, department = ?, team_id = ?, repo_link = ?
            WHERE project_id = ?;",
            [$name, $desc, $reqs, $status, $dept, $teamId, $repo, $projectId]
        );
        redirect("updated=1");

    } catch (PDOException $e){
        error_log("Project $action failed: " . $e->getMessage());
        redirect("error=db");
    }
?>