<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace MFTFBuddy\Tests\Filter;

use MFTFBuddy\Tests\Exceptions\TestFrameworkException;

/**
 * Class FilterList has a list of filters.
 */
class FilterList
{
    /**
     * List of filters
     * @var \MFTFBuddy\Tests\Filter\FilterInterface[]
     */
    private $filters = [];

    /**
     * Constructor for Filter list.
     *
     * @param array $filters
     * @throws \Exception
     */
    public function __construct(array $filters = [])
    {
        foreach ($filters as $filterType => $filterValue) {
            $className = "MFTFBuddy\Tests\Filter\Test\\" . ucfirst($filterType);
            if (!class_exists($className)) {
                throw new TestFrameworkException("Filter type '" . $filterType . "' do not exist.");
            }
            $this->filters[$filterType] = new $className($filterValue);
        }
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param string $filterType
     * @return \MFTFBuddy\Tests\Filter\FilterInterface
     */
    public function getFilter(string $filterType): FilterInterface
    {
        return $this->filters[$filterType];
    }
}
