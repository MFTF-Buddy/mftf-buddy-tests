<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\ObjectManager\ConfigLoader;

/**
 * Class Primary
 * Primary DI configuration loader
 *
 * @internal
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
// @codingStandardsIgnoreFile
class Primary
{
    /**
     * Framework mode
     *
     * @var string
     */
    protected $appMode = 'developer';

    /**
     * Load primary DI configuration
     *
     * @return array
     */
    public function load()
    {
        $reader = new \MFTFBuddy\Tests\ObjectManager\Config\Reader\Dom(
            new \MFTFBuddy\Tests\Config\FileResolver\Primary(),
            new \MFTFBuddy\Tests\ObjectManager\Config\Mapper\Dom(
                $this->createArgumentInterpreter()
            ),
            new \MFTFBuddy\Tests\ObjectManager\Config\SchemaLocator(),
            new \MFTFBuddy\Tests\Config\ValidationState($this->appMode)
        );

        return $reader->read();
    }


    /**
     * Return newly created instance on an argument interpreter, suitable for processing DI arguments
     *
     * @return \MFTFBuddy\Tests\Data\Argument\InterpreterInterface
     */
    protected function createArgumentInterpreter()
    {
        $booleanUtils = new \MFTFBuddy\Tests\Stdlib\BooleanUtils();
        $constInterpreter = new \MFTFBuddy\Tests\Data\Argument\Interpreter\Constant();
        $result = new \MFTFBuddy\Tests\Data\Argument\Interpreter\Composite(
            [
                'boolean' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\Boolean($booleanUtils),
                'string' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\StringUtils($booleanUtils),
                'number' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\Number(),
                'null' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\NullType(),
                'object' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\DataObject($booleanUtils),
                'const' => $constInterpreter,
                'init_parameter' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\Argument($constInterpreter)
            ],
            \MFTFBuddy\Tests\ObjectManager\Config\Reader\Dom::TYPE_ATTRIBUTE
        );
        // Add interpreters that reference the composite
        $result->addInterpreter('array', new \MFTFBuddy\Tests\Data\Argument\Interpreter\ArrayType($result));
        return $result;
    }
}
