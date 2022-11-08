<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

 namespace MFTFBuddy\Tests\Config\Reader;

use MFTFBuddy\Tests\Config\MftfApplicationConfig;
use MFTFBuddy\Tests\Exceptions\FastFailException;
use MFTFBuddy\Tests\Util\Logger\LoggingUtil;

/**
 * Filesystem configuration loader. Loads configuration from XML files, split by scopes.
 */
class Filesystem implements \MFTFBuddy\Tests\Config\ReaderInterface
{
    /**
     * File locator
     *
     * @var \MFTFBuddy\Tests\Config\FileResolverInterface
     */
    protected $fileResolver;

    /**
     * Config converter
     *
     * @var \MFTFBuddy\Tests\Config\ConverterInterface
     */
    protected $converter;

    /**
     * The name of file that stores configuration
     *
     * @var string
     */
    protected $fileName;

    /**
     * Path to corresponding XSD file with validation rules for merged config
     *
     * @var string
     */
    protected $schema;

    /**
     * Path to corresponding XSD file with validation rules for separate config files
     *
     * @var string
     */
    protected $perFileSchema;

    /**
     * List of id attributes for merge
     *
     * @var array
     */
    protected $idAttributes = [];

    /**
     * Class of dom configuration document used for merge
     *
     * @var string
     */
    protected $domDocumentClass;

    /**
     * Config validation state object.
     *
     * @var \MFTFBuddy\Tests\Config\ValidationStateInterface
     */
    protected $validationState;

    /**
     * Default scope.
     *
     * @var string
     */
    protected $defaultScope;

    /**
     * File path to schema file.
     *
     * @var string
     */
    protected $schemaFile;

    /**
     * Constructor
     *
     * @param \MFTFBuddy\Tests\Config\FileResolverInterface    $fileResolver
     * @param \MFTFBuddy\Tests\Config\ConverterInterface       $converter
     * @param \MFTFBuddy\Tests\Config\SchemaLocatorInterface   $schemaLocator
     * @param \MFTFBuddy\Tests\Config\ValidationStateInterface $validationState
     * @param string                                           $fileName
     * @param array                                            $idAttributes
     * @param string                                           $domDocumentClass
     * @param string                                           $defaultScope
     */
    public function __construct(
        \MFTFBuddy\Tests\Config\FileResolverInterface $fileResolver,
        \MFTFBuddy\Tests\Config\ConverterInterface $converter,
        \MFTFBuddy\Tests\Config\SchemaLocatorInterface $schemaLocator,
        \MFTFBuddy\Tests\Config\ValidationStateInterface $validationState,
        $fileName,
        $idAttributes = [],
        $domDocumentClass = \MFTFBuddy\Tests\Config\Dom::class,
        $defaultScope = 'global'
    ) {
        $this->fileResolver = $fileResolver;
        $this->converter = $converter;
        $this->fileName = $fileName;
        $this->idAttributes = array_replace($this->idAttributes, $idAttributes);
        $this->validationState = $validationState;
        $this->schemaFile = $schemaLocator->getSchema();
        $this->perFileSchema = $schemaLocator->getPerFileSchema() && $validationState->isValidationRequired()
            ? $schemaLocator->getPerFileSchema() : null;
        $this->domDocumentClass = $domDocumentClass;
        $this->defaultScope = $defaultScope;
    }

    /**
     * Load configuration scope
     *
     * @param string|null $scope
     * @return array
     */
    public function read($scope = null)
    {
        $fileList = $this->getFileList($scope);
        if (!count($fileList)) {
            return [];
        }
        $output = $this->readFiles($fileList);

        return $output;
    }

    // pc ->
    /**
     * @param string|null $scope
     * @return array|\MFTFBuddy\Tests\Util\Iterator\File
     */
    public function getFileList($scope = null)
    {
        $scope = $scope ?: $this->defaultScope;
        $fileList = $this->fileResolver->get($this->fileName, $scope);

        return $fileList;
    }
    // pc <-

    /**
     * Read configuration files
     *
     * @param \MFTFBuddy\Tests\Util\Iterator\File $fileList
     * @return array
     * @throws \Exception
     */
    protected function readFiles($fileList)
    {
        /** @var \MFTFBuddy\Tests\Config\Dom $configMerger */
        $configMerger = null;
        $debugLevel = MftfApplicationConfig::getConfig()->getDebugLevel();
        foreach ($fileList as $content) {
            //check if file is empty and continue to next if it is
            if (!$this->verifyFileEmpty($content, $fileList->getFilename())) {
                continue;
            }
            try {
                if (!$configMerger) {
                    $configMerger = $this->createConfigMerger($this->domDocumentClass, $content);
                } else {
                    $configMerger->merge($content);
                }
                if (strcasecmp($debugLevel, MftfApplicationConfig::LEVEL_DEVELOPER) === 0) {
                    $this->validateSchema($configMerger, $fileList->getFilename());
                }
            } catch (\MFTFBuddy\Tests\Config\Dom\ValidationException $e) {
                throw new \Exception("Invalid XML in file " . $fileList->getFilename() . ":\n" . $e->getMessage());
            }
        }
        $this->validateSchema($configMerger);

        $output = [];
        if ($configMerger) {
            $output = $this->converter->convert($configMerger->getDom());
        }
        return $output;
    }

    /**
     * Return newly created instance of a config merger
     *
     * @param string $mergerClass
     * @param string $initialContents
     * @return \MFTFBuddy\Tests\Config\Dom
     * @throws \UnexpectedValueException
     */
    protected function createConfigMerger($mergerClass, $initialContents)
    {
        $result = new $mergerClass(
            $initialContents,
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

    /**
     * Checks if content is empty and logs warning, returns false if file is empty
     *
     * @param string $content
     * @param string $fileName
     * @return boolean
     */
    protected function verifyFileEmpty($content, $fileName)
    {
        if (empty($content)) {
            if (MftfApplicationConfig::getConfig()->verboseEnabled()) {
                LoggingUtil::getInstance()->getLogger(Filesystem::class)->warning(
                    "XML File is empty.",
                    ["File" => $fileName]
                );
            }
            return false;
        }
        return true;
    }

    /**
     * Validate read xml against expected schema
     *
     * @param string $configMerger
     * @param string $filename
     * @throws \Exception
     * @return void
     */
    protected function validateSchema($configMerger, $filename = null)
    {
        if ($this->validationState->isValidationRequired()) {
            $errors = [];
            if ($configMerger && !$configMerger->validate($this->schemaFile, $errors)) {
                foreach ($errors as $error) {
                    $error = str_replace(PHP_EOL, "", $error);
                    LoggingUtil::getInstance()->getLogger(Filesystem::class)->criticalFailure(
                        "Schema validation error ",
                        ($filename ? [ "file"=> $filename, "error" => $error]: ["error" => $error]),
                        true
                    );
                }
                throw new FastFailException("Schema validation errors found in xml file(s)" . $filename);
            }
        }
    }
}
