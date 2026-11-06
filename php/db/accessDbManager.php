<?php
require "littleMahjongDbManager.php";

// Interfaccia per eseguire le operazioni legate a registrazione e login sul database;
// non esegue validazione sul pattern delle credenziali

class AccessDbManager extends LittleMahjongDbManager {
    function userExists( $username ) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return;
        }
        try {
            $sql = "SELECT 1 FROM `utente` WHERE `Username` = :usr";
            $statement = $this->prepareStatement($sql, [":usr", $username] );
            $statement->execute();
            return !$statement->fetch() == false;
        } 
        catch( Exception $e ) {
            error_log("Problema nell'esecuzione della query: ".$e->getMessage());
            exit(1);
        }
    }

    // Registra un utente;
    // se la registrazione ha successo restituisce true, altrimenti false
    function registraUtente($username, $password) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return false;
        }
        try {
            $hash =  password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO utente(`Username`, `Hash`, `Stato`) VALUES( :usr, :pwdHash, 'online');";
            $statement = $this->prepareStatement(
                $sql, [":usr", $username], [":pwdHash", $hash]
            );
            $this->pdo->beginTransaction();
            $result = $statement->execute();
            $this->pdo->commit();
            echo "<script>console.log($result)</script>";
            return $result;

        }
        catch( Exception $e ) {
            $this->pdo->rollBack();
            error_log("Si è verificato un problema nella registrazione dell'utente: ".$e->getMessage());
            exit;
        }
    }

    // verifico se le credenziali inserite sono registrate nel database; 
    // restituisce null se l'utente non esiste o se esiste ma la password non corrisponde,
    // altrimenti restituisco l'id dell'utente
    function verificaCredenziali($username, $password) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return null;
        }
        try {
            $query = "SELECT `Hash`, `UserId` FROM utente WHERE `Username` = :usr";
            $statement = $this->prepareStatement($query, [":usr", $username]);
            $statement->execute();
            if ( !( $result = $statement->fetch() ) ) 
                return null;
            if (!password_verify($password, $result["Hash"], ))
                return null;
            return $result["UserId"];
        } 
        catch( Exception $e ) {
            error_log("Problema nell'esecuzione della query: ".$e->getMessage());
            exit(1);
        }
    }

    /**
     *  Restituisce l'id assegnato ad un utente, null se l'utente non esiste
     */
    function getUserId($username) {
        if( !$this->isOpened() ){
            # debug
            error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
            return null;
        }
        try {
            $query = "SELECT `UserId` FROM utente WHERE `Username` = :usr";
            $statement = $this->prepareStatement($query, [":usr", $username]);
            $statement->execute();
            if( !( $id = $statement->fetch()["UserId"]  ) ) {
                return null;
            }
            return $id;
        }
        catch (Exception $e) {
            error_log("Problema nell'esecuzione della query: ".$e->getMessage());
            exit(1);
        }
    }

    /**
     * Restituisce lo stato dell'utente, null se l'utente non esiste
     */
    // function getUserStatus($username) {
    //     if( !$this->isOpened() ){
    //         # debug
    //         error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
    //         return null;
    //     }
    //     try {
    //         $query = "SELECT `Stato` FROM utente WHERE `Username` = :usr";
    //         $statement = $this->prepareStatement($query, [":usr", $username]);
    //         $statement->execute();
    //         if( !( $stato = $statement->fetch()["Stato"]  ) ) {
    //             return null;
    //         }
    //         return $stato;
    //     }
    //     catch (Exception $e) {
    //         error_log("Problema nell'esecuzione della query: ".$e->getMessage());
    //         exit(1);
    //     }
    // }

    /**
     * Cambia lo stato dell'utente $username in $status 
     * @param string $status ammette come valori "online", "offline" e "ingame"
     * @throws \InvalidArgumentException se viene fornito un valore di $status non ammesso
     * @return bool se l'aggiornamento del database ha successo, false altrimenti
     */
    // function setUserStatus($status, $username) {
    //     if( !$this->isOpened() ){
    //         # debug
    //         error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);
    //         return false;
    //     }
    //     try {
    //         if( !( $status == "offline" || $status == "online" || $status == "ingame" )  )
    //             throw new InvalidArgumentException(" lo stato inserito non è valido");
    //         $sql = "UPDATE utente SET `Stato` = :status WHERE `Username` = :usr";
    //         $statement = $this->prepareStatement($sql, 
    //             [":status", $status], [":usr", $username]
    //         );
    //         $this->pdo->beginTransaction();
    //         $result = $statement->execute();
    //         $this->pdo->commit();
    //         echo "<script>console.log($result)</script>";
    //         return $result;
    //     } 
    //     catch( InvalidArgumentException $e ) {
    //         error_log($e->__tostring());
    //         exit;
    //     }
    //     catch( Exception $e ) {
    //         $this->pdo->rollBack();
    //         error_log("Si è verificato un problema nell'update dello stato dell'utente: ".$e->getMessage());
    //         exit;
    //     }   
    // }
    
}