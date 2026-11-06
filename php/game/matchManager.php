<?php

require_once __DIR__."/../log/logConfig.php";
require_once __DIR__."/player.php";

if(session_status() !== PHP_SESSION_ACTIVE) session_start();

class MatchManager {
    private $matchId;
    private $players = [];
    // private $round;
    private $numPlayers = 0;

    public function __construct($matchId) {
        $this->matchId = $matchId;
    }


    /**
     * adds a user to the match and returns a Player object
     */
    public function addPlayerToMatch($username) {
        // return $this->players[] = new Player( $this->numPlayers++, $username );
        $this->players[$this->numPlayers++] = $username;
        if ( $this->numPlayers == NUM_PLAYERS )  
            $this->startMatch();
    }

    public function getPlayerId($playerName) {
        $i = 0;
        while ( $this->players[$i] != $playerName  ) {
            if ( ++$i >= $this->numPlayers ) {
                return -1;
            }
        }
        return $i;
    }
    
    public function startMatch() {
        // $i = 0;
        // $tmp = [];
        // while( $i < $this->numPlayers ) {
        //     $tmp[] = new Player( $i, $this->players[$i]); 
        // }
        $_SESSION["matchManager"] = $this;
        echo "<script>console.log('GO!')</script>";
        // return new RoundManager($tmp);
        return $_SESSION["roundManager"] = new RoundManager($this->matchId, $this->players);
    }
}