<?php
namespace Jakhotiya\TestGen\Test\Unit\Code\Generator;

/**
 * Test class for @see \Jakhotiya\TestGen\Code\Generator\Test
 */
class TestTest extends \PHPUnit\Framework\TestCase
{


    public function setUp() : void
    {
        $this->subject = new \Jakhotiya\TestGen\Code\Generator\Test();
        parent::setUp();
    }

    /**
     * test for generate method
     */
    public function testGenerate()
    {
        $this->subject->generate();
    }

    /**
     * test for getErrors method
     */
    public function testGetErrors()
    {
        $this->subject->getErrors();
    }

    /**
     * test for getSourceClassName method
     */
    public function testGetSourceClassName()
    {
        $this->subject->getSourceClassName();
    }

    /**
     * test for getSourceClassNameWithoutNamespace method
     */
    public function testGetSourceClassNameWithoutNamespace()
    {
        $this->subject->getSourceClassNameWithoutNamespace();
    }

    /**
     * test for init method
     */
    public function testInit($sourceClassName, $resultClassName)
    {
        $this->subject->init($sourceClassName, $resultClassName);
    }
}
