<?php

namespace HGG\Pardot\ResponseHandler;

/**
 * AbstractResponseHandler
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
abstract class AbstractResponseHandler
{
    /**
     * document
     *
     * @var mixed
     * @access protected
     */
    protected $document;

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
     * @param mixed $rawDocument
     *
     * @access public
     * @return void
     */
    public function __construct($rawDocument)
    {
        $this->document = $rawDocument;
    }

    /**
     * parse
     *
     * @param mixed $object
     *
     * @access public
     * @return void
     */
    public function parse($object)
    {
        $this->doParse($object);

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
     * doParse
     *
     * @param mixed $document
     * @param mixed $object
     *
     * @access protected
     * @return void
     */
    abstract protected function doParse($document, $object);
}
