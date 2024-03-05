<?php

namespace Yeganemehr\WHMCSStubGenerator\Generators;

use Laminas\Code\Generator\ClassGenerator as LaminasClassGenerator;
use Yeganemehr\WHMCSStubGenerator\Generator;

class ClassGenerator extends LaminasClassGenerator
{
    public static function filterDirectMembers(\ReflectionClass $reflection, array $members): array
    {
        return array_filter($members, fn (\ReflectionMethod|\ReflectionProperty|\ReflectionClassConstant $m) => $m->getDeclaringClass()->getName() == $reflection->getName());
    }

    public static function fromReflection(\ReflectionClass $reflection): self
    {
        $generator = new self(
            name: $reflection->getShortName(),
            namespaceName: $reflection->getNamespaceName(),
            docBlock: Generator::generateDocBlock($reflection),
        );

        $generator->setAbstract($reflection->isAbstract());
        $generator->setFinal($reflection->isFinal());
        $parent = $reflection->getParentClass();
        if (false !== $parent) {
            $generator->setExtendedClass($parent->getName());
        }
        $generator->setImplementedInterfaces($reflection->getInterfaceNames());

        $methods = self::filterDirectMembers($reflection, $reflection->getMethods());
        $generator->addMethods(array_map([MethodGenerator::class, 'fromReflection'], $methods));

        $properties = self::filterDirectMembers($reflection, $reflection->getProperties());
        $generator->addProperties(array_map([PropertyGenerator::class, 'fromReflection'], $properties));

        $constants = self::filterDirectMembers($reflection, $reflection->getReflectionConstants());
        $generator->addProperties(array_map([PropertyGenerator::class, 'fromReflection'], $constants));

        $generator->addTraits(array_map(fn (string $t) => '\\'.$t, $reflection->getTraitNames()));
        foreach ($reflection->getTraitAliases() as $replace => $method) {
            $generator->removeMethod($replace);
            $generator->addTraitAlias('\\'.$method, $replace);
        }

        return $generator;
    }
}
