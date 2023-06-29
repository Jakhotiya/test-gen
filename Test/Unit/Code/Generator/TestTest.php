<?php
namespace Jakhotiya\TestGen\Test\Unit\Code\Generator;

/**
 * Test class for @see \Jakhotiya\TestGen\Code\Generator\Test
 */
class TestTest extends \PHPUnit\Framework\TestCase
{
    private $subject;

    private $io;

    private $definedClass;

    private $classGenerator;

    protected function setUp() : void
    {
        $sourceClass = \Jakhotiya\TestGen\Test\Unit\Code\Generator\Fixture\Foo::class;
        $resultClassName = '\Jakhotiya\TestGen\Test\Unit\Code\Generator\Fixture\FooTest';
        $this->io = $this->createMock(\Jakhotiya\TestGen\Code\Generator\Io::class);
        $this->definedClass = $this->createMock(\Magento\Framework\Code\Generator\DefinedClasses::class);
        $this->classGenerator = new \Magento\Framework\Code\Generator\ClassGenerator();
        $this->subject = new \Jakhotiya\TestGen\Code\Generator\Test($sourceClass,$resultClassName,$this->io,$this->classGenerator,$this->definedClass);
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

        $callback = function ($generateClassCode){
            self::assertStringContainsString('public function testWithACallableFunction(',$generateClassCode);
            self::assertStringContainsString('$this->subject->withACallableFunction',$generateClassCode);
            self::assertStringContainsString('private $url;',$generateClassCode);
            self::assertStringContainsString('$this->subject = new \Jakhotiya\TestGen\Test\Unit\Code\Generator\Fixture\Foo($this->url,$this->acl)',$generateClassCode);
            self::assertStringContainsString('$this->url = $this->createMock(\Magento\Framework\Url::class);',$generateClassCode);
            return true;
        };

        $this->io->expects($this->once())
            ->method('writeResultFile')
            ->with($resultFilename,$this->callback($callback),);
        $this->subject->generate();
    }

    public function testGenerateOfClassWithoutConstructor()
    {
        $sourceClass = \Jakhotiya\TestGen\Test\Unit\Code\Generator\Fixture\Bar::class;
        $resultClassName = '\Jakhotiya\TestGen\Test\Unit\Code\Generator\Fixture\BarTest';
        $this->subject = new \Jakhotiya\TestGen\Code\Generator\Test($sourceClass,$resultClassName,$this->io,$this->classGenerator,$this->definedClass);

        $this->definedClass->method('isClassLoadable')->willReturn(true);
        $this->io->method('makeResultFileDirectory')->willReturn(true);
        $this->io->method('fileExists')->willReturn(true);
        $resultFilename = 'app/code/Jakhotiya/TestGen/Test/Unit/Code/Generator/Fixture/BarTest.php';
        $this->io->method('generateResultFileName')->willReturn($resultFilename);

        $callback = function ($generateClassCode){
            self::assertStringContainsString('public function testWithACallableFunction(',$generateClassCode);
            self::assertStringContainsString('$this->subject->withACallableFunction',$generateClassCode);
            self::assertStringContainsString('$this->subject = new \Jakhotiya\TestGen\Test\Unit\Code\Generator\Fixture\Bar()',$generateClassCode);
            return true;
        };

        $this->io->expects($this->once())
            ->method('writeResultFile')
            ->with($resultFilename,$this->callback($callback),);
        $this->subject->generate();
    }

}
