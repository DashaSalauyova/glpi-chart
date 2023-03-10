
<?php

function debug($data){
    echo '<pre>' . print_r($data, return:1) . '</pre>';
}

function test(){
    global $DB;
    $result = $DB->query("SELECT COUNT(*) as 'necessaryGlpiFckngKeyThere' FROM `glpi_tickets`");
    return $DB->result($result, 0, 'necessaryGlpiFckngKeyThere');
}

function getfetch(){
    global $DB;
    $query = "SELECT id, name FROM glpi_tickets GROUP BY id";
    $result = $DB->query($query);
    while ($data = $DB->fetchAssoc($result)) {
        $values[$data['id']] = $data['name'];
    }
    return $values;
}

//calculer tous les tickets par l'année indiquée, function de demarrage(function de test)
function getAllTicketsByYears(int $year) {
    global $DB;
    // $query = "SELECT YEAR(date_creation) AS year_creation FROM `glpi_tickets`
    $query = "SELECT YEAR(date_creation) AS year_creation,
    COUNT(*) AS total FROM `glpi_tickets`
    WHERE YEAR(date_creation) = $year
    GROUP BY year_creation";
    $result = $DB->query($query);
    $values = [];
    while ($data = $DB->fetchAssoc($result)){
        $values[$data['year_creation']] = $data['total'];
    }
    return $values;
}

//function qui calcule les tickets par type(incidents ou demandes)
//All incidents or Requests by Year
function getTicketsTypeByYear(int $year, int $type) {
    global $DB;
    // $query = "SELECT YEAR(date_creation) AS year_creation FROM `glpi_tickets`
    $query = "SELECT YEAR(date_creation) AS year_creation,
    COUNT(*) AS total FROM `glpi_tickets`
    WHERE YEAR(date_creation) = $year AND type = $type
    GROUP BY year_creation";
    $result = $DB->query($query);
    $values = [];
    while ($data = $DB->fetchAssoc($result)){
        $values[$data['year_creation']] = $data['total'];
    }
    return $values;
}

//relevé des mois par année precisée donnant le nombre de tickets(tous type tickets confondus)
function getCountMonth(int $year): array {
    global $DB;
    $query = "SELECT MONTH(date_creation) AS month_of_year,
    COUNT(*) AS total FROM `glpi_tickets`
    WHERE YEAR(date_creation) = $year 
     GROUP BY month_of_year";
    $result = $DB->query($query);
    $values = [];
    while ($data = $DB->fetchAssoc($result)){
        $values[$data['month_of_year']] = $data['total'];
    }
    return  $values;
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

function backlog(int $id_type){
    global $DB;
    $query = "SELECT WEEK(date_creation) AS 'week_of_year', COUNT(*) AS backlog FROM glpi_tickets
    WHERE `type`= $id_type AND `status` != 5 AND `status` != 6 GROUP BY WEEK(date_creation)";
    $result = $DB->query($query);
    $backlog = [];
    "SELECT COUNT(*) AS backlog FROM glpi_tickets 
    WHERE `type`= 1 AND `status` != 5 AND `status` != 6";
    while ($data = $DB->fetchAssoc($result)){   
        $backlog[$data['week_of_year']] = $data['backlog'];
    }
    return $backlog;

}

function insertMysql($year, $value) {
    for ($i = 1; $i <= 52, $i++;) {
    global $DB;
    $backlog = "SELECT WEEK(`date_creation`), backlog FROM glpi_backlog WHERE WEEK(`date_creation`) = ($i-=1) AND YEAR(`date_creation`) = $year";
    $new_incidents = "SELECT WEEK(date_creation) AS week_of_year, COUNT(*) as new_incidents
    FROM glpi_tickets WHERE `type`= 1 AND `status` = 1 AND YEAR(`date_creation`) = $year GROUP BY week_of_year";
    $resolved_incidents = "SELECT WEEK(date_creation) AS week_of_year, COUNT(*) as resolved_incidents
    FROM glpi_tickets WHERE `type`= 1 AND `status` = 5 AND YEAR(`date_creation`) = $year GROUP BY week_of_year";
    $newBacklog = $backlog + ($new_incidents - $resolved_incidents);
    $query = "INSERT INTO glpi_backlog(backlog) VALUES($value) ON DUPLICATE KEY UPDATE backlog";
    $result = $DB->query($query);
    return $result;
   }
}

// function queryAndInjectionBack(){
//     echo "etape 1 : calcul de newBacklog" . "\n";
//     // echo getBack() . "\n <br/>";
//     echo "etape 2 : \n";
//     echo 'prepare mysql injection <br/>';
//     global $DB;
//     $newBacklog = getBack();
//     $period_data = getPeriodToBacklog();
//     $query = "INSERT INTO glpi_backlog(`week`) VALUES($period_data)";
//     $result = $DB->query($query);
//     echo 'etape 3 : injection <br/>';
//     return $result;
// }
// echo "etape 1 : calcul de newBacklog" . "\n";
// // echo getBack() . "\n <br/>";
// echo "etape 2 : \n";
// echo 'prepare mysql injection <br/>';

function queryAndInjectionBacklog($year, $week){
    
    // for($week = 1; $week <= 52; $week++) {
        global $DB;
        //calcul total de nouveaux par semaine
        $new_incidents = "SELECT COUNT(*) as `new_incidents` FROM glpi_tickets
        WHERE WEEK(`date_creation`) = $week AND YEAR(`date_creation`) = $year AND `type`= 1 AND `status` = 1";


        // $res = $DB->query($new_incidents);

        // $res->fetchAll();

        //total de resolus par semaine
        $resolved_incidents = "SELECT COUNT(*) as `resolved_incidents` FROM glpi_tickets
        WHERE WEEK(`date_creation`) = $week AND YEAR(`date_creation`) = $year AND `type`= 1 AND `status` = 5";
        $DB->query($resolved_incidents);
        //recuperation le backlog de la semaine precedente
        $backlog = "SELECT backlog FROM `glpi_backlog` WHERE $week-=1";
        $DB->query($backlog);
        //calcul d'un nouveau backlog à la fin de la semaine en cours
        $newBacklog = ($new_incidents - $resolved_incidents) + $backlog;
        //injection du nouveau backlog dans le mysql 
        $injection = "INSERT INTO glpi_backlog(`week`, `backlog`) VALUES($week, $newBacklog)";
        $DB->query($injection);
        echo 'etape 3 : injection <br/>';
       
    // }
   
}

function staticData(){
    
    $weeks = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42];
    for($week = 1; $week <= 52; $week++) {
     
        //calcul total de nouveaux par semaine
        $new_incidents = 8;
        //total de resolus par semaine
        $resolved_incidents = 3;
        //recuperation le backlog de la semaine precedente
        $backlogOfLastWeek = 10;
        // $backlog = "SELECT backlog FROM `glpi_backlog` WHERE $week-=1";
        
        //calcul d'un nouveau backlog à la fin de la semaine en cours
        $newBacklog = ($new_incidents - $resolved_incidents) + $backlogOfLastWeek;
        //injection du nouveau backlog dans le mysql 

        $backlogOfLastWeek = $newBacklog;

        echo "week $week have newBacklog $newBacklog  <br />";
        // echo "backlog is " . $backlogOfLastWeek . "<br/>";
        // echo "Newbacklog is " . $newBacklog . "<br/>";
        // echo "for week {$week} the $newBacklog is " . $newBacklog;
    }
    
}
    
//calcul total des resolus par semaine
function debuger2(){
    global $DB;
    $resolved_incidents = "SELECT COUNT(*) as `resolved_incidents` FROM glpi_tickets
    WHERE WEEK(`date_creation`) = 32 AND YEAR(`date_creation`) = 2022 AND `type`= 1 AND `status` = 5";
    $result = $DB->query($resolved_incidents);
    return $DB->result($result, 0, 'resolved_incidents');
}

function backlog_mysql($week){
    global $DB;
    $backlog = "SELECT backlog FROM `glpi_backlog` WHERE `week`= $week-1";
    $result = $DB->query($backlog);
    return $DB->result($result, 0, 'backlog');
}

function insertCurrentBacklog($week, $newBacklog){
    global $DB;
    // $newBacklog = getNewBacklog();
    $week = 32;
    $injection = "INSERT INTO glpi_backlog(`week`, `backlog`) VALUES($week, $newBacklog)";
    $DB->query($injection);
    return $DB->insert($injection);
}

//Statistique par semaines, avec id-status en param pour differencier les nouveaux(1) et les resolus(5)
function getWeeksOnlyWithtNicChevalier(int $year, int $id_type, int $id_status){
    global $DB;
    $query = "SELECT WEEK(date_creation) AS week_of_year FROM `glpi_tickets` WHERE YEAR(`date_creation`) = ? AND `type`= ? AND `status` = ? GROUP BY week_of_year";
    $stmt = $DB->prepare($query);
    $stmt->bind_param('iii', $year, $id_type, $id_status);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    return $data;
    // while ($data = $DB->fetchAssoc($result)){
    //     $values[$data['week_of_year']] = $data['all_tickets'];
    // }
    // while($data = $DB->fetchAll($result)){
    //     $values[$data['week_of_year']];
    // return $values;
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

// function putInBacklog(){

    // $weeks = getWeeksOnly(2022);
    // foreach ($weeks as $week){
    //     //Нужно взять счет открытых за эту неделю
    //     $sales = "SELECT COUNT(*) FROM glpi_tickets 
    //             WHERE type = 1 AND status != 5 AND status != 6 
    //             AND week(date_creation) = $week";
    //     //предыдущий бэклог из базы
    //     $oldBacklog = "SELECT backlog FROM glpi_backlog WHERE week = $week=-1";
    //     //добавить к нему бэклог из базы
    //     $newBacklog = $sales + $oldBacklog;
    //     //передать новый бэклог в базу. 
    //     echo 'Backlog de la semaine ' . $week . 'est ' . $newBacklog;
    
    // }
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


// recuperer le dernier backlog(semaine precedente) de la bdd
//предыдущий бэклог из базы
// function getOldBacklog($week, $type){
//     global $DB;
//     if($week-1 == NULL){
    
//         // for($week=53; $week !== NULL; $week--)
//         // {
        
//             global $DB;     
//             $sql = "SELECT backlog AS oldBacklog FROM glpi_backlog 
//                     WHERE type = $type";
//             $result = $DB->query($sql);
//             while($data = $result->fetch_all(MYSQLI_ASSOC)){
//                 $week = $data['2'];
//             }
//             return $week;


//     }else{
//         $sql = "SELECT backlog AS oldBacklog FROM glpi_backlog 
//                 WHERE week = $week-1 AND type = $type";
//         $result = $DB->query($sql);
//         $oldBacklog = $DB->result($result, 0, 'oldBacklog');
//     }
//     return $oldBacklog;

// function getNewBacklog($week, $type) {
//     $sales = getSalesOfCurrentWeek($week, $type);
//     $oldBacklog = getOldBacklog($week, $type);
//     $newBacklog = $sales + $oldBacklog; 
//     return $newBacklog;
// }

    //pour chaque semaine nous allons faire les 4 étapes décrites ci-dessus
// function registerBacklogByType($week, $type){
//     getSalesOfCurrentWeek($week, $type);
//     getOldBacklog($week, $type);
//     getNewBacklog($week, $type);
//     registerBacklog($week, $type);
//     //test
//     $sales = getSalesOfCurrentWeek($week, $type);
//     $oldBacklog = getOldBacklog($week, $type);
//     $newBacklog = getNewBacklog($week, $type);
//     //les afficher, test
//     echo 'Backlog de la semaine ' . $week . ' est ' . $sales . '<br>';
//     echo 'Old Backlog: est ' . $oldBacklog . '<br>'; 
//     echo 'Le nouveau est donc ' . $newBacklog . '<br>';
//     $date_creation = date('d/m/Y');
//     echo 'date de creation est ' . $date_creation;
// }

// function getBacklogDetails(int $week, int $type){
//     global $DB;
//     $sql = "SELECT $week, type,
//             COUNT(*) AS backlog FROM glpi_tickets 
//             WHERE week(date_creation) <= ? AND type = ?
//             AND status != 5 AND status != 6 AND is_deleted != 1";
//     $stmt = $DB->prepare($sql);
//     $stmt->bind_param('ii', $week, $type);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     while ($data = $DB->fetchAssoc($result)){   
//         // $data[$week] = $data['backlog'];
//         $data['week'] = $week;
//         debug($data);
//     }
//     $stmt->close();
// }
//appliquer le backlog pour les incidents et pour toutes les semaines
// foreach($weeks as $week){
//     getBacklogDetails($week, 1);
// }
// //appliquer pour les demandes et pour toutes les semaines
// foreach($weeks as $week){
//     getBacklogDetails($week, 2);
// }
//теперь нужен INSERT, передать бэклог в базу
//Inserer les données precédemment recuperées dans la table backlog
// function testBacklog(int $week, int $type){

//     global $DB;
//     $sql = "INSERT INTO glpi_backlog (week, type, backlog)
//     VALUES  ($week, 
//             (SELECT type FROM glpi_tickets 
//             WHERE week(date_creation) <= $week
//             AND type = $type
//             AND status != 5
//             AND status != 6 
//             AND is_deleted != 1),
//             (SELECT COUNT(*) AS backlog FROM glpi_tickets 
//             WHERE week(date_creation) <= $week
//             AND type = $type
//             AND status != 5
//             AND status != 6
//             AND is_deleted != 1)
//             )";
//             $data = $DB->query($sql);
//             return $DB->insert($sql);
//     // $stmt = $DB->prepare($sql);
//     // $stmt->bind_param('iiiii', $week, $week, $type, $week, $type);
//     // $stmt->execute();
// }

// echo 'backlog ' . '<br />';
// debug(getBacklog(1));
