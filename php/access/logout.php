<?php
session_start();

// require "./../db/accessDbManager.php";
require_once "./../utils/errorUtils.php";

// $accessDbManager = new AccessDbManager();
// $accessDbManager->openConnection();

// DEBUG
// echo "utente: ".$_SESSION["user"]." con id: ".$_SESSION["userId"];

// if ( !$accessDbManager->setUserStatus("offline", $_SESSION["user"]) ) {
//     # debug
//     notificaErroreAccesso("si è verificato un problema con il database. Riprova pi\u00f9 tardi.");
//     $accessDbManager->closeConnection();
//     exit(1);
// }

// DEBUG
// echo "nuovo stato: ".$accessDbManager->getUserStatus($_SESSION["user"]);

// $accessDbManager->closeConnection();

session_unset();
session_destroy();
header("Location: ./../../index.php");
exit;