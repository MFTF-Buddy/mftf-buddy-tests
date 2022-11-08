<?php

declare(strict_types=1);

namespace MFTFBuddy\Tests\Config;

use Symfony\Component\Dotenv\Dotenv;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class EnvFile
{
    public const ENV_FILE_NAME = '.env_mb';

    protected const SOURCE_ENV_FILES = [
        '/dev/tests/acceptance/.env',
        '/vendor/magento/magento2-functional-testing-framework/etc/config/.env.example',
    ];

    protected const KNOWN_VARS = [
        #*** Set the base URL for your Magento instance ***#
        'MAGENTO_BASE_URL',

        #*** Uncomment if you are running Admin Panel on separate domain (used with MB_MAGENTO_BACKEND_NAME) ***#
        'MAGENTO_BACKEND_BASE_URL',

        #*** Set the Admin Username and Password for your Magento instance ***#
        'MAGENTO_BACKEND_NAME',
        'MAGENTO_ADMIN_USERNAME',
        'MAGENTO_ADMIN_PASSWORD',

        #*** Path to CLI entry point and command parameter name. Uncomment and change if folder structure differs from standard Magento installation
        'MAGENTO_CLI_COMMAND_PATH',
        'MAGENTO_CLI_COMMAND_PARAMETER',

        #*** Selenium Server Protocol, Host, Port, and Path, with local defaults. Uncomment and change if not running Selenium locally.
        'SELENIUM_PROTOCOL',
        'SELENIUM_HOST',
        'SELENIUM_PORT',
        'SELENIUM_PATH',
        'SELENIUM_CLOSE_ALL_SESSIONS',

        #*** Browser for running tests, default chrome. Uncomment and change if you want to run tests on another browser (ex. firefox).
        'BROWSER',

        #*** Uncomment and set host & port if your dev environment needs different value other than MAGENTO_BASE_URL for Rest API Requests ***#
        'MAGENTO_RESTAPI_SERVER_PROTOCOL',
        'MAGENTO_RESTAPI_SERVER_HOST',
        'MAGENTO_RESTAPI_SERVER_PORT',

        #*** To use HashiCorp Vault to manage _CREDS secrets, uncomment and set vault address and secret base path ***#
        'CREDENTIAL_VAULT_ADDRESS',
        'CREDENTIAL_VAULT_SECRET_BASE_PATH',

        #*** To use AWS Secrets Manager to manage _CREDS secrets, uncomment and set region, profile is optional, when omitted, AWS default credential provider chain will be used ***#
        'CREDENTIAL_AWS_SECRETS_MANAGER_PROFILE',
        'CREDENTIAL_AWS_SECRETS_MANAGER_REGION',

        #*** Uncomment these properties to set up a dev environment with symlinked projects ***#
        'MAGENTO_BP',
        'TESTS_BP',
        'FW_BP',
        'TESTS_MODULE_PATH',

        #*** Uncomment this property to change the default timezone MFTF will use for the generateDate action ***#
        'DEFAULT_TIMEZONE',

        #*** These properties impact the modules loaded into MFTF, you can point to your own full path, or a custom set of modules located with the core set
        'MODULE_ALLOWLIST',
        'MODULE_BLOCKLIST',
        'MODULE_BLOCK_MAGENTO',
        'CUSTOM_MODULE_PATHS',

        #*** Bool property which allows the user to toggle debug output during test execution
        'MFTF_DEBUG',

        #*** Bool property which allows the user to generate and run tests marked as skipped
        'ALLOW_SKIPPED',

        #*** Default timeout for wait actions
        'WAIT_TIMEOUT',

        #*** Uncomment and set to enable all tests, regardless of passing status, to have all their Allure artifacts.
        'VERBOSE_ARTIFACTS',

        #*** Uncomment and set to enable browser log entries on actions in Allure. Blocklist is used to filter logs of a specific "source"
        'ENABLE_BROWSER_LOG',
        'BROWSER_LOG_BLOCKLIST',

        #*** Uncomment and set to true to use Codeception's interactive pause functionality
        'ENABLE_PAUSE',

        #*** Elastic Search version used for test ***#
        'ELASTICSEARCH_VERSION',

        #*** Lifetime (in seconds) of Magento Admin WebAPI Token; if token is older than this value a refresh attempt will be made just before the next WebAPI call ***#
        'MAGENTO_ADMIN_WEBAPI_TOKEN_LIFETIME',
    ];

    public static function generate(): void
    {
        $config = [];
        $env = new Dotenv();
        foreach (self::SOURCE_ENV_FILES as $sourceEnvFile) {
            $sourceFilePath = BP . $sourceEnvFile;
            if (!file_exists($sourceFilePath)) {
                continue;
            }

            $config = $env->parse(
                file_get_contents($sourceFilePath),
                basename($sourceEnvFile)
            );

            break;
        }

        $additionalVars = [];
        foreach ($config as $varName => $varValue) {
            if (in_array($varName, self::KNOWN_VARS)) {
                continue;
            }
            $additionalVars[$varName] = $varValue;
        }

        $loader = new FilesystemLoader(FW_BP . '/templates/');
        $twig = new Environment($loader);
        $contents = $twig->render(
            'env_mb.twig',
            [
                'config' => $config,
                'additional_vars' => $additionalVars,
            ]
        );

        file_put_contents(
            ENV_FILE_PATH . self::ENV_FILE_NAME,
            $contents
        );
    }
}
