<?php

require_once __DIR__."/../log/logConfig.php";

const NUM_PLAYERS = 2;
// punti con cui ogni giocatore comincia il match
const STARTING_POINTS = 500;
// La somma dei punti dei giocatori rimane sempre la stessa.
// Affinché si possa decretare un vincitore, deve esserci una disparità 
// di punti alla fine del 4° round 
const MIN_POINTS_FOR_WIN = STARTING_POINTS + 1;
// punti minimi che ogni giocatore deve avere alla fine di un round per procedere al successivo
const MIN_POINTS_NEEDED = 0;
// Numero di round da giocare prima di poter concludere la partita 
// (a meno che un giocatore sia sceso sotto gli 0 punti alla fine di un round precedente)
const MIN_ROUNDS_NEEDED = 4;
// numero di tessere nella mano, escludendo la tessera pescata a inizio turno
const HAND_SIZE = 13;
// TODO: yaku table

// tile suits and values
const NUM_TILE_COPIES = 4;
const BAMBOO = "b";
const PIN = "p";
const NUMERIC_SUIT = [BAMBOO, PIN];
const DRAGON = "d";
const WIND = "w";
const MIN_NUMERIC_VALUE = 1;
const MAX_NUMERIC_VALUE = 9;
const GREEN = "G";
const RED = "R";
const WHITE = "W";
const DRAGON_VALUE = [GREEN, RED, WHITE];
const EAST = "E";
const SOUTH = "S";
const WIND_VALUE = [EAST, SOUTH];

define("SUIT", array_merge(NUMERIC_SUIT, [DRAGON, WIND]) );
define( 
    "TOT_TILES", 
    NUM_TILE_COPIES * ( count(NUMERIC_SUIT) * MAX_NUMERIC_VALUE + count(DRAGON_VALUE) + count( WIND_VALUE ) )
);  // 92

// Associa una tessera ad un numero.
// Serve per ordinare la mano.
function toOrdinal($tile) {
    $dec = array_search( $tile[0], SUIT);
    switch ($dec) {
        case 0 :
        case 1 :
            // bambù o pin
            $unit = intval($tile[1]);
            break;
        case 2 :
            // drago
            $unit = array_search( $tile[1], DRAGON_VALUE);
        break;
        case 3 : 
            // vento
            $unit = array_search( $tile[1], WIND_VALUE);
            break;
        default :
            -1;
    } 
    return $dec * 10 + $unit;
}




// class GameConfig {
//     static const NUM_PLAYERS = 2;
//     // punti con cui ogni giocatore comincia il match
//     static const STARTING_POINTS = 25000;
//     // deve esserci una disparità di punti affinché si possa decretare un vincitore
//     static const MIN_POINTS_FOR_WIN = STARTING_POINTS + 1;
//     // punti minimi che ogni giocatore deve avere alla fine di un round per procedere al successivo
//     static const MIN_POINTS_NEEDED = 0;
//     // numero di round ad giocare prima di poter concludere la partita, se nessun giocatore è mai sceso sotto gli 0
//     static const MIN_ROUNDS_NEEDED = 4;
//     // numero di tessere nella mano, escludendo la tessera pescata a inizio turno
//     static const HAND_SIZE = 13;
//     // TODO: yaku table
    
//     // tile suits and values
//     static const NUM_TILE_COPIES = 4;
//     static const BAMBOO = "b";
//     static const PIN = "p";
//     static const NUMERIC_SUIT = [BAMBOO, PIN];
//     static const DRAGON = "d";
//     static const WIND = "w";
//     static const MIN_NUMERIC_VALUE = 1;
//     static const MAX_NUMERIC_VALUE = 9;
//     static const GREEN = "G";
//     static const RED = "R";
//     static const WHITE = "W";
//     static const DRAGON_VALUE = [GREEN, RED, WHITE];
//     static const EAST = "E";
//     static const SOUTH = "S";
//     static const WIND_VALUE = [EAST, SOUTH];
//     function getAllSuits() {
//         return SUIT;
//     }
//     function getTotTiles() {
//         return TOT_TILES;
//     }
// }