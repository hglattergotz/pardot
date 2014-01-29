<?php

namespace HGG\Pardot\ResponseHandler;

use HGG\Pardot\Exception\RuntimeException;
use HGG\Pardot\Exception\AuthenticationErrorException;

/**
 * XmlResponseHandler
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class XmlResponseHandler extends AbstractResponseHandler
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
        if (!$document instanceof SimpleXmlElement) {
            throw new RuntimeException('Document is not instance of SimpleXmlElement');
        }

        if ('ok' !== (string) $document->attributes()->stat) {
            $errorCode = (integer) $document->err->attributes()->code;
            $errorMessage = (string) $document->err;

            if (in_array($errorCode, array(1, 15))) {
                throw new AuthenticationErrorException($errorMessage, $errorCode);
            } else {
                throw new RuntimeException($errorMessage, $errorCode);
            }
        }

        if ('login' == $object) {
            $this->result = (string) $document->api_key;
        } else {
            if (!empty($document->result)) {
                $this->resultCount = (integer) $document->result->total_results;
                $this->result = $document->xpath('/rsp/result/'.$object);
            } elseif (!empty($document->$object)) {
                $this->resultCount = 1;
                $this->result = array($document->$object);
            } else {
                throw new RuntimeException('Unknown response format');
            }
        }
    }
}

