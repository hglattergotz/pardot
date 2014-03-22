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
     * @param string $object The name of the Pardot object being processed
     *
     * @access protected
     * @throws AuthenticationErrorException,
     *         InvalidArgumentException,
     *         RuntimeException
     * @return array
     */
    protected function doParse($object)
    {
        $object = $this->objectNameToKey($object);

        if ('ok' !== $this->document['@attributes']['stat']) {
            $errorCode = (int) $this->document['@attributes']['err_code'];
            $errorMessage = $this->document['err'];

            if (in_array($errorCode, array(1, 15))) {
                throw new AuthenticationErrorException($errorMessage, $errorCode);
            } else {
                throw new RuntimeException($errorMessage, $errorCode);
            }
        } else {
            if (array_key_exists('result', $this->document)) {
                $this->parseMultiRecordResult($object);
            } elseif (array_key_exists($object, $this->document)) {
                $this->resultCount = 1;
                $this->result = $this->document[$object];
            } elseif (array_key_exists('api_key', $this->document)) {
                $this->resultCount = 0;
                $this->result = $this->document['api_key'];
            } else {
                throw new RuntimeException('Unknown response format '.json_encode($this->document));
            }
        }
    }

    /**
     * Parse response document containing multiple records
     *
     * @param string $object The name of the Pardot object being requested
     *
     * @access protected
     * @throws InvalidArgumentException
     * @return void
     */
    protected function parseMultiRecordResult($object)
    {
        $this->resultCount = (int) $this->document['result']['total_results'];

        if (0 === $this->resultCount) {
            $this->result = array();
        } else {
            if (array_key_exists($object, $this->document['result'])) {
                $this->result = $this->document['result'][$object];
            } else {
                $msg = sprintf('The response does not contain the expected object key \'%s\'', $object);

                throw new InvalidArgumentException($msg);
            }
        }
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

