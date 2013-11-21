<?php

namespace HGG\Pardot;

use HGG\Pardot\Exception\PardotException;
use HGG\Pardot\Exception\PardotAuthenticationErrorException;

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
     * @access protected
     * @return void
     */
    protected function parse($document, $object)
    {
        if (!$document instanceof SimpleXmlElement) {
            throw new PardotException('Document is not instance of SimpleXmlElement');
        }

        if ('ok' !== (string) $document->attributes()->stat) {
            $errorCode = (integer) $document->err->attributes()->code;
            $errorMessage = (string) $document->err;

            if (15 === $errorCode) {
                throw new PardotAuthenticationErrorException($errorMessage, $errorCode);
            } else {
                throw new PardotException($errorMessage, $errorCode);
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
                throw new PardotException('Unknown response format');
            }
        }
    }
}

