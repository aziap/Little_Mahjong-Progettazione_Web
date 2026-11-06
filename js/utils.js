"use strict";


const BAMBOO = "b";
const PIN = "p";
const NUMERIC_SUIT = [BAMBOO, PIN];
const DRAGON = "d";
const WIND = "w";
const GREEN = "G";
const RED = "R";
const WHITE = "W";
const DRAGON_VALUE = [GREEN, RED, WHITE];
const EAST = "E";
const SOUTH = "S";
const WIND_VALUE = [EAST, SOUTH];

const SUIT_ARR = [BAMBOO, PIN, WIND, DRAGON];

const PROJECT_DIR = "/perroni_596654/";

/**
 * Restituisce il path del file index.php nel formato "http://path/to/index/directory/"
 */
function getIndexDirectory() {
    return window.location.protocol + "//" + window.location.hostname + PROJECT_DIR;
}

/**
 * Appende un elemento "p" con testo 'msg' al nodo 'parentNode'
 * 'parentNode' deve essere una stringa o il nodo del DOM dove voglio che venga mostrato il messaggio.
 * Se è una stringa, viene interpretato come l'id del nodo dove scrivere il messaggio
 */
function showMessage(msg, parentNode, replace = false) {
    const p = document.createElement("p");
    p.innerText = msg;
    if ( typeof parentNode == "string" ) {
        parentNode = document.getElementById(parentNode); 
    } 
    if ( replace ) {
        removeAllChildren(parentNode);
    }
    return parentNode.appendChild(p);    
}

function addRowToTable(table, elemArr, isHeader = false) {
    const row = table.appendChild( 
        document.createElement( isHeader ? "thead" : "tr" ) 
    );
    for ( let e of elemArr ) {
        row.appendChild( document.createElement("td") ).textContent = e;
    }
    return row;
}

/**
 * Fa una richiesta con metodo get allo script php individuato da urlFromIndex
 * con chiave key e valore value
 * responseType indica se la risorsa è richiesta sottoforma di testo ("text") o di json ("json")
 * La richiesta è del tipo:
 * fetch("/indexDir/urlFromIndex.php?key=value")
 */
async function asyncRequest(urlFromIndex, key, value, responseType = "text", additionalKey = "" , additionalValue = "") {
    let url = getIndexDirectory() + urlFromIndex + "?" + key + "=" + value;
    if ( additionalKey !== "") {
        url += "&" + additionalKey + "=" + additionalValue;
    }

    // DEBUG
    console.log(url);

    try {
        const response = await fetch(url);
        if ( !response.ok ) {
            throw new Error("Errore in " + url + " stato: " + response.status);
        }
        let resource;
        if ( responseType == "json" ) {
            resource = await response.json();
        } else {
            resource = await response.text();
        }
        return resource;
    }
    catch (error) {
        // DEBUG
        // TODO: gestire l'errore (o messaggio al client o ignore)
        console.log(
            responseType == "text" ?
            error : JSON.stringify(error)
        );

    }
}

/**
 * Data una tessera, ritorna un numero.
 * Serve per l'ordinamento delle tessere nella mano
 */
function tileToOrdinal(tile) {
    const suit = tile[0];
    const value = tile[1];
    let ordinal = 0;
    ordinal += ( SUIT_ARR.indexOf(suit) + 1 ) * 10;
    
    switch (suit) {
        case DRAGON :
            ordinal += DRAGON_VALUE.indexOf(value);
            break;
        case WIND :
            ordinal += WIND_VALUE.indexOf(value);
            break;
        default :
            ordinal += parseInt(value);
            break;
    }

    return ordinal;
}

function sortHand(hand) {
    hand.sort(
        (a, b) => {
            return tileToOrdinal(a) - tileToOrdinal(b);
        }
    );
}

/**
 * Sort delle tessere come elementi del DOM.
 * tileParentNode: nodo del DOM di cui ordinare gli elementi figlio
 */
function sortTiles(tileParentNode) {
    if ( typeof tileParentNode == "string" ) {
        tileParentNode = document.getElementById(tileParentNode);
    }

    // codice preso dalla risposta di ahuigo
    // in https://stackoverflow.com/questions/282670/easiest-way-to-sort-dom-nodes 
    [...tileParentNode.children]
        .sort((a, b) => {
            return tileToOrdinal(a.id) - tileToOrdinal(b.id);
        })
        .forEach(node => tileParentNode.appendChild(node));
    // fine codice preso da terzi
}


function actionToString(action, LowercaseFirstLetter = false) {
    switch (parseInt(action)) {
        case Action.PON :
            return LowercaseFirstLetter? "pon" : "Pon";
        case Action.CHII :
            return LowercaseFirstLetter? "chii" : "Chii";
        case Action.RON :
            return LowercaseFirstLetter? "ron" : "Ron";
        case Action.TSUMO :
            return LowercaseFirstLetter? "tsumo" : "Tsumo";
        case Action.PASS :
            return LowercaseFirstLetter? "pass" : "Pass";
        default :
            // DEBUG
            console.log( "No result string for the action: " + action );

            return "";
    }
}

/**
 * Esegue una funzione ad intervalli regolari, finché questa non termina con successo
 * @param task: funzione che restituisce true se termina con successo, false altrimenti.
 * @param retryInterval: intervallo di tempo dopo cui rieseguire la funzione in caso 
 * di fallimento
 * @param timerId: id del timer da impostare
 * @returns void
 */
function retryTillSuccess(task, retryInterval, timerId = null) {
    return timerId = setInterval(
        () => {
            if( task() )
                clearInterval(timerId); 
        }, retryInterval)
}


function removeAllChildren(element) {
    if ( typeof element == "string" ) {
        element = document.getElementById(element);
    }
    while ( element.firstChild ) {
        element.removeChild(element.firstChild);
    }
}