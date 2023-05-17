<?php declare(strict_types=1);
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_S3amazon
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\S3amazon\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Check CORS Policy
 */
class AfterConfigSave implements ObserverInterface
{
    /**
     * @param RequestInterface $request
     * @param Webkul\S3amazon\Helper\Data $helper
     */
    public function __construct(
        RequestInterface $request,
        \Webkul\S3amazon\Helper\Data $helper
    ) {
        $this->request = $request;
        $this->helper = $helper;
    }

    /**
     * Process Stock during config save
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $request = $this->request;
        $params = $request->getParam('groups');
        $staticFilesBucket = $this->helper->getConfigValue('s3_amazon/staticfile_settings/bucket');
        $staticFilesRegion = $this->helper->getConfigValue('s3_amazon/staticfile_settings/region');
        $staticFilesActive = $this->helper->getConfigValue('s3_amazon/staticfile_settings/active');

        if (!$staticFilesActive || !$staticFilesBucket || !$staticFilesRegion) {
            return true;
        }

        $options = [
            'version' => 'latest',
            'region' => $staticFilesRegion,
            'credentials' => [
                'key' => $this->helper->encrypt(
                    $this->helper->getConfigValue('s3_amazon/general_settings/access_key')
                ),
                'secret' => $this->helper->encrypt(
                    $this->helper->getConfigValue('s3_amazon/general_settings/secret_key')
                )
            ]
        ];

        $client = null;
        $corsAvailable = false;
        $storedCorsRules = [];

        try {
            try {
                $client = new \Aws\S3\S3Client($options);
                $result = $client->getBucketCors([
                    'Bucket' => $staticFilesBucket
                ]);

                if (empty($result->get('CORSRules'))) {
                    $corsAvailable = false;
                } else {
                    $cors = $result->get('CORSRules');
                    $storedCorsRules['CORSRules'] = $cors;
                    $corsAvailable = $this->checkCorsAvailablity($cors);
                }

                if (!$corsAvailable) {
                    if (!empty($storedCorsRules)) {
                        $storedCorsRules['CORSRules'][] = $this->getCorsConfig()['CORSRules'][0];
                    }
                    $result = $client->putBucketCors([
                        'Bucket' => $staticFilesBucket,
                        'CORSConfiguration' => $storedCorsRules
                    ]);
                }
            } catch (\Exception $e) {
                $result = $client->putBucketCors([
                    'Bucket' => $staticFilesBucket,
                    'CORSConfiguration' => $this->getCorsConfig()
                ]);
            }
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * @param array $cors
     * @return bool
     */
    private function checkCorsAvailablity($cors)
    {
        $corsAvailable = false;
        foreach ($cors as $corsItem) {
            foreach ($corsItem['AllowedOrigins'] as $origin) {
                if ($origin == '*') {
                    $corsAvailable = true;
                    break;
                }
            }
        }

        return $corsAvailable;
    }

    /**
     * Default CORSRules values
     * @return array
     */
    private function getCorsConfig(): array
    {
        return [
            'CORSRules' => [
                [
                    'AllowedHeaders' => ['*'],
                    'AllowedMethods' => ['POST', 'GET', 'HEAD', 'PUT', 'DELETE'],
                    'AllowedOrigins' => ['*'],
                    'ExposeHeaders' => [],
                    'MaxAgeSeconds' => 3000
                ],
            ],
        ];
    }
}
