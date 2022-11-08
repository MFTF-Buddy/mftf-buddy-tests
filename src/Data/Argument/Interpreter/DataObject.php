<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MFTFBuddy\Tests\Data\Argument\Interpreter;

use MFTFBuddy\Tests\Data\Argument\InterpreterInterface;
use MFTFBuddy\Tests\Stdlib\BooleanUtils;

class DataObject implements InterpreterInterface
{
    /**
     * Utility methods for the boolean data type.
     *
     * @var \MFTFBuddy\Tests\Stdlib\BooleanUtils
     */
    protected $booleanUtils;

    /**
     * DataObject constructor.
     * @param BooleanUtils $booleanUtils
     */
    public function __construct(BooleanUtils $booleanUtils)
    {
        $this->booleanUtils = $booleanUtils;
    }

    /**
     * Compute and return effective value of an argument
     *
     * @param array $data
     * @return array
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function evaluate(array $data)
    {
        $result = ['instance' => $data['value']];
        if (isset($data['shared'])) {
            $result['shared'] = $this->booleanUtils->toBoolean($data['shared']);
        }
        return $result;
    }
}
