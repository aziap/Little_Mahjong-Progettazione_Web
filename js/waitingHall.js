"use strict";

// Codici di stato del matchmaking
const ERROR = -1;
const TABLE_CREATED = 0;
const WAITING_FOR_PLAYERS = 1;
const TABLE_JOINED = 2;
const MATCH_STARTING = 3;

const WAIT_TIMEOUT = 1000; 

let tableSearchUpdater = null;

function displaySearchState(code) {
    switch ( parseInt(code) ) {
        case ERROR :
            showMessage("Si è verificato un errore nella ricerca di un tavolo", "info-ricerca-tavolo", true);
            return;
        case TABLE_CREATED :
            showMessage("Nessun tavolo pronto trovato, creato un nuovo tavolo", "info-ricerca-tavolo", true);
            return;
        case WAITING_FOR_PLAYERS : 
            showMessage("In attesa di altri giocatori...", "info-ricerca-tavolo", true);
            return;
        case TABLE_JOINED : 
            showMessage("Trovato un tavolo, in attesa di altri giocatori...", "info-ricerca-tavolo", true);
            return;
        case MATCH_STARTING :
            tableSearchUpdater.close();
            showMessage("La partita sta per cominciare!", "info-ricerca-tavolo", true);

            setTimeout(() => {
                window.location.href = "./match.php";
            }, WAIT_TIMEOUT);
            return;
        default :
            showMessage("È stato ricevuto un evento inaspettato dal server", "info-ricerca-tavolo");
    }
}


function initializeWaitingHall() {
    tableSearchUpdater = new EventSource("./game/tableSearch.php");
    tableSearchUpdater.addEventListener("message", 
        (evt) => {
            displaySearchState(evt.data);
        }
    );
    
    tableSearchUpdater.addEventListener("error", 
        (err) => {
            if( err.currentTarget.readyState == EventSource.CONNECTING
            )
            console.log("Connessione chiusa dal server. Riprovo tra 1 secondo.");
            else 
            console.error("Si è verificato un errore nel server. Stato della connessione: ", err.currentTarget.readyState);
        }
    );

    document.getElementById("cancel-button").addEventListener("click",
        () => {
            let goBack = confirm("Sei sicuro di voler tornare indietro?") 
            if ( goBack ) {
                // TODO: gestire roba lato server
                tableSearchUpdater.close();
                window.history.back(); 
            }
        }
    )
}
