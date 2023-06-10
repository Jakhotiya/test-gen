<?php

namespace Jakhotiya\TestGen\Test\Unit\Code\Generator\Fixture;

class Foo
{
    private $url;

    private $acl;

    public function __construct( \Magento\Framework\Url $url,
     \Magento\Framework\Acl $acl
    ){
        $this->url = $url;
        $this->acl = $acl;
    }

    public function withACallableFunction(callable $fn){
        return $fn();
    }

    public function withClassAsNamedParameter(\DateTime $dateTime){

    }
    public function withNamedParameterType(int $a){
        if($a >= 4){
            return $a;
        }
        return $a*4;

    }

    public function methodWithUnionType(int|string $a){

    }
}
