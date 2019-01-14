<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Fetcher\NightlyAppFetcher;
use OCA\Passwords\Helper\Favicon\BestIconHelper;
use OCA\Passwords\Helper\Image\ImagickHelper;
use OCA\Passwords\Helper\Words\LocalWordsHelper;
use OCA\Passwords\Helper\Words\RandomCharactersHelper;
use OCA\Passwords\Helper\Words\SnakesWordsHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class AdminSettingsController
 *
 * @package OCA\Passwords\Controller
 */
class AdminSettingsController extends Controller {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var NightlyAppFetcher
     */
    protected $nightlyAppFetcher;

    /**
     * AdminSettingsController constructor.
     *
     * @param string               $appName
     * @param IRequest             $request
     * @param ConfigurationService $config
     * @param FileCacheService     $fileCacheService
     * @param NightlyAppFetcher    $nightlyAppFetcher
     */
    public function __construct($appName, IRequest $request, ConfigurationService $config, FileCacheService $fileCacheService, NightlyAppFetcher $nightlyAppFetcher) {
        parent::__construct($appName, $request);
        $this->config            = $config;
        $this->fileCacheService  = $fileCacheService;
        $this->nightlyAppFetcher = $nightlyAppFetcher;
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @return JSONResponse
     */
    public function set(string $key, $value): JSONResponse {

        if($value === 'true') $value = true;
        if($value === 'false') $value = false;

        if($key === 'backup/files/maximum' && $value < 0) $value = '';
        if($key === 'service/images' && $value === HelperService::IMAGES_IMAGICK && !ImagickHelper::isAvailable()) {
            return new JSONResponse(['status' => 'failed', 'message' => 'Graphics library not installed']);
        };

        if($this->checkWordsService($key, $value)) {
            return new JSONResponse(['status' => 'failed', 'message' => 'Service is not available on this system']);
        }

        if($value === '') {
            $this->config->deleteAppValue($key);
        } else {
            $this->config->setAppValue($key, $value);
        }

        if($key === 'nightly/enabled') $this->setNightlyStatus($value);

        return new JSONResponse(['status' => 'ok']);
    }

    /**
     * @param string $key
     *
     * @return JSONResponse
     */
    public function cache(string $key): JSONResponse {
        $this->fileCacheService->clearCache($key);

        if(
            FileCacheService::FAVICON_CACHE == $key &&
            $this->config->getAppValue('service/favicon') === HelperService::FAVICON_BESTICON &&
            $this->config->getAppValue(BestIconHelper::BESTICON_CONFIG_KEY, BestIconHelper::BESTICON_DEFAULT_URL) === BestIconHelper::BESTICON_DEFAULT_URL
        ) {
            return new JSONResponse(['status' => 'error', 'message' => 'You can not clear this cache']);
        }

        return new JSONResponse(['status' => 'ok']);
    }

    /**
     * @param $enabled
     */
    protected function setNightlyStatus($enabled) {
        $nightlyApps = $this->config->getSystemValue('allowNightlyUpdates', []);

        if($enabled) {
            if(!in_array(Application::APP_NAME, $nightlyApps)) $nightlyApps[] = Application::APP_NAME;
            $this->config->setSystemValue('allowNightlyUpdates', $nightlyApps);
            $this->nightlyAppFetcher->get();
        } else {
            $index = array_search(Application::APP_NAME, $nightlyApps);
            if($index !== false) unset($nightlyApps[ $index ]);
            $this->config->setSystemValue('allowNightlyUpdates', $nightlyApps);
            $this->nightlyAppFetcher->clearDb();
        }
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @return bool
     */
    protected function checkWordsService(string $key, $value): bool {
        return $key === 'service/words' &&
               in_array($value, [HelperService::WORDS_LOCAL, HelperService::WORDS_RANDOM, HelperService::WORDS_SNAKES]) &&
               (
                   ($value === HelperService::WORDS_LOCAL && !LocalWordsHelper::isAvailable()) ||
                   ($value === HelperService::WORDS_RANDOM && !RandomCharactersHelper::isAvailable()) ||
                   ($value === HelperService::WORDS_SNAKES && !SnakesWordsHelper::isAvailable())
               );
    }
}