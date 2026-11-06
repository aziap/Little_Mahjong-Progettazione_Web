<?php

// require __DIR__."/../db/accessDbManager.php";
require_once __DIR__."./../utils/errorUtils.php";

define("USERNAME_PATTERN", "/^[A-Za-z0-9_]{2,20}$/");
define("PASSWORD_PATTERN", "/^\S{8,255}$/");


if(session_status() !== PHP_SESSION_ACTIVE) session_start();

if ( !empty($_SESSION["User"]) || !empty($_SESSION["UserId"]) ) {
    notificaErroreAccesso("Hai già effettuato l\'accesso al sito");

    // DEBUG
        // $_SESSION["user"] = null; 
        // $_SESSION["userId"] = null;
    // END DEBUG

    exit;
}

if( $_SERVER["REQUEST_METHOD"] != "POST" ) {
    notificaErroreAccesso("metodo non valido");
    exit; 
}

if( empty( $_POST["username"] ) ) {
    notificaErroreAccesso("il campo username non pu\u00f2 essere vuoto");
    exit;
}

if( empty( $_POST["password"] ) ) {
    notificaErroreAccesso("il campo password non pu\u00f2 essere vuoto");
    exit;
}

if ( preg_match(USERNAME_PATTERN, $_POST["username"]) == 0 ) {
    notificaErroreAccesso( 
        strlen($_POST["username"]) < 2 || strlen($_POST["username"]) > 20 ?
        "lo username deve contenere almeno 2 caratteri, fino ad un massimo di 20"
        : "lo username può contenere solo lettere minuscole o maiuscole, numeri e underscore ( _ )"
    );
    exit;
}

if ( preg_match(PASSWORD_PATTERN, $_POST["password"]) == 0 ) {
    notificaErroreAccesso("la password deve contenere almeno 8 caratteri, fino ad un massimo di 255");
    exit;
}

// se arrivo qui, le credenziali sono conformi ai pattern indicati