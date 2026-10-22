<?php
    echo '<link rel="stylesheet" href="projects.css">';
    // echo '<div id="navBar">';
    // echo '</div>';
    echo '<div class="search-sort">';
        echo '<label for="search">Search:</label>';
        echo '<input type="text" id="searchBar" placeholder="Search projects...">';
        echo '<label for="deptSort">Sort by Department</label>';
        echo '<select id="deptSort">';
            echo '<option value="all" selected>All Departments</option>';
            echo '<option value="cs">Computer Science</option>';
            echo '<option value="egr">Engineering</option>';
        echo '</select>';
    echo '</div>';

    echo '<div class="projects">';
            echo '<div class="project-title">';
                echo '<h3>Project title here <span>(Department)</span> </h3>';
                echo '<p>Project status here</p>';   
            echo '</div>';
            echo '<div class="project-details">';
                echo '<p>Project description and requirements here</p>'; 
                echo '<p>Team(s) that worked on the project here.</p>';
                echo '<span>Dates for when the project was worked on</span>';
            echo '</div>';
            echo '<div class="project-links">';
                echo '<p>Repo link here</p>';
                echo '<button class="proj_req">Request project</button>';
            echo '</div>';

    echo '</div>';
?>
<script> 
    window.addEventListener('load', init);

    function init(){
        // fetch("navbar.php")
        // .then(res => res.text())
        // .then(data => {
        //     document.getElementById("navBar").innerHTML = data;
        // })
        // .catch(error => console.error("Error loading navbar: ", error));
        
        let title = document.querySelector(".project-title");
        title.addEventListener('click', openDetails);

        let searchBar = document.getElementById("searchBar");
        let sort = document.getElementById("deptSort");

        searchBar.addEventListener('input', updateSearch);
        sort.addEventListener('change', updateSort);
    }

    function openDetails(){
        console.log("This will open up a little progress window");
    }

    function updateSearch(){
        console.log("Search through projects by title or team");
    }

    function updateSort(){
        console.log(this.value);
    }
</script>
