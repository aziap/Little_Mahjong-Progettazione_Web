<?php

require_once __DIR__."./gameConfig.php";
require_once __DIR__."./actions.php";
require_once __DIR__."./winningHand.php";
require_once __DIR__."./../log/logConfig.php";

/**
 * Classe statica che contiene metodi per controllare se una tripletta è valida, se una mano è vincente, se una tessera completa un certo tipo di tripletta, o se una tessera può completare una mano vincente
 */
class HandValidator {

    public static function isPon($tile1, $tile2, $tile3) {
        $reg = "/[^".$tile1."]/";
        return ! preg_match($reg, $tile2.$tile3);
    }

    public static function isChii($tile1, $tile2, $tile3) {
        $suit = $tile1[0]; 
        if( $suit == DRAGON || $suit == WIND)
            return false;
        $tmp = [$tile1, $tile2, $tile3];
        sort($tmp);
        $leastNum = $tmp[0][1];
        // Se il numero più piccolo è maggiore di 7, sicuramente
        // non ho un Chii (la sequenza "maggiore" è 7-8-9)
        if($leastNum > 7) {
            return false; 
        }

        $pattern = $suit.$leastNum++.$suit.$leastNum++.$suit.$leastNum;

        return strcmp($pattern, implode($tmp)) == 0;
        // $reg = "/[^".$suit.$leastNum++.$leastNum++.$leastNum."]/";
        
        // // DEBUG
        // debug_log("RegExp per il controllo del chii: $reg. Risultato:... ");
        // var_error_log(preg_match($reg, implode($tmp)));
        // // END DEBUG
        // return preg_match($reg, implode($tmp)) === 0;
    }

    /**
     * Data una mano e una tessera, dice se questa può essere chiamata, e per cosa.
     * @param string $discard: tessera scartata dall'avversario.
     * @param string[] $hand: mano del giocatore 
     * @return Action[] | bool se la tessera non può essere chiamata ritorna false, 
     * altrimenti i tipi di chiamate che il giocatore può fare 
     */
    public static function canBeCalled($discard, $hand) {
        // Array in cui scrivo le chiamate possibili
        $call = null;
        $suit = $discard[0];
        $reg = "/^[$suit]/";
        $sameSuitTiles = array_values( preg_grep($reg, $hand) );
        // Se non ci sono tessere dello stesso seme, sicuramente non posso chiamare lo scarto
        if( empty($sameSuitTiles) )
            return false;
        
        // controllo se posso chiamre Pon
        if ( count( preg_grep("/^[$suit][".$discard[1]."]$/", $sameSuitTiles ) ) > 1 ) {
            $call[] = Action::PON;
        }

        // Se ho un drago o un vento, non posso chiamare Chii, vado al passo successivo
        if ( $suit != DRAGON && $suit != WIND ) {
            // controllo se, tra quelle dello stesso seme, ho tessere il cui valore dista 1 o 2 da quello della tessera scartata
            $prevGapFound = false;
            $prevFound = false;
            $nextFound = false;
            $nextGapFound = false;
            
            foreach( $sameSuitTiles as $t ) {
                if( $discard == $t )
                    continue;

                $prevGapFound = ( $prevGapFound || $t[1] == $discard[1] - 2);
                $prevFound = ( $prevFound || $t[1] == $discard[1] - 1);
                $nextFound = ( $nextFound || $t[1] == $discard[1] + 1);
                $nextGapFound = ( $nextGapFound || $t[1] == $discard[1] + 2);
            }

            if ( 
                ( $prevGapFound && $prevFound )
                || ( $prevFound && $nextFound )
                || ( $nextFound && $nextGapFound )
             )
                $call[] = Action::CHII;
        }

        // Anche se la tessera non può essere chiamata per un Chii o per un Pon, potrebbe completare la coppia di una mano vincente
        if ( self::isWinning( array_merge( $hand, [$discard] ) ) )
            $call[] = Action::RON;

        return isset($call) ? $call : false;
    }


    /**
     * Prende in input una mano. 
     * Non è necessario includere le triplette aperte, poiché sono blocchi validi per definizione.
     * Restituisce true se è una mano vincente, false altrimenti.
     * @param string[] $hand
     * @return bool
     */
    public static function isWinning($hand) {

        // CODICE SOSTITUITO DA extractBlocks()

        // sort($hand);
        // // Divido la mano in blocchi ti tessere dello stesso seme
        // $block = [];
        // foreach( SUIT as $s ){
        //     $reg = "/^[".$s."]/";
        //     // Uso array_values per resettare gli indici
        //     $tmp = array_values( preg_grep($reg, $hand) );
        //     if ( !empty($tmp) ) 
        //         $block[] = $tmp;
        // }
        // DEBUG
        // var_error_log($block);

        $blockArray = self::extractBlocks($hand);

        debug_log("Blocchi estratti: ...");
        var_error_log($blockArray, "...");

        // CODICE SOSTITUITO DA extractPair() (enhanced)

        // $possiblePairFound = false;
        // $pairIndex = -1;
        // foreach( $block as $key => $b ) {
        //     if (count($b) % 3 == 1)
        //         return false;
        //     if ( count($b) % 3 == 2 && $possiblePairFound )
        //         return false;
        //     if ( count($b) % 3 == 2 ){
        //         $possiblePairFound = true;
        //         $pairIndex = $key;
        //     }
        // }
        // // Non c'è un blocco che potrebbe contenere la coppia
        // if ( !$possiblePairFound )
        //     return false;
        // // Esamino il blocco che potrebbe contenere la coppia e, se presente, la estraggo
        // $pairBlock = null;
        // if( count( $block[$pairIndex] ) == 2 ) {
        //     $pairBlock = $block[$pairIndex];
        // }
        // else if ( ! self::extractPair($block[$pairIndex], $pair) ) {
        //     return false;
        // }
        
        // // verifico se tutti i blocchi sono validi
        // foreach ( $block as $key=>$b ) {
        //     var_error_log($b, "Debug: examining block b in ".__FUNCTION__.". b: ");
        //     // Ho già validato il blocco che contiene la coppia
        //     if ( $key ==  $pairIndex )
        //         continue;
        //     if ( ! self::validateBlock($b) ) {
        //         return false;
        //     }
        // }
        // Tutti i blocchi sono validi, la mano è vincente
        // controllo se devo restituire i blocchi e la coppia negli array passati per riferimento

        $pairArray = [];
        // Se non sono in grado di individuare una coppia, allora non ho una mano valida
        if ( !self::extractPair($blockArray, $pairArray) )
            return false;


        // Verifico che ogni blocco sia formato da Chii e/o Pon
        foreach($blockArray as $block) {
            if ( !self::validateBlock($block) )
                return false;
        }

        return true;

        // if ( isset($blockArray) && isset($pairArray) ) {
        //     $blockArray = $block;
        //     $pairArray = $pair;
        // }
        // return true;
    }

    public static function extractBlocks($hand) {
        sort($hand);
        // Divido la mano in blocchi di tessere dello stesso seme
        $blockArray = [];
        foreach( SUIT as $s ){
            $reg = "/^[".$s."]/";
            // Uso array_values per resettare gli indici
            $tmp = array_values( preg_grep($reg, $hand) );
            if ( !empty($tmp) ) 
                $blockArray[] = $tmp;
        }

        // DEBUG
        // var_error_log($blockArray, "In function ".__FUNCTION__.", ".__FILE__.": ".__LINE__);
        
        return $blockArray;
    }


    /**
     * Data una mano di tessere nascoste (non è necessario includere le triplette aperte) divise in blocchi ordinati dello stesso seme, estrae la coppia dal blocco che la contiene, restituendo i blocchi e la coppia stessa. Attenzione: modifica il blocco che contiene la coppia. Se l'operazione dà esito negativo, il blocco non viene modificato.
     * @param array $blockArray array di blocchi ordinati di tessere dello stesso seme. Il blocco contenente la coppia viene modificato.
     * @param mixed $pairArray oggetto in cui verrà restituita la coppia valida estratta. null se non ne è stata trovata alcuna.
     * @return bool true se ho trovato la coppia, false altrimenti.
     */
    public static function extractPair(&$blockArray, &$pairArray){
        // Perché una mano sia valida, deve essere composta da 4 triplette e una coppia.
        // La mano deve quindi avere tutti blocchi di dimensione multipla di 3 e uno solo di dimensione D tale che D % 3 = 2.
        $possiblePairFound = false;
        $pairIndex = -1;
        foreach( $blockArray as $key => $b ) {
            if (count($b) % 3 == 1)
                return false;
            if ( count($b) % 3 == 2 && $possiblePairFound )
                return false;
            if ( count($b) % 3 == 2 ){
                $possiblePairFound = true;
                $pairIndex = $key;
            }
        }

        // Non c'è un blocco che potrebbe contenere la coppia
        if ( !$possiblePairFound )
        return false;
        // Se il blocco che contiene la coppia è la coppia stessa, lo tolgo dall'array di blocchi originale e lo restituisco come coppia
        if( count($blockArray[$pairIndex]) == 2 ) {
            $pairArray = $blockArray[$pairIndex];
            unset( $blockArray[$pairIndex] );
            // DEBUG
            // error_log("In function ".__FUNCTION__." ".__FILE__.": ".__LINE__. ": il blocco è la coppia $pairArray[0], $pairArray[1] ");
            
            return true;
        }
        // Esamino il blocco che potrebbe contenere la coppia e, se presente, la estraggo
        $pairBlock = $blockArray[$pairIndex];
        // if( count( $blockArray[$pairIndex] ) == 2 ) {
        //     $pairBlock = $blockArray[$pairIndex];
        // }

        $pairArray = null;
        $prev = $pairBlock[0];
        $lastPairTried = "";
        $tmp = $pairBlock;
        // Cerco le coppie
        for ( $i = 1; $i < count($pairBlock); $i++ ) {
            // Tessera che sto esaminando
            $cur = $pairBlock[$i];
            if($prev == $cur && $lastPairTried != $cur) {
                $lastPairTried = $cur;
                $pairArray = array_splice($tmp, $i - 1, 2);
                
                // DEBUG
                // var_error_log($pairArray, "In function ".__FUNCTION__.", examinating \$pairArray. ".__FILE__.": ".__LINE__.": ");
                // var_error_log($tmp, "In function ".__FUNCTION__.", examinating \$tmp ".__FILE__.": ".__LINE__);
                // END DEBUG
                
                // Ho una coppia candidata
                // Controllo se, estraendola, il blocco è valido
                if (self::validateBlock($tmp)) {
                    $blockArray[$pairIndex] = $tmp;
                    return true;
                }
                // Se non era valido, reinserisco la coppia e continuo
                $tmp = $pairBlock;
                $pairArray = null;
            }
            $prev = $cur;
        }
        // Non ho trovato una coppia valida

        // DEBUG
        // error_log("In function ".__FUNCTION__.", ".__FILE__.": ".__LINE__);
        // var_error_log($blockArray);
        // var_error_log($pairArray);
        // END DEBUG

        return false;
    }


    /**
     * Dato un blocco ordinato di tessere dello stesso seme e di dimensione multipla di 3, indica se il blocco è valido o meno
     * @param array $block
     * @return bool true se valido, false altrimenti
     */
    public static function validateBlock($block) {
        // DEBUG
        debug_log("examining block: ...");
        var_error_log($block, "...");
        // END DEBUG

        if ( count($block) == 3 ) {
            if ( self::isChii(...$block) )
                return true;
            if ( self::isPon(...$block) )
                return true;
            return false;
        }
        // Controllo se le prime 3 tessere formano un Pon
        $tmp = [];

        if ( $block[0] == $block[1] && $block[1] == $block[2] ) {
            //DEBUG
            // error_log("ho un pon");
            // Ho un Pon, lo estraggo e controllo il resto del 
            // blocco
            $tmp = array_slice($block, 3);
            debug_log("controllo se ho un pon...");
            var_error_log($tmp,"...");
            return self::validateBlock($tmp);
        }

        
        // Qui controllo se è presente un Chii nel blocco, 
        // quindi se il blocco è di draghi o venti e arrivo qui,
        // non è sicuramente un blocco valido
        if ( $block[0][0] == DRAGON || $block[0][0] == WIND )
            return false;
        $value = intval($block[0][1]);

        $tmp = $block;
        // Controllo se la prima tessera forma un Chii con altre 2    
        $chii = [array_shift($tmp)];
        // DEBUG
        // var_error_log($chii);

        $i = 0;
        $sequence = 1;
        $offset = 0;

        // DEBUG
        debug_log("sono prima del foreach, value: $value");

        foreach ( $tmp as $t ) {
            // Se ho un gap, il blocco non è valido
            if ( $t[1] > $value + 1 )
                return false;
            if ( $t[1] == $value + 1) {
                // Ho trovato la tessera successiva, la estraggo
                $sequence++;
                $value++;
                $chii[] = array_splice($tmp, $offset, 1)[0];
                $offset--;
                // DEBUG
                debug_log("al giro $i ho chii[] = ". implode(",", $chii));
                $i++;
                if ( $sequence == 3 ) {
                    debug_log("valido ". implode(",", $tmp));
                    return self::validateBlock($tmp);
                }
            }
            $offset++;
        }
        return false;
    }

    /**
     * Data una mano vincente divisa in tessere nascoste e triplette aperte, trova i tipi di mano vincente che si possono individuare. 
     * Restituisce un array contenente i punteggi per ogni tipo di mano vincente trovata e il totale
     * @param string[] $concealedTiles tessere nascoste nella mano del giocatore
     * @param string $playerWind vento del giocatore
     * @param string[] $openTiles tessere delle triplette aperte, se presenti
     * @param int $winCondition se la vittoria è avvenuta per Ron o Tsumo
     * @return array[][] array contenente array associativi WinningHandType => Score
     */
    public static function getHandScore($concealedTiles, $playerWind, $openTiles = [], $winCondition = Action::RON) {
        // Inizializzo a true o a false per convenienza riguardo i controlli che verranno fatti successivamente
        $winningHands = [
            ALL_SIMPLE      => true,
            ALL_PON         => true,
            PLAYER_WIND_PON     => false,
            GREEN_DRAGON_PON    => false,
            RED_DRAGON_PON      => false,
            WHITE_DRAGON_PON    => false,
            FULLY_CONCEALED => false,
            HALF_FLUSH      => false,
            FULL_FLUSH      => true
        ];

        $block = self::extractBlocks($concealedTiles);
        $pair = [];
        self::extractPair( $block, $pair );
        // Rimetto insieme i blocchi, senza la coppia
        $concealedTiles = [];
        foreach( $block as $b ) {
            array_push($concealedTiles, ...$b);
        }

        // DEBUG
        // var_error_log($concealedTiles, "In function ".__FUNCTION__." ".__FILE__.": ".__LINE__.". Tessere nascoste, senza coppia: ");
        $visibility = empty($openTiles) ? CONCEALED : OPEN;
        // testo se ho una Fully Concealed Hand
        $winningHands[FULLY_CONCEALED] = ( $visibility == CONCEALED ) 
            && $winCondition == Action::TSUMO;
        
        $hand = $visibility == OPEN ? 
            array_merge( $concealedTiles, $openTiles )
            : $concealedTiles;
        // error_log(__FILE__.__LINE__.": ");
        // var_error_log($tmp);
        // foreach ( $tmp as $arr ) {
        //     array_push($hand, ...$arr);
        // }
        sort($hand);


        // Testo se ho un All Pon, uno o più Pon di draghi o un Pon del vento del giocatore
        $tmp = array_chunk($hand, 3);
        var_error_log($tmp, "Should be array of 3d arrays. In function ".__FUNCTION__." ".__FILE__.": ".__LINE__.". ");
        
        foreach ( $tmp as $t ) {
            // DEBUG
            // var_error_log($t, "Should be a Pon. In function ".__FUNCTION__." ".__FILE__.": ".__LINE__.". ");
            if ( !self::isPon(...$t) ) {
                $winningHands[ALL_PON] = false;
                continue;
            }
            // La mano è ordinata e tutti i blocchi di uno stesso seme hanno dimensione multipla di 3, quindi mi basta testare solo la prima tessera di una terna  
            $winningHands[PLAYER_WIND_PON] = $winningHands[PLAYER_WIND_PON]
                || $t[0] == WIND.$playerWind;
            $winningHands[GREEN_DRAGON_PON] = $winningHands[GREEN_DRAGON_PON]
                || $t[0] == DRAGON.GREEN; 
            $winningHands[RED_DRAGON_PON] = $winningHands[RED_DRAGON_PON]
                || $t[0] == DRAGON.RED;
            $winningHands[WHITE_DRAGON_PON] = $winningHands[WHITE_DRAGON_PON]
                || $t[0] == DRAGON.WHITE;
        }

        // Se ho draghi o venti ("honours"), non posso avere All Simple e Full Flush
        array_push($hand, ...$pair);
        // DEBUG
        debug_log("seziono la mano");
        var_error_log($hand, "...hand: ");
        // END DEBUG
        $reg = "/^[".DRAGON.WIND."]/";
        $honours = array_values( preg_grep($reg, $hand) );
        $numeric = array_values( array_diff($hand, $honours) );
        var_error_log($honours, "...honours: ");
        var_error_log($numeric, "...numeric: ");
        
        if ( !empty( $numeric ) )
        {
            $suit = $numeric[0][0];
            // RegEx valida sia per Full Flush che per Half Flush
            $regFlush = "/^[^$suit]/";
        }
        
        if ( !empty($honours) ) {
            $winningHands[ALL_SIMPLE] = false;
            $winningHands[FULL_FLUSH] = false;
        } 
        else {
            // Se non ho honours, rimangono da testare solo All Simple e Full Flush
            // Testo se ho un All Simple  
            if ( $winningHands[ALL_SIMPLE] ) {
                $winningHands[ALL_SIMPLE] =  empty( preg_grep("/[19]$/", $hand) ) ;
            }
            
            // Testo se ho un Full Flush, ovvero se tutte le tessere sono di un solo seme
            if ( $winningHands[FULL_FLUSH] ) {
                $winningHands[FULL_FLUSH] = empty( preg_grep($regFlush, $numeric) );
            }
            // Ho già escluso la possibilità di avere altre mani, posso calcolare il punteggio
            // DEBUG
            debug_log("Mani vincenti trovate...");
            var_error_log($winningHands, "...");
            // END DEBUG
            return self::calculateScore($winningHands, $visibility);
        }
        
        // Ho un Half Flush se tutte le tessere numeriche sono dello stesso seme
        $winningHands[HALF_FLUSH] = empty( preg_grep($regFlush, $numeric) );

        // DEBUG
        debug_log("Mani vincenti trovate...");
        var_error_log($winningHands, "...");
        // END DEBUG
        return self::calculateScore($winningHands, $visibility);
    } 

    // Calcola il punteggio effettivo della mano
    public static function calculateScore($winningHands, $visibility) {
        $result = [];
        $TotalScore = 0;
        $score = 0;
        foreach ( $winningHands as $key => $value) {
            if ( !$value )
                continue;
            $score = SCORE[$key][$visibility];
            $result[$key] = $score;
            $TotalScore += $score;
        }
        // Se la mano del giocatore non rispetta nessuno dei requisiti per una mano vincente "canonica",
        // dò 50 punti alla mano e la contrassegno come Base Hand
        if ( !$TotalScore ) {
            $result["Base Hand"] = BASE_HAND_SCORE;
            $TotalScore = BASE_HAND_SCORE;
        }
        // $result["Totale"] = $TotalScore;
        // DEBUG
        debug_log("mani e punteggi...");
        var_error_log($result, "...");
        // END DEBUG
        return $result;
    }

}
