<?php

namespace Yeganemehr\WHMCSStubGenerator\Generators;

use Laminas\Code\Generator\InterfaceGenerator as LaminasInterfaceGenerator;
use Yeganemehr\WHMCSStubGenerator\Generator;

class InterfaceGenerator extends LaminasInterfaceGenerator
{
    public static function fromReflection(\ReflectionClass $reflection): self
    {
        $generator = new self(
            name: $reflection->getShortName(),
            namespaceName: $reflection->getNamespaceName(),
            docBlock: Generator::generateDocBlock($reflection),
        );

        $generator->setImplementedInterfaces($reflection->getInterfaceNames());

        $methods = ClassGenerator::filterDirectMembers($reflection, $reflection->getMethods());
        $generator->addMethods(array_map([MethodGenerator::class, 'fromReflection'], $methods));

        $properties = ClassGenerator::filterDirectMembers($reflection, $reflection->getProperties());
        $generator->addProperties(array_map([PropertyGenerator::class, 'fromReflection'], $properties));

        $constants = ClassGenerator::filterDirectMembers($reflection, $reflection->getReflectionConstants());
        $generator->addProperties(array_map([PropertyGenerator::class, 'fromReflection'], $constants));

        return $generator;
    }
}
