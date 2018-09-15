<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\AppInfo\Application;
use OCP\BackgroundJob;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;

/**
 * Class EnvironmentService
 *
 * @package OCA\Passwords\Services
 */
class EnvironmentService {

    /**
     * @var null|string
     */
    protected $userId;

    /**
     * @var bool
     */
    protected $isCliMode;

    /**
     * @var bool
     */
    protected $isCronJob;

    /**
     * @var bool
     */
    protected $isAppUpdate;

    /**
     * @var bool
     */
    protected $isGlobalMode;

    /**
     * @var mixed
     */
    protected $maintenanceEnabled;

    /**
     * EnvironmentService constructor.
     *
     * @param string|null $userId
     * @param IConfig     $config
     * @param IRequest    $request
     * @param ILogger     $logger
     */
    public function __construct(string $userId = null, IConfig $config, IRequest $request, ILogger $logger) {
        $this->maintenanceEnabled = $config->getSystemValue('maintenance', false);
        $this->isCliMode          = PHP_SAPI === 'cli';
        $this->checkIfCronJob($request);
        $this->checkIfAppUpdate($request);
        $this->isGlobalMode = $this->maintenanceEnabled || $this->isCliMode || $this->isAppUpdate || $this->isCronJob;

        if($this->isGlobalMode) {
            // Debugging info
            $logger->debug('Passwords runs '.($request->getRequestUri() ? $request->getRequestUri():$request->getScriptName()).' in global mode', ['app' => Application::APP_NAME]);
        }
        if(!$this->isGlobalMode) $this->userId = $userId;
    }

    /**
     * @return null|string
     */
    public function getUserId(): ?string {
        return $this->userId;
    }

    /**
     * @return bool
     */
    public function isCliMode(): bool {
        return $this->isCliMode;
    }

    /**
     * @return bool
     */
    public function isCronJob(): bool {
        return $this->isCronJob;
    }

    /**
     * @return bool
     */
    public function isGlobalMode(): bool {
        return $this->isGlobalMode;
    }

    /**
     * @return mixed
     */
    public function isMaintenanceEnabled(): bool {
        return $this->maintenanceEnabled;
    }

    /**
     * @param IRequest $request
     */
    protected function checkIfCronJob(IRequest $request): void {
        $this->isCronJob = ($request->getRequestUri() === '/cron.php' && in_array($this->getBackgroundJobType(), ['ajax', 'webcron'])) ||
                           ($this->isCliMode && $this->getBackgroundJobType() === 'cron' && strpos($request->getScriptName(), 'cron.php') !== false);
    }

    /**
     * @param IRequest $request
     */
    protected function checkIfAppUpdate(IRequest $request): void {
        $this->isAppUpdate = false;
        if($this->isCronJob) return;

        try {
            $this->isAppUpdate = $request->getPathInfo() === '/settings/ajax/updateapp.php';
        } catch(\Exception $e) {
        }
    }

    /**
     * @return string
     * @TODO remove in 2019.1.0
     */
    protected function getBackgroundJobType() {
        if(BackgroundJob::getExecutionType() !== '') return BackgroundJob::getExecutionType();

        return \OC::$server->getConfig()->getAppValue('core', 'backgroundjobs_mode', 'ajax');
    }
}