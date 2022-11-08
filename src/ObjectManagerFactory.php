<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MFTFBuddy\Tests;

use MFTFBuddy\Tests\ObjectManager\Factory;
use MFTFBuddy\Tests\Stdlib\BooleanUtils;

/**
 * Object Manager Factory.
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
// @codingStandardsIgnoreFile
class ObjectManagerFactory
{
    /**
     * Object Manager class name.
     *
     * @var string
     */
    protected $locatorClassName = '\MFTFBuddy\Tests\ObjectManager';

    /**
     * DI Config class name.
     *
     * @var string
     */
    protected $configClassName = '\MFTFBuddy\Tests\ObjectManager\Config';

    /**
     * Create Object Manager.
     *
     * @param array $sharedInstances
     * @return ObjectManager
     */
    public function create(array $sharedInstances = [])
    {
        /** @var \MFTFBuddy\Tests\ObjectManager\Config $diConfig */
        $diConfig = new $this->configClassName();

        $factory = new Factory($diConfig);
        $argInterpreter = $this->createArgumentInterpreter(new BooleanUtils());
        $argumentMapper = new \MFTFBuddy\Tests\ObjectManager\Config\Mapper\Dom($argInterpreter);


        $sharedInstances['MFTFBuddy\Tests\Data\Argument\InterpreterInterface'] = $argInterpreter;
        $sharedInstances['MFTFBuddy\Tests\ObjectManager\Config\Mapper\Dom'] = $argumentMapper;

        /** @var \MFTFBuddy\Tests\ObjectManager $objectManager */
        $objectManager = new $this->locatorClassName($factory, $diConfig, $sharedInstances);

        $factory->setObjectManager($objectManager);
        ObjectManager::setInstance($objectManager);

        self::configure($objectManager);

        return $objectManager;
    }

    /**
     * Return newly created instance on an argument interpreter, suitable for processing DI arguments.
     *
     * @param \MFTFBuddy\Tests\Stdlib\BooleanUtils $booleanUtils
     * @return \MFTFBuddy\Tests\Data\Argument\InterpreterInterface
     */
    protected function createArgumentInterpreter(
        \MFTFBuddy\Tests\Stdlib\BooleanUtils $booleanUtils
    ) {
        $constInterpreter = new \MFTFBuddy\Tests\Data\Argument\Interpreter\Constant();
        $result = new \MFTFBuddy\Tests\Data\Argument\Interpreter\Composite(
            [
                'boolean' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\Boolean($booleanUtils),
                'string' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\StringUtils($booleanUtils),
                'number' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\Number(),
                'null' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\NullType(),
                'const' => $constInterpreter,
                'object' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\DataObject($booleanUtils),
                'init_parameter' => new \MFTFBuddy\Tests\Data\Argument\Interpreter\Argument($constInterpreter),
            ],
            \MFTFBuddy\Tests\ObjectManager\Config\Reader\Dom::TYPE_ATTRIBUTE
        );
        // Add interpreters that reference the composite
        $result->addInterpreter('array', new \MFTFBuddy\Tests\Data\Argument\Interpreter\ArrayType($result));
        return $result;
    }

    /**
     * Get Object Manager instance.
     *
     * @return ObjectManager
     */
    public static function getObjectManager()
    {
        if (!$objectManager = ObjectManager::getInstance()) {
            $objectManagerFactory = new self();
            $objectManager = $objectManagerFactory->create();
        }

        return $objectManager;
    }

    /**
     * Configure Object Manager.
     * This method is static to have the ability to configure multiple instances of Object manager when needed.
     *
     * @param \MFTFBuddy\Tests\ObjectManagerInterface $objectManager
     * @return void
     */
    public static function configure(\MFTFBuddy\Tests\ObjectManagerInterface $objectManager)
    {
        $objectManager->configure(
            $objectManager->get(\MFTFBuddy\Tests\ObjectManager\ConfigLoader\Primary::class)->load()
        );
    }
}
