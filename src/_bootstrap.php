<?php
// @codingStandardsIgnoreFile
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use MFTFBuddy\Tests\Config\EnvFile;
use Symfony\Component\Dotenv\Dotenv;

// define framework basepath for schema pathing
// defined('FW_BP') || define('FW_BP', realpath(__DIR__ . '/../../../'));
defined('FW_BP') || define('FW_BP', realpath(__DIR__ . '/../'));
// get the root path of the project
$projectRootPath = BP;
defined('PROJECT_ROOT') || define('PROJECT_ROOT', $projectRootPath);

$envFilePath = realpath($projectRootPath . '/dev/tests/acceptance/') . DIRECTORY_SEPARATOR;
defined('ENV_FILE_PATH') || define('ENV_FILE_PATH', $envFilePath);

if (!file_exists(ENV_FILE_PATH . EnvFile::ENV_FILE_NAME)) {
    // Generate MB env file
    EnvFile::generate();
}

// Load constants from MB env file
$env = new Dotenv();
if (function_exists('putenv')) {
    $env->usePutenv();
}
$env->populate(
    $env->parse(
        file_get_contents(ENV_FILE_PATH . EnvFile::ENV_FILE_NAME),
        ENV_FILE_PATH . EnvFile::ENV_FILE_NAME
    ),
    true
);

if (array_key_exists('TESTS_MODULE_PATH', $_ENV) xor array_key_exists('TESTS_BP', $_ENV)) {
    throw new Exception(
        'You must define both parameters TESTS_BP and TESTS_MODULE_PATH or neither parameter'
    );
}

foreach ($_ENV as $key => $var) {
    defined($key) || define($key, $var);
}

if (array_key_exists('MAGENTO_BP', $_ENV)) {
    defined('TESTS_BP') || define('TESTS_BP', realpath(PROJECT_ROOT . DIRECTORY_SEPARATOR . 'dev/tests/acceptance'));
}

defined('MB_MAGENTO_CLI_COMMAND_PATH') || define(
    'MB_MAGENTO_CLI_COMMAND_PATH',
    'dev/tests/acceptance/utils/command.php'
);
defined('MB_MAGENTO_CLI_COMMAND_PARAMETER') || define('MB_MAGENTO_CLI_COMMAND_PARAMETER', 'command');
defined('MB_DEFAULT_TIMEZONE') || define('MB_DEFAULT_TIMEZONE', 'America/Los_Angeles');
defined('MB_WAIT_TIMEOUT') || define('MB_WAIT_TIMEOUT', 30);
defined('MB_VERBOSE_ARTIFACTS') || define('MB_VERBOSE_ARTIFACTS', false);
$env->populate(
    [
        'MB_MAGENTO_CLI_COMMAND_PATH' => MB_MAGENTO_CLI_COMMAND_PATH,
        'MB_MAGENTO_CLI_COMMAND_PARAMETER' => MB_MAGENTO_CLI_COMMAND_PARAMETER,
        'MB_DEFAULT_TIMEZONE' => MB_DEFAULT_TIMEZONE,
        'MB_WAIT_TIMEOUT' => MB_WAIT_TIMEOUT,
        'MB_VERBOSE_ARTIFACTS' => MB_VERBOSE_ARTIFACTS,
    ],
    true
);

try {
    new DateTimeZone(MB_DEFAULT_TIMEZONE);
} catch (\Exception $e) {
    throw new \Exception("Invalid MB_DEFAULT_TIMEZONE in .env_mb: " . MB_DEFAULT_TIMEZONE . PHP_EOL);
}

defined('MAGENTO_BP') || define('MAGENTO_BP', realpath(PROJECT_ROOT));
// TODO REMOVE THIS CODE ONCE WE HAVE STOPPED SUPPORTING dev/tests/acceptance PATH
// define TEST_PATH and TEST_MODULE_PATH
defined('TESTS_BP') || define('TESTS_BP', realpath(MAGENTO_BP . DIRECTORY_SEPARATOR . 'dev/tests/acceptance'));

$RELATIVE_TESTS_MODULE_PATH = '/tests/functional/Magento';
defined('TESTS_MODULE_PATH') || define(
    'TESTS_MODULE_PATH',
    realpath(TESTS_BP . $RELATIVE_TESTS_MODULE_PATH)
);
