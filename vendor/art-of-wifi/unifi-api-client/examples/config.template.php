<?php
/**
 * Copyright (c) 2021, Art of WiFi
 *
 * This file is subject to the MIT license that is bundled with this package in the file LICENSE.md
 */

/**
 * Controller configuration
 * ===============================
 * Copy this file to your working directory, rename it to config.php and update the section below with your UniFi
 * controller details and credentials
 */
$controlleruser     = ''; // the user name for access to the UniFi Controller (leave empty when using an API key)
$controllerpassword = ''; // the password for access to the UniFi Controller (leave empty when using an API key)
$controllerurl      = ''; // full url to the UniFi Controller, eg. 'https://22.22.11.11:8443', for UniFi OS-based
                          // controllers a port suffix isn't required, no trailing slashes should be added
$controllerversion  = ''; // the version of the Controller software

/**
 * API key authentication (optional, UniFi OS only)
 * ====================================================
 * If you prefer to use API key authentication instead of username/password, set the API key below.
 * When set, the username and password above are ignored. Generate an API key in the UniFi OS console
 * under Integrations > Create New API Key.
 */
$controllerapikey = ''; // API key for UniFi OS-based controllers (leave empty to use username/password)

/**
 * set to true (without quotes) to enable debug output to the browser and the PHP error log
 */
$debug = false;
