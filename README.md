# Project Vault
A web application for the **Elizabethtown College School of Engineering and Computer Science** that stores and organizes student project ideas, so students can browse available projects, understand their requirements, and pick up where previous teams left off.

# Project Description
The goal of this project is to create a database system that stores and organizes project ideas that can be used by students in the School of Engineering and Computer Science. The system allows project information to be easily accessed and maintained so that students can review available projects and understand their requirements before selecting one.

The database stores information such as the project description, who requested the project, project requirements, the students or teams who have worked on it, the current status of the project, where previous work was left, and the location of the most recent Git repository.

# Project Goals
- Create a centralized database for storing and organizing project ideas.
- Let students browse, search and filter projects by name, team, or department.
- Keep a history of progress updates so new teams can see where previous work was left off.
- Give credit to everyone who worked on a project, including past students without accounts.
- Control who can change a project: only its team (or an admin) can edit.

# Overview
**Project Vault** is built with PHP, MySQL, and plain HTML/CSS/Javascript. Users login with an @etown.edu account and can:
- **Login/Register:** Create an account (must be an @etown.edu) and login.
- **Projects:** Browse every project. Add a new project for one of your teams. Team members can edit or delete their project; everyone else can request it.
- **Project Documentation:** See a project's details and its progress updates (newest first). Team members can add or delete updates.
- **Teams:** See your teams, create a new team, leave a team, and browse all teams. Admins can add or remove anyone.
- **Requests:** Request to join a project. Team members (or an admin) approve or deny requests. Approving adds the student to the project's team.
- **My Profile:** Edit your account details.

There are three main roles: any logged-in user, team member, and admin. Each role has its own actions they can perform. 
[Roles and Permissions](docs/roles_projectvault.png)

# Database Design
[ERD Model](docs/updated_erd_projectvault.png)

Notes:
- Team Members (team_member) are linked to real accounts and control who can edit a project.
- Contributors (project_contributor) are typed names that give credit to anyone who worked on the project, even without an account.
- A team can be empty (ex. an old project whose students never made accounts). An admin can add someone to that team so the project can still be maintained.

# Setup
Requirements:
- PHP8 or newer
- MySQL8 (with MySQL Workbench preferably)
- A local web server, such as XAMPP

Steps:
1. Clone the repository.
2. Create the database. Run the files in this order: schema.sql, v2_schema.sql, v3_schema.sql.
3. Set your database login. Add the connection settings to a includes/config.php file to match your MySQL username and password.
4. Start the server and open the login page in your browser. (the official website link: https://projectvault.etowndb.com/web/)

# Future Additions
- **GitHub Integration:** Use the reserved repository table to show each project's latest commit and commit date.
- **Link Contributors to Accounts:** When a past contributor registers, let them claim their name so the project_contributor row is linked to their account.
- **Admin Role Management:** Let admins promote other users to admin status from the site, instead of running SQL.
- **Edit Progress Updates:** Currently progress updates can only be added or deleted.
- **File Uploads:** Upload images or documents as visual materials instead of only links.
- **Configure Login to be a Valid Etown Account:** Replace the custom login with Etown's single sign on, so users sign in with their official Etown accouunt.

# Owners
Katia Daly and Kathy Saldana Renteria