<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\ObjectManager\Definition;

/**
 * Class Runtime
 */
class Runtime implements \MFTFBuddy\Tests\ObjectManager\DefinitionInterface
{
    /**
     * Definitions.
     *
     * @var array
     */
    protected $definitions = [];

    /**
     * Reader.
     *
     * @var \MFTFBuddy\Tests\Code\Reader\ClassReader
     */
    private $reader;

    /**
     * Runtime constructor.
     * @param \MFTFBuddy\Tests\Code\Reader\ClassReader|null $reader
     */
    public function __construct(\MFTFBuddy\Tests\Code\Reader\ClassReader $reader = null)
    {
        $this->reader = $reader ? : new \MFTFBuddy\Tests\Code\Reader\ClassReader();
    }

    /**
     * Get list of method parameters
     *
     * Retrieve an ordered list of constructor parameters.
     * Each value is an array with following entries:
     *
     * array(
     *     0, // string: Parameter name
     *     1, // string|null: Parameter type
     *     2, // bool: whether this param is required
     *     3, // mixed: default value
     * );
     *
     * @param string $className
     * @return array|null
     */
    public function getParameters($className)
    {
        if (!array_key_exists($className, $this->definitions)) {
            $this->definitions[$className] = $this->reader->getConstructor($className);
        }
        return $this->definitions[$className];
    }

    /**
     * Retrieve list of all classes covered with definitions
     *
     * @return array
     */
    public function getClasses()
    {
        return [];
    }
}
