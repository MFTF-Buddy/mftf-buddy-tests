<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\Exceptions;

use MFTFBuddy\Tests\Util\Logger\LoggingUtil;

/**
 * Class XmlException
 */
class XmlException extends \Exception
{
    /**
     * XmlException constructor.
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

        parent::__construct($message);
    }
}
