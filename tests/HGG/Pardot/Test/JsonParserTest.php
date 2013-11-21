<?php

namespace HGG\Pardot\Test;

use HGG\Pardot\JsonResponseHandler;

class JsonParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider allPassDataProvider
     */
    public function testPass($document, $object, $expected)
    {
        $handler = new JsonResponseHandler($document, $object);
        $this->assertEquals($expected, $handler->getResult());
    }

    public function allPassDataProvider()
    {
        return array(
            array(
                array(
                    '@attributes' => array(
                        'stat' => 'ok',
                        'version' => 1
                    ),
                    'api_key' => '12345'
                ),
                'login',
                '12345'
            ),
            array(
                array(
                    '@attributes' => array(
                        'stat' => 'ok',
                        'version' => 1
                    ),
                    'result' => array(
                        'total_results' => 1,
                        'prospect' => array(
                            array('field1' => 'value')
                        )
                    )
                ),
                'prospect',
                array(
                    array('field1' => 'value')
                )
            ),
            array(
                array(
                    '@attributes' => array(
                        'stat' => 'ok',
                        'version' => 1
                    ),
                    'prospect' => array(
                        array('field1' => 'value')
                    )
                ),
                'prospect',
                array(
                    array('field1' => 'value')
                )
            )
        );
    }

    /**
     * @dataProvider allFailDataProvider
     */
    //public function testFail($document)
    //{
        //try {
            //// Failing code here
            //$this->fail('Expected exception not raised.');
        //} catch (\Exception $e) {
            //return;
        //}
    //}

    //public function allFailDataProvider()
    //{
        //return array(
        //);
    //}
}
