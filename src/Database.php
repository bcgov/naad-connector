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
     * Updates an alert's status in the database.
     *
     * @param Alert $alert The alert to update.
     *
     * @return void
     */
    public function updateAlert(Alert $alert): void
    {
        $existingAlert = $this->entityManager->find(Alert::class, $alert->getId());

        if ($existingAlert) {
            $existingAlert->setSuccess($alert->getSuccess());
            $existingAlert->setSendAttempted($alert->getSendAttempted());
            $existingAlert->setFailures($alert->getFailures());
        } else {
            throw new \RuntimeException(
                'Alert not found for update: ' . $alert->getId()
            );
        }

        $this->entityManager->flush();
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

    /**
     * Placeholder for fetching unsent alerts from the database.
     *
     * @return array Returns an array of Alert entities.
     */
    public function getUnsentAlerts(): array
    {
        $retryIntervalMinutes = 5;
        $now = new \DateTime();

        $query = $this->entityManager->createQuery(
            'SELECT a
            FROM Bcgov\NaadConnector\Entity\Alert a
            WHERE a.success = false
            AND (
                a.send_attempted IS NULL OR
                a.send_attempted < :retryThreshold
            )'
        );

        // Calculate the retry threshold based on failure count.
        $retryThreshold = clone $now;
        $retryThreshold->modify('-' . $retryIntervalMinutes . ' minutes');

        $query->setParameter('retryThreshold', $retryThreshold);

        // Execute the query and return results.
        $alerts = $query->getResult();
        $this->entityManager->clear();
        
        return $alerts;
    }
}
