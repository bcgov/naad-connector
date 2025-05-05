<?php
namespace Bcgov\NaadConnector;

/**
 * Security headers for this API
 *
 * For Best Practices, see:
 * https://owasp.org/www-project-secure-headers/index.html#div-bestpractices_configuration-proposal
 *
 * Many headers do not apply, since this POST request does not reach a browser,
 * but another API.
 */
$headers = [
    'Accept' => 'application/json',
    'Accept-Encoding' => 'gzip, deflate',
    'Content-Type' => 'application/json',
    'User-Agent' => 'bcgov/naad-connector/1.0.0',
    'X-Content-Type-Options' => 'nosniff',
    'X-Requested-With' => 'XMLHttpRequest', // Signals this is non-browser request.
];

return $headers;
