<?php 
    require_once __DIR__ . "/../includes/config.php";
    require_once __DIR__ . "/../includes/PVDatabase.php";

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: projects.php');
        exit;
    }

    $name = trim($_POST['projectName'] ?? '');
    $desc = trim($_POST['projectDescription'] ?? '');
    $reqs = trim($_POST['projectReqs'] ?? '');
    $status = $_POST['projectStatus'] ?? '';
    $dept = $_POST['department'] ?? '';
    $repo = trim($_POST['repoLink'] ?? '');
    $teamId = (int)($_POST['teamId'] ?? 0);

    $allowedStatus = ['not started', 'in progress', 'completed'];
    $allowedDepts = ['Computer Science', 'Engineering'];

    if($name === '' || $teamId <= 0 || !in_array($status, $allowedStatus, true) || ($dept !== '' && !in_array($dept, $allowedDepts, true))){
        header("Location: projects.php?error=invalid");
        exit;
    }

    if ($repo !== '' && !filter_var($repo, FILTER_VALIDATE_URL)){
        header("Location: projects.php?error=invalid");
        exit;
    }

    $desc = $desc !== '' ? $desc : null;
    $reqs = $reqs !== '' ? $reqs : null;
    // $teamId = $teamId > 0 ? $teamId : null;
    $dept = $dept !== '' ? $dept : null;
    $repo = $repo !== '' ? $repo : null;

    try{
        PVDatabase::executeSQL(
            "INSERT INTO projects
                (project_name, project_description, project_requirements,
                project_status, department, team_id, repo_link)
            VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$name, $desc, $reqs, $status, $dept, $teamId, $repo]
        );
    } catch (PDOException $e){
        error_log("Insert project failed: " . $e->getMessage());
        header("Location: projects.php?error=db");
        exit;
    }

    header("Location: projects.php?added=1");
    exit;
?>