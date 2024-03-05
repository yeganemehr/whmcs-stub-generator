<?php

namespace Yeganemehr\WHMCSStubGenerator\Generators;

use Laminas\Code\Generator\MethodGenerator as LaminasMethodGenerator;
use Yeganemehr\WHMCSStubGenerator\Generator;

class MethodGenerator extends LaminasMethodGenerator
{
    public static function fromReflection(\ReflectionMethod $reflection): self
    {
        $generator = new self(
            name: $reflection->getName(),
            docBlock: Generator::generateDocBlock($reflection)
        );

        $generator->setStatic($reflection->isStatic());
        $generator->setFinal($reflection->isFinal());
        if (!$reflection->getDeclaringClass()->isInterface()) {
            $generator->setAbstract($reflection->isAbstract());
        }
        $generator->setVisibility(Generator::getClassMemberVisibity($reflection));
        $generator->setReturnType($reflection->getReturnType()?->__toString());
        $generator->setParameters(array_map([ParameterGenerator::class, 'fromReflection'], $reflection->getParameters()));

        return $generator;
    }
}
