<?php
namespace Bcgov\NaadConnector;

/**
 * Security headers for this API
 *
 * For Best Practices, see:
 * https://owasp.org/www-project-secure-headers/index.html#div-bestpractices_configuration-proposal
 */
$headers = [
    'Strict-Transport-Security' => 'max-age=63072000; includeSubDomains',
    'X-Frame-Options' => 'deny',
    'X-Content-Type-Options' => 'nosniff',
    'Content-Type' => 'application/json',
    'Content-Security-Policy' => "default-src 'self'; form-action 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; upgrade-insecure-requests; block-all-mixed-content", // Added default-src
    'X-Permitted-Cross-Domain-Policies' => 'none',
    'Referrer-Policy' => 'no-referrer',
    'Clear-site-Data' => "cache", "cookies", "storage",
    'Cross-Origin-Embedder-Policy' => 'require-corp',
    'Cross-Origin-Opener-Policy' => 'same-origin',
    'Cross-Origin-Resource-Policy' => 'same-origin',
    'Permissions-Policy' => 'ccelerometer=(), autoplay=(), camera=(), cross-origin-isolated=(), display-capture=(), encrypted-media=(), fullscreen=(), geolocation=(), gyroscope=(), keyboard-map=(), magnetometer=(), microphone=(), midi=(), payment=(), picture-in-picture=(), publickey-credentials-get=(), screen-wake-lock=(), sync-xhr=(self), usb=(), web-share=(), xr-spatial-tracking=(), clipboard-read=(), clipboard-write=(), gamepad=(), hid=(), idle-detection=(), interest-cohort=(), serial=(), unload=()',
    'Cache-Control' => 'no-store, max-age=0'
];

return $headers;