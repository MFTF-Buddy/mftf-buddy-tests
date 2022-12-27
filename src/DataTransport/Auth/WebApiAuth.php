<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MFTFBuddy\Tests\DataTransport\Auth;

use MFTFBuddy\Tests\DataGenerator\Handlers\CredentialStore;
use MFTFBuddy\Tests\Exceptions\FastFailException;
use MFTFBuddy\Tests\Util\MftfGlobals;
use MFTFBuddy\Tests\DataTransport\Protocol\CurlInterface;
use MFTFBuddy\Tests\DataTransport\Protocol\CurlTransport;
use MFTFBuddy\Tests\Exceptions\TestFrameworkException;
use MFTFBuddy\Tests\DataTransport\Auth\Tfa\OTP;

/**
 * Class WebApiAuth
 */
class WebApiAuth
{
    const PATH_ADMIN_AUTH = 'V1/integration/admin/token';

    /** Rest request headers
     *
     * @var string[]
     */
    private static $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    /**
     * Tokens for admin users
     *
     * @var string[]
     */
    private static $adminAuthTokens = [];

    /**
     * Timestamps of when admin user tokens were created.  They need to be refreshed every ~4 hours
     *
     * @var int[]
     */
    private static $adminAuthTokenTimestamps = [];

    /**
     * Return the API token for an admin user
     * Use MB_MAGENTO_ADMIN_USERNAME and MB_MAGENTO_ADMIN_PASSWORD when $username and/or $password is/are omitted
     *
     * @param string $username
     * @param string $password
     * @return string
     * @throws FastFailException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function getAdminToken($username = null, $password = null)
    {
        $login = $username ?? getenv('MB_MAGENTO_ADMIN_USERNAME');
        $password = $password ?? getenv('MB_MAGENTO_ADMIN_PASSWORD');

        if (!$password) {
            try {
                $encryptedSecret = CredentialStore::getInstance()->getSecret('magento/MAGENTO_ADMIN_PASSWORD');
                $password = CredentialStore::getInstance()->decryptSecretValue($encryptedSecret);
            } catch (TestFrameworkException $e) {
                $message = "Password not found in credentials file";
                throw new FastFailException($message . $e->getMessage(), $e->getContext());
            }
        }

        if (!$login || !$password) {
            $message = 'Cannot retrieve API token without credentials. Please fill out .env_mb.';
            $context = [
                    'MB_MAGENTO_BASE_URL' => getenv('MB_MAGENTO_BASE_URL'),
                    'MB_MAGENTO_BACKEND_BASE_URL' => getenv('MB_MAGENTO_BACKEND_BASE_URL'),
                    'MB_MAGENTO_ADMIN_USERNAME' => getenv('MB_MAGENTO_ADMIN_USERNAME'),
                    'MB_MAGENTO_ADMIN_PASSWORD' => getenv('MB_MAGENTO_ADMIN_PASSWORD'),
                ];
            throw new FastFailException($message, $context);
        }

        if (self::hasExistingToken($login)) {
            return self::$adminAuthTokens[$login];
        }

        try {
            $authUrl = MftfGlobals::getWebApiBaseUrl() . self::PATH_ADMIN_AUTH;

            $data = [
                'username' => $login,
                'password' => $password
            ];

            if (Tfa::isEnabled()) {
                $authUrl = MftfGlobals::getWebApiBaseUrl() . Tfa::getProviderWebApiAuthEndpoint('google');
                $data['otp'] = OTP::getOTP();
            }

            $transport = new CurlTransport();
            $transport->write(
                $authUrl,
                json_encode($data, JSON_PRETTY_PRINT),
                CurlInterface::POST,
                self::$headers
            );
        } catch (TestFrameworkException $e) {
            $message = "Cannot retrieve API token with credentials. Please check configurations in .env_mb.\n";
            throw new FastFailException($message . $e->getMessage(), $e->getContext());
        }

        try {
            $response = $transport->read();
            $transport->close();
            $token = json_decode($response);
            if ($token !== null) {
                self::$adminAuthTokens[$login] = $token;
                self::$adminAuthTokenTimestamps[$login] = time();
                return $token;
            }
            $errMessage = "Invalid response: {$response}";
        } catch (TestFrameworkException $e) {
            $transport->close();
            $errMessage = $e->getMessage();
        }

        $message = 'Cannot retrieve API token with credentials.';
        try {
            // No exception will ever throw from here
            $message .= Tfa::isEnabled() ? ' and 2FA settings:' : ':' . PHP_EOL;
        } catch (TestFrameworkException $e) {
        }
        $message .= $errMessage;
        $context = ['url' => $authUrl];
        throw new FastFailException($message, $context);
    }

    /**
     * Is there an existing WebAPI admin token for this login?
     *
     * @param string $login
     * @return boolean
     */
    private static function hasExistingToken(string $login)
    {
        if (!isset(self::$adminAuthTokens[$login])) {
            return false;
        }

        $tokenLifetime = getenv('MB_MAGENTO_ADMIN_WEBAPI_TOKEN_LIFETIME');

        $isTokenExpired = $tokenLifetime && time() - self::$adminAuthTokenTimestamps[$login] > $tokenLifetime;

        return !$isTokenExpired;
    }
}
