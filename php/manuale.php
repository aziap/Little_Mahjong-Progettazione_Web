<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="author" content ="Asia Perroni">
    <meta name="keywords" content ="game">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="./../css/img/favicon-16x16.png">
    <link rel="stylesheet" href="./../css/main.css" type="text/css" media="screen">
    <link rel="stylesheet" href="./../css/docu.css" type="text/css" media="screen">
    <title>Little Mahjong - Manuale</title>
</head>

<body id="docu-wrapper">
    <header id="manuale-titolo">
        <h1>
            Manuale
        </h1>
    </header>
    <div id="docu-container" class="user-content-container">
        <section id="istruzioni">
            <h2>
                Istruzioni d'uso        
            </h2>
            <p>
                Little Mahjong si propone di offrire una versione "ridotta" del <em>Mahjong</em>, gioco da tavolo multigiocatore popolare specialmente in Cina e in Giappone, e vagamente somigliante per alcuni aspetti a giochi di carte occidentali come il Poker o la Scala Quaranta. La semplificazione maggiore &eacute; data dal fatto che le partite in questa applicazione sono da 2 giocatori anzich&eacute; da 4, e che durino un solo round.
            </p>
            <p>
                Per cominciare a giocare, bisogna prima registrarsi al sito, scegliendo uno username e una password. 
            </p>
            <p>
                Fatto questo, attraverso la propria Home &eacute; possibile registrarsi per giocare una partita premendo il bottone <em>Gioca</em>, e attendendo che un qualsiasi altro giocatore si registri a sua volta.
            </p>
        </section>

        <section id="gioco">
            <h2>
                Regole del gioco
            </h2>
            
            <section id="scopo">
                <h3>
                    Scopo del gioco
                </h3>
                <p>
                    Ogni giocatore ha la sua mano composta da 13 tessere pi&uacute; una aggiunta. Durante lo svolgimento del round, si pescano e scartano tessere con lo scopo di formare una <em>mano valida</em> di 14 tessre, ovvero scomponibile in 4 blocchi da 3 e una coppia di tessere identiche.
                </p>
                <p>
                    Ad ogni turno, un giocatore pesca una tessera dal muro, e sceglie una tessera dalla sua mano da scartare, che pu&oacute; anche essere quella che ha appena pescato. L'avversario pu&oacute; rubare la tessera scartata per completare una delle sue triplette. Il round termina quando un giocatore pesca la sua tessera vincente, oppure la ruba all'avversario.
                </p>
            </section>

            <section id="tessere">
                <h3>
                    Tessere
                </h3>
                <p>
                    Le tessere hanno un seme e un valore, similmente al Poker. I semi sono divisi in 2 macro-categorie: <em>numerici</em> e <em>onori</em>. I semi numerici, in questa versione del gioco, sono 2: <em>pin</em> e <em>bamb&uacute;</em>. Entrambi i tipi hanno valori che vanno dal'1 al 9.
                </p>
                <figure>
                    <img src="./../css/img/pin-bamboo.png" style="width:50%">
                    <figcaption>Pin (sopra) e bamb&uacute; (sotto)</figcaption>
                </figure>
                <p>
                    Anche gli onori sono di 2 tipi: <em>draghi</em> e <em>venti</em>. &Eacute; da notare che in questa versione si usano solo 2 dei 4 venti.
                </p>
                <div id="img-onori">
                    <figure>
                        <img src="./../css/img/winds.png">
                        <figcaption>Venti</figcaption>
                    </figure>
                    <figure>
                        <img src="./../css/img/dragons.png">
                        <figcaption>Draghi</figcaption>
                    </figure>
                </div>
                <p>Ogni tessera è presente in 4 copie nel <em>muro</em> (l'equivalente del mazzo).</p>
            </section>

            <section id="blocchi">
                <h3>
                    Blocchi validi
                </h3>
                <p>I blocchi validi da 3 sono di 2 tipi: le scale (<em>Chii</em>) e le triplette di tessere identiche (<em>Pon</em>).</p>
                <h4 id="chii">
                    Chii
                </h4>
                <p>
                    I Chii si possono fare solo con <strong>tessere numeriche dello stesso seme</strong>, e sono scale non cicliche, ad esempio 1-2-3 di pin &eacute; un Chii valido, 8-9-1 no.   
                </p>
                <h4 id="pon">
                    Pon
                </h4>
                <p>
                    I Pon possono essere fatti con qualunque tipo di tessera, purch&eacute; abbiano stesso seme e stesso valore.
                </p>
                <p>
                    Per comporre delle triplette, si possono <em>chiamare</em> le tessere scartate dall'avversario, se se ne hanno nella mano 2 con cui completarla. Le <em>triplette aperte</em> vengono messe da parte, valgono nel conteggio delle 14 tessere, ma non possono essere modificate. 
                    Importante: quando si clicca sul bottone Chii o Pon, bisogna selezionare 2 tessere con cui comporre la tripletta. Ad esempio, se l'avversario ha scartato il 4 di pin e in mano ho il 5 e il 6, deve cliccare su quelle tessere. A quel punto, si abiliterà il punlsante "Conferma". Premuto questo, <strong>il giocatore che ha fatto la chiamata, deve scartare una tessera.</strong> Se durante la selezione si preme il pulsante "Cancella", invece, non verr&aacute; chiamata la tessera, e si passer&aacute; direttamente alla fase di scarto.
                </p>
            </section>

            <section id="mani-vincenti">
                <h3>
                    Mani vincenti
                </h3>
                <p>
                    Si pu&oacute; vincere in 2 modi: pescando la propia <em>tessera vincente</em> (<em>Tsumo</em>) o chiamandola dall'avversario (<em>Ron</em>). Se le tessere terminano senza che nessun giocatore sia riuscito a comporre una mano vincente, si ha un pareggio.
                </p>
                <h3>
                    Mani vincenti
                </h3>
                <p>
                    Una mano valida pu&oacute; rientrare in pi&uacute; tipi di mano vincente. Di seguito una breve descrizione delle mani vincenti usate in questa versione del gioco.
                </p>
                <table id="mani-vincenti-tab">
                    <tr>
                        <td>
                            All Simple
                        </td>
                        <td>
                            Solo semi numerici con valori da 2 a 8 compresi.
                        </td>
                    </tr>

                    <tr>
                        <td>
                            All Pon
                        </td>
                        <td>
                            Tutti i blocchi da 3 tessere sono Pon.
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Fully Concealed
                        </td>
                        <td>
                            Non si hanno triplette aperte e la tessera vincente è stata pescata (Tsumo).
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Green / Red / White Dragon
                        </td>
                        <td>
                            Si ha uno o pi&uacute; Pon di draghi. 
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Player Wind
                        </td>
                        <td>
                            Si ha un Pon di venti corrispondente al proprio vento del round. 
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Half Flush
                        </td>
                        <td>
                            Si hanno tessere numeriche di un solo tipo (pin o bamb&uacute;) e onori. 
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Full Flush
                        </td>
                        <td>
                            Si hanno solo tessere numeriche di un solo seme. 
                        </td>
                    </tr>

                </table>
                

            </section>

        </section>

    </div>

</body>