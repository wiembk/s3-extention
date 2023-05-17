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
namespace Webkul\S3amazon\Controller\Adminhtml\Check;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends Action
{
    /**
     * @var JsonFactory
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
     * @param JsonFactory $resultJsonFactory
     * @param Webkul\S3amazon\Helper\Data $helper
     * @param Webkul\S3amazon\Logger\Logger $s3Logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
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
            'region' => $data['region'],
            'version' => 'latest',
            'credentials' => [
                'key' => $access,
                'secret' => $secret
            ]
        ];
        try {
            $this->client = new \Aws\S3\S3Client($options);
            $result = $this->client->headBucket([
                'Bucket' => $data['bucket']
            ]);
            $response = ['status' => true, 'message' => 'Available'];
        } catch (\Exception $e) {
            $this->s3Logger->info(json_encode($e->getMessage()));
            $response = [
                'status'  => false,
                'message' => __('The specified bucket does not exist')
            ];
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->s3Logger->info(json_encode($e->getMessage()));
            $response = [
                'status'  => false,
                'message' => $e->getMessage()
            ];
        }

        return $this->resultJsonFactory->create()->setJsonData(json_encode($response));
    }
}
