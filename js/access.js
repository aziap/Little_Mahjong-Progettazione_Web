"use strict";

const [EMPTY_FIELD, PATTERN_MISMATCH, INCORRECT_PASSWORD_REPEAT] = [0, 1, 3];

function showValidationError(flag, field = "") {
    let errorMessage;
    switch ( flag ) {
        case EMPTY_FIELD :
            errorMessage = "Tutti i campi devono essere compilati";
            break;
        case PATTERN_MISMATCH :
            errorMessage = "Il campo " + field + " non rispetta i requisiti";
            break;   
        case INCORRECT_PASSWORD_REPEAT :
            errorMessage = "I valori delle password non coincidono";
            break;
        default:
            errorMessage = "Errore sconosciuto";
    }
    alert(errorMessage);
}


function checkField() {
    field = document.querySelectorAll("#access input");
    for (f of field) {
        if ( f.isValid() )
            continue;
        if (f.value === "") {
            showValidationError(EMPTY_FIELD);
            return;
        }
        showValidationError(PATTERN_MISMATCH, f.getAttribute("name"))
        return;
    }
}

function handleInvalidField(event) {
    //  controllo se il campo è invalido perché vuoto
    if (event.currentTarget.value === "") {
        showValidationError(PATTERN_MISMATCH, event.currentTarget.getAttribute("name"));
        return;
    }
    // altrimenti è il pattern a non essere rispettato
    showValidationError(EMPTY_FIELD);
}



document.addEventListener("DOMContentLoaded", initAccessPage);

