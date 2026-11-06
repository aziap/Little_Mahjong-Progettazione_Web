<?php 


require_once __DIR__."/../db/gameDbManager.php";
require_once __DIR__."/wallManager.php";
require_once __DIR__."/roundManager.php";
require_once __DIR__."/player.php";
require_once __DIR__."/actions.php";
require_once __DIR__."/roundPhases.php";
require_once __DIR__."/gameUtils.php";
require_once __DIR__."/../log/logConfig.php";

if(session_status() !== PHP_SESSION_ACTIVE) session_start();

$roundManager = $_SESSION["roundManager"];

if($roundManager->hasToInitialize()){
    while( !$roundManager->startNewMatch() ) {
        sleep(1);
    }

    while( !$roundManager->startRound() ) {
        sleep(1);
    }
}
else {

    while( !$roundManager->getRoundStartingInfos() ) {
        usleep(200000); // 0.2 s
    }

    while( !$roundManager->getRoundState() ) {
        usleep(200000); // 0.2 s
    }
}
