<?php

namespace Bcgov\NaadConnector;

use DOMDocument;
use SimpleXMLElement;
use SoapClient;
use SoapFault;

/**
 * Handles verification of NAADS DSS alert signatures via DSS SOAP requests.
 *
 * This class extracts the verification URL from the alert XML
 * and sends a DSS VerifyRequest
 * to the NAADS DSS provider for signature validation.
 * 
 * @category Client
 * @package  NaadConnector
 * @author   Kyle Shapka <kyle.shapka@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://alerts.pelmorex.com/
 */
class NaadVerifier
{
    /**
     * Verifies the signature of a given NAADS DSS alert.
     *
     * @param string $alertXml The full alert XML as a string.
     *
     * @return bool Returns true if the signature is valid, false otherwise.
     */
    public function verifySignature(string $alertXml): bool
    {
        if (empty($alertXml)) {
            return false;
        }

        // Build and send DSS request.
        $requestXml = $this->buildRequestXml($alertXml);

        // Send using SoapClient.
        //$responseXml = $this->sendSoapRequest($requestXml);

        // Send using cURL.
        $soapEnvelope = $this->buildSoapEnvelope($requestXml);
        $responseXml = $this->sendClassicSoapRequest($soapEnvelope);

        return $this->parseVerificationResponse($responseXml);
    }

    /**
     * Builds the DSS VerifyRequest SOAP XML.
     *
     * @param string $alertXml The signed alert XML.
     *
     * @return string The SOAP request XML.
     */
    private function buildRequestXml(string $alertXml): string
    {
        // Generate a unique request
        $requestId = uniqid('naad_connector_', true);

        // Parse out the alert ID.
        $xml = new SimpleXMLElement($alertXml);
        $alertId = (string) $xml->identifier;

        // Build the VerifyRequest XML.
        return sprintf(
            '<VerifyRequest RequestID="%1$s"
            xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
            xmlns="http://www.docs.oasisopen.org/dss/oasis
            -dss-1.0-core-schema-cd-02.xsd"
            Profile="BC Government">
                <InputDocuments>
                    <Document ID="%2$s">%3$s</Document>
                </InputDocuments>
                <OptionalInputs>
                    <SignaturePlacement WhichDocument="%2$s"
                    CreateEnvelopedSignature="true"/>
                </OptionalInputs>
                <SignatureObject>
                    <SignaturePtr WhichDocument="%2$s"
                    XPath="//cs:Signature[Id = &quot;NAADS Signature&quot;]" />
                </SignatureObject>
            </VerifyRequest>',
            $requestId,
            $alertId,
            $alertXml
        );
    }

    /**
     * Wraps the request XML in a SOAP 1.1 envelope.
     *
     * @param string $requestXml The request XML to be wrapped in the SOAP envelope.
     *
     * @return string The full SOAP envelope containing the request XML.
     */
    private function buildSoapEnvelope(string $requestXml): string
    {
        // The verification request XML needs to be encoded in UTF-8 characters.
        $encodedXml = htmlspecialchars($requestXml, ENT_QUOTES, 'UTF-8');
        return sprintf(
            '<?xml version="1.0" encoding="utf-8"?>
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema"
            xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
                <soap12:Body>
                    <DSSVerifySignedNaadsAlert
                    xmlns="https://dss1.naad-adna.pelmorex.com/">
                        <DSSMessageWithSignedAlertStr>
                            %s
                        </DSSMessageWithSignedAlertStr>
                    </DSSVerifySignedNaadsAlert>
                </soap12:Body>
            </soap12:Envelope>',
            $encodedXml
        );
    }
    
    /**
     * Sends the SOAP request to NAADS DSS using cURL.
     *
     * @param string $soapEnvelope The SOAP request XML.
     *
     * @return string|false The SOAP response XML, or false on failure.
     */
    private function sendClassicSoapRequest(string $soapEnvelope)
    {
        // Set up cURL options.
        $target = 'https://dss1.naad-adna.pelmorex.com';
        $action = "https://dss1.naad-adna.pelmorex.com/DSSVerifySignedNaadsAlert";
        $options = [
            CURLOPT_URL => $target,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $soapEnvelope,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/soap+xml;charset=UTF-8",
                "SOAPAction: $action",
                "Content-Length: " . strlen($soapEnvelope),
            ],
        ];

        // Initialize cURL session.
        $ch = curl_init();
        curl_setopt_array($ch, $options);

        // Execute the cURL request and capture the response.
        $response = curl_exec($ch);
        
        // Close the session.
        curl_close($ch);

        // Decode the results into a format that can be parsed.
        $xml = new SimpleXMLElement($response);
        $namespaces = $xml->getNamespaces(true);
        $namespace = $namespaces[''];

        $xml->registerXPathNamespace('ns', $namespace);

        // Get the content of DSSVerifySignedNaadsAlertResult (HTML-encoded).
        $resultString = (string) $xml
        ->xpath('//ns:DSSVerifySignedNaadsAlertResult')[0];

        // Decode HTML entities.
        $resultString = html_entity_decode($resultString);

        // Return the decoded result as XML.
        return $resultString;
    }

    /**
     * Sends the SOAP request to NAADS DSS.
     *
     * @param string $requestXml The SOAP request XML.
     *
     * @return string|false The SOAP response XML, or false on failure.
     */
    private function sendSoapRequest(string $requestXml)
    {
        $wsdl
            = 'https://dss1.naad-adna.pelmorex.com/PTINAADSDSSWebService.asmx?WSDL';

        try {
            $client = new SoapClient(
                $wsdl, [
                'trace' => true,
                'exceptions' => true,
                'soap_version' => SOAP_1_2,
                ]
            );

            $params = [
                "DSSMessageWithSignedAlertStr" => $requestXml,
            ];

            $data = $client->DSSVerifySignedNaadsAlert($params);

            return $data->DSSVerifySignedNaadsAlertResult;
        } catch (SoapFault $e) {
            // TODO: Remove debugging code.
            error_log("SOAP Fault: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Parses the NAADS DSS verification response.
     *
     * @param string $response The response XML string.
     *
     * @return bool Returns true if verification is successful, false otherwise.
     */
    private function parseVerificationResponse(string $response): bool
    {
        error_log('response: ' . $response);
        try {
            // Set up the xpath.
            $xml = new SimpleXMLElement($response);
            $namespaces = $xml->getNamespaces(true);
            $namespace = $namespaces[''];
            $xml->registerXPathNamespace('ns', $namespace);
            $result = $xml->xpath('//ns:VerifyResponse/ns:Result');

            $resultMajor = (string)$result[0]->ResultMajor;
            $resultMinor = (string)$result[0]->ResultMinor;
            $resultMessage = (string)$result[0]->ResultMessage;
        
            // Check if the major response indicates success.
            if ($resultMajor === 'urn:oasis:names:tc:dss:1.0:resultmajor:Success') {
                // TODO: Remove debugging code.
                error_log(" ✅ Verification Success");
                return true;
            }
            // TODO: Remove debugging code.
            error_log("❌ Verification failed");
            error_log("Message: " . $resultMessage);
            error_log("ResultMajor: " . $resultMajor);
            error_log("ResultMinor: " . $resultMinor);
            return false;
        } catch (Exception $e) {
            // TODO: Remove debugging code.
            error_log("❌ Error parsing response XML: " . $e->getMessage());
            return false;
        }
    }
}
