<?php 
# __DIR__ si risolve nel path assoluto della cartella corrente,
# permettendo di includere il file da livelli diversi di sottocartelle
include_once __DIR__."/../log/logConfig.php";
require "dbConfig.php";

class LittleMahjongDbManager {
    protected $pdo;

    function LittleMahjongDbManager() {
        $this->pdo = null;
    }

    function openConnection() {
        if( $this->isOpened() ){
            # debug
            error_log('Info: connessione già aperta');
            return;
        }
        try {
            $connString = "mysql:host=".DBHOSTNAME.";dbname=".DBNAME;
            $user = DBUSERNAME;
            $password = DBPASSWORD;
            $this->pdo = new PDO($connString, $user, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            # debug
            // if ($this->pdo != null) {
            //     error_log('Info: non è stata effettuata la connessione al database in '.__FILE__.": ".__LINE__);;
            // }
        } 
        catch( PDOException $e ) {
            fullErrorLog( "Error: connessione al database fallita: ", $e );
            exit(1);
        }
        catch(Exception $e) {
            fullErrorLog("Error: Si è verificato un problema inatteso.", $e);
            exit(1);
        }
    }

    /**
     * 
     * parameter: array contenente 2 stringhe con struttura [":placeholder", "parametro"]
     * la gestione delle eccezioni è lasciata al chiamante
     */
    public function prepareStatement($sqlString, ...$parameter) {
        $statement = $this->pdo->prepare($sqlString);
        foreach ($parameter as $p) {
            $statement->bindValue($p[0], $p[1]);
        }
        return $statement;
    }

    function isOpened() {
        return !$this->pdo == null;
    }

    function closeConnection() {
        $this->pdo = null;
    }

}