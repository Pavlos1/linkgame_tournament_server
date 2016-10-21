<?php
    session_name('linkgame_session_id');
    // TODO: change this once TLS is up & running
    $secure = false;
    $httponly = true;
    ini_set('session-use_only_cookies', 1);
    ini_set("session.entropy_file", "/dev/urandom");
    ini_set("session.entropy_length", "512");

    $cookieParams = session_get_cookie_params();
    session_set_cookie_params($cookieParams["lifetime"],
        $cookieParams["path"], 
        $cookieParams["domain"], 
        $secure,
        $httponly);

    session_start();
    if (!isset($_SESSION['csrf'])) { $_SESSION['csrf'] = base64_encode( openssl_random_pseudo_bytes(32) ); }

    // User has submitted login form
    if (isset($_POST['uid']) && isset($_POST['password']) && isset($_POST['what']) && ($_POST['what'] === 'login')) {
        // TODO: Figure out how LDAP works

        if ((!isset($_POST['csrf'])) || (!($_POST['csrf'] === $_SESSION['csrf']))) {
            showLogin('CSRF verification failed.');
        }

        if (($_POST['uid'] === '') || ($_POST['password'] === '')) {
            showLogin('Username and password fields cannot be blank.');
        }

        if (!ctype_alnum($_POST['uid'])) {
            showLogin('UID cannot have special characters');
        } 
        
        $ds = ldap_connect("ldaps://csitldap.anu.edu.au/", 389);
        if (! $ds) {
            showLogin('LDAP server seems to be down. Please try again later.');
        }
        $rb = ldap_bind($ds, $_POST['uid'], $_POST['password']);
        if (! $rb) {
            showLogin('Credentials incorrect.');
        }
        
        // User authenticated
        $_SESSION['uid'] = $_POST['uid'];
        $_SESSION['last_access'] = time();
    }

    // Is the user already logged in?
    if (isset($_SESSION['uid'])) {
        // Has the session timed out?
        if (isset($_SESSION['last_access']) && (time() - $_SESSION['last_access'] <= 1800)) {
            $uid=$_SESSION['uid'];
            $_SESSION['last_access'] = time();
        } else {
            showLogin('Your session has timed out.');
        }
    } else {
            showLogin();
    }

    function showLogin($message='') {
?>
    <html>
        <h1>Login Required</h1>
        <?php if (isset($message)) { echo("<p>$message</p>"); } ?>
        <h3>Please enter your ANU credentials</h3>
        <p>(These will not be stored on the server.)</p>
        <form method="post" action="/index.php">
        <input type="hidden" name="what" value="login"/>
        <input type="hidden" name="csrf" value="<?=$_SESSION['csrf']?>"/>
        <table>
            <tr>
                <td><p>Username: </p></td>
                <td><input type="text" name="uid"/></td>
            </tr>
            <tr>
                <td><p>Password: </p></td>
                <td><input type="password" name="password"/></td>
            </tr>
            <tr><td><button type="submit">Login</button></td></tr>
        </table>
        </form>
    </html>
<?php
    exit();
    }
?>
