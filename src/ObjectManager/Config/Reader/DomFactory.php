<?php
/**
 * Config reader factory
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MFTFBuddy\Tests\ObjectManager\Config\Reader;

/**
 * Factory class for \MFTFBuddy\Tests\ObjectManager\Config\Reader\Dom
 */
class DomFactory
{
    /**
     * Object Manager instance
     *
     * @var \MFTFBuddy\Tests\ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $instanceName = null;

    /**
     * Factory constructor
     *
     * @param \MFTFBuddy\Tests\ObjectManagerInterface $objectManager
     * @param string                                                     $instanceName
     */
    public function __construct(
        \MFTFBuddy\Tests\ObjectManagerInterface $objectManager,
        $instanceName = \MFTFBuddy\Tests\ObjectManager\Config\Reader\Dom::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \MFTFBuddy\Tests\ObjectManager\Config\Reader\Dom
     */
    public function create(array $data = [])
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
