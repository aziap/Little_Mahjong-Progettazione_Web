"use strict";

const SEPARATOR = ',';
const TSUMO_INDICATOR = '~';
const TOT_TILES = 92;
const HAND_SIZE = 13;

// Fasi del gioco
const Phase = {
    CALL : 0,
    TILE_DRAW : 1,
    DISCARD : 2,
    SETUP : 3,
    HAND_DRAW : 4,
    ROUND_END : 5,
    OPPONENT_TURN : 6,
    TILE_SELECTION : 7
};

let currentPhase = Phase.SETUP;

let player = null;
let opponent = null;

let yourId = null;
let opponentId = null;

let gameGraphicInterface;
let Action;
let gameEventSource;

let selectedTiles = [];
let numSelected = 0;
let lastCall = null;

let retryTimerId = null;

let remainingTiles = TOT_TILES;

// let gameGraphicInterface = null;


// // event handler per scartare le tessere durante la fase di scarto del giocatore
async function discardOnClick(event) {
    if ( currentPhase != Phase.DISCARD || !event.target.classList.contains("tile"))
        return;

    // DEBUG
    console.log("You clicked on " + event.target.getAttribute("id"));
    console.log(event.target);
    // END DEBUG

    // se l'operazione è fallita, non faccio niente

    let result;
    try {
        result = await player.discardTile(event.target)
    } 
    catch(error) {
        handleGameError(error);
    }

    if ( !result )
        return;
    // avanzo alla prossima fase
    // evt.currentTarget.classList.remove("draw");
    // currentPhase = Phase.OPPONENT_TURN;
}

// Event handler per selezionare le tessere da usare per formare
// una tripletta aperta
function selectForTriplet(event) {
    if ( currentPhase != Phase.TILE_SELECTION 
        || !event.target.classList.contains("tile")
    )
        return;
    // Se la tessera non era selezionata, incremento il numero di tessere selezionate, altrimenti lo decremento
    numSelected += event.target.classList.toggle("selected") ? +1 : -1;

    // DEBUG:
    console.log(numSelected);

    document.getElementById("confirm-button").disabled = numSelected != 2;

    // confirmButton.disabled = numSelected != 2;
}

function initializePlayers(event) {
    let playerInfos = JSON.parse(event.data);
    player = new Player (...Object.values(playerInfos[0]));
    opponent = playerInfos[1];
    currentPhase = Phase.HAND_DRAW;
    if ( player.wind == EAST ) {
        player.drawStartingHand();
    } 
    // Disegno la mano dell'avversario
    gameGraphicInterface.displayHand(opponent.playerId);
}

async function callHandler(event) {
    
    if ( currentPhase != Phase.CALL )
        return;
    
    // DEBUG
    console.log("Debug: hai cliccato su " + actionToString(event.target.value));
    lastCall = parseInt(event.target.value);

    switch(lastCall) {
        case Action.CHII :
        case Action.PON :
            currentPhase = Phase.TILE_SELECTION;
            gameGraphicInterface.getLastDiscardNode(OPPONENT).classList.add("selected");
            gameGraphicInterface.displaySelectionButtons();
            break;

        case Action.RON :
            console.log("hai vinto!");
            try {
                const result = await player.declareVictory(Action.RON);
                gameEventSource.close();
                // debug
                console.log(result);
        
                if ( !result ) {
                    return;
                }
                player.readResult();
            }
            catch (error) {
                handleGameError(error);
            }
            break;

        default :   // PASS
            let result;
            try {
                result = await player.pass();
                if (!result) {
                    return;
                }
                result = await player.drawTile();
                if ( result === null ) {
                    gameEventSource.close();
                    console.log("end by draw");
                    // TODO: mostra messaggio
                }
                gameGraphicInterface.decrementTileCount();

            }
            catch (error) {
                handleGameError(error);
            }
            // currentPhase = Phase.DISCARD;
    }
    gameGraphicInterface.hideCallButtons();
}

/**
 * Event listener per il bottone Tsumo (la tessera appena pescata completa una mano vincente).
 *  Lo Tsumo può essere chiamato solo in fase di pescaggio, quindi va gestito
 * in modo diverso dalle altre chiamate.
 */
async function declareTsumoHandler() {
    if ( currentPhase != Phase.DISCARD )
        return;

    // DEBUG
    console.log("complimenti, hai vinto!")

    try {
        const result = await player.declareVictory(Action.TSUMO);
        
        // debug
        console.log(result);
        gameEventSource.close();
        gameGraphicInterface.hideCallButtons();
        lastCall = Action.TSUMO;
        // TODO: schermata fine gioco
        player.readResult();
    }
    catch (error) {
        handleGameError(error);
    }

}

async function passOnTsumoHandler() {
    if ( currentPhase != Phase.DISCARD ) 
        return;
    console.log("debug: ma sei sicuro?");
    try {
        let result = await player.pass();
        if( !result ) {
            return;
        }
        gameGraphicInterface.hideCallButtons();
    } 
    catch (error) {
        handleGameError(error);
    }

}

async function confirmSelection() {
    const selectedFromHand = 
        document.querySelectorAll("#concealed-zone-" + CLIENT_PLAYER + " > .tile.selected");
    // DEBUG
    console.log("added tiles: ")
    console.log(selectedFromHand);
    // END DEBUG
    const result = await player.callTile(lastCall, [...selectedFromHand])
    if ( result ) {
        gameGraphicInterface.hideSelectionButtons();
        numSelected = 0;
        lastCall = null;
    } 
    else {
        alert("Le tesser selezionate non erano un "
            + actionToString(lastCall) 
            + " valido. Ritenta!"
        )
        console.log("chiamata fallita");
    }
}

async function cancelSelection() {
    // DEBUG
    console.log("hai annullato la selezione, la tessera verrà passata");
    
    for ( let t of document.querySelectorAll(".tile.selected")) {
        t.classList.remove("selected");
    }
    gameGraphicInterface.hideSelectionButtons();
    gameGraphicInterface.changeVisibility("message-section", "none");
    numSelected = 0;
    lastCall = null;

    try {
        let result = player.pass();
        if ( !result )
            return false;
        result = await player.drawTile();
        if (result === null ) {
            gameEventSource.close();
            console.log("end by draw");
        }
        gameGraphicInterface.decrementTileCount();
    }
    catch(error) {
        console.error(error);
    }

}



function initializeGame() {
    currentPhase = Phase.SETUP;
    
    gameGraphicInterface = new GameGraphicInterface();
    gameGraphicInterface.initializeScene();

    document.getElementById("concealed-zone-" + CLIENT_PLAYER).addEventListener(
        "click", discardOnClick
    );

    document.getElementById("draw-zone-" + CLIENT_PLAYER).addEventListener(
        "click", discardOnClick
    );

    document.getElementById("concealed-zone-" + CLIENT_PLAYER).addEventListener (
        "click", selectForTriplet
    );

    const confirmButton = document.getElementById("confirm-button");
    confirmButton.disabled = true;
    confirmButton.addEventListener("click", confirmSelection);

    document.getElementById("cancel-button").addEventListener("click", cancelSelection)

    gameEventSource = new EventSource("./game/gameEvents.php"); 


    // TODO: onpagehide prompt "ma sei proprio sicurosicurosicuro???"
    // Se l'utente chiude la tab, chiudo la connessione con lo script server
    window.onpagehide = () => { gameEventSource.close() };

    setGameEventHandlers(gameEventSource);

} 

document.addEventListener("DOMContentLoaded", initializeGame);