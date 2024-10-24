<?php

namespace Bcgov\NaadConnector\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use SimpleXMLElement;

#[ORM\Entity]
#[ORM\Table(name: "alerts")]
/**
 * Alert model to be used with the database.
 * 
 * @category Database
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://www.doctrine-project.org/
 */
class Alert
{
    #[ORM\Id]
    #[ORM\Column]
    private string $id;

    #[ORM\Column]
    private string $body;

    #[ORM\Column]
    private DateTime $received;

    #[ORM\Column]
    private DateTime $send_attempted;

    #[ORM\Column]
    private int $failures;

    #[ORM\Column]
    private bool $success;

    /**
     * Gets the alert ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Sets the alert ID.
     *
     * @param string $id The unique identifier of the alert.
     *
     * @return void
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * Gets the alert body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Sets the alert body.
     *
     * @param string $body The raw XML body of the alert.
     *
     * @return void
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * Gets the received date and time.
     *
     * @return \DateTimeInterface
     */
    public function getReceived(): \DateTime
    {
        return $this->received;
    }

    /**
     * Sets the received date and time.
     *
     * @param \DateTime $received The date and time the alert
     *                            was received from NAAD.
     *
     * @return void
     */
    public function setReceived(\DateTime $received): void
    {
        $this->received = $received;
    }

    /**
     * Gets the send attempted date and time.
     *
     * @return \DateTimeInterface|null
     */
    public function getSendAttempted(): DateTime
    {
        return $this->send_attempted;
    }

    /**
     * Sets the send attempted date and time.
     *
     * @param \DateTime|null $sendAttempted The last time an alert was
     *                                      attempted to send to its destination.
     *
     * @return void
     */
    public function setSendAttempted(?\DateTime $sendAttempted): void
    {
        $this->send_attempted = $sendAttempted;
    }

    /**
     * Gets the number of failures.
     *
     * @return int
     */
    public function getFailures(): int
    {
        return $this->failures;
    }

    /**
     * Sets the number of failures.
     *
     * @param int $failures The number of times the alert has
     *                      failed to send to its destination.
     *
     * @return void
     */
    public function setFailures(int $failures): void
    {
        $this->failures = $failures;
    }

    /**
     * Gets the success status.
     *
     * @return bool
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Sets the success status.
     *
     * @param bool $success Whether the alert was successfully
     *                      sent to its destination.
     *
     * @return void
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * Creates an alert from a SimpleXMLElement.
     *
     * @param SimpleXMLElement $xml XML to create alert from.
     *
     * @return Alert
     */
    public static function fromXml(SimpleXMLElement $xml): Alert
    {
        $alert = new Alert();
        $id = $xml->identifier;

        $alert->setId($id);
        $alert->setBody($xml->asXML());
        $alert->setReceived(new DateTime());

        return $alert;
    }
}
