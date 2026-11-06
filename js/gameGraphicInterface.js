"use strict";

const NUM_PLAYER = 2;
const DISCARDS_PER_ROW = 8;
// Se arrivo alla 4^ riga, ignoro il limite di DISCARDS_PER_ROW (8) tessere
const MAX_DISCARD_ROW_NUM = 4;
// Numero massimo di tessere nascoste nella mano del giocatore, non contando la tessera appena pescata 
const MAX_NUM_TILES_HAND = 13;
// Rapporto tra l'ampiezza e l'altezza dell'immagine che raffigura la tessera
const TILE_WIDTH_TO_HEIGHT_RATIO = 1.336;

const TILE_MARGIN_TO_WIDTH_RATIO = 0.01;
const DISCARD_SCALE = 0.9;



class GameGraphicInterface {

    tileId = 0;
    resultWindow = null;
    
    constructor() {
        // Tutte le misure sono scalate in base alla lunghezza del lato del "tavolo" (ovvero del div contenitore)
        this.tableSideLen = 700;
        this.tiledWidth = 0.065 * this.tableSideLen;
        this.tileHeight = TILE_WIDTH_TO_HEIGHT_RATIO * this.tiledWidth;
        this.tileRadius = this.tiledWidth * 0.02;
        // Offset dell'angolo in alto a sinistra area degli scarti 
        // del giocatore rispetto al lato sinistro del tavolo
        this.discardAreaOffsetX = 0.5 * (this.tableSideLen - DISCARDS_PER_ROW * this.tiledWidth);
        // Offset dell'angolo in alto a sinistra dell'area degli scarti 
        // del giocatore rispetto al lato superiore del tavolo
        this.discardAreaOffsetY = 0.4 * this.tableSideLen;

        // Offset dell'angolo in alto a sinistra dell'area della mano 
        // rispetto al lato sinistra del tavolo
        this.handOffsetX = 0.5 * ( this.tableSideLen - (MAX_NUM_TILES_HAND + 1.5) * this.tiledWidth );
        this.handOffsetY = -0.01 * this.tableSideLen;
    }

    initializeScene() {
        // COMMENT: in generale qui potrei assemblare i pezzi, invece di chiamare getelementbyid in ogni singola funzione, tanto che ritorno i nodi
        const table = this.createTable();
        for (let i = 0; i < NUM_PLAYER; i++) {
            this.createHandSection(i);
            this.createDiscardArea(i);
        }
        table.append(this.createMessageSection());
        const callSection = table.appendChild(this.createCallSection());
        const selecttionButtons = this.createSelectionButtons();
        for ( let b of selecttionButtons ) {
            callSection.append(b);
        }
    }
    
    createTable() {
        const gameWrapper = document.getElementById("game-wrapper");
        const table = document.createElement("div")
        gameWrapper.append(table);
        table.id = "table";
        table.style.width = this.tableSideLen + "px";
        table.style.height = this.tableSideLen + "px";


        for (let i = 0; i < NUM_PLAYER; i++) {
            const playerSection = document.createElement("div");
            table.append(playerSection);
            playerSection.setAttribute( "id", "player-section-" + i);
            playerSection.setAttribute( "class", "player-section");
        }
        return table;
    }

    createHandSection(playerType) {
        const handSection =  document.createElement("div");
        document.getElementById("player-section-" + playerType).append(handSection);
        handSection.setAttribute("class", "hand-section");
        handSection.setAttribute("id", "hand-section-" + playerType);
        handSection.style.translate = this.handOffsetX + "px " + this.handOffsetY + "px";
        
        // zona delle tessere nascoste
        const concealedZone = document.createElement("div");
        handSection.append(concealedZone)
        concealedZone.setAttribute("class", "concealed-zone");
        concealedZone.setAttribute("id", "concealed-zone-" + playerType);

        // zona della tessera appena pescata
        const drawZone = document.createElement("div");
        handSection.append(drawZone)
        drawZone.setAttribute("class", "draw-zone");
        drawZone.setAttribute("id", "draw-zone-" + playerType);
        drawZone.style.width = this.tiledWidth;
        drawZone.style.marginLeft = "5px"; 

        return handSection;
    }

    createDiscardArea(playerType) {
        const discardArea = document.createElement("div");
        document.getElementById("player-section-" + playerType).append(discardArea);
        discardArea.setAttribute("class", "discard-area");
        discardArea.setAttribute("id", "discard-area-" + playerType);
        // discardArea.style.transform = " translate(" + this.discardA reaOffsetX + "px, " + this.discardAreaOffsetY + "px)";
        // discardArea.style.transform = "scale(" + DISCARD_SCALE +") "
            // + "translate(" + this.discardAreaOffsetX + "px, " + this.discardAreaOffsetY + "px) ";
            // + "translateX(" + this.discardAreaOffsetX + "px) ";
        discardArea.append(this.addDiscardRow(playerType));
        return discardArea;
    }

    displayRoundInfo(player, opponent) {
        const infoWrapper = document.getElementById("round-info-wrapper");
        const infoPlayerArr = [];
        for (let p of [player, opponent]) {
            console.log(p);
            infoPlayerArr[p.type] = document.createElement("div");
            infoPlayerArr[p.type].classList.add("info-player");
            infoPlayerArr[p.type].id = "info-player-" + p.type;
            infoPlayerArr[p.type].appendChild( 
                document.createElement("p")
            ).classList.add("name");
            infoPlayerArr[p.type].appendChild( 
                document.createElement("p")
            ).classList.add("wind");
            infoPlayerArr[p.type].appendChild( 
                document.createElement("p")
            ).classList.add("points");
            infoPlayerArr[p.type].children[0].textContent = p.name;
            infoPlayerArr[p.type].children[1].textContent = p.wind;
            infoPlayerArr[p.type].children[2].textContent = "Punti: " + p.points;
        }
        
        const wallInfo = document.createElement("div");
        wallInfo.id = "info-wall";
        wallInfo.appendChild( document.createElement("p") ).textContent = "Tessere rimaste:";
        wallInfo.appendChild( document.createElement("p") ).id = "count";
        wallInfo.lastChild.textContent = TOT_TILES - HAND_SIZE * NUM_PLAYER;
        infoWrapper.append(infoPlayerArr[1]);
        infoWrapper.append(wallInfo);
        infoWrapper.append(infoPlayerArr[0]);
        return infoWrapper;
    }

    decrementTileCount() {
        const countNode = document.getElementById("count");
        let count = parseInt(countNode.textContent);
        countNode.textContent = (count - 1);
    }

    addDiscardRow(playerType) {
        const discardRow = document.createElement("div");
        // discardRow.setAttribute("class", "discard-row");
        return document.getElementById("discard-area-" + playerType).appendChild(discardRow);
    }
    
    
    moveTileToDiscard(playerType, tileNode) {
        const discardZone = document.getElementById("discard-area-" + playerType);
        let row = discardZone.lastChild;
        if (row.childElementCount == DISCARDS_PER_ROW 
            && discardZone.childElementCount < MAX_DISCARD_ROW_NUM
        ) {
            row = this.addDiscardRow(playerType)
        }

        // Rimuovo la tessera dalla sua posizione attuale e la metto negli scarti del giocatore target
        const drawZone = document.getElementById("draw-zone-" + playerType);
        this.moveTile(tileNode, row);
        if ( !drawZone.hasChildNodes() )
            return;
        // Se la tessera scartata non era quella appena pescata, 
        // metto la tessera appena pescata nella mano, tra le altre
        const concealedZone = document.getElementById("concealed-zone-" + playerType);
        this.moveTile(drawZone.firstChild, concealedZone);
        sortTiles(concealedZone);
    }

    getLastDiscardNode(playerType) {
        return document.getElementById("discard-area-" + playerType).lastChild.lastChild;
    }
    
    addOpenTriplet(playerType, tileArray) {
        const handSection = document.getElementById("hand-section-" + playerType);
        const triplet = document.createElement("div");
        handSection.append(triplet);
        triplet.setAttribute("class", "open-triplet");

        // Aggiungo le tessere
        // tileArray.push(this.getLastDiscardNode( (playerType + 1) % NUM_PLAYER ));
        for ( let t of tileArray ) {
            t.classList.remove("selected");
            this.moveTile(t, triplet);
        }
        sortTiles(triplet);
    }

    /**
     * Mostra nel browser la mano del giocatore player
     * playerType: tipo del giocatore (giocatore "client" o avversario)
     * hand: array di string che rappresentano le tessere
     */
    displayHand(playerType, hand = null) {
        if( hand !== null ) {
            sortHand(hand);
        }
        const concealedZone = document.getElementById("concealed-zone-" + playerType );
        for( let j = 0; j < 13; j++) {
            const tile = this.createTile( playerType == OPPONENT ? "" :  hand[j] );
            concealedZone.append(tile);
        }
    }

    displayDrawnTile(player, draw = "") {
        const tile = this.createTile(draw);
        // tile.classList.add("draw");
        document.getElementById("draw-zone-" + player).append(tile);
    }
    

    /**
     * Rivelo una tessera precedentemente nascosta
     */
    revealTile(tileNode , tileString) {
        // const suitValue = tileId.slice(0, SUIT_VALUE_CHARS);
        
        // DEBUG
        // console.log("in revealTile.\ntileNode prima:")
        // console.log(tileNode);
        // console.log("valore della tessera: " + tileString);
        // END DEBUG


        tileNode.classList.remove("concealed"); 
        this.drawTileFace(tileNode, tileString);

        // DEBUG
        // console.log("in revealTile.\ntileNode dopo:")
        // console.log(tileNode);
        // END DEBUG

        // tile.style.backgroundImage = "url('./../css/img/tile/" + tileString + ".svg'), "
        // + " url('./../css/img/tile/front.svg')";
        // tileElem.id = tileString + "-" + this.tileId++;

        // tileElem.textContent = suitValue;
        // tileElem.id = tileId;
    }

    /**
     * Crea l'elemento grafico che rappresenta una tessera e lo restituisce.
     * Se tileString è una stringa vuota, la tessera è dell'avversario
     * e il giocatore "client" ne vede solo il dorso.
     * Altrimenti tileString è l'identificatore univoco della tessera
     */
    createTile(tileString = "") {
        const tile = document.createElement("div");       
        tile.setAttribute("class", "tile");
        tile.style.width = this.tiledWidth + "px"; 
        tile.style.height = this.tileHeight + "px";
        tile.style.borderRadius = this.tiledWidth * 0.02 + "px";
        tile.style.margin = TILE_MARGIN_TO_WIDTH_RATIO * this.tiledWidth + "px" ;

        if (tileString) {
            // tile.addEventListener("click", discardOnClick); // discardOnclick è definita in ./game.js
            this.drawTileFace(tile, tileString);
        } 
        else {
            tile.classList.add("concealed");
        }
        return tile;
    }

    /**
     * Disegna la faccia della tessera
     */
    drawTileFace(tileNode, tileString) {
        tileNode.style.backgroundImage = "url('./../css/img/tile/" + tileString + ".svg'), "
            + " url('./../css/img/tile/front.svg')";
        tileNode.id = tileString + "-" + this.tileId++;
    }

    /**
     * tileNode: nodo del DOM rappresentante la tessera
     * destElem: nodo dove spostare la tessera oppure stringa
     * contenente l'id di questo
     */
    moveTile(tileNode, destElem) {
        if( typeof destElem == "string") {
            destElem = document.getElementById(destElem);
        }
        tileNode.parentNode.removeChild(tileNode);
        return destElem.appendChild(tileNode);
    }

    /**
     * Crea la sezione dove appariranno i bottoni per fare le chiamate
     */
    createCallSection() {
        const callSection = document.createElement("div");
        callSection.setAttribute("id", "call-section");
        return callSection;
    }

    /**
     * Creo i bottoni per fare le chiamate, inizialmente non visibili.
     * Prima di chiamare questa funzione, devo ricevere dal server
     * l'oggetto che, tra le altre, contenga la codifica per le azioni
     * Pon, Chii, Ron, Tsumo, Pass
     */
    createCallButtons(actionObj) {
        // DEBUG
        // console.log("Oggetto Action:");
        // console.log(actionObj);
        // DEBUG

        const callSection = document.getElementById("call-section");
        const callArr = [actionObj.PON, actionObj.CHII, actionObj.RON, actionObj.TSUMO, actionObj.PASS];
        for(let call of callArr) {
            const callBtn = document.createElement("button");
            callSection.append(callBtn);
            callBtn.value = call;
            callBtn.id = actionToString(call, true) + "-button";
            callBtn.setAttribute("class", "call-button");
            callBtn.innerText = actionToString(call);
            callBtn.style.display = "none";
        }
    }

    /**
     * Crea i bottoni ("Conferma" e "Cancella") da mostrare quando, dopo aver
     * chiamato una tessera scartata, viene chiesto al giocatore di 
     * selezionare 2 tessere da aggiungere per formare una tripletta
     */
    createSelectionButtons() {
        const confirmButton = document.createElement("button");
        confirmButton.textContent = "Conferma";
        confirmButton.id = "confirm-button";
        confirmButton.setAttribute("class", "selection-button");
        confirmButton.style.display = "none";


        const cancelButton = document.createElement("button");
        cancelButton.textContent = "Cancella";
        cancelButton.id = "cancel-button";
        cancelButton.setAttribute("class", "selection-button");
        cancelButton.style.display = "none";
        return [confirmButton, cancelButton];
    }

    changeVisibility(elem, visibility) {
        if( typeof elem == "string" ) {
            elem = document.getElementById(elem);
        }
        const prevVisibility = elem.style.display;
        elem.style.display = prevVisibility !=  visibility ? visibility : prevVisibility;
    }


    displayCallButtons(actionArray) {
        for ( let action of actionArray ) {
            this.changeVisibility(this.getButton(action), "block");
        }
    }

    hideCallButtons() {
        for ( let b of document.getElementsByClassName("call-button") ) {
            this.changeVisibility(b, "none");
        }
    }

    displaySelectionButtons() {
        for ( let b of document.getElementsByClassName("selection-button") ) {
            this.changeVisibility(b, "block");
        }
    }

    hideSelectionButtons() {
        for ( let b of document.getElementsByClassName("selection-button") ) {
            this.changeVisibility(b, "none");
        }
    }

    getButton(action) {
        return document.getElementById( actionToString(action, true) + "-button" );
    }

    createMessageSection() {
        const messageSection = document.createElement("div");
        messageSection.id = "message-section";

        
        // DEBUG
        // messageSection.style.display = "none"
        messageSection.innerText = "gneppo";
        // END DEBUG

        return messageSection;
    }

    clearMessageSection() {
        removeAllChildren("message-section");
    }

    // createResultWindow(confirmEventListener) {
    //     const resultWindow = document.body.appendChild( document.createElement("div") );
    //     resultWindow.id = "result-window";
    //     // dove scrivere i risultati
    //     const resultSection = resultWindow.appendChild( document.createElement("div") );
    //     resultSection.id = "result-section";
    //     const resultConfirm = resultWindow.appendChild( document.createElement("button") );
    //     resultConfirm.id = "result-confirm";
    //     resultConfirm.textContent = "Conferma";
    //     resultConfirm.addEventListener("click", confirmEventListener);
    //     return resultWindow;
    // }

    createResultWindow(confirmEventListener) {
        this.resultWindow = new InfoWindow("result-window");
        const confirmButton = this.resultWindow.addButton("result-confirm", "Conferma", confirmEventListener);
        confirmButton.classList.add("confirm-btn");
        return this.resultWindow;
    }

    // Mostra i risultati in caso di Ron o Tsumo
    /**
     * @param {PLayer} winner: oggetto rappresentante il giocatore che ha vinto
     * @param {PLayer} loser: oggetto rappresentante il giocatore che ha perso
     * @param {Action} winCondition: Ron o Tsumo
     * @param {String[]} hand: mano del giocatore vincente
     * @param {String} winningTile: tessera che completa la mano
     * @param {int} totalScore: valore totale della mano in punti
     * @param {Array} handType: array associativo ["tipoMano" => puntiTipo] 
     */
    writeVictoryResult(winner, loser, winCondition, hand, winningTile, handType, totalScore) {
        if ( this.resultWindow == null ) {
            console.error("Finestra dei risultati non trovata");
            return false;
        }
        const resultContent = this.resultWindow.getContentArea();
        //  Header
        const header = resultContent.appendChild(document.createElement("header"));
        header.textContent = ( winner.type == CLIENT_PLAYER ? 
            "Hai dichiarato " : winner.name + " ha dichiarato " ) 
            + ( typeof winCondition == "string" ? winCondition : actionToString(winCondition)); // DEBUG
            // TODO: togliere typeof, serve solo per la finestra di test
            
        // Contenitore dove mostrare le tessere della mano
        const handWrapper = resultContent.appendChild(document.createElement("div"));
        handWrapper.id = "winning-hand-wrapper";
        // Disegno la mano
        for (let tile of hand) {
            handWrapper.append( this.createTile(tile) );
        }
        const winningTileContainer = handWrapper.appendChild( document.createElement("div") );
        winningTileContainer.id = "winning-tile-container";
        winningTileContainer.append( this.createTile(winningTile) );

        // DEBUG
        // resultContent.appendChild( document.createElement("h1") ).textContent = actionToString(winCondition);

        // const winTxt = resultContent.appendChild( document.createElement("h1") );
        // winTxt.textContent = winCondition;
        // winTxt.id = "win-condition";

        // resultContent.append( document.createElement("br") );

        // creo la tabella con i punti per ogni tipo di mano vincente presente
        const resultTable = resultContent.appendChild( document.createElement("table") ); 
        resultTable.id = "result-table";
        for ( let e of Object.entries(handType) ) {
            addRowToTable(resultTable, e);
        }
        addRowToTable(resultTable, ["Totale:", totalScore])
        // const total = resultContent.appendChild( document.createElement("p") );
        // total.id = "total-hand-score";
        // total.textContent = "Totale: " + totalScore;

        resultContent.append( document.createElement("hr") );

        // Punti totali di ciascun giocatore
        const playerPointWrapper = resultContent.appendChild( document.createElement("div") );
        playerPointWrapper.id = "player-point-wrapper"
        playerPointWrapper.append( document.createElement("p") );
        playerPointWrapper.append( document.createElement("p") );
        playerPointWrapper.children[winner.type].textContent = "Punti di " + winner.name + ": " + winner.points;
        playerPointWrapper.children[loser.type].textContent = "Punti di " + loser.name + ": " + loser.points;

        return true;
    }

    showResult() {
        if ( this.resultWindow == null ) {
            console.error("Finestra dei risultati non trovata");
            return false;
        }
        document.body.style.pointerEvents = "none";
        this.resultWindow.showInfoWindow();
    }
}

