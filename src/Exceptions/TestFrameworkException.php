<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\Exceptions;

use MFTFBuddy\Tests\Util\Logger\LoggingUtil;

/**
 * Class TestFrameworkException
 */
class TestFrameworkException extends \Exception
{
    /**
     * Exception context
     *
     * @var array
     */
    protected $context;

    /**
     * TestFrameworkException constructor.
     * @param string $message
     * @param array  $context
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function __construct($message, $context = [])
    {
        list($childClass, $callingClass) = debug_backtrace(false, 2);
        LoggingUtil::getInstance()->getLogger($callingClass['class'])->error(
            $message,
            $context
        );

        $this->context = $context;
        parent::__construct($message);
    }

    /**
     * Return exception context
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
