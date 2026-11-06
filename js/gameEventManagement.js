"use strict";

const SHOW_CALL_TIMER = 2000;

function setGameEventHandlers(gameEventSource) {
    gameEventSource.addEventListener("event-0", setEnvironmentVar);
    
    // DEBUG
    gameEventSource.addEventListener("message", 
        (event) => {
            console.log("messaggio: " + event.data);
        } 
    )
    gameEventSource.addEventListener("match-start", initializePlayers);
    gameEventSource.addEventListener("hand-draw", getPlayerHand);
    gameEventSource.addEventListener("tile-draw", getPlayerTile);
    gameEventSource.addEventListener("discard", getDiscard);
    gameEventSource.addEventListener("call", getOpenTriplet);
    gameEventSource.addEventListener("match-end", getEndingAction);
}

function setEnvironmentVar(event) {
    Action = JSON.parse(event.data);
    console.log(event.data);
    // DEBUG
    // console.log(Action);
    // Creo la i bottoni per le chiamate
    gameGraphicInterface.createCallButtons(Action);
    for (let c of [Action.PON, Action.CHII, Action.RON, Action.PASS]) {
        gameGraphicInterface.getButton(c)
            .addEventListener("click", callHandler);
    }
    // Handler per lo Tsumo
    gameGraphicInterface.getButton(Action.TSUMO)
    .addEventListener("click", declareTsumoHandler);

    // Handler per il pass sullo Tsumo
    gameGraphicInterface.getButton(Action.PASS)
    .addEventListener("click", passOnTsumoHandler);
}

function initializePlayers(event) {
    // DEBUG
    console.log(event.data);

    let playerArr = JSON.parse(event.data);

    
    player = new Player( CLIENT_PLAYER, ...Object.values( playerArr[0]) ); 
    
    // DEBUG 
    console.log(playerArr);
    console.log(player);
    // END DEBUG

    opponent = new Player( OPPONENT, ...Object.values( playerArr[1]) );
    gameGraphicInterface.displayRoundInfo(player, opponent);
}

async function getPlayerHand(event) {
    // DEBUG
    console.log(event.data);
    
    if ( event.data == "" ) {
        // L'avversario ha pescato la propria mano 
        if ( player.wind == EAST ) {
            // posso pescare una tessera
            const result = await player.drawTile();
            if (!result) {
                alert("Si è verificato un problema con il server");
                return;
            }
            currentPhase = Phase.DISCARD;
        }
        else 
            currentPhase = Phase.OPPONENT_TURN;
        return;
    }

    player.drawStartingHand( event.data.split(',') );
    // Disegno anche la mano dell'avversario, nascosta
    opponent.drawStartingHand();
    // DEBUG
    console.log(player.hand);
}

function getPlayerTile(event) {
    let tile = event.data;
    // Controllo se è la pescata dell'avversario
    if ( tile == "" ) {
        opponent.drawTile();
        console.log("l'avversario ha pescato");
        return;
    }
    // Controllo se posso chiamare Tsumo
    if (tile.charAt(0) == TSUMO_INDICATOR) {
        // TODO:
        // mostra bottone Tsumo

        tile = tile.substring(1);
    }
    player.drawTile(tile);
    currentPhase = Phase.DISCARD;
    // DEBUG
    console.log("tessera pescata: " + player.drawnTile);
}

async function getDiscard(event) {
    let arr = event.data.split(',');

    // DEBUG
    console.log("in getDiscard, data:");
    console.log(event.data);
    console.log("in getDiscard, arr:");
    console.log(arr);
    //  END DEBUG

    const discardedTileType = arr.shift();

    // Prendo una tessera dalla mano dell'avversario e la rivelo 
    // (per semplicità supponiamo che non sia rilevante quale, quindi prendo la prima tessera nascosta)
    const tileNode = document.getElementById("concealed-zone-" + OPPONENT).firstChild;
    opponent.discardTile(tileNode, discardedTileType);

    console.log("l'avversario ha scartato: " + discardedTileType);
    
    // Controllo se il giocatore può chiamare
    if ( arr[0] == Action.PASS ) {
    // Versione con async await:
        let result;
        try {
            result = await player.pass();
            // debug
            console.log(result);
            if (!result) {
                // TODO: mostrare messaggio errore (tipo errore inaspettato)
                return;
            }
            result = await player.drawTile();
        }
        catch (error) {
            handleGameError(error);
        }
        return;
    }
    currentPhase = Phase.CALL;
    // Mostro i bottoni per le chiamate disponibili 
    gameGraphicInterface.displayCallButtons(arr);
}

function getOpenTriplet(event) {
    // DEBUG
    console.log(event.data);

    const tileType = event.data.split(",");
    // Leggo la chiamata
    lastCall = tileType.shift();

    // DEBUG
    console.log("array di tipi:");
    console.log(tileType);
    // END DEBUG
    
    showMessage("L'avversario ha chiamato un " + actionToString(lastCall),
        "message-section", true);
    setTimeout(gameGraphicInterface.clearMessageSection, SHOW_CALL_TIMER);
    
    // Prendo l'ultimo scarto del giocatore e 2 tessere nascoste dell'avversario 
    // (prendo le prime 2, ma è indifferente)
    const tileNode = [gameGraphicInterface.getLastDiscardNode(CLIENT_PLAYER)];
    for (let i = 0; i < 2; i++) {
        tileNode.push(document.getElementById("concealed-zone-" + OPPONENT).childNodes[i]);
    }
    
    // Rivelo le tessere (per non dover trattare differentemente
    // lo scarto, lo sovrascrivo)
    for( let i = 0; i < 3; i++ ) {
       gameGraphicInterface.revealTile(tileNode[i], tileType[i]);
    }

    opponent.callTile(lastCall, tileNode);
}

function getEndingAction(event) {
    // DEBUG
    console.log(event.data);

    lastCall = event.data;
    gameEventSource.close();
    showMessage("L'avversario ha dichiarato " + actionToString(lastCall),
        "message-section", true);    
        currentPhase = Phase.ROUND_END;
    
    player.readResult();
}