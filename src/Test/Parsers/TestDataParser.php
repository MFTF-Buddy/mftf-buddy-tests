<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\Test\Parsers;

use MFTFBuddy\Tests\Config\DataInterface;
use MFTFBuddy\Tests\Exceptions\TestFrameworkException;

/**
 * Class TestDataParser
 */
class TestDataParser
{
    /**
     * @var DataInterface
     */
    private $testData;

    /**
     * TestDataParser constructor.
     *
     * @param DataInterface $testData
     * @throws \MFTFBuddy\Tests\Exceptions\TestFrameworkException
     */
    public function __construct(DataInterface $testData)
    {
        $this->testData = array_filter($testData->get('tests'), function ($value) {
            return is_array($value);
        });
    }

    /**
     * Returns an array of data based on *Test.xml files
     *
     * @return array
     * @throws TestFrameworkException
     */
    public function readTestData()
    {
        return $this->testData;
    }
}
