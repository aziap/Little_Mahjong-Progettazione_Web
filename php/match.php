<?php

require "./game/matchSetup.php";

if(session_status() !== PHP_SESSION_ACTIVE) session_start();
    
if ( empty($_SESSION["user"]) ) {
    header("Location: ./../");
    exit;
}

if ( empty($_SESSION["matchId"]) || empty($_SESSION["roundManager"]) || empty($_SESSION["wallManager"])
|| empty($_SESSION["player"]) || empty($_SESSION["opponent"]) ) {

    // DEBUG
    debug_log("Warning: Il match non era stato inizializzato...");
    var_error_log($_SESSION);
    var_error_log($_SERVER);

    clearGameSession();
    header("Location: ./home.php");
    exit(1);
}

session_write_close();

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="author" content ="Asia Perroni">
    <meta name="keywords" content ="game">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="./css/img/favicon-16x16.png"/>
    <link rel="stylesheet" href="./../css/main.css" type="text/css" media="screen">
    <link rel="stylesheet" href="./../css/game.css" type="text/css" media="screen">
    <link rel="stylesheet" href="./../css/resultWindow.css" type="text/css" media="screen">
    <script type="text/javascript" src="./../js/utils.js"></script>
    <script type="text/javascript" src="./../js/game.js"></script>
    <script type="text/javascript" src="./../js/player.js"></script>
    <script type="text/javascript" src="./../js/gameGraphicInterface.js"></script>
    <script type="text/javascript" src="./../js/gameEventManagement.js"></script>
    <script type="text/javascript" src="./../js/infoWindow.js"></script>
    <script type="text/javascript" src="./../js/error.js"></script>
    <title>Little Mahjong</title>
</head>

<body>

    <div id="game-wrapper">
        <div id="round-info-wrapper">
        </div>
    </div>

</body>

</html>
