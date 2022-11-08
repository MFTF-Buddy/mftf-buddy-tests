<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MFTFBuddy\Tests\DataGenerator\Config;

use MFTFBuddy\Tests\Exceptions\Collector\ExceptionCollector;
use MFTFBuddy\Tests\Util\Validation\DuplicateNodeValidationUtil;

/**
 * MFTF actionGroup.xml configuration XML DOM utility
 * @package MFTFBuddy\Tests\DataGenerator\Config
 */
class Dom extends \MFTFBuddy\Tests\Config\MftfDom
{
    // pc ->
    // const DATA_FILE_NAME_ENDING = "Data";
    const DATA_FILE_NAME_ENDING = "Data.xml";
    // pc <-
    const DATA_META_FILENAME_ATTRIBUTE = "filename";
    const DATA_META_NAME_ATTRIBUTE = "name";

    /**
     * NodeValidationUtil
     * @var DuplicateNodeValidationUtil
     */
    private $validationUtil;

    /**
     * Entity Dom constructor.
     * @param string             $xml
     * @param string             $filename
     * @param ExceptionCollector $exceptionCollector
     * @param array              $idAttributes
     * @param string             $typeAttributeName
     * @param string             $schemaFile
     * @param string             $errorFormat
     */
    public function __construct(
        $xml,
        $filename,
        $exceptionCollector,
        array $idAttributes = [],
        $typeAttributeName = null,
        $schemaFile = null,
        $errorFormat = self::ERROR_FORMAT_DEFAULT
    ) {
        $this->validationUtil = new DuplicateNodeValidationUtil('key', $exceptionCollector);
        parent::__construct(
            $xml,
            $filename,
            $exceptionCollector,
            $idAttributes,
            $typeAttributeName,
            $schemaFile,
            $errorFormat
        );
    }

    /**
     * Takes a dom element from xml and appends the filename based on location
     *
     * @param string      $xml
     * @param string|null $filename
     * @return \DOMDocument
     */
    public function initDom($xml, $filename = null)
    {
        $dom = parent::initDom($xml, $filename);

        if (strpos($filename, self::DATA_FILE_NAME_ENDING)) {
            $entityNodes = $dom->getElementsByTagName('entity');
            foreach ($entityNodes as $entityNode) {
                /** @var \DOMElement $entityNode */
                $entityNode->setAttribute(self::DATA_META_FILENAME_ATTRIBUTE, $filename);
                $this->validationUtil->validateChildUniqueness(
                    $entityNode,
                    $filename,
                    $entityNode->getAttribute(self::DATA_META_NAME_ATTRIBUTE)
                );
            }
        }

        $itemNodes = $dom->getElementsByTagName('item');
        /** @var \DOMElement $itemNode */
        foreach ($itemNodes as $itemKey => $itemNode) {
            if ($itemNode->hasAttribute("name") === false) {
                $itemNode->setAttribute("name", (string)$itemKey);
            }
        }
        return $dom;
    }
}
