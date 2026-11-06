
        <form id="access" method="post" action="./php/access/checkLogin.php">
            <label for="username">Username:</label>
            <input id="username" name="username" type="text" placeholder="Username" pattern="^[A-Za-z0-9_]{2,20}$" required autofocus>

            <label for="password">Password:</label>
            <input id="password" name="password" type="password" placeholder="Password" pattern="^\S{8,255}$" required>

            <input type="submit" id="submit-btn" class="confirm-button" name="login-btn" value="Login">
            <em id="switch-form">
                Non sei ancora registrato?
                <a href="./index.php?accessMethod=signup" id="link-registrazione">Registrati!</a>
            </em>
        </form>