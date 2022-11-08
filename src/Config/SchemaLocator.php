<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\Config;

use MFTFBuddy\Tests\Exceptions\TestFrameworkException;
use MFTFBuddy\Tests\Util\Path\FilePathFormatter;

/**
 * Configuration schema locator.
 */
class SchemaLocator implements \MFTFBuddy\Tests\Config\SchemaLocatorInterface
{
    /**
     * Path to corresponding XSD file with validation rules for merged config.
     *
     * @var string
     */
    private $schemaPath;

    /**
     * Path to corresponding XSD file with validation rules for separate config files.
     *
     * @var string
     */
    private $perFileSchema;

    /**
     * Class constructor
     *
     * @param string      $schemaPath
     * @param string|null $perFileSchema
     * @throws TestFrameworkException
     */
    public function __construct($schemaPath, $perFileSchema = null)
    {
        if (constant('MB_FW_BP') && file_exists(FilePathFormatter::format(MB_FW_BP) . $schemaPath)) {
            $this->schemaPath = FilePathFormatter::format(MB_FW_BP) . $schemaPath;
            $this->perFileSchema = $perFileSchema === null ? null : FilePathFormatter::format(MB_FW_BP)
                . $perFileSchema;
        } else {
            $path = dirname(dirname(dirname(__DIR__)));
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
            $this->schemaPath = $path . DIRECTORY_SEPARATOR . $schemaPath;
            $this->perFileSchema = $perFileSchema === null ? null : $path . DIRECTORY_SEPARATOR . $perFileSchema;
        }
    }

    /**
     * Get path to merged config schema
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->schemaPath;
    }

    /**
     * Get path to pre file validation schema
     *
     * @return null
     */
    public function getPerFileSchema()
    {
        return $this->perFileSchema;
    }
}
