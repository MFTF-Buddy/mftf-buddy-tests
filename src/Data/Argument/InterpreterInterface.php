<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MFTFBuddy\Tests\Data\Argument;

/**
 * Interface that encapsulates complexity of expression computation
 */
interface InterpreterInterface
{
    /**
     * Compute and return effective value of an argument
     *
     * @param array $data
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function evaluate(array $data);
}
