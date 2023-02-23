<?php

declare(strict_types=1);

namespace MFTFBuddy\Tests\Console\Command;

use Exception;
use MFTFBuddy\Tests\DataTransport\Protocol\CurlInterface;
use MFTFBuddy\Tests\DataTransport\Protocol\CurlTransport;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpLogs extends Command
{
    private const ARG_TEST_SESSION_ID = 'test-session-id';
    private const ARG_OUTPUT_DIR = 'output-dir';

    /**
     * @var string
     */
    private $apiBaseUrl;

    /**
     * @var string
     */
    private $magentoTestSessionId;

    protected function configure(): void
    {
        $this->setName('mftf-buddy:dump-logs');
        $this->setDescription('Dump logs from the test session.');

        $this->addArgument(
            self::ARG_TEST_SESSION_ID,
            InputArgument::REQUIRED,
            'Test session ID.'
        );
        $this->addArgument(
            self::ARG_OUTPUT_DIR,
            InputArgument::OPTIONAL,
            'Output directory.',
            './logs'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->apiBaseUrl = rtrim(getenv('MB_API_BASE_URL'), '/');
        $this->magentoTestSessionId = $input->getArgument(self::ARG_TEST_SESSION_ID);

        $outputDir = $input->getArgument(self::ARG_OUTPUT_DIR);
        if (strpos($outputDir, '/') !== 0) {
            $outputDir = getcwd() . '/' . $outputDir;
        }

        $transport = $this->getTransport();

        $url = $this->apiBaseUrl . "/test_sessions/{$this->magentoTestSessionId}";
        $transport->write(
            $url,
            [],
            CurlInterface::GET,
            [
                'Accept: application/ld+json',
            ]
        );
        try {
            $response = $transport->read(null, null, null, $url);
        } finally {
            $transport->close();
        }

        $response = preg_replace('/[[:cntrl:]]/', '', $response);
        $responseJson = json_decode($response, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception('Json error: ' . json_last_error_msg() . ', response: ' . substr($response, 0, 1000));
        }

        $type = $responseJson['@type'] ?? '';
        if ($type !== 'TestSession') {
            throw new Exception("Unexpected type '$type'");
        }

        $cwd = getcwd();
        try {
            $newDir = "$outputDir/magento_session_{$this->magentoTestSessionId}";
            if (!file_exists($newDir)) {
                mkdir($newDir, 0777, true);
            }
            chdir($newDir);

            $magentoNode = $responseJson['magentoNode'] ?? null;
            if ($magentoNode) {
                $this->handleMagentoNodes([$magentoNode], 'success');
            }

            $failedMagentoNodes = $responseJson['failedMagentoNodes'] ?? [];
            if ($failedMagentoNodes) {
                $this->handleMagentoNodes($failedMagentoNodes, 'failure');
            }
        } finally {
            chdir($cwd);
        }
    }

    protected function getTransport(): CurlTransport
    {
        $transport = new CurlTransport();
        $transport->addOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $transport->addOption(CURLOPT_TIMEOUT, 300/*getenv('MB_API_TIMEOUT')*/);

        return $transport;
    }

    protected function handleMagentoNodes(array $magentoNodes, string $status): void
    {
        foreach ($magentoNodes as $magentoNode) {
            $type = $magentoNode['@type'] ?? '';
            if ($type !== 'MagentoNode') {
                throw new Exception("Unexpected type '$type'");
            }

            $id = $magentoNode['@id'] ?? '';

            $matches = [];
            if (!preg_match(':^/magento_nodes/(.*)$:', $id, $matches)) {
                throw new Exception("Non-parsable id '$id'");
            }
            $magentoNodeId = $matches[1];

            $cwd = getcwd();
            try {
                $newDir =  "$status/magento_node_$magentoNodeId";
                if (!file_exists($newDir)) {
                    mkdir($newDir, 0777, true);
                }
                chdir($newDir);

                $hubLog = $magentoNode['hubLog'] ?? null;
                if ($hubLog) {
                    file_put_contents('hub_log.txt', gzdecode(base64_decode($hubLog)));
                }

                $log = $magentoNode['log'] ?? null;
                if ($log) {
                    file_put_contents('log.txt', gzdecode(base64_decode($log)));
                }

                $mftfLog = $magentoNode['mftfLog'] ?? null;
                if ($mftfLog) {
                    file_put_contents('mftf_log.txt', gzdecode(base64_decode($mftfLog)));
                }

                $this->handleSeleniumSessions($magentoNodeId);

            } finally {
                chdir($cwd);
            }
        }
    }

    protected function handleSeleniumSessions(string $magentoNodeId): void
    {
        $page = 1;

        do {
            $transport = $this->getTransport();

            $url = $this->apiBaseUrl . "/selenium-director/test_sessions?magentoGridSessionId={$this->magentoTestSessionId}&magentoGridNodeId={$magentoNodeId}&page=$page";
            $transport->write(
                $url,
                [],
                CurlInterface::GET,
                [
                    'Accept: application/ld+json',
                ]
            );
            try {
                $response = $transport->read(null, null, null, $url);
            } finally {
                $transport->close();
            }

            $response = preg_replace('/[[:cntrl:]]/', '', $response);
            $responseJson = json_decode($response, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new Exception('Json error: ' . json_last_error_msg() . ', response: ' . substr($response, 0, 1000));
            }

            $type = $responseJson['@type'] ?? '';
            if ($type !== 'hydra:Collection') {
                throw new Exception("Unexpected type '$type'");
            }

            $seleniumSessions = $responseJson['hydra:member'] ?? [];

            $this->handleSeleniumSessionsPage($seleniumSessions);

            $page++;
            $hasNextPage = (bool)($responseJson['hydra:view']['hydra:next'] ?? false);
        } while ($hasNextPage);
    }

    protected function handleSeleniumSessionsPage(array $seleniumSessions): void
    {
        foreach ($seleniumSessions as $seleniumSession) {
            $type = $seleniumSession['@type'] ?? '';
            if ($type !== 'TestSession') {
                throw new Exception("Unexpected type '$type'");
            }

            $id = $seleniumSession['@id'] ?? '';

            $matches = [];
            if (!preg_match(':^/test_sessions/(.*)$:', $id, $matches)) {
                throw new Exception("Non-parsable id '$id'");
            }
            $seleniumSessionId = $matches[1];

            $seleniumNode = $seleniumSession['seleniumNode'] ?? null;
            if (!$seleniumNode) {
                continue;
            }

            $type = $seleniumNode['@type'] ?? '';
            if ($type !== 'SeleniumNode') {
                throw new Exception("Unexpected type '$type'");
            }

            $id = $seleniumNode['@id'] ?? '';

            $matches = [];
            if (!preg_match(':^/selenium_nodes/(.*)$:', $id, $matches)) {
                throw new Exception("Non-parsable id '$id'");
            }
            $seleniumNodeId = $matches[1];

            $cwd = getcwd();
            try {
                $newDir = "selenium_session_$seleniumSessionId/selenium_node_$seleniumNodeId";
                if (!file_exists($newDir)) {
                    mkdir($newDir, 0777, true);
                }
                chdir($newDir);

                $containerLog = $seleniumNode['containerLog'] ?? null;
                if ($containerLog) {
                    file_put_contents('container_log.txt', gzdecode(base64_decode($containerLog)));
                }

                $directorLog = $seleniumNode['directorLog'] ?? null;
                if ($directorLog) {
                    file_put_contents('director_log.txt', gzdecode(base64_decode($directorLog)));
                }

                $log = $seleniumNode['log'] ?? null;
                if ($log) {
                    file_put_contents('log.txt', gzdecode(base64_decode($log)));
                }

                $webDriverLog = $seleniumNode['webDriverLog'] ?? null;
                if ($webDriverLog) {
                    file_put_contents('web_driver_log.txt', gzdecode(base64_decode($webDriverLog)));
                }
            } finally {
                chdir($cwd);
            }
        }
    }
}
