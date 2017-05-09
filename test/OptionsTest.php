<?php

class OptionsTest extends PHPUnit_Framework_TestCase
{
    public function testBlackList()
    {
        $options = new \f2r\SimpleHttp\Options();
        $options->addHostBlackList('wrong');
        $options->addHostBlackList(['`forbidden`i', '`no-way`i'], true);
        $this->assertTrue($options->isHostValid('good'));
        $this->assertTrue($options->isHostValid('not-wrong'));
        $this->assertFalse($options->isHostValid('wrong'));
        $this->assertTrue($options->isHostValid('wrong-again'));
        $this->assertFalse($options->isHostValid('hots-forbidden'));
        $this->assertFalse($options->isHostValid('forbidden-way'));
        $this->assertFalse($options->isHostValid('is-forbidden-way'));
    }

    /**
     * @expectedException \f2r\SimpleHttp\Exception\HostPatternException
     */
    public function testWrongPattern()
    {
        $options = new \f2r\SimpleHttp\Options();
        $options->addHostWhiteList('`pattern', true);

    }

    public function testWhiteList()
    {
        $options = new \f2r\SimpleHttp\Options();
        $options->addHostWhiteList([]);
        $this->assertTrue($options->isHostValid('anyone'));
        $options->addHostWhiteList('white-one');
        $this->assertFalse($options->isHostValid('anyone'));
        $this->assertTrue($options->isHostValid('white-one'));
    }
}
