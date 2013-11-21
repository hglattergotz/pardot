<?php

namespace HGG\Pardot\Test;

use HGG\Pardot\Connector;

class JsonParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider allPassDataProvider
     */
    public function testPass()
    {
        $this->assertTrue(false);
    }

    public function allPassDataProvider()
    {
        return array(
        );
    }

    /**
     * @dataProvider allFailDataProvider
     */
    public function testFail()
    {
        try {
            // Failing code here
            $this->fail('Expected exception not raised.');
        } catch (\Exception $e) {
            return;
        }
    }

    public function allFailDataProvider()
    {
        return array(
        );
    }
}
