<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\Config\Reader;

use MFTFBuddy\Tests\Config\MftfApplicationConfig;
use MFTFBuddy\Tests\Exceptions\Collector\ExceptionCollector;
use MFTFBuddy\Tests\Util\Iterator\File;

class MftfFilesystem extends \MFTFBuddy\Tests\Config\Reader\Filesystem
{
    /**
     * Method to redirect file name passing into Dom class
     *
     * @param File $fileList
     * @return array
     * @throws \Exception
     */
    public function readFiles($fileList)
    {
        // pc ->
        // $result = [];
        // foreach ($fileList as $file) {
        //     $result[] = $fileList->getFilename();

        // }
        // // return iterator_to_array($fileList);
        // return $result;
        // pc <-

        $exceptionCollector = new ExceptionCollector();
        /** @var \MFTFBuddy\Tests\Test\Config\Dom $configMerger */
        $configMerger = null;
        $debugLevel = MftfApplicationConfig::getConfig()->getDebugLevel();
        foreach ($fileList as $key => $content) {
            //check if file is empty and continue to next if it is
            if (!parent::verifyFileEmpty($content, $fileList->getFilename())) {
                continue;
            }
            try {
                if (!$configMerger) {
                    $configMerger = $this->createConfigMerger(
                        $this->domDocumentClass,
                        $content,
                        $fileList->getFilename(),
                        $exceptionCollector
                    );
                } else {
                    $configMerger->merge($content, $fileList->getFilename(), $exceptionCollector);
                }
                 // run per file validation with generate:tests -d
                if (strcasecmp($debugLevel, MftfApplicationConfig::LEVEL_DEVELOPER) === 0) {
                    $this->validateSchema($configMerger, $fileList->getFilename());
                }
            } catch (\MFTFBuddy\Tests\Config\Dom\ValidationException $e) {
                throw new \Exception("Invalid XML in file " . $key . ":\n" . $e->getMessage());
            }
        }
        $exceptionCollector->throwException();

         //run validation on merged file with generate:tests
        if (strcasecmp($debugLevel, MftfApplicationConfig::LEVEL_DEFAULT) === 0) {
            $this->validateSchema($configMerger);
        }

        $output = [];
        if ($configMerger) {
            $output = $this->converter->convert($configMerger->getDom());
        }
        return $output;
    }

    /**
     * Return newly created instance of a config merger
     *
     * @param string             $mergerClass
     * @param string             $initialContents
     * @param string             $filename
     * @param ExceptionCollector $exceptionCollector
     * @return \MFTFBuddy\Tests\Config\Dom
     * @throws \UnexpectedValueException
     */
    protected function createConfigMerger($mergerClass, $initialContents, $filename = null, $exceptionCollector = null)
    {
        $result = new $mergerClass(
            $initialContents,
            $filename,
            $exceptionCollector,
            $this->idAttributes,
            null,
            $this->perFileSchema
        );
        if (!$result instanceof \MFTFBuddy\Tests\Config\Dom) {
            throw new \UnexpectedValueException(
                "Instance of the DOM config merger is expected, got {$mergerClass} instead."
            );
        }
        return $result;
    }
}
