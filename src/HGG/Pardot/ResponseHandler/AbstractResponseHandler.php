<?php

namespace HGG\Pardot\ResponseHandler;

/**
 * Base class for all Response Handlers
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
abstract class AbstractResponseHandler
{
    /**
     * data
     *
     * @var mixed
     * @access protected
     */
    protected $data;

    /**
     * objectName
     *
     * @var string
     * @access protected
     */
    protected $objectName;

    /**
     * resultCount
     *
     * @var mixed
     * @access protected
     */
    protected $resultCount;

    /**
     * result
     *
     * @var mixed
     * @access protected
     */
    protected $result;

    /**
     * __construct
     *
     * @param mixed  $data       Data decoded from the response body, in case
     *                           format is JSON this will be an assoc array, if
     *                           format is xml then it will be a
     *                           SimpleXMLElement
     * @param string $objectName The name of the Pardot object this response is
     *                           for
     *
     * @access public
     * @return void
     */
    public function __construct($data, $objectName)
    {
        $this->data = $data;
        $this->objectName = $objectName;
    }

    /**
     * parse
     *
     * @access public
     * @return void
     */
    public function parse()
    {
        $this->doParse();

        return $this;
    }

    /**
     * getResultCount
     *
     * @access public
     * @return void
     */
    public function getResultCount()
    {
        return $this->resultCount;
    }

    /**
     * getResult
     *
     * @access public
     * @return void
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * This is where the actual parsing of the response happens in the
     * specialized Handlers
     *
     * @access protected
     * @return void
     */
    abstract protected function doParse();
}

