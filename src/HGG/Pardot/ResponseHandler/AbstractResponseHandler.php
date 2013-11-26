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
     * object
     *
     * @var mixed
     * @access protected
     */
    protected $object;

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
     * @param array  $document
     * @param string $object
     *
     * @access public
     * @return void
     */
    public function __construct(array $document, $object)
    {
        $this->object = $object;
        $this->parse($document, $object);
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
     * parse
     *
     * @param mixed $document
     * @param mixed $object
     *
     * @access protected
     * @return void
     */
    abstract protected function parse($document, $object);
}
