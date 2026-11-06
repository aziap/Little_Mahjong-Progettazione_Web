"use strict";

// tipi di giocatore
const CLIENT_PLAYER = 0;
const OPPONENT = 1;

/**
 * Classe che collega le azioni eseguite dall'utente e gli eventi
 * ricevuti dal server all'aggiornamento della schermata
 * di gioco
 */
class Player {
    constructor(type, id, name, wind, points) {
        // type indica se si tratta del "giocatore client" (0) o dell'avversario (1)
        this.type = type;
        this.id = id;
        this.name = name;
        this.wind = wind;
        this.points = points;
        this.hand = null;
        this.openTriplets = null;
        this.drawnTile = null;
    }

    // Aggiunge le tessere alla mano del giocatore
    // hand è null se il giocatore è l'avversario 
    drawStartingHand(hand = null) {
        this.hand = hand;
        gameGraphicInterface.displayHand(this.type, hand);
    }


    async drawTile() {
        let draw = "";

        if (this.type == CLIENT_PLAYER) {

            // DEBUG
            console.log("Ho provato a pescare una tessera");
            draw = await asyncRequest("php/game/executePlayerActions.php",
                "playerAction", Action.DRAW_TILE);
            if ( draw == -1 ) {
                console.log("Sembra che avessi già pescato una tessera");
                return false;            
            }
            // DEBUG
            console.log("risposta ricevuta: " + draw);
            
            if (draw.charAt(0) == TSUMO_INDICATOR) {
            // Mostro il bottone per chiamare Tsumo
            gameGraphicInterface.displayCallButtons([Action.TSUMO, Action.PASS]);
            draw = draw.substring(1);
            }
            currentPhase = Phase.DISCARD;
            // DEBUG
            console.log("tessera pescata: " + draw);

            // Non c'erano più tessere da pescare, il round è finito
            if ( draw == 0 ) {
                // TODO: mostrare un messaggio
                console.log("Fine per pareggio");
                return true;
            }

        }
        // this.drawnTile = draw;
        gameGraphicInterface.displayDrawnTile(this.type, draw);
        return true;
    }

    async discardTile(tileNode, tileType = "") {
        if (this.type == CLIENT_PLAYER) {
            const tileName = tileNode.id.slice(0, 2);

            // DEBUG
            console.log("Tessera da scartare: " + tileName);


            const result = await asyncRequest("php/game/executePlayerActions.php",
                "playerAction",
                Action.DISCARD_TILE,
                "text", 
                "discardedTile",
                tileName
            );
            if ( result == -1 ) {
                // DEBUG
                console.log("In questo momento non puoi scartare");
                return false;
            }
            if ( result == 0 ) {
                // DEBUG
                console.log("Qualcosa è andato storto, riprova");
            return false;
            }
            if (result !== tileName) {
                // DOBUG
                console.log("Questa non è la tessera che stavi cercando:" + result);
                return false;
            }
            // L'operazione ha avuto successo

            console.log("scarto: " + result);

            currentPhase = Phase.OPPONENT_TURN;
        } 
        else {
            // Rivelo lo scarto 
            gameGraphicInterface.revealTile(tileNode, tileType);
        }
        gameGraphicInterface.moveTileToDiscard(this.type, tileNode);
        return true;
    }

    async pass() {
        const result = await asyncRequest("php/game/executePlayerActions.php",
            "playerAction",
            Action.PASS
        );
        return result === "1"; 
    }

    /**
     * Chiama la tessera appena scartata dall'avversario per formare
     * una tripletta aperta.
     * @param {Action.PON | Action.CHII} callType: tipo di chiamata.
     * @param {Object[]} selectedTileNodes: array di 3 elementti DOM rappresentanti le tessere
     * con cui formare la tripletta
     */
    async callTile(callType, selectedTileNodes) {

        if (this.type == CLIENT_PLAYER) {

            let addedTiles = [];
            selectedTileNodes.forEach(
                (elem) => {
                    addedTiles.push(elem.id.slice(0, 2));
                }
            );
            
            const result = await asyncRequest("php/game/executePlayerActions.php",
                "playerAction",
                callType, 
                "text",
                "addedTiles",
                addedTiles.toString()
            );

            // Il giocatore ha fatto qualcosa di imprevisto
            if (result == -1) {

                // DEBUG
                console.log("la chiamata non è valida");

                return false;
            }
            // Il giocatore non ha selezionato un Pon o un Chii valido
            if (result == 0) {
                // TODO:    - prompt riselezione
                
                // DEBUG
                console.log("le tessere selezionate non corrispondevano alla chiamata! Ritenta")

                return false;
            }
            // La chiamata è andata a buon fine
            currentPhase = Phase.DISCARD;
            selectedTileNodes.push(gameGraphicInterface.getLastDiscardNode(opponent.type));
        }
        gameGraphicInterface.addOpenTriplet(this.type, selectedTileNodes );
        
        // DEBUG
        console.log("GG, hai chiamato un " + actionToString(callType) + "!");

        return true;
    }

    async declareVictory(winCondition) {
        const result = await asyncRequest("php/game/executePlayerActions.php",
            "playerAction",
            winCondition
        );
        // DEBUG
        console.log(result);
        
        if( result == -1) {
            return false;
        }

        return result;
        
        // displayMatchResult(this.type, winCondition, matchResults)
        
    }

    async readResult(/*winCondition*/) {
        const result = await asyncRequest( "php/game/executePlayerActions.php",
            "playerAction", Action.READ_RESULT, "json" );

        // DEBUG
        console.log(result);

        if ( result == 0 ) {
            console.log("Risultato non trovato")
            return false;
        }

        if ( result == -1 ) {
            console.error("Qualcosa è andato storto");
            // TODO: gestisco l'errore
        }
        
        const handType = JSON.parse(result["HandType"]);

        let playerIsWinner = result["Winner"] == player.name;
        player.points += (playerIsWinner? +1 : -1) * result["Points"];
        opponent.points += (playerIsWinner? -1 : +1) * result["Points"];

        console.log(handType);

        gameGraphicInterface.createResultWindow( () => { window.location.href = "./home.php" });
        gameGraphicInterface.writeVictoryResult(
            playerIsWinner ? player : opponent,
            playerIsWinner ? opponent : player,
             lastCall, 
             result["WinningHand"].split(','),
             result["WinningTile"],
             handType,
             result["Points"]
        );

        gameGraphicInterface.showResult();

        // TODO
        // gameGraphicInterface.showResult();
        return true;

        // DEBUG
        // alert(opponent.name + " ha dichiarato " + actionToString(winCondition)
        //     + "!\nHai perso!")
        // showMessage("***CENSORED***", "message-section", true);
        // END DEBUG
    }
        
}


