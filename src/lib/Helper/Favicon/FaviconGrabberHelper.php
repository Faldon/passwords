<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Helper\Icon\FallbackIconGenerator;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;

/**
 * Class FaviconGrabberHelper
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class FaviconGrabberHelper extends AbstractFaviconHelper {

    const FAVICON_GRABBER_URL = 'http://favicongrabber.com';
    const API_WAIT_TIME       = 1;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var string
     */
    protected $prefix = HelperService::FAVICON_FAVICON_GRABBER;

    /**
     * BestIconHelper constructor.
     *
     * @param ConfigurationService  $config
     * @param HelperService         $helperService
     * @param FileCacheService      $fileCacheService
     * @param FallbackIconGenerator $fallbackIconGenerator
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(
        ConfigurationService $config,
        HelperService $helperService,
        FileCacheService $fileCacheService,
        FallbackIconGenerator $fallbackIconGenerator
    ) {
        $this->config = $config;
        parent::__construct($helperService, $fileCacheService, $fallbackIconGenerator);
    }

    /**
     * @param string $domain
     *
     * @return string
     * @throws \Exception
     * @throws \Throwable
     */
    protected function getFaviconData(string $domain) {
        $json = $this->sendApiRequest($domain);
        $icon = $this->analyzeApiResponse($json, $domain);

        return $icon !== null ? $icon:$this->getDefaultFavicon($domain)->getContent();
    }

    /**
     * @param string $domain
     *
     * @return array
     */
    protected function sendApiRequest(string $domain) {
        $this->checkRequestTimeout();
        $request = new RequestHelper();
        $data    = $request
            ->setAcceptResponseCodes([200, 400])
            ->setUserAgent(
                'Nextcloud/'.$this->config->getSystemValue('version').
                ' Passwords/'.$this->config->getAppValue('installed_version').
                ' Instance/'.$this->config->getSystemValue('instanceid')
            )->send(self::FAVICON_GRABBER_URL.'/api/grab/'.$domain);
        $this->setLastRequestTime();

        return json_decode($data, true);
    }

    /**
     * @param array  $json
     * @param string $domain
     *
     * @return null|string
     * @throws \Exception
     */
    protected function analyzeApiResponse(array $json, string $domain) {
        if(isset($json['error'])) throw new \Exception("Favicongrabber said: {$json['error']} ({$domain})");

        $iconData   = null;
        $sizeOffset = null;
        foreach($json['icons'] as $icon) {
            list($iconData, $sizeOffset) = $this->analyzeApiIcon($icon, $iconData, $sizeOffset);
        }

        return $iconData;
    }

    /**
     * @param $icon
     * @param $iconData
     * @param $sizeOffset
     *
     * @return array
     */
    protected function analyzeApiIcon($icon, $iconData, $sizeOffset): array {
        $info = pathinfo($icon['src']);
        if(!isset($info['extension'])) return [$iconData, $sizeOffset];
        $ext = $info['extension'];
        if(!in_array($ext, ['png', 'ico', 'gif', 'jpg', 'jpeg'])) return [$iconData, $sizeOffset];

        if($iconData === null) {
            $iconData = $this->loadIcon($icon['src']);
        } else if(isset($icon['sizes'])) {
            $size = explode('x', $icon['sizes'])[0];
            if(!is_numeric($size)) return [$iconData, $sizeOffset];

            $offset = abs(256 - $size);
            if($offset < $sizeOffset || $sizeOffset === null) {
                $sizeOffset = $offset;
                $iconData   = $this->loadIcon($icon['src'], $iconData);
            }
        }

        return [$iconData, $sizeOffset];
    }

    /**
     * @param string      $url
     * @param string|null $data
     *
     * @return null|string
     */
    protected function loadIcon(string $url, string $data = null) {
        $request = new RequestHelper();
        $request->setUrl($url);
        $iconData = $request->sendWithRetry();
        $mime     = $request->getInfo('content_type');

        if(substr($mime, 0, 5) !== 'image') return $data;

        return empty($iconData) ? $data:$iconData;
    }

    /**
     *
     */
    protected function checkRequestTimeout() {
        $lastRequest = $this->config->getAppValue('security/fg/api/request', 0);
        if(time() - $lastRequest < self::API_WAIT_TIME) {
            sleep(self::API_WAIT_TIME);
        }
    }

    /**
     *
     */
    protected function setLastRequestTime() {
        $this->config->setAppValue('security/fg/api/request', time());
    }
}