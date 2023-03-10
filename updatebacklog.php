<?php

function debug($data){
    echo '<pre>' . print_r($data, return:1) . '</pre>';
}

function getWeeksOnly(){
    global $DB;
    var_dump($DB);
    $sql = "SELECT WEEKOFYEAR(date) AS weeks FROM `glpi_tickets` 
    WHERE YEAR(`date_creation`) >= 2020 GROUP BY weeks";
    $result = $DB->query($sql);
    while ($data = $DB->fetchAssoc($result)){
        $weeks[$data['weeks']] = $data['weeks'];
    }
    return $weeks;
}

function registerBacklog(int $week, int $type){
    global $DB;
    $sql_count = "SELECT COUNT(*) AS backlog FROM glpi_tickets 
                WHERE week(date_creation) <= ? 
                AND type = ?
                AND status != 5 
                AND status != 6 
                AND is_deleted != 1";
    $stmt_count = $DB->prepare($sql_count);
    $stmt_count->bind_param('ii', $week, $type);
    $stmt_count->execute();
    $result = $stmt_count->get_result();
    $row = $result->fetch_assoc();
    $backlog = $row['backlog'];
    /*contrainte en tant qu'une clef étrangere est créée précedemment dans la table glpi_backlog
    sur 2 champs : type et week. Cela permet d'effectuer la requete suivante 
    sans doubler la ligne si la combinaison de valeurs pour les champs existe
    sinon faire une insertion des données qui n'existent pas*/
    $sql_insert = "INSERT INTO glpi_backlog (week, type, backlog) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                backlog = VALUES (backlog)";
    $stmt_insert = $DB->prepare($sql_insert);
    $stmt_insert->bind_param('iii', $week, $type, $backlog);
    $stmt_insert->execute();
}
