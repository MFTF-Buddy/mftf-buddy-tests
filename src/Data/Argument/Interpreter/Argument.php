<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MFTFBuddy\Tests\Data\Argument\Interpreter;

use MFTFBuddy\Tests\Data\Argument\InterpreterInterface;

/**
 * Interpreter that returns value of an application argument, retrieving its name from a constant
 */
class Argument implements InterpreterInterface
{
    /**
     * Interpreter that returns value of a constant by its name.
     *
     * @var Constant
     */
    private $constInterpreter;

    /**
     * Argument constructor.
     * @param Constant $constInterpreter
     */
    public function __construct(Constant $constInterpreter)
    {
        $this->constInterpreter = $constInterpreter;
    }

    /**
     * Compute and return effective value of an argument.
     *
     * @param array $data
     * @return array
     */
    public function evaluate(array $data)
    {
        return ['argument' => $this->constInterpreter->evaluate($data)];
    }
}
