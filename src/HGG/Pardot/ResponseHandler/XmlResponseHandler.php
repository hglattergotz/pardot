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
     * @param mixed $object
     *
     * @access protected
     * @return void
     */
    protected function doParse($object)
    {
        if (!$this->document instanceof SimpleXmlElement) {
            throw new RuntimeException('document is not instance of SimpleXmlElement');
        }

        if ('ok' !== (string) $this->document->attributes()->stat) {
            $errorCode = (integer) $this->document->err->attributes()->code;
            $errorMessage = (string) $this->document->err;

            if (in_array($errorCode, array(1, 15))) {
                throw new AuthenticationErrorException($errorMessage, $errorCode);
            } else {
                throw new RuntimeException($errorMessage, $errorCode);
            }
        }

        if ('login' == $object) {
            $this->result = (string) $this->document->api_key;
        } else {
            if (!empty($this->document->result)) {
                $this->resultCount = (integer) $this->document->result->total_results;
                $this->result = $this->document->xpath('/rsp/result/'.$object);
            } elseif (!empty($this->document->$object)) {
                $this->resultCount = 1;
                $this->result = array($this->document->$object);
            } else {
                throw new RuntimeException('Unknown response format');
            }
        }
    }
}

