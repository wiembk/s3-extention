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
use Magento\MediaStorage\Helper\File\Storage\Database;

class ProductSaveAfter implements ObserverInterface
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
     * @param \Magento\Downloadable\Model\Link $link
     * @param \Magento\Downloadable\Model\SampleFactory $sampleFactory
     * @param \Webkul\S3amazon\Helper\Data $helper
     * @param \Webkul\S3amazon\Logger\Logger $s3Logger
     * @param \Webkul\S3amazon\Model\MediaStorage\File\Storage\S3storage $s3Storage
     * @param Database $database
     */
    public function __construct(
        \Magento\Downloadable\Model\Link $link,
        \Magento\Downloadable\Model\SampleFactory $sampleFactory,
        \Webkul\S3amazon\Helper\Data $helper,
        \Webkul\S3amazon\Logger\Logger $s3Logger,
        \Webkul\S3amazon\Model\MediaStorage\File\Storage\S3storage $s3Storage,
        Database $database
    ) {
        $this->_link = $link;
        $this->sampleFactory = $sampleFactory;
        $this->_helper = $helper;
        $this->s3Logger = $s3Logger;
        $this->s3Storage = $s3Storage;
        $this->database = $database;
    }

    /**
     * Product save after event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $bucket = $this->_helper->getConfigValue('s3_amazon/general_settings/bucket');
        $client = $this->_helper->getClient();
        $productId = $observer->getProduct()->getId();
        $product = $observer->getProduct();

        if ($product->getTypeId() == 'downloadable' && $client) {
            $linkCollection = $this->_link->getCollection()
                ->addFieldToFilter('product_id', ['eq' => $productId]);
            $sampleCollection = $this->sampleFactory->create()->getCollection()
                ->addFieldToFilter('product_id', ['eq' => $productId]);
            try {
                // code for link
                $this->processLinks($linkCollection, $bucket, $client);
                // code for sample
                $this->processSamples($sampleCollection, $bucket, $client);
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $this->s3Logger->info(json_encode($error));
            }
        }
    }

    /**
     * @param \Magento\Downloadable\Model\Link $linkCollection
     * @param string $bucket
     * @param \Aws\S3\S3Client $client
     */
    protected function processLinks($linkCollection, $bucket, $client)
    {
        foreach ($linkCollection as $link) {
            $linkStatus = false;
            $linkFile = $sampleFile = null;
            if ($link->getLinkType() == "file") {
                $file = $link->getLinkFile();
                $filename = "downloadable/files/links/".ltrim($file, '/');
                $status = $this->s3Storage->fileExists($filename);
                $linkFile = $client->getObjectUrl(
                    $bucket,
                    $filename
                );
                if ($status && $linkFile) {
                    $client->putObjectAcl([
                        'Bucket' => $bucket,
                        'Key' => $filename,
                        'ACL' => 'private'
                    ]);
                    $linkStatus = true;
                }
            }
            if ($link->getSampleType() == "file") {
                $file = $link->getSampleFile();
                $filename = "downloadable/files/link_samples/".ltrim($file, '/');
                $status = $this->s3Storage->fileExists($filename);
                $sampleFile = $client->getObjectUrl(
                    $bucket,
                    $filename
                );
                if ($status && $sampleFile) {
                    $linkStatus = true;
                }
            }

            if ($linkStatus) {
                $this->saveLink($link, $linkFile, $sampleFile);
            }
        }
    }

    /**
     * @param \Magento\Downloadable\Model\Sample $sampleCollection
     * @param string $bucket
     * @param \Aws\S3\S3Client $client
     */
    protected function processSamples($sampleCollection, $bucket, $client)
    {
        foreach ($sampleCollection as $sample) {
            if ($sample->getSampleType() == "file") {
                $file = $sample->getSampleFile();
                $filename = "downloadable/files/samples/".ltrim($file, '/');

                $status = $this->s3Storage->fileExists($filename);
                $uploadedFile = $client->getObjectUrl(
                    $bucket,
                    $filename
                );
                if ($status && $uploadedFile) {
                    $this->saveSample($sample, $uploadedFile);
                }
            }
        }
    }

    /**
     * @param Magento\Downloadable\Model\Link $link
     * @param string $linkPath
     * @param string $samplePath
     */
    private function saveLink($link, $linkPath, $samplePath)
    {
        $data['title'] = $this->_helper->getTitleById($link->getId());
        if ($linkPath) {
            $data['link_url'] = $linkPath;
            $data['link_file'] = '';
            $data['link_type'] = 'url';
        }
        if ($samplePath) {
            $data['sample_url'] = $samplePath;
            $data['sample_file'] = '';
            $data['sample_type'] = 'url';
        }
        $link->addData($data)->setId($link->getId())->save();
    }

    /**
     * @param Magento\Downloadable\Model\Sample $sample
     * @param string $filePath
     */
    private function saveSample($sample, $filePath)
    {
        $data = [
            'sample_file' => '',
            'title'     => $this->_helper->getSampleTitleById($sample->getId()),
            'sample_url'  => $filePath,
            'sample_type' => "url"
        ];
        $sample->addData($data)->setId($sample->getId())->save();
    }
}
