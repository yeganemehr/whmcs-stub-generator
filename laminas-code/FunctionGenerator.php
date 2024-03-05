<?php

namespace Laminas\Code\Generator;

use Laminas\Code\Reflection\FunctionReflection;

class FunctionGenerator extends AbstractGenerator
{
    /**
     * @return FunctionGenerator
     */
    public static function fromReflection(FunctionReflection $reflectionFunction)
    {
        $function = static::copyFunctionSignature($reflectionFunction);

        // set the namespace
        if ($reflectionFunction->inNamespace()) {
            $function->setNamespaceName($reflectionFunction->getNamespaceName());
        }

        $function->setSourceContent($reflectionFunction->getContents(false));
        $function->setSourceDirty(false);

        if ('' != $reflectionFunction->getDocComment()) {
            $function->setDocBlock(DocBlockGenerator::fromReflection($reflectionFunction->getDocBlock()));
        }

        $function->setBody(static::clearBodyIndention($reflectionFunction->getBody()));

        return $function;
    }

    /**
     * Returns a FunctionGenerator based on a FunctionReflection with only the signature copied.
     *
     * This is similar to fromReflection() but without the function body and phpdoc as this is quite heavy to copy.
     * It's for example useful when creating proxies where you normally change the function body anyway.
     */
    public static function copyFunctionSignature(FunctionReflection $reflectionFunction): FunctionGenerator
    {
        $function = new static();

        $function->returnType = TypeGenerator::fromReflectionType($reflectionFunction->getReturnType(), null);

        $function->setReturnsReference($reflectionFunction->returnsReference());
        $function->setName($reflectionFunction->getName());

        foreach ($reflectionFunction->getParameters() as $reflectionParameter) {
            $function->setParameter(
                $reflectionParameter->isPromoted()
                    ? PromotedParameterGenerator::fromReflection($reflectionParameter)
                    : ParameterGenerator::fromReflection($reflectionParameter)
            );
        }

        return $function;
    }

    /**
     * Generate from array.
     *
     * @deprecated this API is deprecated, and will be removed in the next major release. Please
     *             use the other constructors of this class instead.
     *
     * @configkey name             string        [required] Class Name
     * @configkey docblock         string        The DocBlock information
     * @configkey parameters       string        Class which this class is extending
     * @configkey body             string
     * @configkey returntype       string
     * @configkey returnsreference bool
     *
     * @return FunctionGenerator
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function fromArray(array $array)
    {
        if (!isset($array['name'])) {
            throw new Exception\InvalidArgumentException('Function generator requires that a name is provided for this object');
        }

        $function = new static($array['name']);
        foreach ($array as $name => $value) {
            // normalize key
            switch (\strtolower(\str_replace(['.', '-', '_'], '', $name))) {
                case 'docblock':
                    $docBlock = $value instanceof DocBlockGenerator ? $value : DocBlockGenerator::fromArray($value);
                    $function->setDocBlock($docBlock);
                    break;
                case 'namespacename':
                    $function->setNamespaceName($value);
                    break;
                case 'parameters':
                    $function->setParameters($value);
                    break;
                case 'body':
                    $function->setBody($value);
                    break;
                case 'returntype':
                    $function->setReturnType($value);
                    break;
                case 'returnsreference':
                    $function->setReturnsReference((bool) $value);
            }
        }

        return $function;
    }

    /**
     * Identify the space indention from the first line and remove this indention
     * from all lines.
     *
     * @param string $body
     *
     * @return string
     */
    protected static function clearBodyIndention($body)
    {
        if (empty($body)) {
            return $body;
        }

        $lines = \explode("\n", $body);

        $indention = \str_replace(\trim($lines[1]), '', $lines[1]);

        foreach ($lines as $key => $line) {
            if (\substr($line, 0, \strlen($indention)) == $indention) {
                $lines[$key] = \substr($line, \strlen($indention));
            }
        }

        $body = \implode("\n", $lines);

        return $body;
    }

    protected ?DocBlockGenerator $docBlock = null;

    protected string $name = '';
    protected ?string $namespaceName = null;

    /** @var ParameterGenerator[] */
    protected array $parameters = [];

    protected string $body = '';

    private ?TypeGenerator $returnType = null;

    private bool $returnsReference = false;

    /**
     * @param ?string                               $name
     * @param string                                $namespaceName
     * @param ParameterGenerator[]|array[]|string[] $parameters
     * @param ?string                               $body
     * @param DocBlockGenerator|string|null         $docBlock
     */
    public function __construct(
        $name = null,
        $namespaceName = null,
        array $parameters = [],
        $body = null,
        $docBlock = null
    ) {
        if ($name) {
            $this->setName($name);
        }
        if (null !== $namespaceName) {
            $this->setNamespaceName($namespaceName);
        }
        if ($parameters) {
            $this->setParameters($parameters);
        }
        if ($body) {
            $this->setBody($body);
        }
        if ($docBlock) {
            $this->setDocBlock($docBlock);
        }
    }

    /** @return string */
    public function __toString()
    {
        return $this->generate();
    }

    /**
     * @param ?string $namespaceName
     *
     * @return static
     */
    public function setNamespaceName($namespaceName)
    {
        $this->namespaceName = $namespaceName;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getNamespaceName()
    {
        return $this->namespaceName;
    }

    /**
     * @param ParameterGenerator[]|array[]|string[] $parameters
     *
     * @return FunctionGenerator
     */
    public function setParameters(array $parameters)
    {
        foreach ($parameters as $parameter) {
            $this->setParameter($parameter);
        }

        $this->sortParameters();

        return $this;
    }

    /**
     * @param ParameterGenerator|array|string $parameter
     *
     * @return FunctionGenerator
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setParameter($parameter)
    {
        if (\is_string($parameter)) {
            $parameter = new ParameterGenerator($parameter);
        }

        if (\is_array($parameter)) {
            $parameter = ParameterGenerator::fromArray($parameter);
        }

        if (!$parameter instanceof ParameterGenerator) {
            throw new Exception\InvalidArgumentException(\sprintf('%s is expecting either a string, array or an instance of %s\ParameterGenerator', __METHOD__, __NAMESPACE__));
        }

        $this->parameters[$parameter->getName()] = $parameter;

        $this->sortParameters();

        return $this;
    }

    /**
     * @return ParameterGenerator[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string $body
     *
     * @return FunctionGenerator
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string|null $returnType
     *
     * @return FunctionGenerator
     */
    public function setReturnType($returnType = null)
    {
        $this->returnType = null === $returnType
            ? null
            : TypeGenerator::fromTypeString($returnType);

        return $this;
    }

    /**
     * @return TypeGenerator|null
     */
    public function getReturnType()
    {
        return $this->returnType;
    }

    /**
     * @param bool $returnsReference
     *
     * @return FunctionGenerator
     */
    public function setReturnsReference($returnsReference)
    {
        $this->returnsReference = (bool) $returnsReference;

        return $this;
    }

    public function returnsReference(): bool
    {
        return $this->returnsReference;
    }

    /**
     * @return string
     */
    public function generate()
    {
        $output = '';

        if (null !== ($namespace = $this->getNamespaceName())) {
            $output .= 'namespace '.$namespace.';'.self::LINE_FEED.self::LINE_FEED;
        }

        $indent = $this->getIndentation();

        if (($docBlock = $this->getDocBlock()) !== null) {
            $docBlock->setIndentation($indent);
            $output .= $docBlock->generate();
        }

        $output .= 'function '
            .($this->returnsReference ? '& ' : '')
            .$this->getName().'(';

        $output .= \implode(', ', \array_map(
            static fn (ParameterGenerator $parameter): string => $parameter->generate(),
            $this->getParameters()
        ));

        $output .= ')';

        if ($this->returnType) {
            $output .= ' : '.$this->returnType->generate();
        }

        $output .= self::LINE_FEED.'{'.self::LINE_FEED;

        if ($this->body) {
            $output .= \preg_replace('#^((?![a-zA-Z0-9_-]+;).+?)$#m', $indent.'$1', \trim($this->body))
                .self::LINE_FEED;
        }

        $output .= '}'.self::LINE_FEED;

        return $output;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function setName($name)
    {
        if (str_contains($name, '\\')) {
            $namespace = \substr($name, 0, strrpos($name, '\\'));
            $name = \substr($name, strrpos($name, '\\') + 1);
            $this->setNamespaceName($namespace);
        }

        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param DocBlockGenerator|string $docBlock
     *
     * @return AbstractMemberGenerator
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setDocBlock($docBlock)
    {
        if (\is_string($docBlock)) {
            $docBlock = new DocBlockGenerator($docBlock);
        } elseif (!$docBlock instanceof DocBlockGenerator) {
            throw new Exception\InvalidArgumentException(\sprintf('%s is expecting either a string, array or an instance of %s\DocBlockGenerator', __METHOD__, __NAMESPACE__));
        }

        $this->docBlock = $docBlock;

        return $this;
    }

    public function removeDocBlock(): void
    {
        $this->docBlock = null;
    }

    /**
     * @return DocBlockGenerator|null
     */
    public function getDocBlock()
    {
        return $this->docBlock;
    }

    /**
     * Sort parameters by their position.
     */
    private function sortParameters(): void
    {
        \uasort(
            $this->parameters,
            static fn (ParameterGenerator $item1, ParameterGenerator $item2) => $item1->getPosition() <=> $item2->getPosition()
        );
    }
}
