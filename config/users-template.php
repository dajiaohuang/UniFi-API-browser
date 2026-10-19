<?php
/**
 * Copyright © 2024, Art of WiFi
 * www.artofwifi.net
 *
 * @license This file is subject to the MIT license bundled with this package in the file LICENSE.md
 */

/**
 * INSTRUCTIONS
 * =============
 * If you wish to implement restricted access to this tool based on username and password,
 * please follow these steps and guidelines:
 *
 * - create a copy of this file, name it users.php and store in the same directory
 * - in this new file, populate the array below with user accounts as required
 * - please make sure not to have any empty entries!
 * - please take care in keeping the PHP syntax for the $users array intact
 * - please make sure not to create any duplicate user_name values
 *
 * Password hashing
 * ================
 * Two hash formats are accepted for the 'password' value:
 *
 * 1. RECOMMENDED — a bcrypt/argon2 hash produced by PHP's password_hash(). Generate one with:
 *      php -r "echo password_hash('your_password', PASSWORD_DEFAULT), PHP_EOL;"
 *    The resulting string starts with "$2y$" (bcrypt) or "$argon2..." and is verified with
 *    password_verify().
 *
 * 2. LEGACY — the raw SHA-512 hex hash of the password (still supported for backward compatibility,
 *    but weaker because it is unsalted and fast). Generate one with:
 *      On Linux/macOS:  echo -n 'your_password' | sha512sum | cut -d' ' -f1
 *      On Windows:      'your_password' | openssl dgst -sha512
 *
 * IMPORTANT NOTE:
 * If you do not create the users.php file or do not create any user accounts, the UniFi API Browser tool
 * will be accessible without providing any means of authentication.
 */
$users = [
    [
        'user_name' => '', // string, the username
        'password'  => '', // string, a password_hash() hash (recommended) or the SHA-512 hex hash of the password
    ],
    [
        'user_name' => '', // string, the username
        'password'  => '', // string, a password_hash() hash (recommended) or the SHA-512 hex hash of the password
    ],
];
