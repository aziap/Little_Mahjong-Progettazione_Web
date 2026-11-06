
<form id="access" method="post" action="./php/access/checkSignup.php">
    <div class="tooltip-container">
        <label for="username">Username:</label>
        <div class="tooltip">?
            <span class="tooltip-text">Da 2 a 20 caratteri. Usare lettere maiuscole, minuscole, numeri e underscore (_).</span>
        </div>
    </div>
    <input id="username" name="username" type="text" placeholder="Username" pattern="^[A-Za-z0-9_]{2,20}$" required autofocus>

    <div class="tooltip-container">
        <label for="password">Password:</label>
        <div class="tooltip">?
            <span class="tooltip-text">Almeno 8 caratteri qualsiasi.</span>
        </div>
    </div>
    <input id="password" name="password" type="password" placeholder="Password" pattern="^\S{8,255}$" required>

    <label for="password-repeat">Conferma la password:</label>
    <input id="password-repeat" name="password-repeat" type="password" placeholder="Password" pattern="^\S{8,255}$" required>

    <input type="submit" id="submit-btn" class="confirm-button" name="signup-btn" value="Registrati">
    <em id="switch-form">
        Sei gi&agrave; registrato?
        <a href="./index.php?accessMethod=login" id="link-login">Vai al login</a>
    </em>
</form>