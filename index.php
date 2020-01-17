<?php
/*
 * On indique que les chemins des fichiers qu'on inclut
 * seront relatifs au répertoire src.
 */
set_include_path("./src");

/* Inclusion des classes utilisées dans ce fichier */
require_once("Router.php");

require_once("model/Series/SeriesStorageMySQL.php");
require_once("model/Account/AccountStorageMySQL.php");


/*
 * Cette page est simplement le point d'arrivée de l'internaute
 * sur notre site. On se contente de créer un routeur
 * et de lancer son main.
 */

session_start();
$router = new Router(new SeriesStorageMySQL(),new AccountStorageMySQL());
$router->main();
?>
