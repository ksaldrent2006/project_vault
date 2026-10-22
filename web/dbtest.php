<?php

require_once "includes/config.php";
require_once "includes/PVDatabase.php"

// WORK IN PROGRESS!

$result = Database::getDataFromSQL("SELECT COUNT(*) AS total FROM team");
echo "Connected! Rows in team: " . $result[0]['total'];