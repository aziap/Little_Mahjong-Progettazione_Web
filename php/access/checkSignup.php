<?php

if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require "./patternValidation.php";
require "./../db/accessDbManager.php";


if ( empty($_POST["signup-btn"]) || $_POST["signup-btn"] != "Registrati" ) {
    notificaErroreAccesso("si è verificato un errore inaspettato.");
    exit(1);
}

if ( empty( $_POST["password-repeat"] ) || strcmp($_POST["password-repeat"], $_POST["password"]) ) {
    notificaErroreAccesso("le password inserite non corrispondono.");
    exit(1);
}

$accessDbManager = new AccessDbManager();
$accessDbManager->openConnection();

if ( $accessDbManager->userExists($_POST["username"]) ) {
    notificaErroreAccesso("lo username scelto è già preso.");
    $accessDbManager->closeConnection();
    exit(1);
}

// ho superato i controlli, registro l'utente
if ( !$accessDbManager->registraUtente($_POST["username"], $_POST["password"] ) 
    || ( $_SESSION["userId"] = $accessDbManager->getUserId($_POST["username"]) ) == null ) {
    notificaErroreAccesso("c'è stato un problema con la registrazione. Riprova più tardi.");
    $accessDbManager->closeConnection();
    exit(1);
}


$accessDbManager->closeConnection();

$_SESSION["user"] = $_POST["username"];

header("Location: ./../home.php");