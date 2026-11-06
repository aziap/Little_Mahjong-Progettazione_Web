<?php

// Mani vincenti

const ALL_SIMPLE = "All Simple";
const ALL_PON = "All Pon";
const PLAYER_WIND_PON = "Player Wind";
const GREEN_DRAGON_PON = "Green Dragon";
const RED_DRAGON_PON = "Red Dragon";
const WHITE_DRAGON_PON = "White Dragon";
const FULLY_CONCEALED = "Fully Concealed Hand";
const HALF_FLUSH = "Half Flush";
const FULL_FLUSH = "Full Flush";

const BASE_HAND_SCORE = 50;


const CONCEALED = 0;
const OPEN = 1;

// Tabella dei punteggi

const SCORE = [
    ALL_SIMPLE => [
        CONCEALED   => 100,
        OPEN        => 100
    ],

    ALL_PON => [
        CONCEALED   => 800,
        OPEN        => 200
    ],

    PLAYER_WIND_PON => [
        CONCEALED   => 100,
        OPEN        => 100
    ],

    GREEN_DRAGON_PON => [
        CONCEALED   => 100,
        OPEN        => 100
    ],

    RED_DRAGON_PON => [
        CONCEALED   => 100,
        OPEN        => 100
    ],

    WHITE_DRAGON_PON => [
        CONCEALED   => 100,
        OPEN        => 100
    ],

    FULLY_CONCEALED => [
        CONCEALED   => 100,
        OPEN        => 0
    ],

    HALF_FLUSH => [
        CONCEALED   => 300,
        OPEN        => 200
    ],

    FULL_FLUSH => [
        CONCEALED   => 600,
        OPEN        => 500
    ]
];