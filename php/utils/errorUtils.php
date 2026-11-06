<?php 
function notificaErroreAccesso($msg) {
    echo "<script>
            alert('Errore: $msg');
            window.history.back();
        </script>";
}

/**
 * Stampa $msg nella console. 
 * $msg deve essere su una sola riga. 
 * Gli apici (') devono essere preceduti da un backward slash (\)
 */
function consoleLog($msg) {
    $txt = preg_replace("/[\r\n]+/", "", $msg);
    echo "<script>
    console.log('$txt');
</script>";
}

function debug_log($message) {
    $backtrace = debug_backtrace(0);
    // Il primo elemento è quello relativo a questa funzione, non mi serve
    $debugMsg = ""; 
    foreach ( $backtrace as $callNum => $info ) {
        $debugMsg .= "# $callNum\n"
            . "In ".$info["function"] . ", "
            . $info["file"] . ": "
            . $info["line"] . "\n";
    }
    if( isset($_SESSION["user"]) )
        $debugMsg .= "(".$_SESSION["user"].") ";
    $debugMsg .= $message;
    error_log($debugMsg);
}