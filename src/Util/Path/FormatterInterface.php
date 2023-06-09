<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MFTFBuddy\Tests\Util\Path;

use MFTFBuddy\Tests\Exceptions\TestFrameworkException;

interface FormatterInterface
{
    /**
     * Return formatted path (file path, url, etc) from input string, or false on error.
     *
     * @param string  $input
     * @param boolean $withTrailingSeparator
     *
     * @return string
     * @throws TestFrameworkException
     */
    public static function format(string $input, bool $withTrailingSeparator = true): string;
}
