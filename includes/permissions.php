<?php
/**
 * permissions.php
 * 
 * the place to confirm who is allowed to do what... uses functions so pages can 
 * call and ask the same questions
 */

require_once __DIR__ . "/PVDatabase.php";


// Contributors make sure everyone gets credit on the card, even people without an account.
// Team members decide who can make changes, and only people with accounts can be on a team.
// The admin can step in when a team is empty, either by editing the project directly or by adding someone to the team on manage_teams.php.


// true if this person's role in the person table is 'admin'
function isAdmin($personId) {
    $rows = PVDatabase::getDataFromSQL(
        "SELECT role FROM person WHERE person_id = ?",
        [(int)$personId]
    );
    return !empty($rows) && $rows[0]['role'] === 'admin';
}

// true if this person is on the team that owns this project
function isTeamMember($personId, $projectId) {
    $rows = PVDatabase::getDataFromSQL(
        "SELECT 1 FROM projects p
         JOIN team_member tm ON tm.team_id = p.team_id
         WHERE p.project_id = ? AND tm.member_id = ?",
        [(int)$projectId, (int)$personId]
    );
    return !empty($rows);
}

// true if this person may edit / delete the project; change its progress
// updates, and approve requests for it: team memebrs or an admin
function canManageProject($personId, $projectId) {
    return isAdmin($personId) || isTeamMember($personId, $projectId);
}