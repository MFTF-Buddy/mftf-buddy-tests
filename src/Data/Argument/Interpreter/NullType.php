<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MFTFBuddy\Tests\Data\Argument\Interpreter;

use MFTFBuddy\Tests\Data\Argument\InterpreterInterface;

/**
 * Interpreter of NULL data type
 */
class NullType implements InterpreterInterface
{
    /**
     * {@inheritdoc}
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function evaluate(array $data)
    {
        return null;
    }
}
