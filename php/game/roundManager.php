<?php


/**
 * n.d.a.: Sebbene in questa versione del gioco ci siano solo 2 giocatori
 * e le partite durino un solo round, gran parte dei metodi di 
 * questa classe è scritta per tenere conto di un numero indefinito di 
 * giocatori e di round, definiti nel file ./gameConfig.php. 
 * Per questioni di tempo, non sono state implementate le funzionalità
 * per gestire round successivi al primo, ma, a mio avviso, la struttura presente 
 * ne permetterebbe facilmente la realizzazione. 
 */


require_once __DIR__."/../log/logConfig.php";
require_once __DIR__."/../db/gameDbManager.php";
require_once __DIR__."/wallManager.php";
require_once __DIR__."/player.php";
require_once __DIR__."/roundPhases.php";
require_once __DIR__."/actions.php";
require_once __DIR__."/gameUtils.php";
require_once __DIR__."/endCondition.php";


if(session_status() !== PHP_SESSION_ACTIVE) session_start();

/*
 * Poiché ci sono delle variabili comuni ai due giocatori e che devono essere 
 * scritte nel database, decido arbitrariamente quale dei processi server dei due giocatori
 * si occuperà di scrivere le variabili iniziali.
 */
class RoundManager {

    // Se si vuole testare un certo muro, copiarlo dalla tabella Matchstate (Wall) e incollarlo come stringa in testWall
    // Mettere testingMode a true
    public $testingMode = false;
    public $testWall = "";



    // Indice dell'array $players che determina se questo oggetto dovrà occuparsi di inizializzare il gioco
    private const INITIALIZING_PLAYER = 0;
    private $matchId; 
    private $roundCount = 0;
    // Indice dell'array $players corrispondente al giocatore che sta svolgendo il suo turno
    private $activePlayerId = -1;
    private $currentPhase = Phase::SETUP;

    // Array in cui salvo lo stato del gioco letto dal DB
    private $roundState = [];
    private $players = [];


    public function __construct($matchId, $playersArray) {
        $this->matchId = $matchId;
        $i = 0;
        foreach( $playersArray as $p ) {
            $this->players[] = new Player($i, $p);
            if ( $p == $_SESSION["user"] ){
                $_SESSION["player"] = $this->players[$i];
            }
            else {
                $_SESSION["opponent"] = $this->players[$i];
            }
            $i++;
        }   


    }

    // Restituisce true se questo oggetto deve assegnare i venti ai giocatori, creare il muro,
    // e inizializzare la tabella del DB che conterrà le informazioni generate.
    // Serve per coordinare i 2 processsi server.
    public function hasToInitialize() {
        return $_SESSION["user"] == $this->players[self::INITIALIZING_PLAYER]->getName();
    }

    /**
     * Assegna i venti ai giocatori, crea un nuovo muro, e salva i
     * valori generati nel DB
     * @return bool
     */
    public function startNewMatch() {
        if ( !$this->hasToInitialize() )
            return false;
        $gameDbManager = new GameDbManager();
        $gameDbManager->openConnection();
        // Assegno i venti
        // Il numero di venti usato nel gioco è uguale al numero di giocatori
        $i = 0;
        $winds = WIND_VALUE;
        shuffle($winds);
        foreach( $this->players as $p ) {
            if ( $winds[$i] == EAST )
                $this->activePlayerId = $i;
            $p->setWind( $winds[$i++] );
        }
        // Inizializzo il muro

        // ******************************* DEBUG *******************************
        if ( $this->testingMode ) {
            $_SESSION["wallManager"] = new WallManager($this->testWall);
        }
        else 
        // ******************************* END DEBUG ***************************
            $_SESSION["wallManager"] = new WallManager();
        

        $wall = implode( ',',  $_SESSION["wallManager"]->getWall() );
        $result = $gameDbManager->writeMatchState($this->matchId, ++$this->roundCount, $winds[0], $winds[1], $wall);
        $gameDbManager->closeConnection();
        return $result;
    }

    public function getMatchId() {
        return $this->matchId;
    }

     /**
      * Legge dal database le informazioni iniziali del round, ovvero l'assegnamento dei venti
      * e la composizione del muro
      * @return bool
      */
     public function getRoundStartingInfos() {
        // Leggo dal DB 
        $gameDbManager = new GameDbManager();
        $gameDbManager->openConnection();
        $info = $gameDbManager->readMatchState($this->matchId);
        $gameDbManager->closeConnection();
        // Se le informazioni iniziali del round ancora non sono state scritte
        // o se c'è stato un errore nella lettura, restituisco false
        if ($info == null || array_search(null, $info) !== false) {
            return false;
        }
        $this->players[0]->setWind($info["PlayerWind_0"]);
        $this->players[1]->setWind($info["PlayerWind_1"]);
        $this->activePlayerId = $this->getPlayerIdByWind(EAST);
        $wallManager = new WallManager($info["Wall"]);
        $_SESSION["wallManager"] = $wallManager;
        return true;
    }


    public function startRound() {
        if ( !$this->hasToInitialize() ) {
            return false;
        }
        $gameDbManager = new GameDbManager();
        $gameDbManager->openConnection();
        if ( !$gameDbManager->insertNewRoundState($this->matchId) )
            return false;
            $gameDbManager->closeConnection();
            $gameDbManager->openConnection();

        // Lo stato nel DB è già inizializzato con valori di default, lo leggo
        // e lo assegno alla variabile di istanza roundState
        $this->roundState = $gameDbManager->readRoundState($this->matchId);
        $gameDbManager->closeConnection();
        return $this->roundState !== null ;
    }

    public function getRoundState($readFromDb = true) {
        if (!$readFromDb) {
            return $this->roundState;
        }
        $gameDbManager = new GameDbManager();
        $gameDbManager->openConnection();
        $this->roundState = $gameDbManager->readRoundState($this->matchId);
        $gameDbManager->closeConnection();
        // Se c'è stato un errore, il valore restituito è null
        return $this->roundState;
    }

    public function setRoundState() {
        $gameDbManager = new GameDbManager();
        $gameDbManager->openConnection();
        $res = $gameDbManager->writeRoundState($this->roundState);
        $gameDbManager->closeConnection();
        return $res;
    }


    public function getRoundCount() {
        return $this->roundCount;
    }

    public function getActivePlayerWind() {
        return $this->players[$this->activePlayerId]->getWind();
    }

    // DEBUG
    public function getActivePlayer() {
        return $this->players[$this->activePlayerId];
    }

    public function getCurrentPhase() {
        return $this->currentPhase;
    }

    /**
     * Da usare solo dopo l'azione del "giocatore client". 
     * Scrivo l'ultima azione svolta dal giocatore attivo nella tabella RoundState 
     * e cambio giocatore attivo.
     * Se l'ultima azione che il giocatore ha fatto era scartare una tessera,
     * la scrivo nella colonna "LastDiscard".
     * @param Action $lastAction
     * @param string $tile: tessera scartata
     * @return bool: esito della scrittura nel DB: true se riuscita, false altrimenti.
     * In caso di fallimento, va resettato il campo roundState con getRoundState()
     * prima di eseguire di nuovo questa funzione.
     */
    public function goToNextPlayerTurn($lastAction, $tile = null) {
        $prevRoundState = $this->roundState;

        // Aggiorno il giocatore attivo
        $id = $this->updateActivePlayerId();
        
        // DEBUG
        var_error_log($this->getRoundState(), "Stato iniziale (prima del passaggio di turno) in ".__FILE__.": ".__LINE__." ".__FUNCTION__." " );
        error_log("vento del giocatore: " . $_SESSION["player"]->getWind());
        error_log("vento dell'avversario: " . $_SESSION["opponent"]->getWind());
        // END DEBUG

        $this->roundState["LastAction_". $_SESSION["player"]->getWind() ] = $lastAction; 
        $this->roundState["LastAction_" . $_SESSION["opponent"]->getWind() ] = Action::NO_NEW_ACTION;
        // Passo al turno dell'altro giocatore
        $this->roundState["ActivePlayer"] = $this->players[$id]->getWind();
        
        // DEBUG
        error_log("(".$_SESSION["user"].") Active player da scrivere nel DB: ".$this->roundState["ActivePlayer"].", ".__FILE__.": ".__LINE__." ".__FUNCTION__ );

        $this->roundState["LastDiscard"] = $tile;
        $this->roundState["Turn"]++;

        // Registro il nuovo stato nel DB
        if ( $state = $this->setRoundState() ) {
            // Avanzo alla fase successiva
            $this->advancePhase();
        }
        else {
            $this->roundState = $prevRoundState;
            // Resetto il giocatore attivo
            $this->updateActivePlayerId();  // (avendo solo 2 giocatori, posso usare questa funzione)

            // DEBUG
            error_log("cambio turno fallito");
        }

        // DEBUG
        error_log("(".$_SESSION["user"].") ".__FILE__.": ".__LINE__."\nInfo: la fase è appena cambiata in: " . phaseToString($this->currentPhase));
        var_error_log($this->getRoundState(), "Stato attuale letto dal DB: ".__FILE__.": ".__LINE__." ".__FUNCTION__." ");
        // END DEBUG

        return $state;
    }

    
    /**
     * Aggiorna le ultime azioni dei giocatori senza cambiare giocatore attivo.
     * Usata in caso di pescaggio o chiamata di una tessera e per segnalare 
     * all'altro giocatore che si è letta la sua ultima azione.
     * Fa avanzare la fase corrente del RoundManager.
     * @return mixed Restituisce l'array con lo stato del round se 
     * l'operazione ha successo, altrimenti null.
     */
    public function updateLastAction($playerLastAction = Action::NO_NEW_ACTION, 
        $opponentLastAction = Action::NO_NEW_ACTION
    ) {   
        $this->roundState["LastAction_". $_SESSION["player"]->getWind()] = $playerLastAction;
        // Settando l'ultima azione dell'avversario a NO_NEW_ACTION, si comunica che 
        // il giocatore ha recepito la sua ultima azione, e che eventualmente
        // può procedere al cambio di turno e sovrascrivere questo campo 
        $this->roundState["LastAction_". $_SESSION["opponent"]->getWind()] = Action::NO_NEW_ACTION;
        // $this->roundState["LastAction_". $_SESSION["opponent"]->getWind()] = $opponentLastAction;
        $this->roundState["LastOpenTriplet"] = 
            $playerLastAction == Action::CHII
            || $playerLastAction == Action::PON ?
            implode(',',$_SESSION["player"]->getLastOpenTriplet()) : null; 
        // NOTA:
        // Qui non serve aggiornare anche LastDiscard, poichè scartare è l'ultima azione fatta prima di passare al turno dell'altro giocatore     
        if ( !$this->setRoundState() )
         {
            error_log("Error: update dell'ultima azione fallito. ".__FILE__.": ".__LINE__);
            return null;
        }
        // avanzo la fase in base all'ultima azione del giocatore attivo
        $lastAction = $this->activePlayerId == $_SESSION["player"]->getPlayerId() ? $playerLastAction 
            : $opponentLastAction; 
        
        $this->advancePhase($lastAction);

        return $this->roundState;
    }

    public function updateActivePlayerId() {
        return $this->activePlayerId = ++$this->activePlayerId % NUM_PLAYERS;
    }
    
    /**
     * Passo alla fase succcessiva. Restituisce la nuova fase.
     * @param int $callType: tipo di chiamata. Determina se dopo la fase di chiamata devo pescare o scartare
     * @return int: nuova fase.
     */
    public function advancePhase($callType = Action::PASS) {

        // DEBUG
        error_log("(".$_SESSION['user'].") In " . __FUNCTION__." ".__FILE__." ".__LINE__.". fase prececdente: ".phaseToString($this->currentPhase).". Backtrace: ");
        var_error_log(debug_backtrace(0));
        // END DEBUG
        
        switch ( $this->currentPhase ) {
            case Phase::SETUP :
                // L'Est è sempre il primo giocatore ad agire
                $this->activePlayerId = $this->getPlayerIdByWind(
                    EAST
                );
                $this->currentPhase = Phase::HAND_DRAW;
                break;
            case Phase::HAND_DRAW :
                // Se l'Est ha pescato la sua mano iniziale, 
                // il Sud deve pescare la sua. La fase ancora non cambia
                if ( $this->getActivePlayerWind() == "S" )
                    break;
                $this->currentPhase = Phase::TILE_DRAW;
                break;
            case Phase::TILE_DRAW :
                $this->currentPhase = Phase::DISCARD;
                break;
            case Phase::DISCARD :
                $this->currentPhase = Phase::CALL;
                break;
            case Phase::CALL :
                // DEBUG
                error_log(__FUNCTION__." ".__FILE__." ".__LINE__."\n(".$_SESSION['user'].") Passo alla fase successiva, dopo l'azione: "
                . actionToString($callType)
                . " ($callType)" );
                $this->currentPhase = 
                    $callType == Action::PASS ?
                    Phase::TILE_DRAW
                    : Phase::DISCARD;
                error_log(__FUNCTION__." ".__FILE__." ".__LINE__."\nFase corrente dopo la chiamata: ".phaseToString($this->currentPhase));
                break;
        }
        
        // Se ero nella fase ROUND_END, ci rimango
        error_log("... fase attuale: ".phaseToString($this->currentPhase).". ".__FILE__.": ".__LINE__." ".__FUNCTION__);

        return $this->currentPhase;
    }

    /**
     * Azzera le variabili di sessione relative al gioco e, se il vincitore è il "giocatore client", aggiorno il database
     * @param Action $winCondition: azione che ha portato alla fine del round
     * @param bool $writeResult: se true, scrivo il risultato nel database
     * @param array[] $handScore: array che contiene le mani vincenti e i rispettivi punteggi
     * @return bool
     */
    public function endRound($winCondition = Action::RON, $writeResult = false, $handScore = null ) {
      
        $this->currentPhase = Phase::ROUND_END;
        // DEBUG
        debug_log("Game ended by " . actionToString($winCondition));

        // Se non devo aggiorare il DB, esco
        if ( !$writeResult ) {
            // DEBUG
            debug_log("Non devo aggiornare il DB, bye"); 
            // clearGameSession();
            return true;
        }
        
        $hand = null;
        $winningTile = null;

        if ( $winCondition == Action::SURRENDER ) {
            // se l'avversario si è arreso, l'altro giocatore prende tutti i suoi punti
            $points = STARTING_POINTS; 
        }
        else if ( $winCondition == Action::END_BY_DRAW ) {

            // pareggio
            $points = 0;
            $handScore = null;
        }
        else {
            // Il punteggio del giocatore è calcolato come punteggio totale della mano meno i punti con cui partiva
            $points = end($handScore);
            $hand = $_SESSION["player"]->getHand();
            $winningTile = $winCondition == Action::RON ? 
                $this->roundState["LastDiscard"] 
                : $_SESSION["player"]->getLastDrawnTile();  // Tsumo
            foreach ( $_SESSION["player"]->getOpenTriplets() as $ot ) {
                array_push($hand, ...$ot);
            }
        }

        
        $gameDbManager = new GameDbManager();
        $gameDbManager->openConnection();
        $result = $gameDbManager->writeRoundResult($this->matchId, 
            $_SESSION["player"]->getPlayerId(), 
            $hand != null ? implode(',', $hand) : null,    
            $winningTile,
            $handScore,
             $points
        );
        $gameDbManager->setMatchEndingTime($this->matchId);
        $gameDbManager->closeConnection();

        return $result;
    }

    public function getRoundResult() {
        $gameDbManager = new GameDbManager();
        $gameDbManager->openConnection();

        $result = $gameDbManager->readRoundResult($this->matchId);

        $gameDbManager->closeConnection();

        if ( $result === false || $result === 0 )
            return $result;

        $winnerUser = $this->players[ $result["Winner"] ]->getName();

        return [ "Winner" => $winnerUser, 
            "WinningHand" => $result["WinningHand"], 
            "WinningTile" => $result["WinningTile"],
            "Points" => $result["Points"],
            "HandType" => $result["HandType"]
        ];
    }

    public function getPlayerInfo() {
        return $this->players;
    }
    
    private function getPlayerIdByWind($wind) {
        $i = 0;
        return $this->players[$i]->getWind() == $wind ? $i : ++$i;
    }



}


