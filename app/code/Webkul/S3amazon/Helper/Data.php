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
namespace Webkul\S3amazon\Helper;

use Magento\Framework\App\Action\Action;
use Webkul\S3amazon\Model\MediaStorage\File\Storage;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const DEFAULT_CACHE_TIME = 86400;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * @var \Webkul\MpS3amazon\Logger\Logger
     */
    protected $s3Logger;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    protected $directoryList;
    
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;
    
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $fileSystemIo;
    
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var bool
     */
    private $storageS3;

    /**
     * @param \Magento\Framework\App\Helper\Context       $context
     * @param \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param \Magento\Framework\App\ResourceConnection   $resource
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File   $file
     * @param \Magento\Framework\Filesystem\Io\File       $fileSystemIo
     * @param \Webkul\S3amazon\Logger\Logger              $s3Logger
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor,
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Filesystem\Io\File $fileSystemIo,
        \Webkul\S3amazon\Model\ResourceModel\Title\CollectionFactory $titleCollectionFactory,
        \Webkul\S3amazon\Model\ResourceModel\SampleTitle\CollectionFactory $sampleTitleCollectionFactory,
        \Webkul\S3amazon\Logger\Logger $s3Logger,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_resource = $resource;
        $this->_directoryList = $directoryList;
        $this->_file = $file;
        $this->_fileSystemIo = $fileSystemIo;
        $this->_titleCollectionFactory = $titleCollectionFactory;
        $this->_sampleTitleCollectionFactory = $sampleTitleCollectionFactory;
        $this->_s3Logger = $s3Logger;
        $this->_encryptor = $encryptor;
        $this->serializer = $serializer;
    }

    public function writeLog($log)
    {
        $this->_s3Logger->info(json_encode($log));
    }

    /**
     * funtion to check enable or not.
     * @return boolean
     */
    public function getIsEnable()
    {
        return $this->getConfigValue('s3_amazon/general_settings/active');
    }

    /**
     * funtion to check enable or not static content .
     * @return boolean
     */
    public function getIsEnableStaticAction()
    {
        return $this->getConfigValue('s3_amazon/staticfile_settings/active');
    }

    /**
     * Check S3 is used as Media Storage.
     * @return bool
     */
    public function checkMediaStorageIsS3()
    {
        if ($this->storageS3 === null) {
            $currentStorage = (int) $this->getConfigValue(Storage::XML_PATH_STORAGE_MEDIA);
            $this->storageS3 = $currentStorage === Storage::STORAGE_MEDIA_S3;
        }

        return $this->storageS3;
    }

    /**
     * function to get Config Data.
     * @return string
     */
    public function getConfigValue($field = false)
    {
        if ($field) {
            return $this->scopeConfig
                ->getValue(
                    $field,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $this->getStoreId()
                );
        }
    }

    /**
     * function to get Store Id.
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @return \Magento\Framework\Serialize\SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Validate uploaded file name.
     *
     * @param \Aws\S3\S3Client $client
     * @param $filename
     * @param $bucket
     * @param $count
     * @return string
     */
    public function getValidFileName($client, $filename, $bucket, $count = 1)
    {
        $error = true;
        while ($error) {
            if (!$client->doesObjectExist($bucket, $filename)) {
                $error = false;
                return $filename;
            } else {
                $temp = explode(".", $filename);
                $ext = end($temp);
                array_pop($temp);
                $filename = implode(".", $temp);
                if (strpos($filename, "_") !== false) {
                    $tempFile = explode("_", $filename);
                    if (is_numeric(end($tempFile))) {
                        array_pop($tempFile);
                        $filename = implode("_", $tempFile);
                    }
                }
                $filename .= "_".$count;
                $filename = $filename.".".$ext;
                $count++;
                $filename = $this->getValidFileName($client, $filename, $bucket, $count);
            }
        }
        return $filename;
    }

    /**
     * Return Amazon S3 Client object.
     *
     * @return object
     */
    public function getClient()
    {
        if ($this->getIsEnable()) {
            $options = [
                'version' => 'latest',
                'region' => $this->getConfigValue('s3_amazon/general_settings/region'),
                'credentials' => [
                    'key' => $this->encrypt($this->getConfigValue('s3_amazon/general_settings/access_key')),
                    'secret' => $this->encrypt($this->getConfigValue('s3_amazon/general_settings/secret_key'))
                ]
            ];
            try {
                $client = new \Aws\S3\S3Client($options);
                $client->listObjects([
                    'Bucket' => $this->getConfigValue('s3_amazon/general_settings/bucket')
                ]);
            } catch (\Exception $e) {
                $this->_s3Logger->info(json_encode($e->getMessage()));
                $client = false;
            }
            return $client;
        } else {
            return false;
        }
    }

    /**
     * Return Title of the link.
     *
     * @param $linkId
     * @return string
     */
    public function getTitleById($linkId)
    {
        $collection = $this->_titleCollectionFactory->create()
            ->addFieldToFilter('link_id', ['eq' => $linkId])
            ->addFieldToFilter('store_id', ['eq' => $this->getStoreId()]);
        foreach ($collection as $item) {
            return $item->getTitle();
        }
    }

    /**
     * Return Title of the sample.
     *
     * @param $sampleId
     * @return string
     */
    public function getSampleTitleById($sampleId)
    {
        $collection = $this->_sampleTitleCollectionFactory->create()
            ->addFieldToFilter('sample_id', ['eq' => $sampleId])
            ->addFieldToFilter('store_id', ['eq' => $this->getStoreId()]);
        foreach ($collection as $item) {
            return $item->getTitle();
        }
    }

    /**
     * Create file name.
     *
     * @param $file
     * @param $productId
     * @return string
     */
    public function createFileName($file, $productId)
    {
        $file = explode('/', $file);
        $fileSize = count($file);
        $filename = str_replace(" ", "", $file[$fileSize-1]);
        $filename = "downloadable/files/links/".$productId."/".$filename;
        return $filename;
    }

    /**
     * Return File path.
     *
     * @param $file
     * @return string
     */
    public function getFilePath($file)
    {
        $filePath = $this->_directoryList->getPath('media')."/downloadable/files/links".$file;
        return $filePath;
    }

    /**
     * Delete file from path.
     *
     * @param $filePath
     */
    public function deleteFile($filePath)
    {
        $this->_file->deleteFile($filePath);
    }

    /**
     * Return file name from path.
     *
     * @param $filePath
     * @return string
     */
    public function getBaseName($filePath)
    {
        $fileInfo = $this->_fileSystemIo->getPathInfo($filePath);
        return $fileInfo['basename'];
    }

    /**
     * Return file name from path.
     *
     * @param $filePath
     * @return string
     */
    public function getFileExtension($filePath)
    {
        $fileInfo = $this->_fileSystemIo->getPathInfo($filePath);
        $ext = '';
        if (isset($pathInfo['extension'])) {
            $ext = $pathInfo['extension'];
        }
        
        return $ext;
    }

    /**
     * Check is deleted.
     *
     * @param $downloadableArray
     * @param $linkId
     * @return boolean
     */
    public function isDeleted($downloadableArray, $linkId)
    {
        $flag = true;
        foreach ($downloadableArray as $downloadableLink) {
            if ($downloadableLink['type'] == 'url'
                && $downloadableLink['link_url'] != ''
                && $downloadableLink['link_id'] == $linkId
            ) {
                $flag = false;
            }
        }
        return $flag;
    }

    /**
     * decrypt encrypted data
     *
     * @param $param
     * @return string
     */
    public function encrypt($param)
    {
        return $this->_encryptor->decrypt($param);
    }

    /**
     * @return array
     */
    public function getFileCacheInfo(): array
    {
        $cachingInfo = [];
        $cachingInfo['other'] = self::DEFAULT_CACHE_TIME;
        $cacheInfo = $this->getConfigValue('s3_amazon/cache/cache_control');
        if (!is_array($cacheInfo)) {
            $cacheInfo = $this->getSerializer()->unserialize($cacheInfo);
        }

        foreach ($cacheInfo as $cache) {
            $index = strtolower(trim($cache['file_type']));
            $cachingInfo[$index] = (int)$cache['file_time'] ?? self::DEFAULT_CACHE_TIME;
        }
        
        return $cachingInfo;
    }
}
