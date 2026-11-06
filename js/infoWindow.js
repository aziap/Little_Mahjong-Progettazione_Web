"use strict";

class InfoWindow {
    
    constructor( id ) {
        this.infoWindowElem = document.createElement("div");
        this.infoWindowElem.id = id;
        this.infoWindowElem.classList.add("info-window");

        // dove scrivere i risultati
        const contentArea = this.infoWindowElem.appendChild( document.createElement("div") );
        contentArea.classList.add("info-content-area");
        const buttonArea = this.infoWindowElem.appendChild( document.createElement("div") );
        buttonArea.classList.add("info-button-area");
    }

    getInfoWindow() {
        return this.infoWindowElem;
    }

    getContentArea() {
        return this.infoWindowElem.querySelector(".info-content-area");
    }

    getButtonArea() {
        return this.infoWindowElem.querySelector(".info-button-area");
    }

    addButton(id, text, clickEventListener = null) {
        const button = this.getButtonArea().appendChild( document.createElement("button") );
        button.id = id;
        button.textContent = text;
        if ( clickEventListener != null )
            button.addEventListener("click", clickEventListener)
        return button;
    }

    addContent(content) {
        return this.getContentArea.appendChild(content);
    }

    showInfoWindow() {
        document.body.append(this.infoWindowElem);
    }

    closeInfoWindow() {
        return document.body.removeChild(this.infoWindowElem);
    }
}