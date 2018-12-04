<?php

namespace OCA\Passwords\Cron;

use OCA\Passwords\Fetcher\NightlyAppFetcher;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;

/**
 * Class CheckNightlyUpdates
 *
 * @package OCA\Passwords\Cron
 */
class CheckNightlyUpdates extends AbstractCronJob {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var NightlyAppFetcher
     */
    protected $nightlyAppFetcher;

    /**
     * CheckNightlyUpdates constructor.
     *
     * @param NightlyAppFetcher    $nightlyAppFetcher
     * @param ConfigurationService $config
     * @param LoggingService       $logger
     * @param EnvironmentService   $environment
     */
    public function __construct(NightlyAppFetcher $nightlyAppFetcher, ConfigurationService $config, LoggingService $logger, EnvironmentService $environment) {
        parent::__construct($logger, $environment);
        $this->nightlyAppFetcher = $nightlyAppFetcher;
        $this->config            = $config;
        $this->setInterval(0);
    }

    /**
     * @param $argument
     *
     * @throws \Exception
     */
    protected function runJob($argument) {
        $enabled = $this->config->getAppValue('nightly/enabled', false) === '1';
        $enabled = $this->migrateNightlyKey($enabled);

        if($enabled) {
            $this->nightlyAppFetcher->get();
            if($this->nightlyAppFetcher->isDbUpdated()) $this->logger->debug('Fetched latest app database');
        }
    }

    /**
     * @return bool
     */
    protected function migrateNightlyKey(bool $enabled): bool {
        if($this->config->getAppValue('nightly_updates', null) !== null) {
            if($this->config->getAppValue('nightly_updates', false) === '1') {
                $this->config->setAppValue('nightly/enabled', true);
                $enabled = true;
            }
            $this->config->deleteAppValue('nightly_updates');
        }

        return $enabled;
    }
}