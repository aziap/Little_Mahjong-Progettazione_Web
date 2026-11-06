<?php
    include_once "./php/log/logConfig.php";
    include "./php/utils/errorUtils.php";
    require "./php/db/accessDbManager.php";
    require "./php/db/gameDbManager.php";

    session_start();

    // ignore_user_abort();

    if( isset( $_SESSION["userId"] ) ) {
        header("Location: ./php/home.php");
        exit;
    }

    if( isset( $_GET["accessMethod"] ) && $_GET["accessMethod"] == "login" ) {
        $_SESSION["loadLogin"] = true;
    }

    if( isset( $_GET["accessMethod"] ) && $_GET["accessMethod"] == "signup" ) {
        $_SESSION["loadLogin"] = false;
    }



?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="author" content ="Asia Perroni">
    <meta name="keywords" content ="game">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="./css/img/favicon-16x16.png"/>
    <link rel="stylesheet" href="./css/main.css" type="text/css" media="screen">
    <script type="text/javascript" src="./js/access.js"></script>
    <script type="text/javascript" src="./js/utils.js"></script>
    <title>Little Mahjong</title>
</head>

<body>
    <main>
        <header id="title-header">
            <h1 id="page-title">Little Mahjong</h1>
        </header>
        <div id="form-wrapper" class="user-content-container">
            
            <?php
            // controllo se devo caricare il form di login o quello di registrazione
            if( !isset($_SESSION["loadLogin"]) || $_SESSION["loadLogin"] )
                include "./php/formLogin.php";
            else 
                include "./php/formSignup.php";
            ?>

            
            <hr>
            <a id="link-manuale" href="./php/manuale.php" target="_blank">Leggi il manuale</a>
            
        </div>
    </main>

</body>
</html>