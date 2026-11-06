<?php


if(session_status() !== PHP_SESSION_ACTIVE) session_start();

require "./patternValidation.php";

require "./../db/accessDbManager.php";


if ( empty($_POST["login-btn"]) || $_POST["login-btn"] != "Login" ) {
    notificaErroreAccesso("si è verificato un errore inaspettato.");
    exit;
}

$accessDbManager = new AccessDbManager();
$accessDbManager->openConnection();


// controllo se la password corrisponde allo username inserito
if ( ( $id = $accessDbManager->verificaCredenziali($_POST["username"], $_POST["password"]) ) == null) {
    notificaErroreAccesso("le credenziali inserite non sono corrette.");
    $accessDbManager->closeConnection();
    exit;
} 

// controllo se l'utente ha già fatto l'accesso da un'altra finestra
// PROBLEMA: se l'utente chiude il browser, lo stato non viene aggiornato.
// Questa situaizione non è gestibile usando solo gli strumenti che abbiamo visto a lezione. 
// if ( $accessDbManager->getUserStatus($_POST["username"])  != "offline" ) {
//     notificaErroreAccesso("Hai gi\u00e0 fatto l\'accesso da un altro browser o da un altro dispositivo. Fai il logout per accedere da qui.");
//     $accessDbManager->closeConnection();
//     exit;
// }


// il login è andato a buon fine
// $accessDbManager->setUserStatus("online", $_POST["username"]);
$accessDbManager->closeConnection();

echo "<script>alert('Benvenuto ".$_POST['username']."!')</script>";
$_SESSION["userId"] = $id;
$_SESSION["user"] = $_POST["username"];

header("Location: ../home.php");

