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
namespace Webkul\S3amazon\Model\MediaStorage\File;

use Webkul\S3amazon\Helper\Data;

/**
 * @inheritdoc
 */
class Uploader
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var S3Storage
     */
    private $s3storage;

    /**
     * @param Data $helper
     * @param S3Storage $storageModel
     */
    public function __construct(
        Data $helper,
        \Webkul\S3amazon\Model\MediaStorage\File\Storage\S3storage $s3storage
    ) {
        $this->helper = $helper;
        $this->s3storage = $s3storage;
    }

    /**
     * @param Magento\MediaStorage\Model\File\Uploader $subject
     * @param string $result
     * @return mixed
     */
    public function afterSave($subject, $result)
    {
        if ($this->helper->checkMediaStorageIsS3()) {
            $client = $this->helper->getClient();
            $bucket = $this->helper->getConfigValue('s3_amazon/general_settings/bucket');
            if (!empty($result) && !empty($result['path']) && !empty($result['file'])) {
                $filePath = $result['path'].'/'.$result['file'];
                if (strpos($filePath, 'pub/media') !== false) {
                    $key = substr($filePath, strpos($filePath, 'pub/media/')+strlen('pub/media/'));
                    if ($this->s3storage->fileExists($key)) {
                        $result['file'] = '/'.$result['file'];
                        return $result;
                    }
                }
                $cachingInfo = $this->helper->getFileCacheInfo();
                $ext = $this->helper->getFileExtension($result['file']);
                $time = Data::DEFAULT_CACHE_TIME;
                if (!empty($cachingInfo[$ext])) {
                    $time = $cachingInfo[$ext];
                } else {
                    $time = $cachingInfo['other'];
                }
                $uploadResult = $client->putObject([
                    'Bucket'       => $bucket,
                    'Key'          => $result['file'],
                    'SourceFile'   => $filePath,
                    'ACL'          => 'public-read',
                    'CacheControl' => "max-age={$time}"
                ]);

                $result['file'] = '/'.$result['file'];
            }
        }
        return $result;
    }
}
