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
    $projectInfo = PVDatabase::getDataFromSQL(
        "SELECT p.project_id, p.department, p.project_status, p.project_description, p.team_id, p.repo_link, p.project_name, t.start_date, t.end_date, t.advisor, t.team_name
        FROM team t, projects p
        WHERE t.team_id = p.team_id"
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
                    <option value="cs">Computer Science</option>
                    <option value="egr">Engineering</option>
                </select>
            </div>
            <?php if(empty($projects)): ?>
                <p>No projects found.</p>
            <?php endif; ?>

            <div class="projects">
                <?php foreach ($projectInfo as $p): 
                    $searchText = strtolower(
                        ($p['project_title'] ?? '') . ' ' .
                        ($p['department'] ?? ' '). ' ' .
                        ($p['team_id'] ?? ' '));
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
                        <p>Repo link:</p>
                        <?php if(!empty($p['repo_link'])): ?>
                            <a href="<?= esc($p['repo_link']) ?>" target="_blank" rel="noopener">Repository</a>
                        <?php endif; ?>
                        <button class="proj_req">Request project</button>
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

                    let searchBar = document.getElementById("searchBar");
                    let sort = document.getElementById("deptSort");

                    searchBar.addEventListener('input', updateSearch);
                    sort.addEventListener('change', updateSort);
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
                        card.style.display = (matchSearch && matchDept) ? "" : "none";
                    });
                }
            </script>
        </body>
    </html>