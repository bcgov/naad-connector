<?php

namespace Bcgov\NaadConnector;

use Bcgov\NaadConnector\Entity\Alert;
use Bcgov\NaadConnector\Config\DatabaseConfig;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Exception\EntityIdentityCollisionException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Monolog\Logger;

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

    protected Logger $logger;

    protected DatabaseConfig $dbConfig;

    /**
     * Constructor for Database class.
     *
     * @param Logger         $logger   An instance of Monolog/Logger.
     * @param DatabaseConfig $dbConfig The database config instance.
     */
    public function __construct(Logger $logger, DatabaseConfig $dbConfig)
    {
        $this->logger = $logger;
        $this->dbConfig = $dbConfig;
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
            'password' => $this->dbConfig->getDatabaseRootPassword(),
            'host'     => $this->dbConfig->getDatabaseHost(),
            'port'     => $this->dbConfig->getDatabasePort(),
            'dbname'   => $this->dbConfig->getDatabaseName(),
            'driver'   => 'pdo_mysql',
            ]
        );
        return new EntityManager($connection, $config);
    }

    /**
     * Inserts an alert into the database.
     *
     * @param Alert $alert The alert to insert.
     *
     * @return bool
     */
    public function insertAlert(Alert $alert): bool
    {
        // Try to persist the alert, catching duplicate key violations gracefully.
        try {
            $this->entityManager->persist($alert);
            $this->flush();
        } catch (UniqueConstraintViolationException $e) {
            $this->logger->error('Unique constraint violation: ' . $e->getMessage());
            $this->entityManager = $this->getEntityManager();
            return false;
        } catch (EntityIdentityCollisionException $e) {
            $this->logger->error(
                'Entity identity collision: ' . $e->getMessage()
            );
            $this->entityManager->detach($alert);
            return false;
        }
        return true;
    }

    /**
     * Delete all the alerts (minus ALERTS_TO_KEEP) from the database.
     * ALERTS_TO_KEEP is defined .env or naad-shared-config configMap.
     *
     * @return void
     */
    public function deleteOldAlerts(): void
    {
        $alertRepository = $this->entityManager->getRepository(Alert::class);

        // Retrieve the number of fresh alerts to keep from environment variables.
        $freshAlertsToKeep = $this->dbConfig->getAlertsToKeep();

        // Retrieve all alerts ordered by 'received' date in descending order.
        $alerts = $alertRepository->findBy([], ['received' => 'DESC']);
        $alertsToDelete = array_slice($alerts, $freshAlertsToKeep);
        $alertsDeleted = count($alertsToDelete);

        // Announce the number of stale alerts being deleted.
        $this->logger->info("Deleting {$alertsDeleted} stale alerts:");

        // Delete all alerts to be deleted.
        foreach ($alertsToDelete as $alert) {
            $this->logger->info('Deleting alert: ' . $alert->getId());
            $this->entityManager->remove($alert);
        }

        $this->flush();
    }


    /**
     * Flushes EntityManager causing database queries to be executed.
     *
     * @return void
     */
    public function flush(): void
    {
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
        $this->flush();
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

        // Calculate the retry threshold based on failure count.
        $retryThreshold = clone $now;
        $retryThreshold->modify('-' . $retryIntervalMinutes . ' minutes');

        $qb = $this->entityManager->createQueryBuilder();
        $query = $qb->select(array('a'))
            ->from(Alert::class, 'a')
            ->where('a.success = false')
            ->andWhere(
                $qb->expr()->orX(
                    'a.send_attempted IS NULL',
                    'a.send_attempted < :retryThreshold'
                )
            )
            ->orderBy('a.received', 'DESC')
            ->setMaxResults(5)
            ->setParameter('retryThreshold', $retryThreshold)
            ->getQuery();

        // Execute the query and return results.
        $alerts = $query->getResult();

        return $alerts;
    }

}
