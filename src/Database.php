<?php

namespace Bcgov\NaadConnector;

use Bcgov\NaadConnector\Entity\Alert;
use Bcgov\NaadConnector\NaadVars;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

/**
 * Database class for interacting with the database.
 *
 * @category Database
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://www.doctrine-project.org/
 */
class Database
{

    protected EntityManager $entityManager;

    /**
     * Constructor for Database class.
     */
    public function __construct()
    {
        $this->entityManager = $this->getEntityManager();
    }

    /**
     * Gets an instance of the Doctrine EntityManager.
     *
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        // Extract environment variables from .env file.
        $naadVars = new NaadVars();

        // Create a simple "default" Doctrine ORM configuration.
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [ __DIR__ . '/src' ],
            isDevMode: true,
        );

        // Configuring the database connection.
        $connection = DriverManager::getConnection(
            [
            // TODO: Create non-root user.
            'user'     => 'root',
            'password' => $naadVars-> databaseRootPassword,
            'host'     => $naadVars-> databaseHost,
            'port'     => $naadVars-> databasePort,
            'dbname'   => $naadVars-> databaseName,
            'driver'   => 'pdo_mysql',
            ]
        );
        return new EntityManager($connection, $config);
    }

    /**
     * Persists an alert to the database.
     *
     * @param Alert $alert The alert to persist.
     *
     * @return string
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/reference/working-with-objects.html#persisting-entities
     */
    protected function persistAlert(
        Alert $alert
    ): string {
        $this->entityManager->persist($alert);
        return $alert->getId();
    }

    /**
     * Inserts an alert into the database.
     *
     * @param Alert $alert The alert to insert.
     *
     * @return void
     */
    public function insertAlert( Alert $alert ): void
    {
        try {
            $this->persistAlert($alert);
            $this->entityManager->flush();
        } catch ( UniqueConstraintViolationException $e ) {
            $this->entityManager = $this->getEntityManager();
        }
    }

    /**
     * Gets alerts from the database by an array of ids.
     *
     * @param array $ids The array of alert ids.
     *
     * @return array
     */
    public function getAlertsById( array $ids ): array
    {
        $alertRepository = $this->entityManager->getRepository(Alert::class);
        $alerts          = $alertRepository->findBy([ 'id' => $ids ]);
        $this->entityManager->flush();
        return $alerts;
    }
}
