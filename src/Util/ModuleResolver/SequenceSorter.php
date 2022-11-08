<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\Util\ModuleResolver;

/**
 * Module sequence sorter.
 */
class SequenceSorter implements SequenceSorterInterface
{
    /**
     * Sort files according to specified sequence.
     *
     * @param array $paths
     * @return array
     */
    public function sort(array $paths)
    {
        return $paths;
    }
}
