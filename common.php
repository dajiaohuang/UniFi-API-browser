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

const TOOL_VERSION = '3.0.0';

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
