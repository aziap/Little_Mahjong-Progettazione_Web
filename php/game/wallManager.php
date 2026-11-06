<?php
require_once __DIR__."/gameConfig.php";


class WallManager {
    private $wall = [];
    // Numero di tessere pescate dal muro
    private $drawnTilesCount = 0;
    // Se il setup della partita è stato fatto nel processo del'avversario,
    // devo copiare il muro generato da quest'ultimo, leggendolo dal DB.
   public  function __construct($wall = "") {
        if ( $wall != "" ) {
            $this->wall = explode(",",$wall);
            return;
        }
        $this->buildWall();
        shuffle( $this->wall );
    }

    
    private function getTilesOfSuit($suit, $valueArray) {
        // ogni tessera è una stringa con la struttura "<seme><valore>_<indiceCopia>"
        $tiles = [];
        for ( $i = 0; $i < count($valueArray); $i++ ) {
            $copyId = 0;
            while ( $copyId < NUM_TILE_COPIES ) {
                // $tiles[] = $suit.$valueArray[$i]."_".$copyId++;
                $copyId++;
                $tiles[] = $suit.$valueArray[$i];
            }
        }
        return $tiles;
    }
    
    private function buildWall() {
        // creo le tessere delle suit bamboo e pin e le aggiungo al muro
        $tmp = array_fill( MIN_NUMERIC_VALUE, MAX_NUMERIC_VALUE, true );
        $numericValues = [ ...array_keys($tmp) ];
        foreach (NUMERIC_SUIT as $ns) {
            $this->wall = array_merge( $this->wall, $this->getTilesOfSuit( $ns, $numericValues) );
        }
        // aggiungo le tessere drago
        $this->wall = array_merge( $this->wall, $this->getTilesOfSuit(DRAGON, DRAGON_VALUE) );
        // aggiungo le tessere vento
        $this->wall = array_merge( $this->wall, $this->getTilesOfSuit(WIND, WIND_VALUE) );
    }
    public function getWall() {
        return $this->wall;
    }
    
    public function getDrawnTilesCount() {
        return $this->drawnTilesCount;
    }

    public function extractStartingHand() {
        $hand = array_slice($this->wall, $this->drawnTilesCount, HAND_SIZE);
        $this->drawnTilesCount += HAND_SIZE;
        if ($this->drawnTilesCount > TOT_TILES){
            error_log("Error: non ci sono più tessere nel muro!. ".$this->drawnTilesCount." tessere già pescate.");

        }
        return $hand;
    }

    // Preleva la prossima tessera dal muro. Restituisce null se non ci sono più tessere
    public function extractTile() {
        if ($this->drawnTilesCount + 1 > TOT_TILES)
            error_log("Error: non ci sono più tessere nel muro!. ".$this->drawnTilesCount." tessere già pescate.");
        return $this->drawnTilesCount == TOT_TILES ? null : $this->wall[ $this->drawnTilesCount++ ];
    }

    // incrementa il contatore di tessere pescate di $drawnTiles
    public function incrementDrawnTileCount($drawnTilesNumber = 1) {
        $this->drawnTilesCount += $drawnTilesNumber;
    }

}