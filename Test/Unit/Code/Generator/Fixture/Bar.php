<?php

namespace Jakhotiya\TestGen\Test\Unit\Code\Generator\Fixture;

class Bar
{
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
