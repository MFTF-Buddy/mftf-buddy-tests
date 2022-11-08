<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MFTFBuddy\Tests\Config;

/**
 * Interface FileResolverInterface
 */
interface FileResolverInterface
{
    /**
     * Retrieve the list of configuration files with given name that relate to specified scope
     *
     * @param string $filename
     * @param string $scope
     * @return array|\MFTFBuddy\Tests\Util\Iterator\File
     */
    public function get($filename, $scope);
}
