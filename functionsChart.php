<?php
/*STAT INCIDENTS AND REQUESTS
id_type 1 = incidents,
id_type 2 = demandes,
id-status 1 = new,
id-status 5 = resolves.
*/
function debug($data){
    echo '<pre>' . print_r($data, return:1) . '</pre>';
}

//relevé génerale de tous les les tickets par semaine de l'année
//sans l'année pour le marquage de semaine sur le graphique
function getWeeksLabels(): array {
    global $DB;
    $query = "SELECT WEEK(date_creation) AS week_of_year,
    COUNT(*) AS all_tickets FROM `glpi_tickets`
    GROUP BY week_of_year";
    $result = $DB->query($query);
    $values = [];
    while ($data = $DB->fetchAssoc($result)){
        $values[$data['week_of_year']] = $data['all_tickets'];
    }
    return  $values;
}

//Statistique par semaines
function getStatOpensByWeeks(int $year, int $id_type){
    global $DB;
    $query = "SELECT WEEK(date_creation) AS week_of_year,
    COUNT(*) AS all_tickets FROM `glpi_tickets`
    WHERE YEAR(`date_creation`) = $year AND `type`= $id_type
    GROUP BY week_of_year";
    $result = $DB->query($query);
    $opens = [];
    while ($data = $DB->fetchAssoc($result)){
        $opens[$data['week_of_year']] = $data['all_tickets'];
    }
    return $opens;
}

function getStatResolvesByWeeks(int $year, int $id_type){
    global $DB;
    $query = "SELECT WEEK(solvedate) AS week_of_year,
    COUNT(*) AS all_tickets FROM `glpi_tickets`
    WHERE YEAR(`solvedate`) = $year AND `type`= $id_type
    GROUP BY week_of_year";
    $result = $DB->query($query);
    $resolves = [];
    while ($data = $DB->fetchAssoc($result)){  
        //condition : si le champs n'est pas vide(contient des données)      
        if($data['all_tickets'] != NULL){
            //retourner la semaine et sa valeur
            $resolves[$data['week_of_year']] = $data['all_tickets'];
        } else {
            //sinon retourner la valeur 0
            $resolves[$data['week_of_year']] = 0;
        }
    }
    return $resolves;
}

//afficher les semaines (labels)
function getWeeksOnly(int $year){
    global $DB;
    $sql = "SELECT WEEKOFYEAR(date) AS weeks FROM `glpi_tickets` 
    WHERE YEAR(`date_creation`) = ? GROUP BY weeks";
    $stmt = $DB->prepare($sql);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($data = $DB->fetchAssoc($result)){

        $weeks[$data['weeks']] = $data['weeks'];
    }
 
    return $weeks;
    /* close statement */
    $stmt->close();
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

function getBacklog(int $id_type){
    global $DB;
    $sql = "SELECT week, backlog FROM glpi_backlog
    WHERE type = ? GROUP BY week";
    $stmt = $DB->prepare($sql);
    $stmt->bind_param('i', $id_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $backlog = [];
    while ($data = $DB->fetchAssoc($result)){   
        $backlog[$data['week']] = $data['backlog'];    
    }
    return $backlog;
    $stmt->closeCursor();   
}