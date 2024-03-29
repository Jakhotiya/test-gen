<?php

namespace Jakhotiya\TestGen\Code\Generator;

use Laminas\Code\Generator\ValueGenerator;
use Magento\Framework\Code\Generator\ClassGenerator;
use Magento\Framework\Code\Generator\DefinedClasses;
use \Jakhotiya\TestGen\Code\Generator\Io;

class Test
{
    const ENTITY_TYPE = 'test';

    /**
     * @var string[]
     */
    private $_errors = [];

    /**
     * Source model class name
     *
     * @var string
     */
    private $_sourceClassName;

    /**
     * Result model class name
     *
     * @var string
     */
    private $_resultClassName;

    /**
     * @var Io
     */
    private $_ioObject;

    /**
     * Class generator object
     *
     * @var \Magento\Framework\Code\Generator\CodeGeneratorInterface
     */
    protected $_classGenerator;

    /**
     * @var DefinedClasses
     */
    private $definedClasses;

    private $properties = [];

    /**
     * @param null|string $sourceClassName
     * @param null|string $resultClassName
     * @param Io $ioObject
     * @param \Magento\Framework\Code\Generator\CodeGeneratorInterface $classGenerator
     * @param DefinedClasses $definedClasses
     */
    public function __construct(
        $sourceClassName = null,
        $resultClassName = null,
        Io $ioObject = null,
        \Magento\Framework\Code\Generator\CodeGeneratorInterface $classGenerator = null,
        DefinedClasses $definedClasses = null
    ) {
        if ($ioObject) {
            $this->_ioObject = $ioObject;
        } else {
            $this->_ioObject = new Io(new \Magento\Framework\Filesystem\Driver\File());
        }
        if ($classGenerator) {
            $this->_classGenerator = $classGenerator;
        } else {
            $this->_classGenerator = new ClassGenerator();
        }
        if ($definedClasses) {
            $this->definedClasses = $definedClasses;
        } else {
            $this->definedClasses = new DefinedClasses();
        }

        $this->_sourceClassName = $this->_getFullyQualifiedClassName($sourceClassName);
        if ($resultClassName) {
            $this->_resultClassName = $this->_getFullyQualifiedClassName($resultClassName);
        } elseif ($this->_sourceClassName) {
            $this->_resultClassName = $this->_getDefaultResultClassName($this->_sourceClassName);
        }
    }

    /**
     * Generation template method
     *
     * @return bool
     */
    public function generate()
    {
        try {
            if ($this->_validateData()) {
                $sourceCode = $this->_generateCode();
                if ($sourceCode) {
                    $fileName = $this->_ioObject->generateResultFileName($this->_getResultClassName());
                    $this->_ioObject->writeResultFile($fileName, $sourceCode);
                    return true;
                } else {
                    $this->_addError('Can\'t generate source code.');
                }
            }
        } catch (\Exception $e) {
            $this->_addError($e->getMessage());
        }
        return false;
    }

    /**
     * List of occurred generation errors
     *
     * @return string[]
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Get full source class name, with namespace
     *
     * @return string
     */
    public function getSourceClassName()
    {
        return $this->_sourceClassName;
    }

    /**
     * Get source class without namespace.
     *
     * @return string
     */
    public function getSourceClassNameWithoutNamespace()
    {
        $parts = explode('\\', ltrim($this->getSourceClassName(), '\\'));
        return end($parts);
    }

    /**
     * Get fully qualified class name
     *
     * @param string $className
     * @return string
     */
    protected function _getFullyQualifiedClassName($className)
    {
        $className = ltrim($className, '\\');
        return $className ? '\\' . $className : '';
    }

    /**
     * Get result class name
     *
     * @return string
     */
    protected function _getResultClassName()
    {
        return $this->_resultClassName;
    }

    /**
     * Get default result class name
     *
     * @param string $modelClassName
     * @return string
     */
    protected function _getDefaultResultClassName($modelClassName)
    {
        return $modelClassName . ucfirst(static::ENTITY_TYPE);
    }

    /**
     * Returns list of properties for class generator
     *
     * @return array
     */
    protected function _getClassProperties()
    {
       return  $this->properties;
    }



    /**
     * Generate code
     *
     * @return string
     */
    protected function _generateCode()
    {
        $this->_classGenerator->setName($this->_getResultClassName())
            ->setExtendedClass(\PHPUnit\Framework\TestCase::class)
            ->addMethods($this->_getClassMethods())
            ->addProperties($this->_getClassProperties())
            ->setClassDocBlock($this->_getClassDocBlock());

        foreach($this->_classGenerator->getProperties() as $property){
            $property->omitDefaultValue(true);
        }

        return $this->_getGeneratedCode();
    }

    /**
     * Add error message
     *
     * @param string $message
     * @return $this
     */
    protected function _addError($message)
    {
        $this->_errors[] = $message;
        return $this;
    }

    /**
     * Validate data
     *
     * @return bool
     */
    protected function _validateData()
    {
        $sourceClassName = $this->getSourceClassName();
        $resultClassName = $this->_getResultClassName();
        $resultDir = $this->_ioObject->getResultFileDirectory($resultClassName);

        if (!$this->definedClasses->isClassLoadable($sourceClassName)) {
            $this->_addError('Source class ' . $sourceClassName . ' doesn\'t exist.');
            return false;
        } elseif (/**
         * If makeResultFileDirectory only fails because the file is already created,
         * a competing process has generated the file, no exception should be thrown.
         */
            !$this->_ioObject->makeResultFileDirectory($resultClassName)
            && !$this->_ioObject->fileExists($resultDir)
        ) {
            $this->_addError('Can\'t create directory ' . $resultDir . '.');
            return false;
        }
        return true;
    }

    /**
     * Get class DocBlock
     *
     * @return array
     */
    protected function _getClassDocBlock()
    {
        $description = ucfirst(static::ENTITY_TYPE) . ' class for @see ' . $this->getSourceClassName();
        return ['shortDescription' => $description];
    }

    /**
     * Get generated code
     *
     * @return string
     */
    protected function _getGeneratedCode()
    {
        $sourceCode = $this->_classGenerator->generate();
        return $this->_fixCodeStyle($sourceCode);
    }

    /**
     * Fix code style
     *
     * @param string $sourceCode
     * @return string
     */
    protected function _fixCodeStyle($sourceCode)
    {
        $sourceCode = str_replace(' array (', ' array(', $sourceCode);
        $sourceCode = preg_replace("/{\n{2,}/m", "{\n", $sourceCode);
        $sourceCode = preg_replace("/\n{2,}}/m", "\n}", $sourceCode);
        return $sourceCode;
    }

    /**
     * Get value generator for null default value
     *
     * @return ValueGenerator
     */
    protected function _getNullDefaultValue()
    {
        $value = new ValueGenerator(null, ValueGenerator::TYPE_NULL);

        return $value;
    }

    /**
     * Extract parameter type
     *
     * @param \ReflectionParameter $parameter
     * @return null|string
     */
    private function extractParameterType(
        \ReflectionParameter $parameter
    ): ?string {
        if (!$parameter->hasType()) {
            return null;
        }

        /** @var string $typeName */
        $typeName = '';
        /** @var \ReflectionType $type */
        $type = $parameter->getType();
        //@TODO Handle UnionTypes and IntersectionTYpe
        if(!$type instanceof \ReflectionNamedType){
            return null;
        }
        /** @var \ReflectionNamedType $type */
        if ($type->isBuiltin()) {
            $typeName = $type->getName();
        } elseif ($parameterClass = $this->getParameterClass($parameter)) {
            $typeName = $this->_getFullyQualifiedClassName($parameterClass->getName());
        }

        if ($parameter->allowsNull()) {
            $typeName = '?' . $typeName;
        }

        return $typeName;
    }

    /**
     * Get class by reflection parameter
     *
     * @param \ReflectionParameter $reflectionParameter
     * @return \ReflectionClass|null
     * @throws \ReflectionException
     */
    private function getParameterClass(\ReflectionParameter $reflectionParameter): ?\ReflectionClass
    {
        $parameterType = $reflectionParameter->getType();
        /** @var \ReflectionNamedType $parameterType */
        return $parameterType && !$parameterType->isBuiltin()
            ? new \ReflectionClass($parameterType->getName())
            : null;
    }

    /**
     * Extract parameter default value
     *
     * @param \ReflectionParameter $parameter
     * @return null|ValueGenerator
     * @throws \ReflectionException
     */
    private function extractParameterDefaultValue(
        \ReflectionParameter $parameter
    ): ?ValueGenerator {
        /** @var ValueGenerator|null $value */
        $value = null;
        if ($parameter->isOptional() && $parameter->isDefaultValueAvailable()) {
            $valueType = ValueGenerator::TYPE_AUTO;
            $defaultValue = $parameter->getDefaultValue();
            if ($defaultValue === null) {
                $valueType = ValueGenerator::TYPE_NULL;
            }
            $value = new ValueGenerator($defaultValue, $valueType);
        }

        return $value;
    }

    /**
     * Retrieve method parameter info
     *
     * @param \ReflectionParameter $parameter
     * @return array
     * @throws \ReflectionException
     */
    protected function _getMethodParameterInfo(\ReflectionParameter $parameter)
    {
        $parameterInfo = [
            'name' => $parameter->getName(),
            'passedByReference' => $parameter->isPassedByReference(),
            'variadic' => $parameter->isVariadic()
        ];
        if ($type = $this->extractParameterType($parameter)) {
            $parameterInfo['type'] = $type;
        }
        if ($default = $this->extractParameterDefaultValue($parameter)) {
            $parameterInfo['defaultValue'] = $default;
        }

        return $parameterInfo;
    }

    /**
     * Reinit generator
     *
     * @param string $sourceClassName
     * @param string $resultClassName
     * @return void
     */
    public function init($sourceClassName, $resultClassName)
    {
        $this->_sourceClassName = $sourceClassName;
        $this->_resultClassName = $resultClassName;
    }

    protected function _getDefaultConstructorDefinition()
    {
        return [];
    }

    protected function getSetupMethodBody()
    {
        $mocks = [];
        $reflectionClass = new \ReflectionClass($this->getSourceClassName());
        $constr = $reflectionClass->getConstructor();
        $args = [];
        if($constr !== null) {
            foreach ($constr->getParameters() as $parameter) {
                $type = $this->getParameterClass($parameter);
                if ($type !== null) {

                    $this->properties[] = [
                        'name' => $parameter->getName(),
                        'visibility' => 'private',
                        'omitDefaultValue' => true,
                        'docblock' => [
                            'tags' => [
                                ['name' => 'var', 'description' => '\\' . $type->getName()]
                            ]
                        ]
                    ];
                    $args[] = '$this->' . $parameter->getName();
                    $mocks[] = '$this->' . $parameter->getName() . ' = $this->createMock(\\' . $type->getName() . '::class);';
                }
            }
        }
        $this->properties[] = [
            'name'=> 'subject',
            'visibility'=>'private',
            'omitDefaultValue'=>true,
            'docblock'=>[
                'tags'=>[
                    ['name'=>'var','description'=>'\\'.$this->getSourceClassName()]
                ]
            ]
        ];
        $body = implode(PHP_EOL,$mocks).PHP_EOL;
        $body .= '$this->subject = new ' . $this->getSourceClassName() . '('.implode(',',$args).');'.PHP_EOL;
        $body .= 'parent::setUp();';
        return $body;
    }

    protected function _getClassMethods()
    {
        $methods = [
            [
                'name'=>'setUp',
                'parameters'=>[],
                'visibility' => 'protected',
                'body'=> $this->getSetupMethodBody(),
                 'returntype'=>'void',
                ]
        ];
        $reflectionClass = new \ReflectionClass($this->getSourceClassName());
        $publicMethods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($publicMethods as $method) {
            if (!(
                    $method->isConstructor() ||
                    $method->isFinal() ||
                    $method->isStatic() ||
                    $method->isDestructor()
                )
                && !in_array(
                    $method->getName(),
                    ['__sleep', '__wakeup', '__clone']
                )
            ) {
                $methods[] = $this->_getMethodInfo($method);
            }
        }

        return $methods;
    }

    /**
     * Collect method info
     *
     * @param \ReflectionMethod $method
     * @return array
     */
    protected function _getMethodInfo(\ReflectionMethod $method)
    {
        $parameterNames = [];
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->isVariadic() ? '... $' . $parameter->getName() : '$' . $parameter->getName();
            $parameterNames[] = $name;
            $parameters[] = $this->_getMethodParameterInfo($parameter);
        }

        $returnTypeValue = $this->getReturnTypeValue($method);
        $methodInfo = [
            'name' => 'test'.ucfirst($method->getName()),
            'parameters' => $parameters,
            'body' => $this->_getMethodBody(
                $method->getName(),
                $parameterNames,
                $returnTypeValue === 'void'
            ),
            'docblock' => ['shortDescription' => 'test for '.$method->getName().' method'],
            'returntype' => $returnTypeValue,
        ];

        return $methodInfo;
    }

    /**
     * Build proxy method body
     *
     * @param string $name
     * @param array $parameters
     * @param bool $withoutReturn
     * @return string
     */
    protected function _getMethodBody(
        $name,
        array $parameters = [],
        bool $withoutReturn = false
    ) {
        if (count($parameters) == 0) {
            $methodCall = sprintf('%s()', $name);
        } else {
            $methodCall = sprintf('%s(%s)', $name, implode(', ', $parameters));
        }

        return  '$this->subject->' . $methodCall . ';';
    }

    /**
     * Returns return type
     *
     * @param \ReflectionMethod $method
     * @return null|string
     */
    private function getReturnTypeValue(\ReflectionMethod $method): ?string
    {
        $returnTypeValue = null;
        $returnType = $method->getReturnType();
        if ($returnType) {
            $returnTypeValue = ($returnType->allowsNull() ? '?' : '');
            $returnTypeValue .= ($returnType->getName() === 'self')
                ? $this->_getFullyQualifiedClassName($method->getDeclaringClass()->getName())
                : $returnType->getName();
        }

        return $returnTypeValue;
    }
}
