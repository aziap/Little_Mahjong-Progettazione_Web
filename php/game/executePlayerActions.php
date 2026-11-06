<?php 
/*
Questo script viene eseguito quando il client fa delle
richieste asincrone per eseguire le azioni di gioco.
Quando ricevo una richiesta, controllo che sia ben formata 
e coerente con lo stato del gioco, che è salvato sul server. 
Se i controlli vanno a buon fine, aggiorno gli oggetti che 
tengono traccia dello stato e restituisco al client la risorsa richiesta,
oppure un valore che segnala l'avvenuta esecuzione
dell'azione. In caso contrario, non eseguo l'azione, 
restituisco un valore che segnala allo script lato client
che la richiesta non è stata soddisfatta, e scrivo un messaggio 
sul log del server per tenere traccia delle situazioni anomale.
*/

require_once __DIR__."/wallManager.php";
require_once __DIR__."/roundManager.php";
require_once __DIR__."/player.php";
require_once __DIR__."/actions.php";
require_once __DIR__."/endCondition.php";
require_once __DIR__."/handValidator.php";
require_once __DIR__."/../db/gameDbManager.php";
require_once __DIR__."/../log/logConfig.php";

if(session_status() !== PHP_SESSION_ACTIVE) session_start();

// Carattere speciale preposto ad una tessera pescata, se quella tessera 
// rende vincente la mano del giocatore.
const TSUMO_INDICATOR = '~';

$player = $_SESSION["player"];
$wallManager = $_SESSION["wallManager"]; 
$roundManager = $_SESSION["roundManager"];


// NOTA: potrei doverla editare in modo tale che sia eseguita solo da questo script
function saveSessionBeforeExit($player, $wallManager, $roundManager) {
    if ( isset($_SESSION["player"]) )
        $_SESSION["player"] = $player;
    if ( isset($_SESSION["wallManager"]) )
        $_SESSION["wallManager"] = $wallManager; 
    if ( isset($_SESSION["roundManager"]) )
        $_SESSION["roundManager"] = $roundManager;
    // DEBUG
    // error_log(__FILE__.": ".__LINE__."\nDebug: Sono uscito eseguendo ".__FUNCTION__);
}

// Aggiorno la sessione ogni volta che esco da questo script
register_shutdown_function("saveSessionBeforeExit", $player, $wallManager, $roundManager);


// DEBUG
var_error_log(
    $roundManager, 
    $_SESSION["user"]." è entrato in ".__FILE__.": ".__LINE__." con questo stato: "
);
error_log("... e con questa richiesta: " . actionToString($_GET["playerAction"]));
// END DEBUG


if( !isset( $_SESSION["user"] ) ) {
    error_log("Errore: Utente sconosciuto. ".__FILE__." ".__LINE__);
    header("Location: ./../../");
    exit(1);
}

if( !isset( $_SESSION["player"] ) || $player->getName() != $_SESSION["user"] ) {
    error_log("Errore: ".$_SESSION["user"]." non sta partecipando ad alcuna partita. ".__FILE__." ".__LINE__);
    header("Location: ./../../");
    echo -1;
    exit(1);
}

// Il round è finito e il giocatore richiede i risultati
if ( $_GET["playerAction"] == Action::READ_RESULT ) {
    if ( $roundManager->getCurrentPhase() != Phase::ROUND_END ) {
        echo 0;
        exit(1);
    }

    $result = $roundManager->getRoundResult();

    if ( $result === 0 ) {
        // C'è stato un problema per cui la fase nel roundManager
        // è aggiornata, ma nel DB ancora non è stato scritto
        // il risultato
        echo 0;
        exit(1);
    }

    if ( $result === false ) {
        echo -1;
        exit(1);
    }

    echo json_encode($result);
    exit(0);
}


if( $player->getWind() != $roundManager->getActivePlayerWind() ) {
    error_log("Warning: ".$player->getName()." ha provato a fare qualcosa al di fuori del suo turno. ".__FILE__.": ".__LINE__);
    echo -1;
    exit(1);
}


if ( $roundManager->getCurrentPhase() == Phase::SETUP ){
    // sto ancora inizializzando le variabili di gioco, esco senza fare nulla

    // DEBUG
    error_log("sono ancora nella fase di setup. ".__FILE__.": ".__LINE__);

    echo 1;
    exit(0);
}


if ( !isset( $_GET["playerAction"] ) ) 
    exit(1);    

// Il giocatore pesca la sua mano iniziale
if ( $_GET["playerAction"] == Action::DRAW_STARTING_HAND ) {
    if ( $player->getHand() != null )  {
        error_log($player->getName() . " ha già una mano. ".__FILE__.": ".__LINE__);
        exit(1);
    }
    if ( $roundManager->getCurrentPhase() != Phase::HAND_DRAW ) {
        error_log($_SESSION["user"] . " ha tentato di pescare la sua mano al di fuori della fase appropriata."
            .__FILE__.": ".__LINE__
        );
        exit(1);
    }
    $player->drawStartingHand($wallManager);
    $hand = $player->getHand();
    // var_error_log("Info: mano del giocatore: ", $hand);
    $jsonHand = json_encode($hand);
    // error_log("Debug: mano formato json: $jsonHand");
    echo $jsonHand;
    $roundManager->goToNextPlayerTurn(Action::DRAW_STARTING_HAND);
    // DEBUG
    error_log($_SESSION["user"]." ha pescato la sua mano. " . __FILE__.": ".__LINE__);
    exit(0);
}

// Il giocatore pesca una tessera
if ( $_GET["playerAction"] == Action::DRAW_TILE )  {
    // Il giocatore aveva già pescato in questo turno
    if ( $player->getLastDrawnTile() != null ) {
        error_log("Warning: ".$_SESSION["user"] . " ha già pescato. ".__FILE__.": ".__LINE__);
        echo -1;
        exit(1);
    }
    // Non siamo nella fase di scarto
    if ( $roundManager->getCurrentPhase() != Phase::TILE_DRAW ) {
        error_log("Warning: ".$_SESSION["user"] . " ha tentato di pescare una tessera al di fuori della fase appropriata. "
            .__FILE__.": ".__LINE__
        );
        echo -1;
        exit(1);
    }
    $tile = $player->drawTile($wallManager);
    // Se non ci sono più tessere nel muro, il round finisce
    if ( $tile == null ) {
        $roundManager->endRound(Action::END_BY_DRAW, true);
        $roundManager->updateLastAction(Action::END_BY_DRAW);
        $_SESSION["playerTurnNotified"] = false;
        echo 0;
        exit(0);
    }
    // Controllo se è una tessera vincente per la mano del giocatore
    if ( HandValidator::isWinning( array_merge( $player->getHand(), [$tile] ) ) ) {
        // La logica di validazione di una mano vincente è implementata lato server,
        // devo far sapere al client se può chiamare Tsumo.
        // Nel caso, aggiungo all'inizio della risposta un carattere speciale
        echo TSUMO_INDICATOR.$tile;
        // Setto un flag, così da non dover ripetere il controllo all'arrivo della conferma
        $player->addAvailableCall([Action::TSUMO]);
    }
    else {
        echo $tile;
    }
    error_log("Debug: ". $_SESSION["player"]->getName()." ha pescato $tile. ".__FILE__.": ".__LINE__);
    $roundManager->updateLastAction(Action::DRAW_TILE);
    exit(0);
}

// Il giocatore scarta una tessera
if ( $_GET["playerAction"] == Action::DISCARD_TILE) {
    if ( !isset( $_GET["discardedTile"] ) ) {
        error_log("Warning: ".$_SESSION["user"] . " non ha selezionato alcuna tessera da scartare "
            .__FILE__.": ".__LINE__
        );
        echo 0;
        exit(1);
    }
    $tile = $_GET["discardedTile"];
    if ( $roundManager->getCurrentPhase() != Phase::DISCARD ) {
        error_log("Warning: ".$_SESSION["user"] . " ha tentato di scartare una tessera al di fuori della fase appropriata. "
            .__FILE__.": ".__LINE__
        );
        echo -1;
        exit(1);
    }
    // Controllo se la tessera da scartare è nella mano del giocatore o se è quella appena pescata
    if ( ( array_search( $tile, $player->getHand() ) ) !== false
    || $tile == $player->getLastDrawnTile() ) {
        echo $player->discardTile($tile);
        // Prima di passare al turno  

        // DEBUG
        error_log("Debug: ".$player->getName()." ha scartato $tile. ".__FILE__.": ".__LINE__);

        // Se poteva chiamare Tsumo e non l'ha fatto, lo tolgo dalle chiamate disponibili
        $player->resetAvailableCall();

        // Prima di passare al turno dell'avversario, mi assicuro che questo
        // abbia letto l'ultima azione del giocatore

        while( $roundManager->getRoundState()["LastAction_" . $player->getWind()] 
        != Action::NO_NEW_ACTION) {
            debug_log("Aspetto che l'avversario legga la mia ultima azione");
            usleep(500000);
        }

        $roundManager->goToNextPlayerTurn(Action::DISCARD_TILE, $tile);
        // Resetto questa variabile di sessione per far sì che lo script ./gameEvents.php 
        // torni a leggere il DB in attesa di nuove azioni dell'avversario
        $_SESSION["playerTurnNotified"] = false;
        exit(0);
    }
    // se non l'ho ancora trovata, esco segnalando l'errorre
    error_log("Warning: ".$_SESSION["user"]." ha tentato di scartare una tessera che non era nella sua mano. ".__FILE__." ".__LINE__);
    echo "tessera non trovata";
    exit(1);
}

// Il giocatore può chiamare Tsumo ma passa
if ($player->findAvailableCall(Action::TSUMO) 
&& $roundManager->getCurrentPhase() == Phase::DISCARD
&& $_GET["playerAction"] == Action::PASS) {
    // DEBUG
    debug_log("Poteva chiamare Tsumo ma ha passato");
    $player->resetAvailableCall();
    echo 1;
    exit(0);
}

// Il giocatore chiama una tessera o passa
if ( $_GET["playerAction"] >= Action::PASS 
&& $_GET["playerAction"] <= Action::RON ) {
    if ( $roundManager->getCurrentPhase() != Phase::CALL ) {
        error_log("Warning: ".$_SESSION["user"] . " ha tentato di chiamare una tessera al di fuori della fase appropriata.\n"
            ."tipo di chiamata: " . actionToString($_GET["playerAction"]) 
            .", fase corrente: ". phaseToString($roundManager->getCurrentPhase()) . ".\n"
            .__FILE__.": ".__LINE__
        );
        echo -1;
        exit(1);
    }

    // Il giocatore non può chiamare la tessera
    // o decide di non farlo
    if ( $_GET["playerAction"] == Action::PASS ) {
        error_log($_SESSION["user"]." ha passato.");
        $player->resetAvailableCall();
        echo 1;
        $roundManager->updateLastAction(Action::PASS, Action::NO_NEW_ACTION);
        exit(0);
    }

    // leggo l'ultimo scarto dell'avversario
    $lastDiscard = $roundManager->getRoundState()["LastDiscard"];

    // Il giocatore ha dichiarato una vittoria per Ron
    if ( $_GET["playerAction"] == Action::RON )  {
        // La chiamata non era legittima
        if (!$player->findAvailableCall(Action::RON)) {                
            // DEBUG
            debug_log("Error: " . $_SESSION["user"] . " ha chiamato Ron senza avere una mano vincente");
            echo -1;
            exit(1);
        }

        $concealedTiles = $player->getHand();
        $concealedTiles[] = $lastDiscard;

        $openTiles = [];
        if ( !empty($openTriplets = $player->getOpenTriplets()) ) {
            foreach( $openTriplets as $ot ) {
                $concealedTiles = array_merge($concealedTiles, $ot);
            }
        }

        $result = HandValidator::getHandScore($concealedTiles, $player->getWind(), $openTiles);
        // DEBUG
        error_log("Debug: complimenti, hai vinto!");

        $roundManager->endRound(Action::RON, true, $result);
        $roundManager->updateLastAction(Action::RON);
        // echo json_encode($result);
        echo 1;
        $player->resetAvailableCall();
        exit(0);
    
    }
    // Il giocatore ha chiamato Pon o Chii
    // Se non sono nella fase di scarto, la somma tra le tessere nella mano e quelle messe da parte per formare le triplette aperte deve essere sempre 13.
    // Devo avere almeno 2 tessere per formare la tripletta, una da scartare e una "spaiata" con cui formare la coppia, per un totale di 4
    if ( count( $player->getHand() ) < 4 ) {
        error_log( $_SESSION["user"]." non ha abbastanza tessere in mano per formare una tripletta");
        echo -1;
        exit(1);
    }
    if ( !isset( $_GET["addedTiles"] ) ) {
        error_log(__FILE__.": ".__LINE__
        . "\nError: ".$_SESSION["user"]." non ha selezionato le tessere con cui formare la tripletta" );
        echo -1;
        exit(1);
    }
    $addedTiles = explode( ',', $_GET["addedTiles"] );
    // Sono state selezionate più o meno di 2 tessere
    if (count($addedTiles) != 2) {
        error_log(__FILE__.": ".__LINE__
            . "\nError: ".$_SESSION["user"]. " ha selezionato un numero sbagliato di tesere ("
            . count($addedTiles) . ")" );
        echo -1;
        exit(1);
    }

    foreach ( $addedTiles as $t ) {
        if ( array_search( $t, $player->getHand() ) === false ) {
            error_log("Errore: $t non é nella mano di ".$_SESSION["user"]);
            echo -1;
            exit(1);
        }
    }
    // Controllo che sia una tripletta valida
    if ( ( $_GET["playerAction"] == Action::PON && !HandValidator::isPon($lastDiscard, ...$addedTiles) )
    || ( $_GET["playerAction"] == Action::CHII && !HandValidator::isChii($lastDiscard, ...$addedTiles) )
        ) {
        // Lato client, mostro un messaggio di errore e chiedo di ripetere la selezione
        echo 0;
        debug_log("Warning: Le tessere selezionate non formavano una tripletta valida");
        exit(0);
    }
    // La tripletta è valida, la aggiungo alle triplette aperte del giocatore
    echo implode(',', $player->callTile($lastDiscard, $addedTiles));
    $roundManager->updateLastAction($_GET["playerAction"]);
    $player->resetAvailableCall();
    exit(0);
}

if ( $_GET["playerAction"] == Action::TSUMO ) {
    if ( $roundManager->getCurrentPhase() != Phase::DISCARD || !$player->findAvailableCall(Action::TSUMO)) {
        debug_log("ha provato a chiamare Tsumo senza una mano vincente");
        echo -1;
        exit(1);
    }
    // La chiamata è andata a buon fine
    $roundManager->updateLastAction(Action::TSUMO);            
    
    $concealedTiles = $player->getHand();
    $concealedTiles[] = $player->getLastDrawnTile();

    $openTiles = [];
    if ( !empty($openTriplets = $player->getOpenTriplets()) ) {
        foreach( $openTriplets as $ot ) {
            $openTiles = array_merge($openTiles, $ot);
        }
    }
    $result = HandValidator::getHandScore($concealedTiles, $player->getWind(), $openTiles, Action::TSUMO);

    // DEBUG
    error_log("Debug: complimenti, hai vinto!");

    $roundManager->endRound(Action::TSUMO, true, $result);
    $roundManager->updateLastAction(Action::TSUMO);
    echo json_encode($result);
    // $player->resetAvailableCall();
    exit(0);
}

// Il giocatore si è arreso o è uscito dalla pagina di gioco
if ( $_GET["playerAction"] == Action::SURRENDER ) {
    error_log("Info: ".$_SESSION["utente"]." si è arreso");
    $roundManager->endRound(Action::SURRENDER, null, true);
    $roundManager->updateLastAction(Action::SURRENDER);
    echo 1;
    exit(0);
}


if ( $_GET["playerAction"] == Action::NO_NEW_ACTION ) {
    // Non faccio niente

    // DEBUG
    error_log($player->getName()." non ha fatto niente. ".__FILE__.": ".__LINE__);
    echo 1;
    exit(0);
}

echo -1;
error_log("Warning: unexpected action. ".__FILE__.": ".__LINE__);
exit(0);


