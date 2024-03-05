<?php

namespace Yeganemehr\WHMCSStubGenerator\Generators;

use Laminas\Code\Generator\TraitGenerator as LaminasTraitGenerator;
use Yeganemehr\WHMCSStubGenerator\Generator;

class TraitGenerator extends LaminasTraitGenerator
{
    public static function fromReflection(\ReflectionClass $reflection): self
    {
        $generator = new self(
            name: $reflection->getShortName(),
            namespaceName: $reflection->getNamespaceName(),
            docBlock: Generator::generateDocBlock($reflection)
        );

        $generator->setAbstract($reflection->isAbstract());
        $generator->setFinal($reflection->isFinal());
        $generator->setImplementedInterfaces($reflection->getInterfaceNames());

        $methods = ClassGenerator::filterDirectMembers($reflection, $reflection->getMethods());
        $generator->addMethods(array_map([MethodGenerator::class, 'fromReflection'], $methods));

        $properties = ClassGenerator::filterDirectMembers($reflection, $reflection->getProperties());
        $generator->addProperties(array_map([PropertyGenerator::class, 'fromReflection'], $properties));

        $constants = ClassGenerator::filterDirectMembers($reflection, $reflection->getReflectionConstants());
        $generator->addProperties(array_map([PropertyGenerator::class, 'fromReflection'], $constants));

        $generator->addTraits(array_map(fn (\ReflectionClass $t) => $t->getName(), $reflection->getTraits()));

        return $generator;
    }
}
