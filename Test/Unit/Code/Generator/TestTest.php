<?php
namespace Jakhotiya\TestGen\Test\Unit\Code\Generator;

/**
 * Test class for @see \Jakhotiya\TestGen\Code\Generator\Test
 */
class TestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    private $io;

    private $definedClass;

    protected function setUp() : void
    {
        $sourceClass = \Jakhotiya\TestGen\Test\Unit\Code\Generator\Fixture\Foo::class;
        $resultClassName = '\Jakhotiya\TestGen\Test\Unit\Code\Generator\Fixture\FooTest';
        $this->io = $this->createMock(\Jakhotiya\TestGen\Code\Generator\Io::class);
        $this->definedClass = $this->createMock(\Magento\Framework\Code\Generator\DefinedClasses::class);
        $classGenerator = new \Magento\Framework\Code\Generator\ClassGenerator();
        $this->subject = new \Jakhotiya\TestGen\Code\Generator\Test($sourceClass,$resultClassName,$this->io,$classGenerator,$this->definedClass);
        parent::setUp();
    }

    /**
     * test for generate method
     */
    public function testGenerate()
    {
        $this->definedClass->method('isClassLoadable')->willReturn(true);
        $this->io->method('makeResultFileDirectory')->willReturn(true);
        $this->io->method('fileExists')->willReturn(true);
        $resultFilename = 'app/code/Jakhotiya/TestGen/Test/Unit/Code/Generator/Fixture/FooTest.php';
        $this->io->method('generateResultFileName')->willReturn($resultFilename);

        $callback = function ($subject){
            return strpos($subject,'public function testRun()')!==false
                && strpos($subject,'$this->subject->run(5)')!==false
                && strpos($subject,'private $url;')!==false
                && strpos($subject,'$this->subject = new \Jakhotiya\TestGen\Test\Unit\Code\Generator\Fixture\Foo($this->url,$this->acl)')!==false
                && strpos($subject,'$this->url = $this->createMock(\Magento\Framework\Url::class);')!==false;
        };

        $this->io->expects($this->once())
            ->method('writeResultFile')
            ->with($resultFilename,$this->callback($callback),);
        $this->subject->generate();


    }


}
