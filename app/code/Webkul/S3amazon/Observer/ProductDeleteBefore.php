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

namespace Webkul\S3amazon\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProductDeleteBefore implements ObserverInterface
{
    /**
     * @var \Magento\Downloadable\Model\Link
     */
    protected $link;
    
    /**
     * @var \Webkul\S3amazon\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Webkul\S3amazon\Logger\Logger
     */
    protected $s3Logger;
    
    /**
     * @param \Magento\Downloadable\Model\Link            $link
     * @param \Webkul\S3amazon\Helper\Data                $helper
     * @param \Webkul\S3amazon\Logger\Logger              $s3Logger
     */
    public function __construct(
        \Magento\Downloadable\Model\Link $link,
        \Webkul\S3amazon\Helper\Data $helper,
        \Webkul\S3amazon\Logger\Logger $s3Logger
    ) {
        $this->_link      = $link;
        $this->_helper    = $helper;
        $this->s3Logger   = $s3Logger;
    }

    /**
     * Product delete before event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $bucket = $this->_helper->getConfigValue('s3_amazon/general_settings/bucket');
        $productId = $observer->getProduct()->getId();
        $product = $observer->getProduct();
        $client = $this->_helper->getClient();

        if ($product->getTypeId() == 'downloadable' && $client) {
            $linkCollection = $this->_link->getCollection()
                ->addFieldToFilter('product_id', ['eq'=>$productId]);
            try {
                foreach ($linkCollection as $link) {
                    if ($link->getLinkType() == "url") {
                        $fileName = $this->_helper->getBaseName($link->getLinkUrl());
                        $fileName = "downloadable/files/links/".$productId."/".$fileName;
                        if ($client->doesObjectExist($bucket, $fileName)) {
                            $result = $client->deleteObject([
                                'Bucket'    => $bucket,
                                'Key'       => $fileName
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $this->s3Logger->info(json_encode($error));
            }
        }
    }
}
