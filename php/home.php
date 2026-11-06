<?php

    if(session_status() !== PHP_SESSION_ACTIVE) session_start();

    if( empty($_SESSION["userId"]) )
        header("Location: ./../index.php"); 

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="author" content ="Asia Perroni">
    <meta name="keywords" content ="game">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="./../css/img/favicon-16x16.png" />
    <link rel="stylesheet" href="./../css/main.css" type="text/css" media="screen">
    <link rel="stylesheet" href="./../css/home.css" type="text/css" media="screen">
    <script type="text/javascript" src="./../js/navigation.js"></script>
    <script type="text/javascript" src="./../js/utils.js"></script>
    <title>Little Mahjong - Home</title>
</head>

<body>
    <header id="title-header">
            <h1 id="page-title">Little Mahjong</h1>
    </header>
    <main class="user-content-wrapper">
        <div id="home-menu" class="user-content-container">
            <section id="greetings">
                <h3>
                    Benvenuto <?php echo $_SESSION["user"]; ?>
                </h3>
                <hr>
            </section>
            <section id="home-button-container">
                <button id="register-button" onclick="window.location.href='./waitingHall.php'">Cerca un tavolo</button>
                <button id="leaderboard-button" onclick="window.location.href='./leaderboard.php'">Classifica</button>
                <button id="manual-button" onclick="window.location.href='./leaderboard.php'">Manuale</button>
            </section>
            <hr>
            <div id="logout-container">
                <button id="logout-button" class="confirm-button" onclick="window.location.href='./access/logout.php'">Logout</button> 
            </div>
        </div>
    </main>
</body>
