<?php
class EndCondition {
    // Pareggio
    public const DRAW = 0;
    // Un giocatore vince chiamando la tessera vincente scartata dall'avversario
    public const RON = 1;
    // Un giocatore vince pescando la tessera vincente
    public const TSUMO = 2;
    public const SURRENDER = 3;
}