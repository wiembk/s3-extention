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
namespace Webkul\S3amazon\Model\MediaStorage\File\Storage;

use Magento\Framework\DataObject;
use Webkul\S3amazon\Logger\Logger;
use Aws\S3\S3Client;
use Webkul\S3amazon\Helper\Data;

class S3storage extends DataObject
{
    /**
     * Collect errors during sync process
     *
     * @var string[]
     */
    protected $_errors = [];

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var S3Client
     */
    private $_client;
    
    /**
     * @var Data
     */
    private $_helper;

    /**
     * Store media base directory path
     *
     * @var string
     */
    protected $_mediaBaseDirectory = null;

    /**
     * Core file storage database
     *
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    protected $_storageHelper = null;

    /**
     * @var \Magento\MediaStorage\Helper\File\Media
     */
    protected $_mediaHelper = null;

    /**
     * @var array
     */
    private $objects = [];

    /**
     * @var array
     */
    private $_cachingInfo = [];

    /**
     * @param Logger $logger
     * @param Data $helper
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $storageHelper
     * @param \Magento\MediaStorage\Helper\File\Media $mediaHelper
     * @param \Magento\Framework\Filesystem\Io\File $fileIo
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        \Magento\MediaStorage\Helper\File\Storage\Database $storageHelper,
        \Magento\MediaStorage\Helper\File\Media $mediaHelper,
        \Magento\Framework\Filesystem\Io\File $fileIo
    ) {
        parent::__construct();
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_storageHelper = $storageHelper;
        $this->_mediaHelper = $mediaHelper;
        $this->fileIo = $fileIo;
        $this->_client = $this->_helper->getClient();
    }

    /**
     * Initialization
     *
     * @return $this
     */
    public function init()
    {
        return $this;
    }

    /**
     * Return storage name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getStorageName()
    {
        return __('Amazon S3 Storage');
    }

    /**
     * Check if there was errors during sync process
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->_errors);
    }

    /**
     * Clear files and directories in storage
     *
     * @return $this
     */
    public function clear()
    {
        return $this;
    }

    /**
     * Export directories list from storage
     *
     * @param  int $offset
     * @param  int $count
     * @return array|bool
     */
    public function exportDirectories($offset = 0, $count = 100)
    {
        return false;
    }

    /**
     * Import directories to storage
     *
     * @param  array $dirs
     * @return $this
     */
    public function importDirectories($dirs = [])
    {
        return $this;
    }

    /**
     * Retrieve connection name saved at config
     *
     * @return null
     */
    public function getConfigConnectionName()
    {
        return null;
    }

    /**
     * Retrieve connection name
     *
     * @return null
     */
    public function getConnectionName()
    {
        return "S3 Storage";
    }

    /**
     * Export files list in defined range
     *
     * @param  int $offset
     * @param  int $count
     * @return array|bool
     */
    public function exportFiles($offset = 0, $count = 1)
    {
        $files = [];

        if (empty($this->objects)) {
            $this->objects = $this->_client->listObjects([
                'Bucket' => $this->getBucket(),
                'MaxKeys' => $count,
            ]);
        } else {
            $this->objects = $this->_client->listObjects([
                'Bucket' => $this->getBucket(),
                'MaxKeys' => $count,
                'Marker' => $this->objects[count($this->objects) - 1],
            ]);
        }

        if (empty($this->objects)) {
            return false;
        }

        foreach ($this->objects as $object) {
            if (isset($object['Contents']) && substr($object['Contents'], -1) != '/') {
                $content = $this->_client->getObject([
                    'Bucket' => $this->getBucket(),
                    'Key' => $object['Key'],
                ]);
                if (isset($content['Body'])) {
                    $files[] = [
                        'filename' => $object['Key'],
                        'content' => (string)$content['Body'],
                    ];
                }
            }
        }

        return $files;
    }

    /**
     * Import files list
     *
     * @param  array $files
     * @return $this
     */
    public function importFiles($files = [])
    {
        $cachingInfo = $this->getCachingInfo();
        foreach ($files as $file) {
            try {
                $acl = 'public-read';
                if (strpos($file['directory'] . '/' . $file['filename'], 'downloadable/files/links') !== false) {
                    $acl = 'private';
                }
                $fileName = ltrim($file['directory'] . '/' . $file['filename'], "/");

                $pathInfo = $this->fileIo->getPathInfo($file['filename']);
                $ext = '';
                if (isset($pathInfo['extension'])) {
                    $ext = $pathInfo['extension'];
                }
                $time = Data::DEFAULT_CACHE_TIME;
                if (!empty($cachingInfo[$ext])) {
                    $time = $cachingInfo[$ext];
                } else {
                    $time = $cachingInfo['other'];
                }
                $this->_client->putObject(
                    [
                        'Body' => $file['content'],
                        'Bucket' => $this->getBucket(),
                        'ContentType' => \GuzzleHttp\Psr7\mimetype_from_filename($file['filename']),
                        'Key' => $fileName,
                        'ACL' => $acl,
                        'CacheControl' => "max-age={$time}"
                    ]
                );
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
                $this->_logger->info($e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Save file to storage
     *
     * @param  string $fileName
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return bool
     */
    public function saveFile($fileName)
    {
        try {
            $cachingInfo = $this->getCachingInfo();
            $file = $this->_mediaHelper->collectFileInfo($this->getMediaBaseDirectory(), $fileName);
            $acl = 'public-read';
            if (strpos($fileName, 'downloadable/files/links') !== false) {
                $acl = 'private';
            }
            $pathInfo = $this->fileIo->getPathInfo($fileName);
            $ext = '';
            if (isset($pathInfo['extension'])) {
                $ext = $pathInfo['extension'];
            }
            $time = Data::DEFAULT_CACHE_TIME;
            if (!empty($cachingInfo[$ext])) {
                $time = $cachingInfo[$ext];
            } else {
                $time = $cachingInfo['other'];
            }
            $this->_client->putObject(
                [
                    'Body' => $file['content'],
                    'Bucket' => $this->getBucket(),
                    'ContentType' => \GuzzleHttp\Psr7\mimetype_from_filename($file['filename']),
                    'Key' => $fileName,
                    'ACL' => $acl,
                    'CacheControl' => "max-age={$time}"
                ]
            );
        } catch (\Exception $e) {
            $this->_logger->info(json_encode($e->getMessage()));
        }

        return $this;
    }

    /**
     * Retrieve media base directory path
     *
     * @return string
     */
    public function getMediaBaseDirectory()
    {
        if ($this->_mediaBaseDirectory === null) {
            $this->_mediaBaseDirectory = $this->_storageHelper->getMediaBaseDir();
        }
        return $this->_mediaBaseDirectory;
    }

    /**
     * @return string
     */
    protected function getBucket()
    {
        return $this->_helper->getConfigValue('s3_amazon/general_settings/bucket');
    }

    /**
     * @param $filename
     * @return bool
     */
    public function fileExists($filename)
    {
        return $this->_client->doesObjectExist($this->getBucket(), $filename);
    }

    /**
     * @param string $oldFilePath
     * @param string $newFilePath
     * @return $this
     */
    public function renameFile($oldFilePath, $newFilePath)
    {
        $cachingInfo = $this->getCachingInfo();
        $acl = 'public-read';
        if (strpos($newFilePath, 'downloadable/files/links') !== false) {
            $acl = 'private';
        }

        $pathInfo = $this->fileIo->getPathInfo($newFilePath);
        $ext = '';
        if (isset($pathInfo['extension'])) {
            $ext = $pathInfo['extension'];
        }
        $time = Data::DEFAULT_CACHE_TIME;
        if (!empty($cachingInfo[$ext])) {
            $time = $cachingInfo[$ext];
        } else {
            $time = $cachingInfo['other'];
        }

        $this->_client->copyObject(
            [
                'Bucket' => $this->getBucket(),
                'Key' => $newFilePath,
                'ACL' => $acl,
                'CopySource' => $this->getBucket() . '/' . $oldFilePath,
                'CacheControl' => "max-age={$time}"
            ]
        );

        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function deleteDirectory($path)
    {
        $mediaRelativePath = $this->_storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($mediaRelativePath, '/') . '/';
        $this->_client->deleteMatchingObjects($this->getBucket(), $prefix);

        return $this;
    }

    /**
     * @param string $path
     * @return array
     */
    public function getSubdirectories($path)
    {
        $subdirectories = [];
        $prefix = $this->_storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($prefix, '/') . '/';
        $objects = $this->_client->listObjects([
            'Bucket' => $this->getBucket(),
            'Prefix' => $prefix,
            'Delimiter' => '/',
        ]);

        if (isset($objects['CommonPrefixes'])) {
            foreach ($objects['CommonPrefixes'] as $object) {
                if (!isset($object['Prefix'])) {
                    continue;
                }
                $subdirectories[] = [
                    'name' => $object['Prefix'],
                ];
            }
        }

        return $subdirectories;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function loadByFilename($filename)
    {
        $fail = false;
        try {
            $object = $this->_client->getObject([
                'Bucket' => $this->getBucket(),
                'Key' => $filename,
            ]);

            if ($object['Body']) {
                $this->setData('id', $filename);
                $this->setData('filename', $filename);
                $this->setData('content', (string)$object['Body']);
            } else {
                $fail = true;
            }
        } catch (\Exception $e) {
            $fail = true;
            $this->_logger->info($e->getMessage());
        }

        if ($fail) {
            $this->unsetData();
        }

        return $this;
    }

    /**
     * @param string $path
     * @return array
     */
    public function getDirectoryFiles($path)
    {
        $files = [];
        $prefix = $this->_storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($prefix, '/') . '/';
        $objects = $this->_client->listObjects([
            'Bucket' => $this->getBucket(),
            'Prefix' => $prefix,
            'Delimiter' => '/',
        ]);

        if (isset($objects['Contents'])) {
            foreach ($objects['Contents'] as $object) {
                if (isset($object['Key']) && $object['Key'] != $prefix) {
                    $content = $this->_client->getObject([
                        'Bucket' => $this->getBucket(),
                        'Key' => $object['Key'],
                    ]);

                    if (isset($content['Body'])) {
                        $files[] = [
                            'filename' => $object['Key'],
                            'content' => (string)$content['Body'],
                        ];
                    }
                }
            }
        }

        return $files;
    }

    /**
     * copy file on s3 server
     */
    public function copyFile($oldFilePath, $newFilePath)
    {
        $acl = 'public-read';
        if (strpos($newFilePath, 'downloadable/files/links') !== false) {
            $acl = 'private';
        }
        $cachingInfo = $this->getCachingInfo();
        $pathInfo = $this->fileIo->getPathInfo($newFilePath);
        $ext = '';
        if (isset($pathInfo['extension'])) {
            $ext = $pathInfo['extension'];
        }
        $time = Data::DEFAULT_CACHE_TIME;
        if (!empty($cachingInfo[$ext])) {
            $time = $cachingInfo[$ext];
        } else {
            $time = $cachingInfo['other'];
        }
        $this->_client->copyObject(
            [
                'Bucket' => $this->getBucket(),
                'Key' => $newFilePath,
                'ACL' => $acl,
                'CopySource' => $this->getBucket() . '/' . $oldFilePath,
                'MetadataDirective' => 'REPLACE',
                'CacheControl' => "max-age={$time}"
            ]
        );

        return $this;
    }

    /**
     * Deletes from S3, which belong to one folder
     *
     * @param string $filename
     */
    public function deleteFile($filename)
    {
        try {
            $this->_client->deleteObject([
                'Bucket' => $this->getBucket(),
                'Key' => $filename,
            ]);
        } catch (\Exception $e) {
            $this->_logger->info("Delete: ".$e->getMessage());
        }

        return $this;
    }

    /**
     * return array of cache time
     * @return array
     */
    private function getCachingInfo(): array
    {
        if (empty($this->_cachingInfo)) {
            $this->_cachingInfo = $this->_helper->getFileCacheInfo();
        }

        return $this->_cachingInfo;
    }
}
