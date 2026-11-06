# README

## Introduzione
Progetto realizzato per l'esame di Progettazione Web, sostenuto a gennaio 2025.

Si tratta di una versione a due giocatori e con regole semplificate del riichi mahjong. È possibile consultare un manuale introduttivo avviando il progetto nel browser e cliccando sul "Leggi il manuale"; alternativamente lo si può trovare sotto forma di codice PHP in `Little_Mahjong-Progettazione_Web/php/manuale.php`.

## Setup in ambiente locale

Per eseguire il progetto in locale è necessario disporre di un ambiente server che supporti PHP e MySQL. Si consiglia l'utilizzo di [XAMPP](https://www.apachefriends.org/), che include Apache, PHP e MySQL (MariaDB).

Di seguito sono riportati i passaggi necessari per avviare il progetto:

1. Installare XAMPP e avviare il relativo Control Panel.
2. Copiare la cartella del progetto all'interno della directory `htdocs`, presente nella cartella di installazione di XAMPP (ad esempio `C:\xampp\htdocs\`).
3. Avviare i servizi **Apache** e **MySQL** dal Control Panel di XAMPP.
4. Accedere a phpMyAdmin tramite l'indirizzo `http://localhost/phpmyadmin`.
5. Creare un nuovo database denominato `perroni_596654` e importare al suo interno il file `perroni_596654.sql`, presente nella cartella principale del progetto. Per effettuare l'importazione, selezionare il database appena creato, accedere alla scheda "Importa", scegliere il file SQL e avviare la procedura.
6. Verificare che i parametri di connessione al database presenti nel file `php/db/dbConfig.php` siano compatibili con la configurazione locale di MySQL.

Con la configurazione standard di XAMPP, i parametri di connessione al database sono i seguenti:
- Host: `localhost`
- Username: `root`
- Password: (vuota)
- Porta: `3306`

Una volta completati questi passaggi, il progetto sarà accessibile tramite browser all'indirizzo:

`http://localhost/Little_Mahjong-Progettazione_Web/`

