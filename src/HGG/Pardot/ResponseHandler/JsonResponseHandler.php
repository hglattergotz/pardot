<?php

namespace HGG\Pardot\ResponseHandler;

use HGG\Pardot\Exception\RuntimeException;
use HGG\Pardot\Exception\AuthenticationErrorException;

/**
 * JsonResponseHandler
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class JsonResponseHandler extends AbstractResponseHandler
{
    /**
     * parse
     *
     * @param mixed $document
     * @param mixed $object
     *
     * @access protected
     * @return void
     */
    protected function parse($document, $object)
    {
        if ('ok' !== $document['@attributes']['stat']) {
            $errorCode = $document['@attributes']['err_code'];
            $errorMessage = $document['err'];

            if (15 == $errorCode) {
                throw new AuthenticationErrorException($errorMessage, $errorCode);
            } else {
                throw new RuntimeException($errorMessage, $errorCode);
            }
        } else {
            if (array_key_exists('result', $document)) {
                $this->resultCount = $document['result']['total_results'];
                $this->result = (0 === $this->resultCount) ? array() : $document['result'][$object];
            } elseif (array_key_exists($object, $document)) {
                $this->resultCount = 1;
                $this->result = $document[$object];
            } elseif (array_key_exists('api_key', $document)) {
                $this->resultCount = 0;
                $this->result = $document['api_key'];
            } else {
                throw new RuntimeException('Unknown response format '.json_encode($document));
            }
        }
    }
}

