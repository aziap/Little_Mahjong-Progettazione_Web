<?php 
error_reporting(E_ALL);
ini_set("log_errors", "1");
ini_set("error_log", __DIR__."/LittleMahjongLog.log");

/**
 * Scrive un messaggio nel log aggiungendo il nome del file e la riga
 */
function fullErrorLog($customMessage, $error) {
    error_log($customMessage
    .$error->getMessage()
    ." in ".$error->getFile()
    ." line ".$error->getLine()
    ." stack trace: ".print_r(debug_backtrace()));
}

// Il codice della seguente funzione è preso da https://www.justinsilver.com/technology/writing-to-the-php-error_log-with-var_dump-and-print_r/
function var_error_log( $object, $message = "" ){
    ob_start();                    // start buffer capture
    var_dump( $object );           // dump the values
    $contents = ob_get_contents(); // put the buffer into a variable
    ob_end_clean();                // end capture
    error_log( $message . $contents );        // log contents of the result of var_dump( $object )
}