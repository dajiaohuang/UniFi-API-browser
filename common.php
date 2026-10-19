<?php
/**
 * Copyright © 2024, Art of WiFi
 * www.artofwifi.net
 *
 * @license This file is subject to the MIT license bundled with this package in the file LICENSE.md
 */

require_once 'vendor/autoload.php';

use UniFi_API\Client as ApiClient;
use ArtOfWiFi\UnifiNetworkApplicationApi\UnifiConnector;

const TOOL_VERSION = '3.1.0';

/**
 * Gather some basic information for the About modal.
 */
$curl_info      = curl_version();
$unknown_string = 'unknown';

/**
 * Create the array to pass on to the twig templates.
 */
$about_modal_params = [
    'os_version'                   => php_uname('s') . ' ' . php_uname('r'),
    'php_version'                  => phpversion(),
    'memory_limit'                 => ini_get('memory_limit') . 'B',
    'memory_used'                  => round(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB',
    'curl_version'                 => $curl_info['version'],
    'openssl_version'              => $curl_info['ssl_version'],
    'api_client_version'           => getClientVersion(),
    'official_api_client_version'  => getOfficialClientVersion(),
    'api_browser_version'          => TOOL_VERSION,
];

/**
 * The version of the included classic API client class.
 *
 * @return string
 */
function getClientVersion(): string
{
    $unifi_connection = new ApiClient('user', 'password');
    return $unifi_connection->get_class_version();
}

/**
 * The version of the included official API client class.
 *
 * @return string
 */
function getOfficialClientVersion(): string
{
    return UnifiConnector::VERSION;
}

/**
 * Whether access to this tool requires a login.
 *
 * Mirrors the rule applied by index.php: login is required when config/users.php exists and defines a non-empty
 * $users array. The file is loaded in function scope so it does not leak variables into the caller.
 *
 * @return bool true when users.php defines user accounts
 */
function isUserAuthenticationEnabled(): bool
{
    $users_file = __DIR__ . '/config/users.php';

    if (!is_file($users_file) || !is_readable($users_file)) {
        return false;
    }

    $users = null;
    require $users_file;

    return is_array($users) && count($users) > 0;
}

/**
 * Stop an AJAX request when a login is required and the session is not logged in.
 *
 * Must be called after session_start(). Responds with HTTP 401 and the JSON structure the front end expects, so
 * that the error is shown to the user instead of ending in a silent failure.
 *
 * @return void
 */
function requireAjaxAuthentication(): void
{
    if (!isUserAuthenticationEnabled() || !empty($_SESSION['logged_in'])) {
        return;
    }

    http_response_code(401);
    returnJson([
        'state'   => 'error',
        'message' => 'You are not logged in or your session has expired, please reload the page and log in.',
        'data'    => [],
    ]);

    exit;
}

/**
 * Collect the methods offered by a set of collection menus.
 *
 * Walks the nested menu structure (groups with 'options', which may contain further 'options') and returns the
 * allowed method identifiers. For classic controllers the identifier is the client method name; for official
 * controllers it is "resource::method".
 *
 * @param array $collections menu definition as built by collections.php, collections-official.php and config.php
 * @param bool $official true to build "resource::method" identifiers for the official API
 * @return array<string, true> set of allowed method identifiers
 */
function getAllowedCollectionMethods(array $collections, bool $official = false): array
{
    $allowed = [];

    foreach ($collections as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        if (!empty($entry['options']) && is_array($entry['options'])) {
            $allowed += getAllowedCollectionMethods($entry['options'], $official);
        }

        if (($entry['type'] ?? '') !== 'collection' || empty($entry['method']) || !is_string($entry['method'])) {
            continue;
        }

        if ($official) {
            if (!empty($entry['resource']) && is_string($entry['resource'])) {
                $allowed[$entry['resource'] . '::' . $entry['method']] = true;
            }
        } else {
            $allowed[$entry['method']] = true;
        }
    }

    return $allowed;
}

/**
 * Load the menu definition for official API controllers.
 *
 * collections-official.php defines the same $collections variable as collections.php, so it is loaded in function
 * scope to keep both definitions apart.
 *
 * @return array menu definition for official API controllers
 */
function getOfficialCollections(): array
{
    $collections = [];
    require __DIR__ . '/collections-official.php';

    return $collections;
}

/**
 * Verify a password against the value stored in config/users.php.
 *
 * Accepts a hash produced by password_hash() (bcrypt, argon2) and, for backward compatibility, the raw SHA-512 hex
 * digest of the password (compared case-insensitively and in constant time).
 *
 * @param string $password the password as entered by the user
 * @param string $stored_hash the 'password' value from config/users.php
 * @return bool true when the password matches
 */
function verifyUserPassword(string $password, string $stored_hash): bool
{
    $stored_hash = trim($stored_hash);

    if ($password === '' || $stored_hash === '') {
        return false;
    }

    if (password_get_info($stored_hash)['algo'] !== null) {
        return password_verify($password, $stored_hash);
    }

    if (preg_match('/^[0-9a-f]{128}$/i', $stored_hash) === 1) {
        return hash_equals(strtolower($stored_hash), hash('sha512', $password));
    }

    return false;
}

/**
 * Replace the {site_uid} placeholder in string parameters with the _id (UUID) of the selected site.
 *
 * The Network Application 11.x statistics engine (stat_series()) filters on the site _id rather than on the short
 * site name. fetch_sites.php stores the name => _id map in the session; when the map is missing (e.g. an older
 * session) the _id is looked up through list_sites(). When it still cannot be resolved, the parameter is dropped
 * (null) so that the request succeeds without a site filter.
 *
 * @param array $params parameters as defined in the collection
 * @param string $site_id short name of the selected site
 * @param ApiClient $unifi_connection logged-in classic API client instance
 * @return array parameters with the placeholder substituted
 */
function substituteSiteUid(array $params, string $site_id, ApiClient $unifi_connection): array
{
    $needs_uid = false;

    foreach ($params as $param) {
        if (is_string($param) && str_contains($param, '{site_uid}')) {
            $needs_uid = true;
            break;
        }
    }

    if (!$needs_uid) {
        return $params;
    }

    $site_uid = $_SESSION['site_uids'][$site_id] ?? '';

    if ($site_uid === '') {
        try {
            foreach ($unifi_connection->list_sites() as $site) {
                if (($site->name ?? '') === $site_id && !empty($site->_id)) {
                    $site_uid = $site->_id;
                    $_SESSION['site_uids'][$site_id] = $site_uid;
                    break;
                }
            }
        } catch (Exception $e) {
            error_log('unable to resolve the site _id: ' . $e->getMessage());
        }
    }

    foreach ($params as $key => $param) {
        if (is_string($param) && str_contains($param, '{site_uid}')) {
            $params[$key] = $site_uid !== '' ? str_replace('{site_uid}', $site_uid, $param) : null;
        }
    }

    return $params;
}

/**
 * Output the results with correct JSON formatting.
 *
 * @param $results
 * @return void
 */
function returnJson($results): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo(json_encode($results));
}
