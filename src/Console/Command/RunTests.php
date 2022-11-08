<?php

declare(strict_types=1);

namespace MFTFBuddy\Tests\Console\Command;

use CURLFile;
use DOMElement;
use Exception;
use Magento\Framework\App\ProductMetadataInterface;
use MFTFBuddy\Tests\Config\Dom;
use MFTFBuddy\Tests\DataGenerator\Parsers\DataProfileSchemaParser;
use MFTFBuddy\Tests\DataGenerator\Parsers\OperationDefinitionParser;
use MFTFBuddy\Tests\DataTransport\Protocol\CurlInterface;
use MFTFBuddy\Tests\DataTransport\Protocol\CurlTransport;
use MFTFBuddy\Tests\ObjectManagerFactory;
use MFTFBuddy\Tests\Suite\Parsers\SuiteDataParser;
use MFTFBuddy\Tests\Test\Parsers\ActionGroupDataParser;
use MFTFBuddy\Tests\Test\Parsers\TestDataParser;
use MFTFBuddy\Tests\XmlParser\PageParser;
use MFTFBuddy\Tests\XmlParser\SectionParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RunTests extends Command
{
    private const ARG_SUITE_NAME = 'suite-name';
    private const PAGE = 'page';
    private const SECTION = 'section';
    private const MODULE_DOM_NODE = 'module';
    private const MODULE_MAP_FILE_NAME = 'module_map.json';
    private const TEST_MFTF_PATH_PART = 'Test/Mftf';
    private const APP_CODE_PATH_PART = 'app/code';

    protected ProductMetadataInterface $productMetadata;
    protected array $moduleCodeForModuleFilePath = [];

    public function __construct(
        ProductMetadataInterface $productMetadata,
        string $name = null
    ) {
        parent::__construct($name);

        $this->productMetadata = $productMetadata;
    }

    protected function configure(): void
    {
        $this->setName('mftf-buddy:run-tests');
        $this->setDescription('Run tests');
        $this->addArgument(
            self::ARG_SUITE_NAME,
            InputArgument::REQUIRED,
            'The name of the suite to be runned.'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): void {
        $suiteName = $input->getArgument(self::ARG_SUITE_NAME);

        $fileNames = $this->collectFiles($output);

        $testBundleId = $this->createTestBundle(
            $output,
            $fileNames
        );

        $testSessionId = $this->createTestSession(
            $output,
            $suiteName,
            $testBundleId
        );
    }

    protected function collectFiles(OutputInterface $output): array
    {
        $objectManager = ObjectManagerFactory::getObjectManager();

        $suiteFileNames = [];
        $testDataParser = $objectManager->create(SuiteDataParser::class);
        $parserOutput = $testDataParser->readSuiteData();
        foreach ($parserOutput['suites'] as $entry) {
            $fileNames = explode(',', $entry['filename'] ?? '');
            $suiteFileNames[] = $fileNames;
        }

        $testFileNames = [];
        $testDataParser = $objectManager->create(TestDataParser::class);
        $parserOutput = $testDataParser->readTestData();
        foreach ($parserOutput as $entry) {
            $fileNames = explode(',', $entry['filename']);
            $testFileNames[] = $fileNames;
        }

        $pageFileNames = [];
        $parser = $objectManager->get(PageParser::class);
        $parserOutput = $parser->getData(self::PAGE);
        foreach ($parserOutput as $entry) {
            $fileNames = explode(',', $entry['filename']);
            $pageFileNames[] = $fileNames;
        }

        $sectionFileNames = [];
        $parser = $objectManager->get(SectionParser::class);
        $parserOutput = $parser->getData(self::SECTION);
        foreach ($parserOutput as $entry) {
            $fileNames = explode(',', $entry['filename']);
            $sectionFileNames[] = $fileNames;
        }

        $actionGroupFileNames = [];
        $parser = $objectManager->create(ActionGroupDataParser::class);
        $parserOutput = $parser->readActionGroupData();
        foreach ($parserOutput['actionGroups'] as $entry) {
            $fileNames = explode(',', $entry['filename'] ?? '');
            $actionGroupFileNames[] = $fileNames;
        }

        $dataFileNames = [];
        $parser = $objectManager->create(DataProfileSchemaParser::class);
        $parserOutput = $parser->readDataProfiles();
        foreach ($parserOutput['entity'] as $entry) {
            $fileNames = explode(',', $entry['filename']);
            $dataFileNames[] = $fileNames;
        }

        $metadataFileNames = [];
        $parser = $objectManager->create(OperationDefinitionParser::class);
        $parserOutput = $parser->readOperationMetadata();
        foreach ($parserOutput['operation'] as $entry) {
            $fileNames = explode(',', $entry['filename']);
            $metadataFileNames[] = $fileNames;
        }

        $result = array_merge(
            [],
            ...$suiteFileNames,
            ...$testFileNames,
            ...$pageFileNames,
            ...$sectionFileNames,
            ...$actionGroupFileNames,
            ...$dataFileNames,
            ...$metadataFileNames
        );

        $result = array_values(array_unique($result));

        $result = array_filter(
            $result,
            function ($fileName) {
                return strpos($fileName, BP) === 0;
            }
        );

        return $result;
    }

    protected function createTestBundle(
        OutputInterface $output,
        array $fileNames
    ): string {
        // prepare zip file
        $tmpZipFileName = tempnam('/tmp', 'zip');
        try {
            $zip = new ZipArchive();
            $zip->open($tmpZipFileName, ZipArchive::OVERWRITE);
            try {
                // add content, create map
                $fileNamePrefixSize = strlen(BP) + 1;
                $map = [];
                foreach ($fileNames as $localFilePath) {
                    if (false === strpos($localFilePath, self::TEST_MFTF_PATH_PART)) {
                        continue;
                    }

                    $remoteFilePath = substr($localFilePath, $fileNamePrefixSize);
                    $moduleCode = $this->getModuleCodeForLocalFilePath($localFilePath);

                    // move non-Magento modules from vendor to app/code
                    if (
                        0 === strpos($remoteFilePath, 'vendor/')
                        && 0 !== strpos($remoteFilePath, 'vendor/magento/')
                    ) {
                        list($moduleVendor, $moduleName) = explode('_', $moduleCode);

                        $remoteFilePath = self::APP_CODE_PATH_PART
                            . "/$moduleVendor/$moduleName/"
                            . substr(
                                $localFilePath,
                                strpos($localFilePath, self::TEST_MFTF_PATH_PART)
                            );

                    }

                    $zip->addFile($localFilePath, $remoteFilePath);

                    $map[$remoteFilePath] = $moduleCode;
                }

                // add module map
                $zip->addFromString(self::MODULE_MAP_FILE_NAME, json_encode($map));

            } finally {
                $zip->close();
            }

            $transport = $this->getTransport();

            $body = [
                'secretKey' => getenv('MB_SECRET_KEY'),
                'file' => new CURLFile(
                    $tmpZipFileName,
                    'application/octet-stream',
                    $tmpZipFileName . '.zip'
                ),
            ];
            $transport->write(
                rtrim(getenv('MB_API_BASE_URL'), '/') . '/test_bundles',
                $body,
                CurlInterface::POST,
                [
                    'Accept: application/ld+json',
                    'Content-Type: multipart/form-data',
                ]
            );
            try {
                $response = $transport->read();
            } finally {
                $transport->close();
            }
        } finally {
            unlink($tmpZipFileName);
        }

        $response = json_decode($response, true);

        $type = $response['@type'] ?? '';
        if ($type !== 'TestBundle') {
            throw new Exception("Unexpected type '$type'");
        }

        $id = $response['@id'] ?? '';

        $matches = [];
        if (!preg_match(':^/test_bundles/(.*)$:', $id, $matches)) {
            throw new Exception("Non-parsable id '$id'");
        }
        $result = $matches[1];

        return $result;
    }

    protected function getModuleCodeForLocalFilePath(string $localFilePath): string
    {
        $moduleFilePath = substr(
                $localFilePath,
                0,
                strpos($localFilePath, self::TEST_MFTF_PATH_PART)
            )
            . 'etc/module.xml';

        if (!array_key_exists($moduleFilePath, $this->moduleCodeForModuleFilePath)) {
            $moduleFileContents = file_get_contents($moduleFilePath);
            if (false === $moduleFileContents) {
                throw new Exception("Error reading file '$moduleFilePath'");
            }

            $moduleDom = new Dom($moduleFileContents);

            /** @var DOMElement $moduleNode */
            $moduleNode = $moduleDom->getDom()->getElementsByTagName(self::MODULE_DOM_NODE)[0];
            if (!$moduleNode) {
                throw new Exception("Module node not found in file '$moduleFilePath'");
            }

            $this->moduleCodeForModuleFilePath[$moduleFilePath] = $moduleNode->getAttribute('name');
        }

        return $this->moduleCodeForModuleFilePath[$moduleFilePath];
    }

    protected function createTestSession(
        OutputInterface $output,
        string $suiteName,
        string $testBundleId
    ): string {
        $transport = $this->getTransport();

        $additionalVars = getenv('MB_ADDITIONAL_VARS');
        $settings = [
            #*** Set secret key for MFTF Buddy ***#
            'MB_SECRET_KEY' => getenv('MB_SECRET_KEY'),

            #*** Number of parallel groups of MFTF Buddy tests ***#
            'MB_GROUPS' => getenv('MB_GROUPS'),

            #*** Browsers for running tests on MFTF Buddy ***#
            'MB_BROWSERS' => getenv('MB_BROWSERS'),

            #*** Set the base URL for your Magento instance ***#
            'MB_MAGENTO_BASE_URL' => getenv('MB_MAGENTO_BASE_URL'),

            #*** Uncomment if you are running Admin Panel on separate domain (used with MAGENTO_BACKEND_NAME) ***#
            'MB_MAGENTO_BACKEND_BASE_URL' => getenv('MB_MAGENTO_BACKEND_BASE_URL'),

            #*** Set the Admin Username and Password for your Magento instance ***#
            'MB_MAGENTO_BACKEND_NAME' => getenv('MB_MAGENTO_BACKEND_NAME'),
            'MB_MAGENTO_ADMIN_USERNAME' => getenv('MB_MAGENTO_ADMIN_USERNAME'),
            'MB_MAGENTO_ADMIN_PASSWORD' => getenv('MB_MAGENTO_ADMIN_PASSWORD'),

            #*** Path to CLI entry point and command parameter name. Uncomment and change if folder structure differs from standard Magento installation
            'MB_MAGENTO_CLI_COMMAND_PATH' => getenv('MB_MAGENTO_CLI_COMMAND_PATH'),
            'MB_MAGENTO_CLI_COMMAND_PARAMETER' => getenv('MB_MAGENTO_CLI_COMMAND_PARAMETER'),

            #*** Selenium Server Protocol, Host, Port, and Path, with local defaults. Uncomment and change if not running Selenium locally.
            // 'MB_SELENIUM_PROTOCOL' => getenv('MB_SELENIUM_PROTOCOL'), # unused
            // 'MB_SELENIUM_HOST' => getenv('MB_SELENIUM_HOST'), # unused
            // 'MB_SELENIUM_PORT' => getenv('MB_SELENIUM_PORT'), # unused
            // 'MB_SELENIUM_PATH' => getenv('MB_SELENIUM_PATH'), # unused
            // 'MB_SELENIUM_CLOSE_ALL_SESSIONS' => getenv('MB_SELENIUM_CLOSE_ALL_SESSIONS'), # unused

            #*** Browsers for running tests, default chrome. Uncomment and change if you want to run tests on another browser (ex. firefox).
            // 'MB_BROWSER' => getenv('MB_BROWSER'), # unused

            #*** Uncomment and set host & port if your dev environment needs different value other than MAGENTO_BASE_URL for Rest API Requests ***#
            'MB_MAGENTO_RESTAPI_SERVER_PROTOCOL' => getenv('MB_MAGENTO_RESTAPI_SERVER_PROTOCOL'),
            'MB_MAGENTO_RESTAPI_SERVER_HOST' => getenv('MB_MAGENTO_RESTAPI_SERVER_HOST'),
            'MB_MAGENTO_RESTAPI_SERVER_PORT' => getenv('MB_MAGENTO_RESTAPI_SERVER_PORT'),

            #*** To use HashiCorp Vault to manage _CREDS secrets, uncomment and set vault address and secret base path ***#
            // 'MB_CREDENTIAL_VAULT_ADDRESS' => getenv('MB_CREDENTIAL_VAULT_ADDRESS'), # unused
            // 'MB_CREDENTIAL_VAULT_SECRET_BASE_PATH' => getenv('MB_CREDENTIAL_VAULT_SECRET_BASE_PATH'), # unused

            #*** To use AWS Secrets Manager to manage _CREDS secrets, uncomment and set region, profile is optional, when omitted, AWS default credential provider chain will be used ***#
            // 'MB_CREDENTIAL_AWS_SECRETS_MANAGER_PROFILE' => getenv('MB_CREDENTIAL_AWS_SECRETS_MANAGER_PROFILE'), # unused
            // 'MB_CREDENTIAL_AWS_SECRETS_MANAGER_REGION' => getenv('MB_CREDENTIAL_AWS_SECRETS_MANAGER_REGION'), # unused

            #*** Uncomment this property to change the default timezone MFTF will use for the generateDate action ***#
            'MB_DEFAULT_TIMEZONE' => getenv('MB_DEFAULT_TIMEZONE'),

            #*** These properties impact the modules loaded into MFTF, you can point to your own full path, or a custom set of modules located with the core set
            'MB_MODULE_ALLOWLIST' => getenv('MB_MODULE_ALLOWLIST'),
            'MB_MODULE_BLOCKLIST' => getenv('MB_MODULE_BLOCKLIST'),
            'MB_MODULE_BLOCK_MAGENTO' => getenv('MB_MODULE_BLOCK_MAGENTO'),
            'MB_CUSTOM_MODULE_PATHS' => getenv('MB_CUSTOM_MODULE_PATHS'),

            #*** Bool property which allows the user to toggle debug output during test execution
            'MB_MFTF_DEBUG' => getenv('MB_MFTF_DEBUG'),

            #*** Bool property which allows the user to generate and run tests marked as skipped
            // 'MB_ALLOW_SKIPPED' => getenv('MB_ALLOW_SKIPPED'), # unused

            #*** Default timeout for wait actions
            'MB_WAIT_TIMEOUT' => getenv('MB_WAIT_TIMEOUT'),

            #*** Uncomment and set to enable all tests, regardless of passing status, to have all their Allure artifacts.
            'MB_VERBOSE_ARTIFACTS' => getenv('MB_VERBOSE_ARTIFACTS'),

            #*** Uncomment and set to enable browser log entries on actions in Allure. Blocklist is used to filter logs of a specific "source"
            'MB_ENABLE_BROWSER_LOG' => getenv('MB_ENABLE_BROWSER_LOG'),
            'MB_BROWSER_LOG_BLOCKLIST' => getenv('MB_BROWSER_LOG_BLOCKLIST'),

            #*** Uncomment and set to true to use Codeception's interactive pause functionality
            'MB_ENABLE_PAUSE' => getenv('MB_ENABLE_PAUSE'),

            #*** Elastic Search version used for test ***#
            'MB_ELASTICSEARCH_VERSION' => getenv('MB_ELASTICSEARCH_VERSION'),

            #*** Lifetime (in seconds) of Magento Admin WebAPI Token; if token is older than this value a refresh attempt will be made just before the next WebAPI call ***#
            'MB_MAGENTO_ADMIN_WEBAPI_TOKEN_LIFETIME' => getenv('MB_MAGENTO_ADMIN_WEBAPI_TOKEN_LIFETIME'),

            'MB_ADDITIONAL_VARS' => $additionalVars,

            'MB_SUITE_NAME' => $suiteName,
        ];
        foreach (array_filter(explode(',', $additionalVars)) as $var) {
            $settings[$var] = getenv($var);
        }

        $body = [
            #*** Set secret key for MFTF Buddy ***#
            'secretKey' => getenv('MB_SECRET_KEY'),
            'magentoEdition' => strtolower($this->productMetadata->getEdition()),
            'magentoVersion' => $this->productMetadata->getVersion(),
            'testBundle' => '/test_bundles/' . $testBundleId,
            'settings' => $settings,
        ];
        $transport->write(
            rtrim(getenv('MB_API_BASE_URL'), '/') . '/test_sessions',
            json_encode($body),
            CurlInterface::POST,
            [
                'Accept: application/ld+json',
                'Content-Type: application/ld+json',
            ]
        );
        try {
            $response = $transport->read();
        } finally {
            $transport->close();
        }

        $response = json_decode($response, true);

        $type = $response['@type'] ?? '';
        if ($type !== 'TestSession') {
            throw new Exception("Unexpected type '$type'");
        }

        $id = $response['@id'] ?? '';

        $matches = [];
        if (!preg_match(':^/test_sessions/(.*)$:', $id, $matches)) {
            throw new Exception("Non-parsable id '$id'");
        }
        $result = $matches[1];

        return $result;
    }

    protected function getTransport(): CurlTransport
    {
        $transport = new CurlTransport();
        $transport->addOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $transport->addOption(CURLOPT_TIMEOUT, getenv('MB_API_TIMEOUT'));

        return $transport;
    }
}
