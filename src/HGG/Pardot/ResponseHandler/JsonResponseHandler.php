<?php

namespace HGG\Pardot\ResponseHandler;

use HGG\Pardot\Exception\RuntimeException;
use HGG\Pardot\Exception\InvalidArgumentException;
use HGG\Pardot\Exception\AuthenticationErrorException;

/**
 * Parse a json response document into the expected data structure and handle
 * errors in form of throwing exceptions
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class JsonResponseHandler extends AbstractResponseHandler
{
    /**
     * Parse the response document
     *
     * @access protected
     * @throws AuthenticationErrorException,
     *         InvalidArgumentException,
     *         RuntimeException
     * @return array
     */
    protected function doParse()
    {
        $object = $this->objectNameToKey($this->objectName);

        if ('ok' !== $this->data['@attributes']['stat']) {
            $errorCode = (int) $this->data['@attributes']['err_code'];
            $errorMessage = $this->data['err'];

            if (in_array($errorCode, array(1, 15))) {
                throw new AuthenticationErrorException($errorMessage, $errorCode);
            } else {
                throw new RuntimeException($errorMessage, $errorCode);
            }
        } else {
            if (array_key_exists('result', $this->data)) {
                $this->parseMultiRecordResult($object, $this->data);
            } elseif (array_key_exists($object, $this->data)) {
                $this->parseSingleRecordResult($object, $this->data);
            } elseif (array_key_exists('api_key', $this->data)) {
                $this->resultCount = 0;
                $this->result = $this->data['api_key'];
            } else {
                $asString = true;
                throw new RuntimeException('Unknown response format: '.$this->responseObj->getBoby($asString));
            }
        }
    }

    /**
     * Parse response document containing multiple records
     *
     * @param string $objectName The name of the Pardot object being requested
     * @param array  $data       The data as an associative array
     *
     * @access protected
     * @throws InvalidArgumentException
     * @return void
     */
    protected function parseMultiRecordResult($objectName, $data)
    {
        $this->resultCount = (int) $data['result']['total_results'];

        if (0 === $this->resultCount) {
            $this->result = array();
        } else {
            if (array_key_exists($objectName, $data['result'])) {
                if ($this->resultHasOnlyOneRecord($objectName, $data)) {
                    $this->result = array($data['result'][$objectName]);
                } else {
                    $this->result = $data['result'][$objectName];
                }
            } else {
                $msg = sprintf('The response does not contain the expected object key \'%s\'', $objectName);

                throw new InvalidArgumentException($msg);
            }
        }
    }

    /**
     * Parse the single object out of the data and set the resultCount and
     * result members
     *
     * @param string $objectName
     * @param array  $data
     *
     * @access protected
     * @return void
     */
    protected function parseSingleRecordResult($objectName, $data)
    {
        $this->resultCount = 1;
        $this->result = $data[$objectName];
    }

    /**
     * Determine if the content inside of [result][objecname] consists of a
     * single record or an array of records
     *
     * The results from the Pardot API are unfortunately not consisten and have
     * to be normalized.
     *
     * When the result set has more than limit (which is max 200) records, it
     * requires that multiple calls to the API must be made in order to obtain
     * the entire result set. This kind of thing would typically happen when
     * making a query and not asking for a single record by id or email.
     *
     * One would expect to get an array of records back for all requests of this
     * kind of query.
     *
     * For example, if the total_results value is 250 we would have to make two
     * requests to get all records. The first request would return 200 records
     * and the second would return 50 records.
     * This means that [result][objectname] is an array of records.
     *
     * Unfortunately this is not so in the following edge case:
     * When total_results is 201, the first request again returns an array of
     * 200 records, but the second request simply returns a single record
     * (object), which puts the responsibility of discovering the result
     * contents on the consumer.
     * Since this library decodes a JSON response into associative arrays and
     * not stdClass Objects this becomes a bit difficult.
     *
     * To provide a consisten experience this hack will do the work of
     * ensuring that all responses that are part of a single query return the
     * same type of data - an array of records (or in some cases an array with
     * a single record).
     *
     * @param string $objectName
     * @param array  $data
     *
     * @access protected
     * @return boolean
     */
    protected function resultHasOnlyOneRecord($objectName, $data)
    {
        $json = json_encode($data);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException('Unable to encode previously decoded data back to JSON. Json error: ' . json_last_error());
        }

        $asString = false;
        $objData = json_decode($json, false);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException('Unable to decode json. Json error: ' . json_last_error());
        }

        // At this point we have already estableshed that there exists a
        // property called 'result', which is a stdClass object with a
        // property that has the name of the object ($objectName).
        return is_object($objData->result->$objectName);
    }

    /**
     * Convert the object name to the key name that is returned in the result
     * set. This is not consistently the same thing so a map is necessary. This
     * is not yet comprehensive. There might be more exceptions.
     *
     * @param string $object
     *
     * @access protected
     * @return void
     */
    protected function objectNameToKey($object)
    {
        $map = array(
            'visitorActivity' => 'visitor_activity'
        );

        if (array_key_exists($object, $map)) {
            return $map[$object];
        } else {
            return $object;
        }
    }
}

