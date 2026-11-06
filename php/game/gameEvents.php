<?php

require_once __DIR__."/roundManager.php";
require_once __DIR__."/actions.php";
require_once __DIR__."/handValidator.php";
// require_once __DIR__."/../log/logConfig.php";


if(session_status() !== PHP_SESSION_ACTIVE) session_start();

// TODO: settare un time limit più ragionevole (tipo 1h per stare larghi)
set_time_limit(18000);  // 30 minuti


// possibili eventi
const ERROR             = "error";
const INITIALIZATION    = "initialization";
const MATCH_START       = "match-start";
const HAND_DRAW         = "hand-draw";
const TILE_DRAW_EVENT   = "tile-draw";
const DISCARD           = "discard";
const CALL              = "call";
// const YOUR_DRAW         = "your-draw";
// const OPPONENT_DRAW     = "opponent-draw";
const MATCH_END         = "match-end";
const NO_ACTION         = "";

// Carattere speciale preposto ad una tessera pescata, se quella tessera 
// rende vincente la mano del giocatore.
const TSUMO_INDICATOR = '~';


$wallManager = $_SESSION["wallManager"];
$roundManager = $_SESSION["roundManager"];
$player = $_SESSION["player"];
$opponent = $_SESSION["opponent"];

$_SESSION["playerTurnNotified"] = false;

define("PLAYER_WIND", $player->getWind() );
define("OPPONENT_WIND", $opponent->getWind() );

session_write_close();


// Evito che il server tenti di inviare nuovi header all'inizio della sessione
// codice preso da: 
// https://stackoverflow.com/questions/12315225/reopening-a-session-in-php
ini_set('session.use_only_cookies', false);
ini_set('session.use_cookies', false);
ini_set('session.use_trans_sid', false);
ini_set('session.cache_limiter', null);
// fine codice preso da terzi




header("Content-Type: text/event-stream; charset=utf-8"); 
header("Cache-Control: no-cache");
header("Connection: keep-alive");

function sendEvent($event, $data) {


    echo "retry: 500\n";
    echo "event: $event\n";
    echo "data: $data\n";
    echo "\n";
    if (ob_get_contents()) {
        ob_end_flush();
    }
    flush();
}

function saveSessionVariables() {
    
    global $wallManager;
    global $roundManager;
    global $player;
    global $opponent;
    // global $playerTurnNotified;
    // global $prevOpponentAction;

    $_SESSION["wallManager"] = $wallManager;
    $_SESSION["roundManager"] = $roundManager;
    $_SESSION["player"] = $player;
    $_SESSION["opponent"] = $opponent;

    // $_SESSION["playerTurnNotified"] = $playerTurnNotified;
    // $prevOpponentAction = $prevOpponentAction;

}



while (true) {

    // global $playerTurnNotified;
    // global $prevOpponentAction;

    // Se è il turno del giocatore e gli/le è già stato notificato, non c'è bisogno di leggere nel DB
    // $_SESSION["playerTurnNotified"] viene messa a false quando il giocatore
    // esegue un'azione, nello script "./executePlayerActions.php"
    // if( $_SESSION["playerTurnNotified"] ) {
    //     sendEvent(NO_ACTION, "waiting for player action");
    //     session_write_close();
    //     sleep(1);
    //     continue;
    // }
    
    if(session_status() !== PHP_SESSION_ACTIVE) {
        session_start();    
        $wallManager = $_SESSION["wallManager"];
        $roundManager = $_SESSION["roundManager"];
        $player = $_SESSION["player"];
        $opponent = $_SESSION["opponent"];
    } 

    if ( isset( $_SESSION["playerTurnNotified"] ) && $_SESSION["playerTurnNotified"] ) {
        // DEBUG
        sendEvent(NO_ACTION, "Waiting for player action");
        session_write_close();
        sleep(1);
        continue;
    }
        
    // Setup
    if ( $roundManager->getCurrentPhase() == Phase::SETUP ) {

        // Invio la codifica delle azioni dei giocatori. Così, se
        // nel server la cambio, non devo modificare il client.
        // Devo mandare un oggetto, ma i campi costanti di una classe sono statici, un oggetto di tipo
        // Action sarebbe vuoto. Perciò uso la classe ReflectionClass, che mi dà
        // informazioni sulla classe passata al costruttore.
        $reflectionClassAction = new ReflectionClass('Action');
        sendEvent("event-0", json_encode($reflectionClassAction->getConstants()));
        
        // Invio i dati dei giocatori
        // sendEvent( MATCH_START,  json_encode([$_SESSION["player"], $_SESSION["opponent"]]) );
        sendEvent( MATCH_START,  json_encode([$player, $opponent]) );
        $roundManager->advancePhase();

        // DEBUG
        debug_log("Finito il setup");
        debug_log("Stato attuale letto dal DB: ...");
        var_error_log($roundManager->getRoundState(false), "... ");
        // END DEBUG
    
    }
    
    // Leggo dal DB lo stato del round, finché l'avversario non esegue una nuova azione
    while ( ($state = $roundManager->getRoundState()) === null ) {
        // Se l'operazione fallisce, aspetto mezzo secondo e riprovo 
        usleep(500000);
    }
    
    // $roundManager->getCurrentPhase();
    
    // L'avversario ha pescato la propria mano iniziale
    if ($roundManager->getCurrentPhase() == Phase::HAND_DRAW
    && $state["LastAction_".OPPONENT_WIND] == Action::DRAW_STARTING_HAND ) {
        $wallManager->incrementDrawnTileCount(HAND_SIZE);
        $roundManager->updateActivePlayerId();
        // Ho letto l'ultima azione dell'avversario, nel DB scrivo che 
        // non ha fatto nuove azioni per non rileggere la stessa
        while ( $roundManager->updateLastAction(Action::NO_NEW_ACTION) === null ) {
            usleep(500000);
        }
        sendEvent(HAND_DRAW, "");
    }

    
    // Sono nel turno dell'avversario
    if( $state["ActivePlayer"] == OPPONENT_WIND ) {
        
        // L'avversario non ha ancora fatto niente   
        // if( isset($prevOpponentAction)
        //     && ( $state["LastAction_".OPPONENT_WIND] == $prevOpponentAction
        //         || $state["LastAction_".OPPONENT_WIND] == Action::NO_NEW_ACTION )) {
        if( $state["LastAction_".OPPONENT_WIND] == Action::NO_NEW_ACTION ) {
                    
            sendEvent(NO_ACTION, "Debug: waiting for opponent action. Last opponent action: " . actionToString($state["LastAction_".OPPONENT_WIND]));
            
            saveSessionVariables();    
            session_write_close();     
            
            sleep(1);
            continue;
        } else {
            // DEBUG
            debug_log("sono nel turno di " . $opponent->getName() . ", che ha fatto: " . actionToString($state["LastAction_".OPPONENT_WIND])
        . "\nFase registrata nel RM: " . phaseToString($roundManager->getCurrentPhase())
        . " AP registrato: " . $roundManager->getActivePlayer()->getName());
            sendEvent("", "l'avversario ha fatto un'azione: " . actionToString($state["LastAction_".OPPONENT_WIND]));
        }
        
        if( $state["LastAction_".OPPONENT_WIND] == Action::DRAW_TILE) {
            $wallManager->incrementDrawnTileCount();
            sendEvent(TILE_DRAW_EVENT, "");
            // Se ero nella fase di chiamata, ma l'avversario ha pescato, 
            // vuol dire che prima ha passato. Aggiorno la fase per sincronizzarmi
            // con lo stato reale della partita.
            if ( $roundManager->getCurrentPhase() == Phase::CALL ) {
                $roundManager->advancePhase(Action::PASS);
            }
            // Cambio l'ultima azione dell'avversario in NO_NEW_ACTION
            // e avanzo la fase a quella di scarto
            $roundManager->updateLastAction();
            

        }

        else if( $state["LastAction_".OPPONENT_WIND] == Action::PASS) {
            $roundManager->updateLastAction(Action::NO_NEW_ACTION, Action::PASS );
        }

        else if( $state["LastAction_".OPPONENT_WIND] == Action::PON
            || $state["LastAction_".OPPONENT_WIND] == Action::CHII) {
                // Invio al client la chiamata e la tripletta stringa CSV
                sendEvent(CALL, $state["LastAction_".OPPONENT_WIND] . "," . $state["LastOpenTriplet"] );
                $roundManager->updateLastAction(Action::NO_NEW_ACTION, $state["LastAction_".OPPONENT_WIND] );
        }

        // L'avversario ha vinto
        else if ( $state["LastAction_".OPPONENT_WIND] == Action::RON
        || $state["LastAction_".OPPONENT_WIND] == Action::TSUMO 
        || $state["LastAction_".OPPONENT_WIND] == Action::END_BY_DRAW ) {
            $roundManager->endRound();
            sendEvent(MATCH_END, $state["LastAction_".OPPONENT_WIND] );
            exit(0);
        }
        
    }
    // Sono nel turno del giocatore
    else {      
        // Sono all'inizio della partita e devo pescare la mano
        if ( $roundManager->getCurrentPhase() == Phase::HAND_DRAW ) {
            // Invio la mano al processo client
            $hand = implode(',', $player->drawStartingHand($wallManager) );
            sendEvent(HAND_DRAW, $hand);

            // DEBUG
            error_log("Debug: mano iniziale di ".$player->getName().": $hand. ".__FILE__.": ".__LINE__."\n");
            
            while( !$roundManager->goToNextPlayerTurn(Action::DRAW_STARTING_HAND) ) {
                // Attendo mezzo secondo prima di ritentare
                usleep(500000);
            }
            saveSessionVariables();
            session_write_close();
            sleep(1);
            continue;
        }
        else if( $state["LastAction_".OPPONENT_WIND] == Action::DISCARD_TILE) {
            // TODO: verificare che l'avversario non abbia anche
            // chiamato senza che ce ne accorgessimo (leggendo last open triplet)

            // TODO: implementare meccanismo di wait for ACK in ./executePlayerActions.php per 
            //  evitare questo pezzo (e, in caso di call, di dover lasciare al client di interpretare
            // quale è stata la call)

            // Potrei non aver letto dal DB che l'avversario ha pescato (eventualmente dopo aver passato)
            // se tra il pescaggio e lo scarto è passato troppo poco tempo
            if ( $roundManager->getCurrentPhase() == Phase::CALL
                && $state["LastOpenTriplet"] == null ) {
                sendEvent(TILE_DRAW_EVENT, "");
                // Avanzo lo stato interno alla fase di pescaggio dell'avversario
                $roundManager->advancePhase(Action::NO_NEW_ACTION, Action::PASS);  
            } 
            if ( $roundManager->getCurrentPhase() == Phase::TILE_DRAW ) {  
                $wallManager->incrementDrawnTileCount();
                // Avanzo lo stato interno alla fase di scarto dell'avversario
                $roundManager->advancePhase();
                // Aspetto prima di proseguire, per assicurarmi che l'interfaccia
                // grafica del giocatore sia stata aggiornata 
                sleep(1);
            } 
            // if ( $roundManager->getCurrentPhase() == Phase::TILE_DRAW ) {  



            $lastDiscard = $state["LastDiscard"];
            
// DEBUG: quando entro qui sono nella fase di discard   
// quindi a questo punto entro nella call phase del giocatore
            $roundManager->updateLastAction();  
            // una volta che sono nella fase di chiamata, lo stato 
            // va aggiornato nello script executePlayerActions.php.
            // Qui ancora non tocco niente
            // Controllo se il giocatore può chiamare la tessera
            if ( ( $call = HandValidator::canBeCalled($lastDiscard, $player->getHand()) )
            === false
            ) {
                // Il giocatore può solo passare
                sendEvent(DISCARD, 
                $state["LastDiscard"] . "," . Action::PASS);
            } 
            else {
                $call[] = Action::PASS;
                // Aggiungo le chiamate disponibili
                $player->addAvailableCall($call);
                sendEvent(DISCARD, 
                $state["LastDiscard"] . "," 
                . implode(',', $call));
            }
            // Aggiorno il giocatore attivo nel RoundManager
            $roundManager->updateActivePlayerId();
            $_SESSION["playerTurnNotified"] = true;
            
            // DEBUG
            $callStr = "";
            if ( $call === false ) $call = [Action::PASS];
            foreach ($call as $c) {
                $callStr .= actionToString($c) .', ';
            }
            error_log(__FILE__.": ".__LINE__."\n".$opponent->getName() . " ha scartato $lastDiscard e ".$player->getName()." può chiamare: $callStr");
            // END DEBUG
            
        }
        
    }
    // DEBUG
    sendEvent(NO_ACTION, "end of the loop");
    saveSessionVariables();
    session_write_close();
    // END DEBUG

    sleep(1);
}

// Se per qualche motivo esco dal while, lo segnalo
sendEvent(ERROR, "unexpected event: out of the loop");
saveSessionVariables();
exit(0);







// error_log("turno del giocatore: ".$state["ActivePlayer"]);
// // se l'avversario non ha ancora fatto niente, mando un messaggio vuoto
// if ( $player->getWind() != $roundManager->getActivePlayerWind() ) {
//     error_log("giocatore attivo registrato nel RM: ".$roundManager->getActivePlayerWind());
//     sendEvent(NO_ACTION, "");
    // // exit(0);
// }





/**
 * Quando l'avversario ha eseguito una nuova azione
 */
// if ( $player->getWind() == $roundManager->getActivePlayerWind() ) {
//     // L'avversario non ha ancora finito il suo turno
//     // non faccio niente
//     error_log("sono ".$player->getWind()." ed è il mio turno");
//     error_log("la fase corrente è: ".$roundManager->getCurrentPhase());
//     error_log("LastAction_".OPPONENT_WIND.": ".$state["LastAction_".OPPONENT_WIND]);
//     switch ( $state["LastAction_".OPPONENT_WIND] ) {
//         case Action::NO_NEW_ACTION :
//             sendEvent(NO_ACTION, "");
            // // exit(0);
//         case Action::DRAW_STARTING_HAND : 
//             error_log("ora dovrei pescare la mia mano");
//             $wallManager->incrementDrawnTileCount(HAND_SIZE);
//             // Se sono l'Est, devo aspettare che il Sud
//             // peschi la sua mano per poter pescare di nuovo
//             if ( $roundManager->getCurrentPhase() == Phase::HAND_DRAW )
//                 sendEvent( YOUR_TURN, $state["LastAction_".OPPONENT_WIND] );
//             else 
//             sendEvent(NO_ACTION, "");
//             break;
//             // sendEvent(YOUR_TURN, $state["LastAction_".OPPONENT_WIND]);
    
//         default:
//             sendEvent(NO_ACTION, "");
//     }
    // // exit(0);
// }
// else {
//     sendEvent(NO_ACTION, "");
//     // sendEvent(YOUR_TURN, json_encode($state));
    // // exit(0);
// }

