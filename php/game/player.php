<?php

/*
Corrispettivo lato server della classe Player definita in 
Javascript, nel file player.js. Serve per tenere traccia dello
stato della partita dal lato server. 
*/

require_once __DIR__."/gameConfig.php";
require_once __DIR__."/wallManager.php";
require_once __DIR__."/actions.php";

// Implemento l'interfaccia JsonSerializable per poter inviare in formato JSON 
// i campi privati dell'oggetto 
class Player implements JsonSerializable{
    private $playerId;
    private $name;
    private $wind;
    private $hand = null;
    private $openTriplets = [];
    private $points = STARTING_POINTS;
    private $lastDrawnTile = null;
    // private $lastDiscardedTile = "";
    private $discardedTiles = null;
    // Variabile che mantiene le informazioni su quali chiamate può eseguire il giocatore.
    // Evita di dover ripetere i controlli sulla mano ogni volta che il client
    // richiede di fare un'azione (vedi ./executePlayerActions.php)
    private $availableCall = [];
    public function __construct($playerId, $name, $wind = null) {
        $this->playerId = $playerId;
        $this->name = $name;
        $this->wind = $wind;
    }

    public function jsonSerialize() {
        return [
            "playerId" => $this->playerId,
            "name" => $this->name,
            "wind" => $this->wind,
            "points" => $this->points
            // "hand" => $this->hand,
            // "openTriplets" => $this->openTriplets,
            // "lastDrawnTile" =>$this->lastDrawnTile
        ];
    }

    public function drawStartingHand(& $wallManager) {
        return $this->hand = $wallManager->extractStartingHand();
    } 

    public function drawTile(& $wallManager) {
        return $this->lastDrawnTile = $wallManager->extractTile();
    }

    // Resetta lo stato della mano
    public function discardHand() {
        $this->hand = null;
    }

    /**
     * Toglie la tessera $tile dalla mano del giocatore
     * @param string $tile identificatore della tessera da scartare
     * @return string ritorna la tessera appena scartata
     */
    public function discardTile($tile) {
        // Se $tile è uguale alla tessera appena pescata, scarto quella
        if( $tile == $this->lastDrawnTile ) {
            $this->discardedTiles[] = $this->lastDrawnTile;
            $this->lastDrawnTile = null;
            return $tile;    
        }
        // altrimenti scarto dalla mano
        // $index = array_search( $tile, $this->hand );
        // $this->discardedTiles[] = array_splice($this->hand, $index, 1);
        $this->discardedTiles[] = $this->extractFromHand($tile);
        // Se lo scarto avviene dopo una pescata e non dopo una chiamata,
        // aggiungo la tessera appena pescata nella mano.
        if ( $this->lastDrawnTile != null )
            $this->addDrawToHand();
        return $tile;
    }

    public function extractFromHand($tile){
        // if ( $index = array_search( $tile, $this->hand ) === false )
        //     return false;
        $index = array_search( $tile, $this->hand );
        
        $tmp = array_splice($this->hand, $index, 1);
        // DEBUG
        debug_log("mano dopo lo splice:...");
        var_error_log($this->hand, "...");
        // END DEBUG
        return $tmp[0];
    }
    // Se scarto una tessera dalla mano, aggiungo quella appena pescata
    public function addDrawToHand() {
        $this->hand[] = $this->lastDrawnTile;
        $this->lastDrawnTile = null;
    }

    
    public function getPlayerId() {
        return $this->playerId;
    }

    public function getName() {
        return $this->name;
    }   
    
    public function getWind() {
        return $this->wind;
    }    
    
    public function getHand() {
        return $this->hand;
    }
    
    public function getPoints() {
        return $this->points;
    }    
    
    public function getLastDrawnTile() {
        return $this->lastDrawnTile;
    }    
    
    public function getLastDiscardedTile() {
        // return end($this->discardedTiles);
        $index = array_key_last($this->discardedTiles);
        error_log("last discarded: "
        . ( $index == null ? "" : $this->discardedTiles[$index] ) );
        return $index == null? "" : $this->discardedTiles[$index];
        
        // return $this->lastDiscardedTile;
    }   
    
    public function getDiscardedTiles() {
        return $this->discardedTiles;
    }

    /**
     * Chiama Pon o Chii sulla tessera appena scartata dall'avversario per formare una tripletta aperta.
     * @param string $calledTile: ultima tessera scartata dall'avversario.
     * @param array $addedTiles: array che contiene 2 tessere presenti nella mano del giocatore con cui formare la terna.
     * @return array: la tripletta appena formata.
     */
    public function callTile($calledTile, $addedTiles) {
        $tmp = [$calledTile];
        foreach( $addedTiles as $t ) {
            $tmp[] = $this->extractFromHand($t);
        }
        return $this->openTriplets[] = $tmp;
    }
    public function getLastOpenTriplet() {
        return end($this->openTriplets);
    }

    public function getOpenTriplets() {
        return $this->openTriplets;
    }

    public function setWind($wind) {
        $this->wind = $wind;
    }

    /**
     * Aggiunge i tipi di chiamate che il giocatore può fare sull'
     * ultima tessera scartata dall'avversario
     * @param Action[] $call: array di chiamate da aggiungere 
     * @return void
     */
    public function addAvailableCall($call) {
        foreach ($call as $c) {
            $this->availableCall[] = $c;
        }
    }

    public function findAvailableCall($call) {
        foreach( $this->availableCall as $ac ){
            if( $ac == $call )
                return true;
        }
        return false;
    }

    public function resetAvailableCall() {
        unset($this->availableCall);
    }

    public function setPoints($newValue) {
        return $this->points = $newValue;
    }
}