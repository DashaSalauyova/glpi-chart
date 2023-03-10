
<?php

include('../inc/functionsChart.php');

echo "RESOLUS TEST tickets par semaine de l'année 2022";
debug(getStatResolvesByWeeks(2022, 1));

/*Si utilisateur envoie une forme action, on recupere son choix avec la methode get;
выбранный им год подставляем в функцию
"искать недели по году" и с ее помощью забираем из базы данных статистику по месяцам.
Если данных нет то диаграмма будет пустой, если данные есть, она заполнится
Следующий блок кода можно использовать с пом debug/var_dump чтобы вывести данные
На следующем этапе переменные из данного когда подставим в конфиг js.файла. 
Они станут значениями в конфигурацию и мы получим визульный рендер (диаграмму).**/

//à l'activation du bouton choix de l'année par utilisateur
if(isset($_GET['year'])){
    //on choisis l'année pour laquelle on va recolter les statistiques
    $year = (int)$_GET['year'];
    //on recupere la liste de semaines de la bdd
    $weeks = getWeeksOnly($year);
    //type par defaut est barre sinon line
    $type = $_GET['type'] ?? 'line';
    //au click j'interroge la BD pour y enregistrer/update le backlog
    foreach ($weeks as $week) {
        registerBacklog($week, 1);
    }
    foreach ($weeks as $week) {
        registerBacklog($week, 2);
    }

    $resolvedIncidents = getStatResolvesByWeeks($year, 1);
    $newIncidents  = getStatOpensByWeeks($year, 1);
    $resolvedRequests = getStatResolvesByWeeks($year, 2);
    $newRequests  = getStatOpensByWeeks($year, 2);
    $labels =  getWeeksOnly($year);

    //values for 4 stats
    $valuesNewIncidents = implode(',', $newIncidents);
    $valuesResolvedIncidents = implode(',', $resolvedIncidents);
    $valuesNewRequests = implode(',', $newRequests);
    $valuesResolvedRequests = implode(',', $resolvedRequests);
    //labels for 2 types by 2 status
    $labelNewIncidents = "Incidents nouveaux";
    $labelResolvedIncidents = "Incidents résolus";
    $labelNewRequests = " Demandes nouvelles";
    $labelResolvedRequests = "Demandes résolues";
    //backlog
    $backlogIncidents = getBacklog(1);
    $backlogRequests = getBacklog(2);
    $valuesBacklogIncidents = implode(',', $backlogIncidents);
    $valuesBacklogRequests = implode(',', $backlogRequests);
    $labelBacklogIncidents = "Backlog incidents";
    $labelBacklogRequests = "Backlog Demandes";

    // mets le marquage sur le graph, get array of keys
    // implode transforme array en string afin d'obtenir tous les mois dans une seule liste
    // получаем массив ключей с помощью фун array_keys из $... 
    // в этом случае implode нам не нужен так как все данные цифры,\
    //а если использовать имена недель, то использовать стринг и соответстенно implode 
    $commun_labels = implode(',', array_keys($labels));

    //constante pour le marquage de semaines, recupere toutes les semaines
    // $commun_labels = getWeeksOnly($year);
    $titleIncidentsChart = "Statistique des Incidents par semaine (Annee {$year})";
    $titleRequestsChart = "Statistique des Demandes par semaine (Annee {$year})";
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>diagramme-backlog</title>
        <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous"> -->
        <link rel="stylesheet" type="text/css" href="../css/dashboard-dsalauyova/chart.css">
        <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    </head>
    <body>
    <!--class pour les forms de html (choisir l'année et type de données) -->
    <div class="container" style="color:#a2291f">
        <h2>Activité Support</h2>
        <br>
    </div>
    <div class="container">
            <!-- !!Attention au lien de form, chart.php n'est pas une page par entiere, indiquer page parent central -->
        <form action="central.php">
            <div class="row d-flex align-items-end">
                <div class="col-md-4">
                    <label for="year" class="form-label" style="font-size: 16px;">               
                        Année : 
                    </label>
                    <select class="form-select" aria-label="Select Year" name="year" selected>
                        <?php for($i = 2020; $i <= date('Y'); $i++): ?>
                            <option value="<?= $i; ?>" selected><?=$i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">         
                    <label for="type" class="form-label" style="font-size: 16px;">
                        Type :
                    </label>
                    <select class="form-select" aria-label="Select Type" name="type">
                        <option value="bar">Barre</option>
                        <option value="line">Ligne</option>
                    </select>                  
                </div>
                <div class="col md-4 d-flex align-items-end">
                    <button class="btn btn-danger mt-auto">Valider</button>
                </div>

            </div>
         </form>
    
    </div>
<!-- class pour le block diagrammes
    col-md-6 offset-2 pour la largeur sur 6 colonnes et 2 espace entre les colonnes
    ainsi on affiche deux blocs (incidents et demandes) côte à côte -->
    <div class="container">
        <div class="col-md-6 offset-2">
            <div>
                <canvas id="incidentsChart" aria-label="chart" role="image" height="220"></canvas>
                <canvas id="requestsChart" aria-label="chart" role="image" height="220"></canvas>
                <canvas id="backlogChart" aria-label="chart" role="image" height="220"></canvas>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <!-- <script src="../js/chart.js"></script> -->
                <script>
                    const font = new FontFace("FontAwesome", "url(myfont.woff)");

                    //declaration d'un element canvas html dans une variable constante canvas
                    const canvasOne = document.getElementById('incidentsChart');

                    /********CODE BUILDER OF DIAGRAMME */
                    /*creation d'une nouvelle instance Chart qui prendra en param element html canvas
                    et la configuration de chart.js(https://cdn.jsdelivr...),
                    dans laquelle on va passer nos données dynamiques*/
                    const incidentsChart = new Chart(canvasOne, {
                        type: '<?= $type ?>',
                        data: {
                            
                            labels: [<?= $commun_labels ?>],
                            datasets: [
                                {   
                                    label: '<?= $labelResolvedIncidents ?>',
                                    data: [<?= $valuesResolvedIncidents ?>],
                                    backgroundColor: '#0080ff',
                                    borderWidth: 1,
                                    borderColor: '#cbaa1e'
                                },
                                {   
                                    label: '<?= $labelNewIncidents ?>',
                                    data: [<?= $valuesNewIncidents ?>],
                                    backgroundColor: '#f00020',
                                    borderWidth: 1,
                                    borderColor: '#f00020'
                                },
                                {   
                                    label: '<?= $labelBacklogIncidents ?>',
                                    data: [<?= $valuesBacklogIncidents ?>],
                                    backgroundColor: '#000000',
                                    borderWidth: 1,
                                    borderColor: '#000000'
                                }
                            ]
                        },

                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    text: '<?= $titleIncidentsChart ?>',
                                    font
                                },
                                legend: {
                                    position : 'bottom',
                                    labels: {
                                        font : {
                                            size: 14,
                                            style: 'condensed'
                                        }
                                    }
                                }
                            }
                        }
                    });

//CHART2
                    // const font = new FontFace("FontAwesome", "url(myfont.woff)");
                    const canvasTwo = document.getElementById('requestsChart');
                    /********CODE BUILDER OF DIAGRAMME */
                    /*creation d'une nouvelle instance Chart qui prendra en param element html canvas
                    et la configuration de chart.js(https://cdn.jsdelivr...),
                    dans laquelle on va passer nos données dynamiques*/
                    const requestsChart = new Chart(canvasTwo, {
                        type: '<?= $type ?>',
                        data: {
                            
                            labels: [<?= $commun_labels ?>],
                            datasets: [
                                {   
                                    label: '<?= $labelResolvedRequests ?>',
                                    data: [<?= $valuesResolvedRequests ?>],
                                    backgroundColor: '#008081',
                                    borderWidth: 1,
                                    borderColor: '#008081'
                                },
                                {   
                                    label: '<?= $labelNewRequests ?>',
                                    data: [<?= $valuesNewRequests ?>],
                                    backgroundColor: '#003e83',
                                    borderWidth: 1,
                                    borderColor: '#003e83'
                                },
                                {
                                    label: '<?= $labelBacklogRequests ?>',
                                    data: [<?= $valuesBacklogRequests ?>],
                                    backgroundColor: '#303030',
                                    borderWidth: 1,
                                    borderColor: '#000000'
                                }
                            ]
                        },

                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                title: {
                                    titleSpacing: 1.5,
                                    display: true,
                                    text: '<?= $titleRequestsChart ?>',
                                    font,
                                    }
                                ,
                                legend: {
                                    position : 'bottom',
                                    labels: {
                                        font: {
                                            size: 14,
                                            letterSpacing: 0.5
                                        }
                                    }
                                }
                            }
                        }
                    });
                </script>
            </div>
        </div>
    </div>     
    </body>
</html>