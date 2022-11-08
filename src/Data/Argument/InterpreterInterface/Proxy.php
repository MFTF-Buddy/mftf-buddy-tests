<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MFTFBuddy\Tests\Data\Argument\InterpreterInterface;

/**
 * Proxy class for \MFTFBuddy\Tests\Data\Argument\InterpreterInterface
 */
class Proxy implements \MFTFBuddy\Tests\Data\Argument\InterpreterInterface
{
    /**
     * Object Manager instance
     *
     * @var \MFTFBuddy\Tests\ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Proxied instance name
     *
     * @var string
     */
    protected $instanceName = null;

    /**
     * Proxied instance
     *
     * @var \MFTFBuddy\Tests\Data\Argument\InterpreterInterface
     */
    protected $subject = null;

    /**
     * Instance shareability flag
     *
     * @var boolean
     */
    protected $isShared = null;

    /**
     * Proxy constructor
     *
     * @param \MFTFBuddy\Tests\ObjectManagerInterface $objectManager
     * @param string                                                     $instanceName
     * @param boolean                                                    $shared
     */
    public function __construct(
        \MFTFBuddy\Tests\ObjectManagerInterface $objectManager,
        $instanceName = \MFTFBuddy\Tests\Data\Argument\InterpreterInterface::class,
        $shared = true
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
        $this->isShared = $shared;
    }

    /**
     * Definition of field which should be serialized.
     *
     * @return array
     */
    public function __sleep()
    {
        return ['subject', 'isShared'];
    }

    /**
     * Retrieve ObjectManager from global scope
     * @return void
     */
    public function __wakeup()
    {
        $this->objectManager = \MFTFBuddy\Tests\ObjectManager::getInstance();
    }

    /**
     * Clone proxied instance
     * @return void
     */
    public function __clone()
    {
        $this->subject = clone $this->getSubject();
    }

    /**
     * Get proxied instance
     *
     * @return \MFTFBuddy\Tests\Data\Argument\InterpreterInterface
     */
    protected function getSubject()
    {
        if (!$this->subject) {
            $this->subject = true === $this->isShared
                ? $this->objectManager->get($this->instanceName)
                : $this->objectManager->create($this->instanceName);
        }
        return $this->subject;
    }

    /**
     * {@inheritdoc}
     * @return mixed
     */
    public function evaluate(array $data)
    {
        return $this->getSubject()->evaluate($data);
    }
}
