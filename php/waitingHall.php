<?php

    include "./game/gameUtils.php";

    if(session_status() !== PHP_SESSION_ACTIVE) session_start();
    
    if ( empty($_SESSION["user"]) ) {
        header("Location: ./../");
        exit;
    }

    clearGameSession();
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="author" content ="Asia Perroni">
    <meta name="keywords" content ="game">
    <link rel="shortcut icon" type="image/x-icon" href="./../css/img/favicon-16x16.png" />
    <link rel="stylesheet" href="./../css/main.css" type="text/css" media="screen">
    <link rel="stylesheet" href="./../css/home.css" type="text/css" media="screen">
    <script type="text/javascript" src="./../js/waitingHall.js"></script>
    <script type="text/javascript" src="./../js/utils.js"></script>
    <title>Little Mahjong - Gaming Hall</title>
</head>

<body onload="initializeWaitingHall()">
    <header id="title-header">
            <h1 id="page-title">Little Mahjong</h1>
    </header>
    <main class = "user-content-wrapper">
        <div class="user-content-container">  
            <p>In attesa di cominciare una partita...</p>  
            <div id="info-ricerca-tavolo"></div>
            <button id="cancel-button" class="confirm-button">Cancella</button>
        </div>
    </main>

</bod>
</html>