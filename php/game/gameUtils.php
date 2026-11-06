<?php

// include_once __DIR__."/gameConfig.php";
// function sortHand($hand) {
//     usort($hand, function($s1, $s2) {
//         // 
//     } );
// } 

// function getPlayerByWind($wind, $playerArray) {

// }

// per il debug
function printRoundState($roundManager, $wallManager) {
$roundInfo = [];
$roundInfo["Your name"] = $_SESSION["player"]->getName();
$roundInfo["Your wind"] = $_SESSION["player"]->getWind();
$roundInfo["Opponent name"] = $_SESSION["opponent"]->getName();
$roundInfo["Opponent's wind"] = $_SESSION["opponent"]->getWind();
$roundInfo["Current turn"] = $roundManager->getActivePlayerWind();
$roundInfo["Tiles remaining in the wall"] = TOT_TILES - $wallManager->getDrawnTilesCount();

echo "<table>";
foreach( $roundInfo as $key => $value) {
    echo "<tr>";
    echo "<td>$key:</td>";
    echo "<td>$value</td>";
    echo "</tr>";
}
echo "</table>";
}

// per il debug
function actionToString( $action ) {
    switch($action) {
        case Action::NO_NEW_ACTION :
            return "no new action";
        case Action::DRAW_STARTING_HAND :
            return "draw starting hand";
        case Action::DRAW_TILE :
            return "draw a tile";
        case Action::DISCARD_TILE :
            return "discard a tile";
        case Action::PASS :
            return "pass";
        case Action::PON :
            return "call pon";
        case Action::CHII :
            return "call chii";
        case Action::RON :
            return "declare ron";
        case Action::TSUMO :
            return "declare tsumo";
        case Action::SURRENDER :
            return "surrender";
        case Action::END_BY_DRAW :
            return "draw (end)";
        case Action::READ_RESULT :
            return "read round results";
        default :
            return "unknown action"; 
    }
}

// per il debug
function phaseToString( $phase ) {
    switch ( $phase ) {
        case Phase::SETUP :
            return "setup phase";
        case Phase::TILE_DRAW :
            return "tile draw phase";
        case Phase::DISCARD :
            return "discard phase";
        case Phase::CALL :
            return "call phase";
        case Phase::HAND_DRAW :
            return "hand drawing phase";
        case Phase::ROUND_END :
            return "round end phase";
        default :
        return "unknown phase";
    }
}

function clearGameSession() {
    unset($_SESSION["wallManager"]);
    unset($_SESSION["wallManager"]);
    unset($_SESSION["roundManager"]);
    unset($_SESSION["matchId"]);
    unset($_SESSION["opponent"]);
    unset($_SESSION["player"]);
}