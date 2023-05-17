<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_S3amazon
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\S3amazon\Controller\Adminhtml\Distribution;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Aws\Exception\AwsException;

class Create extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    
    /**
     * @var \Webkul\S3amazon\Helper\Data
     */
    protected $helper;

    /**
     * @var \Webkul\S3amazon\Logger\Logger
     */
    protected $s3Logger;

    /**
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Webkul\S3amazon\Helper\Data $helper
     * @param \Webkul\S3amazon\Logger\Logger $s3Logger
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Webkul\S3amazon\Helper\Data $helper,
        \Webkul\S3amazon\Logger\Logger $s3Logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->s3Logger = $s3Logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $access = $this->helper->encrypt(
            $this->helper->getConfigValue('s3_amazon/general_settings/access_key')
        );
        $secret = $this->helper->encrypt(
            $this->helper->getConfigValue('s3_amazon/general_settings/secret_key')
        );
        $options = [
            'version' => 'latest',
            'region' => $data['region'],
            'credentials' => [
                'key' => $access,
                'secret' => $secret
            ]
        ];
        try {
            $message = '';
            $distributionInfo = [];
            $this->cloudFrontClient = new \Aws\CloudFront\CloudFrontClient($options);

            if ($data['dataAction'] == 'create') {
                $bucketUrl = "{$data['bucket']}.s3.{$data['region']}.amazonaws.com";
                $distributionParams = $this->createDistributionParams($bucketUrl, $data['bucket']);
                $distributionResult = $this->cloudFrontClient->createDistribution([
                    'DistributionConfig' => $distributionParams,
                ]);
            }

            if ($data['dataAction'] == 'getStatus') {
                $distributionResult = $this->cloudFrontClient->getDistribution([
                    'Id' => $data['distributionId'],
                ]);
            }

            if (isset($distributionResult['Distribution']['Id'])) {
                $distributionInfo['id'] = $distributionResult['Distribution']['Id'];
                $distributionInfo['url'] = $distributionResult['Distribution']['DomainName'];
                $distributionInfo['status'] = $distributionResult['Distribution']['Status'];
                $message .= __("Id: %1<br>", $distributionInfo['id']);
                $message .= __("URL: %1<br>", 'https://'.$distributionInfo['url'].'/');
                $message .= __("Status: %1<br>", $distributionInfo['status']);
            }
            
            $response = ['status' => true, 'message' => $message, 'distributionInfo' => $distributionInfo];
        } catch (\Exception $e) {
            $this->s3Logger->info(json_encode($e->getMessage()));
            $response = [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        } catch (AwsException $e) {
            $this->s3Logger->info(json_encode($e->getMessage()));
            $response = [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }

        return $this->resultJsonFactory->create()->setJsonData(json_encode($response));
    }

    /**
     * @return array
     */
    private function createDistributionParams($bucketUrl, $bucket)
    {
        $originName = 'S3-'.$bucket;
        $s3BucketURL = $bucketUrl;
        $callerReference = $originName.'-reference';
        $comment = 'Created from webkul magento S3Amazon module';
        $cacheBehavior = [
            'AllowedMethods' => [
                'CachedMethods' => [
                    'Items' => ['HEAD', 'GET'],
                    'Quantity' => 2,
                ],
                'Items' => ['GET', 'HEAD', 'OPTIONS', 'PUT', 'POST', 'PATCH', 'DELETE'],
                'Quantity' => 7,
            ],
            'Compress' => false,
            'DefaultTTL' => 0,
            'FieldLevelEncryptionId' => '',
            'ForwardedValues' => [
                'Cookies' => [
                    'Forward' => 'none',
                ],
                'Headers' => [
                    'Quantity' => 0,
                ],
                'QueryString' => false,
                'QueryStringCacheKeys' => [
                    'Quantity' => 0,
                ],
            ],
            'LambdaFunctionAssociations' => ['Quantity' => 0],
            'MaxTTL' => 0,
            'MinTTL' => 0,
            'SmoothStreaming' => false,
            'TargetOriginId' => $originName,
            'TrustedSigners' => [
                'Enabled' => false,
                'Quantity' => 0,
            ],
            'ViewerProtocolPolicy' => 'allow-all',
        ];

        $enabled = true;
        $origin = [
            'Items' => [
                [
                    'DomainName' => $s3BucketURL,
                    'Id' => $originName,
                    'OriginPath' => '',
                    'CustomHeaders' => ['Quantity' => 0],
                    'S3OriginConfig' => ['OriginAccessIdentity' => ''],

                ],
            ],
            'Quantity' => 1,
        ];

        $distribution = [
            'CallerReference' => $callerReference,
            'Comment' => $comment,
            'DefaultCacheBehavior' => $cacheBehavior,
            'Enabled' => $enabled,
            'Origins' => $origin,
        ];

        return $distribution;
    }
}
