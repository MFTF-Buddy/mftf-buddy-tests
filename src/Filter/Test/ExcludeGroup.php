<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace MFTFBuddy\Tests\Filter\Test;

use MFTFBuddy\Tests\Exceptions\TestFrameworkException;
use MFTFBuddy\Tests\Filter\FilterInterface;
use MFTFBuddy\Tests\Test\Objects\TestObject;

/**
 * Class ExcludeGroup
 */
class ExcludeGroup implements FilterInterface
{
    const ANNOTATION_TAG = 'group';

    /**
     * @var array
     */
    private $filterValues = [];

    /**
     * Group constructor.
     *
     * @param array $filterValues
     * @throws TestFrameworkException
     */
    public function __construct(array $filterValues = [])
    {
        $this->filterValues = $filterValues;
    }

    /**
     * Filter tests by group.
     *
     * @param TestObject[] $tests
     * @return void
     */
    public function filter(array &$tests)
    {
        if ($this->filterValues === []) {
            return;
        }
        /** @var TestObject $test */
        foreach ($tests as $testName => $test) {
            $groups = $test->getAnnotationByName(self::ANNOTATION_TAG);
            $testExcludeGroup = !empty(array_intersect($groups, $this->filterValues));
            if ($testExcludeGroup) {
                unset($tests[$testName]);
            }
        }
    }
}
