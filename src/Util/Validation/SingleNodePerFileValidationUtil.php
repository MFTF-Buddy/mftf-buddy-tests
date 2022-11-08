<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\Util\Validation;

use MFTFBuddy\Tests\Exceptions\Collector\ExceptionCollector;

/**
 * Class SingleNodePerDocumentValidationUtil
 * @package MFTFBuddy\Tests\Util\Validation
 */
class SingleNodePerFileValidationUtil
{
    /**
     * ExceptionColletor used to catch errors
     *
     * @var ExceptionCollector
     */
    private $exceptionCollector;

    /**
     * SingleNodePerDocumentValidationUtil constructor
     *
     * @param ExceptionCollector $exceptionCollector
     */
    public function __construct($exceptionCollector)
    {
        $this->exceptionCollector = $exceptionCollector;
    }

    /**
     * Validate single node per dom document for a given tag name
     *
     * @param \DOMDocument $dom
     * @param string       $tag
     * @param string       $filename
     * @return void
     */
    public function validateSingleNodeForTag($dom, $tag, $filename = '')
    {
        $tagNodes = $dom->getElementsByTagName($tag);
        $count = $tagNodes->length;
        if ($count === 1) {
            return;
        }

        $errorMsg = "Single <{$tag}> node per xml file. {$count} found in file: {$filename}\n";
        $this->exceptionCollector->addError($filename, $errorMsg);
    }
}
