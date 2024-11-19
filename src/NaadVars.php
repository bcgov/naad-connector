<?php

namespace Bcgov\NaadConnector;

/**
 * NaadVars class holds environment vars and passes them around
 * to modules that need them.
 *
 * @category Client
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alertsarchive.pelmorex.com/en.php
 */
class NaadVars
{

    protected string $naadPort;
    protected string $phpMyAdminPort;
    protected string $rootPassword;
    protected string $serviceHost;
    protected string $servicePort;
    protected string $databaseName;
    protected string $destinationUser;
    protected string $destinationPassword;
    protected string $destinationURL;

    /**
     * Constructor for NaadVars.
     *
     * @throws \RuntimeException if any required environment variable is not set.
     */
    public function __construct()
    {
        $this->rootPassword = $this->_getEnv("MARIADB_ROOT_PASSWORD");
        $this->serviceHost = $this->_getEnv("MARIADB_SERVICE_HOST");
        $this->servicePort = $this->_getEnv("MARIADB_SERVICE_PORT");
        $this->databaseName = $this->_getEnv("MARIADB_DATABASE");
        $this->destinationUser   = $this->_getEnv("DESTINATION_USER");
        $this->destinationPassword = $this->_getEnv("DESTINATION_PASSWORD");
        $this->destinationURL = $this->_getEnv("DESTINATION_URL");
        $this->naadPort = $this->_getEnv("NAAD_PORT");
        $this->phpMyAdminPort = $this->_getEnv("PHPMYADMIN_PORT");
    }

    /**
     * Get an environment variable or throw an exception if not set.
     *
     * @param string $key The name of the environment variable.
     *
     * @return string The value of the environment variable.
     *
     * @throws \RuntimeException if the environment variable is not set.
     */
    private function _getEnv(string $key): string
    {
        $value = getenv($key);


        if (false === $value) {
            error_log('ABOUT TO DUMP ALL THE ENV' . print_r(getenv()));
            throw new \RuntimeException("Environment variable '{$key}':'{$value}' is not set.");
        }

        return $value;
    }

    /**
     * String representation of NaadVars.
     *
     * @return string
     */
    public function __toString(): string
    {
        $vars = json_encode(
            [
            'rootPassword' => $this->rootPassword,
            'serviceHost' => $this->serviceHost,
            'servicePort' => $this->servicePort,
            'databaseName' => $this->databaseName,
            'destinationUser ' => $this->destinationUser ,
            'destinationPassword' => $this->destinationPassword,
            'destinationURL' => $this->destinationURL,
            'naadPort' => $this->naadPort,
            'phpMyAdminPort' => $this->phpMyAdminPort,
            ]
        );

        return $vars;
    }
}
