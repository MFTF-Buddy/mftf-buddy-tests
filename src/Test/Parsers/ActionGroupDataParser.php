<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\Test\Parsers;

use MFTFBuddy\Tests\Config\DataInterface;

/**
 * Class ActionGroupDataParser
 */
class ActionGroupDataParser
{
    /**
     * ActionGroupDataParser constructor.
     *
     * @param DataInterface $actionGroupData
     */
    public function __construct(DataInterface $actionGroupData)
    {
        $this->actionGroupData = $actionGroupData;
    }

    /**
     * Read action group xml and return as an array.
     *
     * @return array
     */
    public function readActionGroupData()
    {
        return $this->actionGroupData->get();
    }
}
