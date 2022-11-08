<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MFTFBuddy\Tests\ObjectManager\Config\Reader;

/**
 * Class Dom
 *
 * @internal
 */
// @codingStandardsIgnoreFile
class Dom extends \MFTFBuddy\Tests\Config\Reader\Filesystem
{
    /**
     * Name of an attribute that stands for data type of node values
     */
    const TYPE_ATTRIBUTE = 'xsi:type';

    /**
     * Dom constructor.
     * @param \MFTFBuddy\Tests\Config\FileResolverInterface $fileResolver
     * @param \MFTFBuddy\Tests\ObjectManager\Config\Mapper\Dom $converter
     * @param \MFTFBuddy\Tests\ObjectManager\Config\SchemaLocator $schemaLocator
     * @param \MFTFBuddy\Tests\Config\ValidationStateInterface $validationState
     * @param string $fileName
     * @param array $idAttributes
     * @param string $domDocumentClass
     * @param string $defaultScope
     */
    public function __construct(
        \MFTFBuddy\Tests\Config\FileResolverInterface $fileResolver,
        \MFTFBuddy\Tests\ObjectManager\Config\Mapper\Dom $converter,
        \MFTFBuddy\Tests\ObjectManager\Config\SchemaLocator $schemaLocator,
        \MFTFBuddy\Tests\Config\ValidationStateInterface $validationState,
        $fileName = 'di.xml',
        $idAttributes = [
            '/config/preference' => 'for',
            '/config/(type|virtualType)' => 'name',
            '/config/(type|virtualType)/arguments/argument' => 'name',
            '/config/(type|virtualType)/arguments/argument(/item)+' => 'name'
        ],
        $domDocumentClass = 'MFTFBuddy\Tests\Config\Dom',
        $defaultScope = 'etc'
    ) {
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName,
            $idAttributes,
            $domDocumentClass,
            $defaultScope
        );
    }

    /**
     * Create and return a config merger instance that takes into account types of arguments
     *
     * @param string $mergerClass
     * @param string $initialContents
     * @return \MFTFBuddy\Tests\Config\Dom
     */
    protected function _createConfigMerger($mergerClass, $initialContents)
    {
        return new $mergerClass($initialContents, $this->_idAttributes, self::TYPE_ATTRIBUTE, $this->_perFileSchema);
    }
}
