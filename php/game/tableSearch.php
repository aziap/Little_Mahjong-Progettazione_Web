<?php

/*
 * "Connette" l'utente ad un altro utente per cominciare un match.
 * Questo script viene chiamato periodicamente (1 secondo) da un oggetto EventSource in ./js/waitingHall.js, 
 * dalla pagina waitingHall.php.
 */

require __DIR__."/roundManager.php";
require_once __DIR__."/../log/logConfig.php";
// require_once __DIR__."/../db/gameDbManager.php";

if(session_status() !== PHP_SESSION_ACTIVE) session_start();

// Nel caso una precedente partita fosse terminata in maniera inaspettata,
// resetto la sessione di gioco
clearGameSession();

header("Content-Type: text/event-stream; charset=utf-8"); 
header("Cache-Control: no-cache");
header("Connection: keep-alive");


const ERROR = -1;
const TABLE_CREATED = 0;
const WAITING_FOR_PLAYERS = 1;
const TABLE_JOINED = 2;
const MATCH_STARTING = 3;

// Il codice della seguente funzione è preso da: https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events/Using_server-sent_events
/*
 * Invia update al client riguardo lo stato della ricerca di un "tavolo" (ovvero di un match) 
 */
function sendUpdate($code) {
    // DEBUG
    // error_log("codice: ".$code);
    echo "retry: 1000\n";
    echo "data: ".$code;
    echo "\n\n";
    if (ob_get_contents()) {
        ob_end_flush();
    }
    flush();
}

$gameDbManager = new GameDbManager();
if ( !$gameDbManager->isOpened() )
    $gameDbManager->openConnection();

/*
 * Se non ho ancora trovato un match, cerco nel database se ce n'è uno con un posto libero  
 */

if ( !isset($_SESSION["matchId"] ) ) {
    $tableId = $gameDbManager->joinMJTable( $_SESSION["user"]);
    
    // DEBUG
    debug_log($tableId == 0 ? "Nessun tavolo disponibile, ne creo uno"
        : "Ho trovato un tavolo"  
    );
    
    if ( $tableId == -1 ) {
        // Si è verificato un errore  
        sendUpdate(ERROR);
        exit(1);
    }

    if ( $tableId != 0 ) {
        sendUpdate(TABLE_JOINED);  

    }
    // Non ci sono tavoli liberi e devo crearne uno
    else {
        $tableId = $gameDbManager->createMJTable($_SESSION["user"]);
        if( $tableId == -1 ) {
            // segnalo l'errore all'utente
            sendUpdate(ERROR);     
            exit(1);
        }
        sendUpdate(TABLE_CREATED);      
    }

}

// Ho trovato un tavolo (o l'ho creato), provo a cominciare un match
// DEBUG
debug_log("Tavolo trovato: ".$tableId."\n");

while ( true ) {
    if ( !isset( $_SESSION["roundManager"] )  && !$gameDbManager->tryStartMatch($tableId) ) {
        sendUpdate(WAITING_FOR_PLAYERS); 
        sleep(1);
        continue;
    }
    break;
}

$players;

if ( ( $players = $gameDbManager->getPlayersNames($tableId) ) == null ) {
    sendUpdate(ERROR);
    exit(1);
}

$_SESSION["matchId"] = $tableId;
$_SESSION["roundManager"] = new RoundManager($tableId, $players);
sendUpdate(MATCH_STARTING);



exit(0);





