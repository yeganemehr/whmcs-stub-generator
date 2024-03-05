<?php

namespace Yeganemehr\WHMCSStubGenerator\Generators;

use Laminas\Code\Generator\PropertyGenerator as LaminasPropertyGenerator;
use Yeganemehr\WHMCSStubGenerator\Generator;

class PropertyGenerator extends LaminasPropertyGenerator
{
    public static function fromReflection(\ReflectionClassConstant|\ReflectionProperty $reflection): self
    {
        $generator = new PropertyGenerator($reflection->getName());

        $docType = Generator::generateDocBlock($reflection);
        if ($docType) {
            $generator->setDocBlock($docType);
        }
        $generator->setVisibility(Generator::getClassMemberVisibity($reflection));
        $generator->setFlags($reflection->getModifiers());
        if ($reflection instanceof \ReflectionProperty) {
            $generator->setStatic($reflection->isStatic());
            $generator->setReadonly($reflection->isReadOnly());
            if ($reflection->hasDefaultValue()) {
                $generator->setDefaultValue($reflection->getDefaultValue());
            }
        } elseif ($reflection instanceof \ReflectionClassConstant) {
            $generator->setConst(true);
            $generator->setDefaultValue($reflection->getValue());
        }

        return $generator;
    }
}
