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
namespace Webkul\S3amazon\Asset\MergeStrategy;

use Webkul\S3amazon\Helper\Data;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

/**
 * Plugin for Direct.
 */
class Direct
{
    /**
     * @var Data
     */
    private $helper;

    protected $bucket;

    protected $client;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper,
        Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $fileIo,
        \Magento\Framework\Filesystem\Driver\File $file
    ) {
        $this->helper = $helper;
        $this->pubStaticDir = $filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW);
        $this->fileIo = $fileIo;
        $this->file = $file;
    }
    
    /**
     * Whether the strategy can be applied
     *
     * @param Magento\Framework\View\Asset\MergeStrategy\Direct $subject
     * @return bool
     */
    public function afterMerge(
        \Magento\Framework\View\Asset\MergeStrategy\Direct $subject,
        $result,
        array $assetsToMerge,
        \Magento\Framework\View\Asset\LocalInterface $resultAsset
    ) {
        if (!$this->helper->getIsEnableStaticAction()) {
            return $result;
        }

        $this->createStorageObject();
        if (!$this->checkAvailablity()) {
            return $result;
        }

        try {
            $filePath = $resultAsset->getPath();
            $absolutePath = $this->pubStaticDir->getAbsolutePath($filePath);
            $cachingInfo = $this->helper->getFileCacheInfo();
            $time = Data::DEFAULT_CACHE_TIME;

            $key = ltrim($filePath, "/");
            $pathInfo = $this->fileIo->getPathInfo($key);
            $ext = '';
            if (isset($pathInfo['extension'])) {
                $ext = $pathInfo['extension'];
            }

            if (!empty($cachingInfo[$ext])) {
                $time = $cachingInfo[$ext];
            } else {
                $time = $cachingInfo['other'];
            }

            $this->client->putObject(
                [
                    'Bucket' => $this->bucket,
                    'SourceFile' => $absolutePath,
                    'Key' => $key,
                    'ACL' => 'public-read',
                    'ContentType' => \GuzzleHttp\Psr7\mimetype_from_filename($key) ?? 'application/octet-stream',
                    'CacheControl' => "max-age={$time}"
                ]
            );

            if ($this->file->isExists($absolutePath)) {
                $this->file->deleteFile($absolutePath);
            }
        } catch (\Exception $e) {
            return $result;
        }
    }

    /**
     * initialize s3 object
     */
    private function createStorageObject()
    {
        $access = $this->helper->encrypt(
            $this->helper->getConfigValue('s3_amazon/general_settings/access_key')
        );
        $secret = $this->helper->encrypt(
            $this->helper->getConfigValue('s3_amazon/general_settings/secret_key')
        );
        $this->bucket = $this->helper->getConfigValue('s3_amazon/staticfile_settings/bucket');
        $region = $this->helper->getConfigValue('s3_amazon/staticfile_settings/region');

        $options = [
            'region' => $region,
            'version' => 'latest',
            'credentials' => [
                'key' => $access,
                'secret' => $secret
            ]
        ];

        $this->client = new \Aws\S3\S3Client($options);
        return $this->client;
    }

    /**
     * check bucket availablity
     */
    private function checkAvailablity()
    {
        try {
            $result = $this->client->headBucket([
                'Bucket' => $this->bucket
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
