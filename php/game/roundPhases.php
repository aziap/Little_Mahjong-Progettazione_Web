<?php
class Phase {
    // Le prime 3 fasi si ripetono ciclicamente
    // nello svolgimento del turno
    public const CALL = 0;
    public const TILE_DRAW = 1;
    public const DISCARD = 2;
    // Le ultime 3 avvengono una sola volta per round
    public const SETUP = 3;
    public const HAND_DRAW = 4;
    public const ROUND_END = 5;
}
