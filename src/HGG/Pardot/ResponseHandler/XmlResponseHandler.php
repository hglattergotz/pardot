<?php

namespace HGG\Pardot\ResponseHandler;

use HGG\Pardot\Exception\RuntimeException;
use HGG\Pardot\Exception\AuthenticationErrorException;

/**
 * ResponseHandler for xml formatted response documents
 *
 * *****************************************************************************
 * *****************************************************************************
 * **** WARNRING: This handler is not tested and not up to date
 * ****           Contributions welcome
 * *****************************************************************************
 * *****************************************************************************
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class XmlResponseHandler extends AbstractResponseHandler
{
    /**
     * Parse the response document
     *
     * @access protected
     * @return void
     */
    protected function doParse()
    {
        // just make it work modified base class
        $this->document = $this->data;
        $object = $this->objectName;

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

