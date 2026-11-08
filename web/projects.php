<?php
    require_once __DIR__ . "/../includes/config.php";
    require_once __DIR__ . "/../includes/PVDatabase.php";

    function esc($value){
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
    $projects = PVDatabase::getDataFromSQL(
        //make sure to add a project title column, I feel like we need that here
        "SELECT project_id, department, project_status, project_description, team_id, repo_link, project_name
        FROM projects"
    );
    $teams = PVDatabase::getDataFromSql(
        "SELECT start_date, end_date, advisor, team_name, team_id
        FROM team;"
    );

    $projectInfo = PVDatabase::getDataFromSQL(
        "SELECT p.project_id, p.department, p.project_status, p.project_description, p.team_id, p.repo_link, p.project_name, t.start_date, t.end_date, t.advisor, t.team_name
        FROM projects p
        LEFT JOIN team t ON t.team_id = p.team_id"
    );

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Project Vault | Projects</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body class="projects-page">
        <div id="navBar">
        </div>
        <h1>Projects</h1>
            <div class="search-sort">
                <label for="searchBar">Search:</label>
                <input type="text" id="searchBar" placeholder="Search projects...">
                <label for="deptSort">Sort by Department</label>
                <select id="deptSort">  
                    <option value="all" selected>All Departments</option>
                    <option value="computer science">Computer Science</option>
                    <option value="engineering">Engineering</option>
                </select>
                <!-- maybe add a sort by start date and end date feature -->
                <button class="addProject">Add Project</button>
            </div>
            <?php if(empty($projects)): ?>
                <p>No projects found.</p>
            <?php endif; ?>
            <dialog id="addForm">
                <h3>Add Project</h3>
                <!-- this will need to be connected to where our post methods are stored -->
                <form action="projectadd.php" method="POST">
                    <label for="projectName">Project Name:</label>
                    <input type="text" id="projectName" name="projectName" required>
                    <label for="allTeams">Team:</label>
                        <select id="allTeams" name="teamId"> 
                            <option value="">Select a team</option>
                            <!-- <option value="0">No team</option> -->
                            <?php foreach ($teams as $t): ?>
                                <option value="<?= (int)$t['team_id'] ?>"><?= esc($t['team_name'])?></option>
                            <?php endforeach; ?>
                        </select>
                    <label for="projectDescription">Project Description:</label>
                    <input type="text" id="projectDescription" name="projectDescription">
                    <label for="projectReqs">Project Requirements:</label>
                    <input type="text" id="projectReqs" name="projectReqs">
                    <label for="projectStatus">Status: </label>
                        <select id="projectStatus" name="projectStatus">
                            <option value="not started"> Not Started </option>  
                            <option value="in progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    <label for="depts">Department:</label>
                        <select id="depts" name="department">
                            <option value=""> No Department Selected </option>  
                            <option value="Computer Science">Computer Science</option>
                            <option value="Engineering">Engineering</option>
                        </select>
                    <label for="repoLink">Repository Link:</label>
                    <input type="text" id="repoLink" name="repoLink">
                    <div class="actions">
                        <button type="button" id="closeBtn">Close</button>
                        <button type="submit" id="submitBtn">Add Project</button>
                    </div>
                </form>
            </dialog>
            <?php if (isset($_GET['added'])): ?>
                <p class="note">Project successfully added.</p>
            <?php elseif (($_GET['error'] ?? '') == 'invalid'): ?>
                <p class="note">Please double check your inputs and try again. </p>
            <?php elseif (($_GET['error'] ?? '') === 'db'): ?>
                <p class="note">Could not save project. Please try again later. </p>
            <?php endif; ?>

            <div class="projects">
                <?php foreach ($projectInfo as $p): 
                    $searchText = strtolower(
                        ($p['project_name'] ?? '') . ' ' .
                        ($p['team_name'] ?? ' '));
                ?>
                    <div class="card project-card" data-search="<?= esc($searchText) ?>" data-dept="<?= esc(mb_strtolower($p['department'] ?? '')) ?>">
                        <div class="project-title">
                            <?php if(!empty($p['project_name'])): ?>
                                <h3><?= esc($p['project_name'])?> <span>(<?= esc($p['department']) ?>)</span> </h3>
                            <?php else: ?>
                                <h3>No Title Given <span>(<?= esc($p['department']) ?>)</span> </h3>
                            <?php endif; ?>
                            <p>Project status: <?= esc($p['project_status']) ?></p>  
                        </div>
                        <div class="project-details">
                        <p>Project description and requirements: <?= esc($p['project_description']) ?></p> 
                        <p>Team(s) that worked on the project: <?= esc($p['team_name']) ?></p>
                        <span>Start Date: <?= esc($p['start_date']) ?></span>
                        <span>End Date: <?= esc($p['end_date']) ?></span>
                    </div>
                    <div class="project-links">
                        <p>Repo link:
                        <?php if(!empty($p['repo_link'])): ?>
                            <a href="<?= esc($p['repo_link']) ?>" target="_blank" rel="noopener">Repository</a>
                        <?php else: ?>
                            <span>No repository link given.</span>
                        <?php endif; ?>
                        </p>
                        <button id="req" class="proj_req">Request project</button>
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
        
                    document.querySelectorAll(".project-title").forEach(t => {
                        t.addEventListener('click', openDetails);
                    });
                    document.querySelectorAll(".proj_req").forEach(r => {
                        r.addEventListener('click', requestProject);
                    });

                    let searchBar = document.getElementById("searchBar");
                    let sort = document.getElementById("deptSort");

                    searchBar.addEventListener('input', updateSearch);
                    sort.addEventListener('change', updateSearch);

                    const popup = document.getElementById("addForm");
                    const addBtn = document.querySelector(".addProject")
                    addBtn.addEventListener('click', () => {
                        popup.showModal();
                    });
                    const closeBtn = document.getElementById("closeBtn");
                    closeBtn.addEventListener('click', () => {
                        popup.close();
                    });
                    //const submitBtn = document.getElementById(".submitBtn");
                    //submitBtn.addEventListener('click', addProject);
                }

                function openDetails(){
                    console.log("This will open up a little progress window");
                }

                function updateSearch(){
                    const query = document.getElementById("searchBar").value.toLowerCase().trim();
                    const dept = document.getElementById("deptSort").value;

                    document.querySelectorAll(".project-card").forEach(card => {
                        const matchSearch = card.dataset.search.includes(query);
                        const matchDept = dept === "all" || card.dataset.dept === dept;
                        card.style.display = (matchSearch) ? "" : "none";
                        card.style.display = (matchSearch && matchDept) ? "" : "none";
                    });
                }

                function requestProject(){
                    console.log("Requesting project");
                }

                function addProject(){
                    console.log("Add project");

                    const name = document.getElementById("projectName").textContent;
                    const team = document.getElementById("allTeams").value;
                    const desc = document.getElementById("projectDescription").textContent;
                    const reqs = document.getElementById("projectReqs").textContent;
                    const status = document.getElementById("projectStatus").value;
                    const depts = document.getElementById("depts").value;
                    const repo = document.getElementById("repoLink").textContent;

                    const sql = "INSERT INTO projects (project_description, team_id, project_requirements, project_status, department, repo_link, project_name) VALUES(?,?,?,?,?,?,?);";
                    // // example insert statements
                    // INSERT INTO team (team_id, start_date, end_date, advisor, team_name)
                    // values (3, '2026-10-6', '2026-12-4', 'Professor Reddig', 'Engineering Team Delta');

                    // INSERT INTO projects (project_description, team_id, project_requirements, project_status, department, repo_link, project_id, project_name)
                    // values ('Create a website where users can log in and see all of the projects for the school of ECS', 1, 'see our repo for requirements', 'in progress', 'Computer Science', 'https://github.com/ksaldrent2006/project_vault', 1, 'Project Vault');

                }
            </script>
        </body>
    </html>