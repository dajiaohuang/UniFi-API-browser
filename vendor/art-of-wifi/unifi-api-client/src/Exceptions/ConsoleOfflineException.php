<?php

namespace UniFi_API\Exceptions;

/**
 * Exception thrown when a console does not respond via the Site Manager proxy (HTTP 408).
 *
 * This typically indicates the console is offline, unreachable, or running firmware older than 5.0.3.
 *
 * @package UniFi_Controller_API_Client_Class
 */
class ConsoleOfflineException extends UnifiApiException
{
}
