<?php
/**
 * Copyright © 2024, Art of WiFi
 * www.artofwifi.net
 *
 * This file is subject to the MIT license that is bundled with this package in the file LICENSE.md
 */

/**
 * load required files
 */
require_once 'vendor/autoload.php';
require_once 'common.php';
require_once 'collections.php';

/**
 * load the configuration file if readable
 */
if (is_file('config/config.php') && is_readable('config/config.php')) {
    require_once 'config/config.php';
} else {
    exit;
}

/**
 * load the file containing user accounts, if readable
 */
if (is_file('config/users.php') && is_readable('config/users.php')) {
    require_once 'config/users.php';
} else {
    exit;
}

/**
 * in order to use the PHP $_SESSION array for temporary storage of variables, session_start() is required
 */
session_start();

$_SESSION['login_error_message'] = 'user name and password do not match, please try again';
$_SESSION['logged_in']           = false;

/**
 * load login form data if present, then login to test
 */
if (isset($_POST['user_name']) && !empty($_POST['user_name']) && isset($_POST['password']) && !empty($_POST['password'])) {
    $user_name = (string) $_POST['user_name'];
    $password  = (string) $_POST['password'];

    /**
     * check the user accounts whether we allow the user to login, if so, we change the value of $_SESSION['logged_in'] to true
     * - password_hash() hashes (bcrypt/argon2) and legacy SHA-512 hex hashes are both accepted, see verifyUserPassword()
     */
    if (!empty($users) && is_array($users)) {
        foreach ($users as $user) {
            if (empty($user['user_name']) || empty($user['password'])) {
                continue;
            }

            if ($user['user_name'] === $user_name && verifyUserPassword($password, (string) $user['password'])) {
                /**
                 * we have a matching user_name/password combination, issue a fresh session id to prevent session fixation
                 */
                session_regenerate_id(true);
                error_log('successful login for user ' . str_replace(["\r", "\n"], ' ', $user_name));
                $_SESSION['logged_in']           = true;
                $_SESSION['login_error_message'] = '';
                break;
            }
        }

        if ($_SESSION['logged_in'] === false) {
            error_log('failed login attempt for user ' . str_replace(["\r", "\n"], ' ', $user_name));
        }
    }
}

header('Location: ' . dirname($_SERVER['REQUEST_URI']));
exit;
