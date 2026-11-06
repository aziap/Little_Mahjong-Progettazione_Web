<?php

include "./../log/logConfig.php";

session_start();

if ( isset( $_GET["userStatus"] ) 
    && $_GET["userStatus"] = "checkIfLogged" ) {
    $result = isset($_SESSION["userId"]) ? 1 : -1;
    echo $result;
    error_log("Notice: ".__FILE__."?userStatus=checkIfLogged"
    . " has been executed with"
    . " a returning value of $result. \n"
    . "Connection status: "
    . (connection_aborted() ? "aborted" : "still active") );
    exit;
}

exit;