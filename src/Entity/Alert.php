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
    private string $_id;

    #[ORM\Column]
    private string $_body;

    #[ORM\Column]
    private DateTime $_received;

    #[ORM\Column]
    private DateTime $_send_attempted;

    #[ORM\Column]
    private int $_failures;

    #[ORM\Column]
    private bool $_success;

    /**
     * Gets the alert ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->_id;
    }

    /**
     * Sets the alert ID.
     *
     * @param string $_id The unique identifier of the alert.
     *
     * @return void
     */
    public function setId(string $_id): void
    {
        $this->_id = $_id;
    }

    /**
     * Gets the alert _body.
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->_body;
    }

    /**
     * Sets the alert _body.
     *
     * @param string $_body The raw XML body of the alert.
     *
     * @return void
     */
    public function setBody(string $_body): void
    {
        $this->_body = $_body;
    }

    /**
     * Gets the _received date and time.
     *
     * @return \DateTimeInterface
     */
    public function getReceived(): \DateTime
    {
        return $this->_received;
    }

    /**
     * Sets the _received date and time.
     *
     * @param \DateTime $_received The date and time the alert
     *                             was received from NAAD.
     *
     * @return void
     */
    public function setReceived(\DateTime $_received): void
    {
        $this->_received = $_received;
    }

    /**
     * Gets the send attempted date and time.
     *
     * @return \DateTimeInterface|null
     */
    public function getSendAttempted(): DateTime
    {
        return $this->_send_attempted;
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
        $this->_send_attempted = $sendAttempted;
    }

    /**
     * Gets the number of _failures.
     *
     * @return int
     */
    public function getFailures(): int
    {
        return $this->_failures;
    }

    /**
     * Sets the number of _failures.
     *
     * @param int $_failures The number of times the alert has
     *                       failed to send to its destination.
     *
     * @return void
     */
    public function setFailures(int $_failures): void
    {
        $this->_failures = $_failures;
    }

    /**
     * Gets the _success status.
     *
     * @return bool
     */
    public function getSuccess(): bool
    {
        return $this->_success;
    }

    /**
     * Sets the _success status.
     *
     * @param bool $_success Whether the alert was successfully
     *                       sent to its destination.
     *
     * @return void
     */
    public function setSuccess(bool $_success): void
    {
        $this->_success = $_success;
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
        $_id = $xml->xpath(
            '/x:alert/x:identifier'
        )[0];
        
        $alert->setId($_id);
        $alert->setBody($xml->asXML());
        $alert->setReceived(new DateTime());

        return $alert;
    }
}
