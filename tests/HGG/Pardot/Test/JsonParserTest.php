<?php

namespace HGG\Pardot\Test;

use HGG\Pardot\ResponseHandler\JsonResponseHandler;

/**
 * JsonParserTest
 *
 * @author Henning Glatter-Götz <henning@glatter-gotz.com>
 */
class JsonParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Run all sunny day tests
     *
     * @param mixed $document
     * @param mixed $object
     * @param mixed $expected
     *
     * @dataProvider allPassDataProvider
     *
     * @access public
     * @return void
     */
    public function testPass($document, $object, $expected)
    {
        $handler = new JsonResponseHandler($document, $object);
        $handler->parse();
        $this->assertEquals($expected, $handler->getResult());
    }

    /**
     * allPassDataProvider
     *
     * @access public
     * @return void
     */
    public function allPassDataProvider()
    {
        $testData = array();

        // Login action response
        $testData[] = array(
                array(
                    '@attributes' => array(
                        'stat' => 'ok',
                        'version' => 1
                    ),
                    'api_key' => '12345'
                ),
                'login',
                '12345'
            );

        // Multi record response from a query
        $testData[] = array(
                array(
                    '@attributes' => array(
                        'stat' => 'ok',
                        'version' => 1
                    ),
                    'result' => array(
                        'total_results' => 2,
                        'prospect' => array(
                            array('field1' => 'value1'),
                            array('field2' => 'value2')
                        )
                    )
                ),
                'prospect',
                array(
                    array('field1' => 'value1'),
                    array('field2' => 'value2')
                )
            );

        // Test visitorActivity request that comes back as visitor_activity
        // (instead of visitorActivity), also happens to be a multi record
        // response
        $testData[] = array(
                array(
                    '@attributes' => array(
                        'stat' => 'ok',
                        'version' => 1
                    ),
                    'result' => array(
                        'total_results' => 2,
                        'visitor_activity' => array(
                            array('field1' => 'value1'),
                            array('field2' => 'value2')
                        )
                    )
                ),
                'visitorActivity',
                array(
                    array('field1' => 'value1'),
                    array('field2' => 'value2')
                )
            );

        // Edge case: second request of a query that returns 201 records where
        // 200 is the limit. So the first response would have returned 200
        // records and the second one just a single record, but it should be
        // wrapped in an array instead of coming back as a single record
        $testData[] = array(
                array(
                    '@attributes' => array(
                        'stat' => 'ok',
                        'version' => 1
                    ),
                    'result' => array(
                        'total_results' => 201,
                        'visitor_activity' => array('field1' => 'value')
                    )
                ),
                'visitorActivity',
                array(
                    array('field1' => 'value')
                )
            );

        // Single record response from a read
        // Note that there is no total_results key and the record is not wrapped
        // inside an array
        $testData[] = array(
                array(
                    '@attributes' => array(
                        'stat' => 'ok',
                        'version' => 1
                    ),
                    'prospect' => array('field1' => 'value')
                ),
                'prospect',
                array('field1' => 'value')
            );

        return $testData;
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
