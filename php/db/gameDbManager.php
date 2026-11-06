<?php
require_once "littleMahjongDbManager.php";
require_once __DIR__."/../utils/errorUtils.php";

/*
Le tabelle nel database usate per gestire il gioco sono:
-   GameHall
Usata per la ricarca e creazione dei "tavoli da gioco".
-   MahjongTable ***********CAMBIATO***********
Una volta riempito il tavolo, uso questa tabella per assegnare i venti del primo round ai giocatori, e 
// TODO: unire alla GameHall(?)
-   MatchState ***********CAMBIATO***********
Serve per coordinare i turni dei giocatori durante lo svolgimento di un round.
Ad ogni turno, il processo del "giocatore attivo" scrive l'azione effettuata. Quando scarta, scrive anche la tessera scartata.
Quando effettua una chiamata, scrive la tripletta aperta appena creata. 
Il processo dell'altro giocatore legge periodicamente. Quando vede che l'avversario ha fatto la sua azione, la riferisce al client e gli passa il controllo.

*/

class GameDbManager extends LittleMahjongDbManager {
    private const TABLE_FULL = 1;
    private const MATCH_READY = 2;

    public function createMJTable($firstPlayer) {
        /**
         * Crea un tavolo con il giocatore $firstPlayer.
         */
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return -1;
        }
        try {
            $sql = "INSERT INTO GameHall(`Player_0`) VALUES( :usr )";
            $statement = $this->prepareStatement( $sql, [":usr", $firstPlayer ]);
            $this->pdo->beginTransaction();
            $tableId = $statement->execute() ?
                $this->pdo->lastInsertId()
                : -1 ;
            $this->pdo->commit();
            return $tableId;
        }
        catch ( Exception $e ) {
            $this->pdo->rollBack();
            error_log("Si è verificato un problema nella creazione del tavolo: ".$e->getMessage());
            // return -1;
            exit(1);
        }
    }

    /**
     * Cerca un tavolo vuoto.
     * Se, dopo aver aggiunto il giocatore al tavolo, questo è pieno, comincio la partita
     * I tavoli non vengono distrutti se un giocatore lo abbandona prima di cominciare una partita, perciò, anche se trovo un tavolo esistente con un posto libero, non è detto che si possa cominciare a giocare
     * @param string $joiningPlayer
     * @return int  -1 in caso di errore, 0 se non trovo alcun tavolo con un posto libero, altrimenti id del tavolo a cui ci si è uniti
     */
    public function joinMJTable($joiningPlayer) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return -1;
        }
        try {
            $sql = "SELECT *
                FROM GameHall 
                WHERE ( MatchStartingTime IS NULL AND MatchEndingTime IS NULL )
                    AND ( Player_0 IS NULL 
                        OR Player_1 IS NULL
                        OR Player_0 = :usr
                        OR Player_1 = :usr
                    )
                ORDER BY MahjongTableId
                LIMIT 1;";
            $statement = $this->prepareStatement($sql, [":usr", $joiningPlayer]);
            $this->pdo->beginTransaction();
            $statement->execute();
            // Se non ci sono tavoli con posti liberi, esco
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            if ( $result == false) {
                $this->pdo->rollBack();
                return 0;
            }
            // Se nel risultato uno dei giocatori era già inizializzato con il nome
            // dell'utente, può significare che questo aveva provato a cominciare una
            // partita, ma la connessione era stata interrotta.
            $freeSlot = array_search($joiningPlayer, $result);
            
            if ( $freeSlot === false ) {
                // Se il risultato non contenteva il nome dell'utente, scelgo la
                // prima posizione libera
                $freeSlot = array_search(null, $result);
            }
            
            // DEBUG
            consoleLog("Posizione $freeSlot libera per $joiningPlayer al tavolo ".$result["MahjongTableId"]);
            
            // Scrivo il nome dell'utente nella tabella
            $sql = "UPDATE GameHall SET `$freeSlot` = :usr WHERE `MahjongTableId` = ".$result["MahjongTableId"];
            consoleLog("query: ".$sql);
            $statement = $this->prepareStatement($sql, [":usr", $joiningPlayer]);
            if (!$statement->execute()) {
                $this->pdo->rollBack();
                return 0;
            }
            $this->pdo->commit();
            return $result["MahjongTableId"];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            fullErrorLog("Si è verificato un problema nella ricerca di un tavolo: ", $e);
            // return -1;
            exit;
        }
    }

    /**
     * Restituisce 0 se ancora non è pieno, 1 se è pieno ma non inizializzato, 2 se pieno e inizializzato.
     * -1 in caso di errore.
     */
    private function getMJTableState($tableId) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return false;
        }
        try {
            $sql = "SELECT IF(`MatchStartingTime` IS NULL, 1, 2) FROM `gamehall` WHERE `MahjongTableId` = :tableId AND `Player_0` IS NOT NULL AND `Player_1` IS NOT NULL";
            $statement = $this->prepareStatement($sql, [":tableId", $tableId]);
            $statement->execute();
            $result = $statement->fetch();
            return $result === false? -1 : $result[0];
        }
        catch (Exception $e) {
            fullErrorLog("Non è stato possibile verificare lo stato del tavolo", $e);
            exit(1);
            // return -1;
        }
    }
    

    public function tryStartMatch($tableId) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return false;
        }
        // Controllo se una transazione è già stata iniziata da una evetnuale funzione chiamante, se no ne inizio una.
        // Se c'era già una transazione in corso, lascio al chiamante il compito di fare rollback o commit.
        // In questo modo, se il tavolo è pronto, posso cominciare una partita senza il rischio che uno dei giocatori si ritiri
        try {
            $previousTransactionOngoing = $this->pdo->inTransaction();
            if( !$previousTransactionOngoing )
                $this->pdo->beginTransaction();
            if ( ( $state = $this->getMJTableState($tableId) ) < 1 ) {
                if(!$previousTransactionOngoing) 
                    $this->pdo->rollBack();
                return false;
            }
            // il tavolo è già stato inizializzato, posso cominciare il match
            if ( $state == self::MATCH_READY ) {
                if(!$previousTransactionOngoing) 
                    $this->pdo->commit();
                return true;
            }
            // Ho trovato un tavolo pieno ma il match non è ancora iniziato, posso finalizzarlo inserendo la data di inizio
            if ( !$this->setMatchStartingTime($tableId) ) {
                if(!$previousTransactionOngoing) 
                    $this->pdo->rollBack();
                return false;
            }
            // In fine, posso inizializzare un match
            if ( !$this->initializeMatch($tableId) ) {
                if(!$previousTransactionOngoing) 
                    $this->pdo->rollBack();
                return false;
            }
            if(!$previousTransactionOngoing) 
                $this->pdo->commit();
            return true;           
        }
        catch (Exception $e) {
            if ( $this->pdo->inTransaction() )
                $this->pdo->rollBack();
            fullErrorLog("Si è verificato un problema nella ricerca di un tavolo: ", $e);
            exit(1);
            // return -1;
        }
    }


    /**
     * Scrive la data e ora di inizio di un match.
     * Da usare solo quando un tavolo è pieno e sto inizializzando un match.
     * @throws Exception se non c'è una transazione un corso.
     * La gestione di questa e altre eventuali eccezioni è lasciata al metodo chiamante.
     */
    private function setMatchStartingTime($tableId){
        if ( !$this->pdo->inTransaction() ) {
            throw new Exception("Errore: si è tentato di scrivere nella tabella GameHall senza iniziare una transazione");
        }
        $sql = "UPDATE `GameHall` SET `MatchStartingTime` = NOW() WHERE `MahjongTableId` = :tableId";
        $statement = $this->prepareStatement($sql, [":tableId", $tableId]);
        return $statement->execute();
    }

    public function setMatchEndingTime($tableId){
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return false;
        }
        try {
            $sql = "UPDATE `GameHall` SET `MatchEndingTime` = NOW() WHERE `MahjongTableId` = :tableId";
            $statement = $this->prepareStatement($sql, [":tableId", $tableId]);
            $this->pdo->beginTransaction();
            $res = $statement->execute();
            if (!$res)
                $this->pdo->rollBack();
            else 
                $this->pdo->commit();
            return $res;
        }
        catch (Exception $e) {
            $this->pdo->rollBack();
            fullErrorLog("Si è verificato un problema nella lettura dei giocatori: ", $e);
            exit(1);
            // return -1;
        }
    }
    

    /**
     * Inserisce una nuova entry nella tabella MatchState
     * Da usare solo quando un tavolo è pieno e sto inizializzando un match.
     * @throws Exception se non c'è una transazione un corso.
     * La gestione delle eccezioni è lasciata al metodo chiamante.
     */
    private function initializeMatch($tableId) {
        if ( !$this->pdo->inTransaction() ) {
            throw new Exception("Errore: si è tentato di scrivere nella tabella MatchState senza iniziare una transazione");
        }
        $sql = "INSERT INTO `MatchState`(`TableId`) VALUES (:tableId)";
        $statement = $this->prepareStatement($sql,[":tableId", $tableId]);
        return $statement->execute();
    }

    /**
     * Dato l'ID di un tavolo, restituisce un array con i nomi dei giocatori.
     */
    public function getPlayersNames($tableId) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return null;
        }
        try {
            $sql = "SELECT `Player_0`, `Player_1` FROM `GameHall` WHERE `MahjongTableId` = :tableId";
            $statement = $this->prepareStatement($sql, [":tableId", $tableId]);
            $statement->execute();
            $res = $statement->fetch(PDO::FETCH_NUM);
            // error_log("giocatori letti trovati nella game hall: ".var_export($res));
            return $res;
        }
        catch (Exception $e) {
            if ( $this->pdo->inTransaction() )
                $this->pdo->rollBack();
            fullErrorLog("Si è verificato un problema nella lettura dei giocatori: ", $e);
            exit(1);
            // return -1;
        }
    }

    /**
     * Scrive nella tabella "MatchState" Le informazioni che i due 
     * processi server dei giocatori devono condividere. 
     * @param int $tableId
     * @param int $numRound 
     * @param string $wind0
     * @param string $wind1
     * @param string $wall tessere che compongono il muro in formato CSV
     * @return bool true se la scrittura va a buon fine, false altrimenti
     */
    public function writeMatchState($tableId, $numRound, $wind0, $wind1, $wall) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return false;
        }
        try {    
            $sql = "UPDATE `MatchState` SET `PlayerWind_0` = :w0, `PlayerWind_1` = :w1, `Wall` = :wall, `CurrentRound` = :r WHERE `TableId` = :tableId";
            $statement = $this->prepareStatement($sql, [":w0", $wind0], [":w1", $wind1], [":wall", $wall], [":r", $numRound], [":tableId", $tableId]);
            $this->pdo->beginTransaction();
            $res = $statement->execute();
            if( $res ) 
                $this->pdo->commit();
            else
                $this->pdo->rollBack();
            return $res;
        }
        catch (Exception $e) {
            $this->pdo->rollBack();
            fullErrorLog("Si è verificato un problema nella scrittura dello stato del match: ", $e);
            exit(1);
        }

    }

    public function readMatchState($tableId) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return null;
        }
        try {
            $sql = "SELECT * FROM `MatchState` WHERE `TableId` = :tableId";
            $statement = $this->prepareStatement($sql, [":tableId", $tableId]);
            if( $statement->execute() === false )
                return null;
            return $statement->fetch(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            fullErrorLog("Si è verificato un problema nella lettura dello stato del match: ", $e);
            exit(1);
        }
    } 

    public function insertNewRoundState($tableId) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return false;
        }
        try {
            $sql = "INSERT INTO `RoundState`(`TableId`) VALUES (:tableId)";
            $statement = $this->prepareStatement($sql, [":tableId", $tableId]);
            $this->pdo->beginTransaction();
            $res = $statement->execute();
            if( $res ) 
                $this->pdo->commit();
            else
                $this->pdo->rollBack();
            return $res;
        }
        catch (Exception $e) {
            $this->pdo->rollBack();
            fullErrorLog("Si è verificato un problema nella scrittura dello stato del round: ", $e);
            exit(1);
        }

    }
    
    // DEBUG
    // BACKUP FUNZIONANTE

    // public function writeRoundState($roundState) {
    //     if( !$this->isOpened() ){
    //         # debug
    //         error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
    //         return false;
    //     }
    //     try {
    //         var_error_log($roundState, "stato da scrivere nel DB ".__FILE__.": ".__LINE__." ");
    //         $sql = "REPLACE INTO `RoundState` VALUES ( :tableId, :ElastAction, :SlastAction, :activePlayer, :lastDiscard, :lastOpenTriplet, :turn )";
    //         $statement = $this->prepareStatement($sql,
    //          [":tableId", $roundState["TableId"]],
    //          [":ElastAction", $roundState["LastAction_E"]],
    //          [":SlastAction", $roundState["LastAction_S"]],
    //          [":activePlayer", $roundState["ActivePlayer"]], 
    //          [":lastDiscard", $roundState["LastDiscard"]],
    //          [":lastOpenTriplet", $roundState["LastOpenTriplet"]],
    //          [":turn", $roundState["Turn"]]
    //         );
    //         $this->pdo->beginTransaction();
    //         $res = $statement->execute();
    //         if( $res ) 
    //             $this->pdo->commit();
    //         else
    //             $this->pdo->rollBack();
    //         return $res;
    //     }
    //     catch (Exception $e) {
    //         $this->pdo->rollBack();
    //         fullErrorLog("Si è verificato un problema nella scrittura dello stato del round: ", $e);
    //         exit(1);
    //     }
    // }

    public function writeRoundState($roundState) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return false;
        }
        try {
            var_error_log($roundState, "stato da scrivere nel DB ".__FILE__.": ".__LINE__." ");
            $sql = "UPDATE `RoundState` 
                SET LastAction_E = :ElastAction, 
                    LastAction_S = :SlastAction,
                    ActivePlayer = :activePlayer, 
                    LastDiscard = :lastDiscard, 
                    LastOpenTriplet = :lastOpenTriplet, 
                    Turn = :turn
                WHERE TableId = :tableId;";
            $statement = $this->prepareStatement($sql,
             [":tableId", $roundState["TableId"]],
             [":ElastAction", $roundState["LastAction_E"]],
             [":SlastAction", $roundState["LastAction_S"]],
             [":activePlayer", $roundState["ActivePlayer"]], 
             [":lastDiscard", $roundState["LastDiscard"]],
             [":lastOpenTriplet", $roundState["LastOpenTriplet"]],
             [":turn", $roundState["Turn"]]
            );
            $this->pdo->beginTransaction();
            $res = $statement->execute();
            if( $res ) 
                $this->pdo->commit();
            else
                $this->pdo->rollBack();
            return $res;
        }
        catch (Exception $e) {
            $this->pdo->rollBack();
            fullErrorLog("Si è verificato un problema nella scrittura dello stato del round: ", $e);
            exit(1);
        }
    }

    public function readRoundState($tableId) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return null;
        }
        try {
            $sql = "SELECT * FROM `RoundState` WHERE `TableId` = :tableId";
            $statement = $this->prepareStatement($sql, [":tableId", $tableId]);
            if( $statement->execute() === false )
                return null;
            return $statement->fetch(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            fullErrorLog("Si è verificato un problema nella lettura dello stato del round: ", $e);
            exit(1);
        }
    }

    public function writeLastAction($tableId, $lastActionE, $lastActionS) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return false;
        } 
        try {
            $sql = "UPDATE `RoundState` SET `LastAction_E` = :lastActionE, `LastAction_S` = :lastActionS WHERE `TableId` = :tableId";
            $statement = $this->prepareStatement($sql, [":lastActionE", $lastActionE], [":lastActionS", $lastActionS], [":tableId", $tableId]);
            $this->pdo->beginTransaction();
            if ( ! ($res = $statement->execute()) ){
                $this->pdo->rollBack();
            } 
            else
                $this->pdo->commit();
            return $res;
        }
        catch (Exception $e) {
            $this->pdo->rollBack();
            fullErrorLog("Si è verificato un problema nella lettura dell\'ultima azione': ", $e);
            exit(1);
        }
    }


    public function readLeaderboard() {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return false;
        }
        try {
            $sql =  " SELECT * FROM `Leaderboard` ORDER BY `Points` DESC";
            return $this->pdo->query($sql);
        } 
        catch (Exception $e) {
            fullErrorLog("Si è verificato un problema nella lettura della classifica: ", $e);
            exit(1);
        }
    }

    /**
     * Salva il risultato del round nella tabella Result. 
     * La tabella Leaderboard viene aggiornata da un trigger dopo l'inserimento del risultato.
     * @param int $tableId
     * @param int $winnerId Id assegnato durante la partita (0 o 1)
     * @param string $winningHand mano vincente in formato CSV
     * @param array[] $handType array associativo mano => punteggio
     * @param int $totalPoints 
     * @return bool
     */
    public function writeRoundResult($tableId, $winnerId, $winningHand, $winningTile, $handType,  $totalPoints) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return false;
        }
        try {
            $sql = "INSERT INTO `Result`(`TableId`, `Winner`, `WinningHand`, `winningTile`, `HandType`, `Points`) VALUES(:tableId, :winner, :winningHand, :winningTile, :handType, :points)";
            $handTypeJson = json_encode($handType);
            $statement = $this->prepareStatement($sql, [":tableId", $tableId], [":winner", $winnerId], [":winningHand", $winningHand], [":winningTile", $winningTile], [":handType", $handTypeJson], [":points", $totalPoints]);
            $this->pdo->beginTransaction();
            $result = $statement->execute();
            if($result) {
                $this->pdo->commit();
            }
            else {
                $this->pdo->rollBack();
            }
            return $result;
        }
        catch (Exception $e) {
            $this->pdo->rollBack();
            fullErrorLog("Si è verificato un problema nella scrittura dello risultato del round: ", $e);
            exit(1);
        }
    }

    public function readRoundResult($tableId) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return false;
        }
        try {
            $sql = "SELECT * FROM `Result` WHERE `TableId` = :tableId";
            $statement = $this->prepareStatement($sql, [":tableId", $tableId]);
            if( !$statement->execute() ) {
                // Si è verificato un errore
                return false;
            }
            if( $statement->rowCount() == 0 ){
                // Non c'è nessun risultato corrispondente a quell'id
                return 0;
            }
                
            return $statement->fetch(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            fullErrorLog("Si è verificato un problema nella scrittura dello risultato del round: ", $e);
            exit(1);
        }
    }

}


    