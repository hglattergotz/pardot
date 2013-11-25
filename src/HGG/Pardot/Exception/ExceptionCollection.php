<?php

namespace HGG\Pardot\Exception;

use HGG\Pardot\Exception\ExceptionInterface;

/**
 * ExceptionCollection
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class ExceptionCollection extends \Exception implements ExceptionInterface, \IteratorAggregate, \Countable
{
    /**
     * exceptions
     *
     * @var mixed
     * @access protected
     */
    protected $exceptions;

    /**
     * __construct
     *
     * @param mixed $message
     * @param int $code
     * @param bool $previous
     * @param bool $exceptions
     * @access public
     * @return void
     */
    public function __construct($message, $code = 0, $previous = null, $exceptions = array())
    {
        parent::__construct($message, $code, $previous);

        foreach ($exceptions as $exception) {
            $this->addException($exception);
        }
    }

    /**
     * addException
     *
     * @param mixed $exception
     * @access public
     * @return void
     */
    public function addException($exception)
    {
        if ($exception->getMessage()) {
            $this->message .= "\n".$exception->getMessage();
        }

        $this->exceptions[] = $exception;
    }

    /**
     * getExceptions
     *
     * @access public
     * @return void
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * count
     *
     * @access public
     * @return void
     */
    public function count()
    {
        return count($this->exceptions);
    }

    /**
     * getIterator
     *
     * @access public
     * @return void
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->exceptions);
    }

    /**
     * getFirst
     *
     * @access public
     * @return void
     */
    public function getFirst()
    {
        return $this->exceptions ? $this->exceptions[0] : null;
    }
}
