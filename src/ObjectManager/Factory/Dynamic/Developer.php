<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MFTFBuddy\Tests\ObjectManager\Factory\Dynamic;

/**
 * Class Developer
 */
class Developer implements \MFTFBuddy\Tests\ObjectManager\FactoryInterface
{
    /**
     * Object manager
     *
     * @var \MFTFBuddy\Tests\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Object manager config
     *
     * @var \MFTFBuddy\Tests\ObjectManager\ConfigInterface
     */
    protected $config;

    /**
     * Definition list
     *
     * @var \MFTFBuddy\Tests\ObjectManager\DefinitionInterface
     */
    protected $definitions;

    /**
     * Object creation stack
     *
     * @var array
     */
    protected $creationStack = [];

    /**
     * Global arguments.
     *
     * @var array
     */
    protected $globalArguments;

    /**
     * Developer constructor.
     * @param \MFTFBuddy\Tests\ObjectManager\ConfigInterface          $config
     * @param \MFTFBuddy\Tests\ObjectManagerInterface|null            $objectManager
     * @param \MFTFBuddy\Tests\ObjectManager\DefinitionInterface|null $definitions
     * @param array                                                                      $globalArguments
     */
    public function __construct(
        \MFTFBuddy\Tests\ObjectManager\ConfigInterface $config,
        \MFTFBuddy\Tests\ObjectManagerInterface $objectManager = null,
        \MFTFBuddy\Tests\ObjectManager\DefinitionInterface $definitions = null,
        $globalArguments = []
    ) {
        $this->config = $config;
        $this->objectManager = $objectManager;
        $this->definitions = $definitions ?: new \MFTFBuddy\Tests\ObjectManager\Definition\Runtime();
        $this->globalArguments = $globalArguments;
    }

    /**
     * Set object manager
     *
     * @param \MFTFBuddy\Tests\ObjectManagerInterface $objectManager
     * @return void
     */
    public function setObjectManager(\MFTFBuddy\Tests\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Resolve constructor arguments
     *
     * @param string $requestedType
     * @param array  $parameters
     * @param array  $arguments
     * @return array
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * Revisited to reduce cyclomatic complexity, left unrefactored for readability
     */
    protected function resolveArguments($requestedType, array $parameters, array $arguments = [])
    {
        $resolvedArguments = [];
        $arguments = count($arguments)
            ? array_replace($this->config->getArguments($requestedType), $arguments)
            : $this->config->getArguments($requestedType);
        foreach ($parameters as $parameter) {
            list($paramName, $paramType, $paramRequired, $paramDefault) = $parameter;
            $argument = null;
            if (!empty($arguments) && (isset($arguments[$paramName]) || array_key_exists($paramName, $arguments))) {
                $argument = $arguments[$paramName];
            } elseif ($paramRequired) {
                if ($paramType) {
                    $argument = ['instance' => $paramType];
                } else {
                    $this->creationStack = [];
                    throw new \BadMethodCallException(
                        'Missing required argument $' . $paramName . ' of ' . $requestedType . '.'
                    );
                }
            } else {
                $argument = $paramDefault;
            }
            if ($paramType && $argument !== $paramDefault && !is_object($argument)) {
                if (!isset($argument['instance']) || !is_array($argument)) {
                    throw new \UnexpectedValueException(
                        'Invalid parameter configuration provided for $' . $paramName . ' argument of ' . $requestedType
                    );
                }
                $argumentType = $argument['instance'];
                $isShared = (isset($argument['shared']) ? $argument['shared'] : $this->config->isShared($argumentType));
                $argument = $isShared
                    ? $this->objectManager->get($argumentType)
                    : $this->objectManager->create($argumentType);
            } elseif (is_array($argument)) {
                if (isset($argument['argument'])) {
                    $argument = isset($this->globalArguments[$argument['argument']])
                        ? $this->globalArguments[$argument['argument']]
                        : $paramDefault;
                } elseif (!empty($argument)) {
                    $this->parseArray($argument);
                }
            }
            $resolvedArguments[] = $argument;
        }
        return $resolvedArguments;
    }

    /**
     * Parse array argument
     *
     * @param array $array
     * @return void
     */
    protected function parseArray(&$array)
    {
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                if (isset($item['instance'])) {
                    $itemType = $item['instance'];
                    $isShared = (isset($item['shared'])) ? $item['shared'] : $this->config->isShared($itemType);
                    $array[$key] = $isShared
                        ? $this->objectManager->get($itemType)
                        : $this->objectManager->create($itemType);
                } elseif (isset($item['argument'])) {
                    $array[$key] = isset($this->globalArguments[$item['argument']])
                        ? $this->globalArguments[$item['argument']]
                        : null;
                } else {
                    $this->parseArray($array[$key]);
                }
            }
        }
    }

    /**
     * Create instance with call time arguments
     *
     * @param string $requestedType
     * @param array  $arguments
     * @return object
     * @throws \Exception
     *
     * @SuppressWarnings(PHPCPD)
     */
    public function create($requestedType, array $arguments = [])
    {
        $type = $this->config->getInstanceType($requestedType);
        $parameters = $this->definitions->getParameters($type);

        if ($parameters === null) {
            return new $type();
        }
        if (isset($this->creationStack[$requestedType])) {
            $lastFound = end($this->creationStack);
            $this->creationStack = [];
            throw new \LogicException("Circular dependency: {$requestedType} depends on {$lastFound} and vice versa.");
        }
        $this->creationStack[$requestedType] = $requestedType;
        try {
            $args = $this->resolveArguments($requestedType, $parameters, $arguments);
            unset($this->creationStack[$requestedType]);
        } catch (\Exception $e) {
            unset($this->creationStack[$requestedType]);
            throw $e;
        }

        $reflection = new \ReflectionClass($type);

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Set global arguments
     *
     * @param array $arguments
     * @return void
     */
    public function setArguments($arguments)
    {
        $this->globalArguments = $arguments;
    }
}
