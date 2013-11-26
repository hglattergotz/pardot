<?php

namespace HGG\Pardot\Exception;

use HGG\Pardot\Exception\ExceptionInterface;

/**
 * InvalidArgumentException
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
    protected $url;
    protected $parameters;

    /**
     * __construct
     *
     * @param string $message
     * @param int    $code
     * @param bool   $previous
     * @param string $url
     * @param bool   $parameters
     *
     * @access public
     * @return void
     */
    public function __construct($message = '', $code = 0, $previous = null, $url = '', $parameters = array())
    {
        parent::__construct($message, $code, $previous);

        $this->url = $url;
        $this->parameters = $parameters;
    }

    /**
     * getUrl
     *
     * @access public
     * @return void
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * setUrl
     *
     * @param mixed $url
     *
     * @access public
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * getParameters
     *
     * @access public
     * @return void
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * setParameters
     *
     * @param mixed $parameters
     *
     * @access public
     * @return void
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }
}

