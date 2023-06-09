<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\Page\Config;

use MFTFBuddy\Tests\Config\MftfApplicationConfig;
use MFTFBuddy\Tests\Exceptions\Collector\ExceptionCollector;
use MFTFBuddy\Tests\Config\Dom\NodeMergingConfig;
use MFTFBuddy\Tests\Config\Dom\NodePathMatcher;
use MFTFBuddy\Tests\Util\ModulePathExtractor;
use MFTFBuddy\Tests\Util\Validation\DuplicateNodeValidationUtil;
use MFTFBuddy\Tests\Util\Validation\SingleNodePerFileValidationUtil;

/**
 * MFTF page.xml configuration XML DOM utility
 * @package MFTFBuddy\Tests\Page\Config
 */
class Dom extends \MFTFBuddy\Tests\Config\MftfDom
{
    const PAGE_META_FILENAME_ATTRIBUTE = "filename";
    const PAGE_META_NAME_ATTRIBUTE = "name";

    /**
     * Module Path extractor
     *
     * @var ModulePathExtractor
     */
    private $modulePathExtractor;

    /**
     * NodeValidationUtil
     *
     * @var DuplicateNodeValidationUtil
     */
    private $validationUtil;

    /** SingleNodePerFileValidationUtil
     *
     * @var SingleNodePerFileValidationUtil
     */
    private $singleNodePerFileValidationUtil;

    /**
     * Page Dom constructor.
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
        $this->modulePathExtractor = new ModulePathExtractor();
        $this->validationUtil = new DuplicateNodeValidationUtil('name', $exceptionCollector);
        $this->singleNodePerFileValidationUtil = new SingleNodePerFileValidationUtil($exceptionCollector);
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

        if ($dom->getElementsByTagName('pages')->length > 0) {
            /** @var \DOMElement $pagesNode */
            $pagesNode = $dom->getElementsByTagName('pages')[0];
            $this->validationUtil->validateChildUniqueness(
                $pagesNode,
                $filename,
                $pagesNode->getAttribute(self::PAGE_META_NAME_ATTRIBUTE)
            );

            // Validate single page node per file
            $this->singleNodePerFileValidationUtil->validateSingleNodeForTag(
                $dom,
                'page',
                $filename
            );

            if ($dom->getElementsByTagName('page')->length > 0) {
                /** @var \DOMElement $pageNode */
                $pageNode = $dom->getElementsByTagName('page')[0];
                $currentModule =
                    $this->modulePathExtractor->getExtensionPath($filename)
                    . '_'
                    . $this->modulePathExtractor->extractModuleName($filename);
                $pageModule = $pageNode->getAttribute("module");
                $pageName = $pageNode->getAttribute("name");
                if ($pageModule !== $currentModule) {
                    if (MftfApplicationConfig::getConfig()->verboseEnabled()) {
                        fwrite(
                            STDERR,
                            "Page Module does not match path Module. " .
                            "(Page, Module): ($pageName, $pageModule) - Path Module: $currentModule" .
                            PHP_EOL
                        );
                    }
                }
                $pageNode->setAttribute(self::PAGE_META_FILENAME_ATTRIBUTE, $filename);
            }
        }

        return $dom;
    }
}
