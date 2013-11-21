<?php

namespace HGG\Pardot;

use HGG\Pardot\Exception\PardotException;
use HGG\Pardot\Exception\PardotAuthenticationErrorException;

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
     * @access protected
     * @return void
     */
    protected function parse($document, $object)
    {
        if ('ok' !== $document['@attributes']['stat']) {
            $isError = true;
            $errorCode = $document['@attributes']['err_code'];
            $errorMessage = $document['err'];

            if (15 === $errorCode) {
                throw new PardotAuthenticationErrorException($errorMessage, $errorCode);
            } else {
                throw new PardotException($errorMessage, $errorCode);
            }
        } else {
            if (array_key_exists('result', $document)) {
                $this->resultCount = $document['result']['total_results'];
                $this->result = $document['result'][$object];
            } elseif (array_key_exists($object, $document)) {
                $this->resultCount = 1;
                $this->result = $document[$object];
            } elseif (array_key_exists('api_key', $document)) {
                $this->resultCount = 0;
                $this->result = $document['api_key'];
            } else {
                print_r($document);
                throw new PardotException('Unknown response format');
            }
        }
    }
}

