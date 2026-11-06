<?php

require "./db/gameDbManager.php";

if(session_status() !== PHP_SESSION_ACTIVE) session_start();

if ( empty($_SESSION["user"]) ) {
    header("Location: ./../index.php");
}


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
    <link rel="stylesheet" href="./../css/resultWindow.css" type="text/css" media="screen">
    <script type="text/javascript" src="./../js/navigation.js"></script>
    <title>Little Mahjong - Classifica</title>
</head>

<body>
    <header id="title-header">
            <h1 id="page-title">Little Mahjong</h1>
    </header>
    
    <main id="leaderboard-wrapper" class="user-content-wrapper">
        <section id="leaderboard-display" class="user-content-container">
            <h1>Classifica</h1>

            <table id="leaderboard-table">
                <thead>
                    <th>#</th><th>Utente</th><th>Punti</th><th>Partite giocate</th>
                </thead>
                <?php

                    $gameDbManager = new GameDbManager();

                    $gameDbManager->openConnection();
                    $result = $gameDbManager->readLeaderboard();
                    $userRank = false;
                    $i = 1;
                    while ( $row = $result->fetch() ){
                        echo "<tr>"
                        ."<td>".$i++."</td>"
                        ."<td>".$row["User"]."</td>"
                        ."<td>".$row["Points"]."</td>"
                        ."<td>".$row["NumPlayedMatches"]."</td>"
                        ."</tr>";
                    }
                    $gameDbManager->closeConnection();

                ?>
        </table>   
        <hr>
            <div id="button-wrapper">
                <button id="back-button" class="confirm-button" onclick="goHome()">Vai alla home</button>
                <button id="logout-button" class="confirm-button" onclick="logout()">Logout</button>
            </div>
        </section>
    </main>

</body>
</html>