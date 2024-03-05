<?php

namespace Yeganemehr\WHMCSStubGenerator\Generators;

use Laminas\Code\Generator\ParameterGenerator as LaminasParameterGenerator;
use Laminas\Code\Generator\ValueGenerator;

class ParameterGenerator extends LaminasParameterGenerator
{
    public static function fromReflection(\ReflectionParameter $reflection): self
    {
        $parameterName = $reflection->getName();
        $generator = new self($parameterName);
        $generator->setVariadic($reflection->isVariadic());
        $generator->setPassedByReference($reflection->isPassedByReference());
        if ($reflection->hasType()) {
            $generator->setType($reflection->getType()->__toString());
        }
        if ($reflection->isOptional()) {
            if (!$reflection->isVariadic()) {
                if (!$reflection->hasType() or $reflection->allowsNull()) {
                    $generator->setDefaultValue(new ValueGenerator(null, ValueGenerator::TYPE_NULL));
                } else {
                    $type = $reflection->getType();
                    if ($type instanceof \ReflectionNamedType) {
                        if ($type->isBuiltin()) {
                            $value = match ($type->getName()) {
                                'array' => [],
                                'string' => '',
                                'int' => 0,
                                'float' => 0,
                                'bool' => true,
                                default => null,
                            };
                            $generator->setDefaultValue($value);
                        }
                    }
                }
            }
        }

        return $generator;
    }
}
