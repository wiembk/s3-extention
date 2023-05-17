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

namespace Webkul\S3amazon\Helper\MediaStorage\File\Storage;

use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\Storage\DatabaseFactory;
use \Webkul\S3amazon\Helper\Data as DataHelper;
use \Webkul\S3amazon\Model\MediaStorage\File\Storage\S3storageFactory;

class DatabaseHelper
{
    /**
     * @var DataHelper
     */
    private $helper;

    /**
     * @var S3storageFactory
     */
    private $s3StorageFactory;

    /**
     * @var object
     */
    private $storageModel;

    /**
     * @var DatabaseFactory
     */
    private $dbStorageFactory;

    /**
     * @param DataHelper $helper
     * @param S3storageFactory $s3StorageFactory
     * @param DatabaseFactory $dbStorageFactory
     */
    public function __construct(
        DataHelper $helper,
        S3storageFactory $s3StorageFactory,
        DatabaseFactory $dbStorageFactory
    ) {
        $this->helper = $helper;
        $this->s3StorageFactory = $s3StorageFactory;
        $this->dbStorageFactory = $dbStorageFactory;
    }

    /**
     * @param Database $subject
     * @param \Closure $proceed
     * @param string $filename
     * @return bool
     */
    public function aroundSaveFileToFilesystem(Database $subject, $proceed, $filename)
    {
        if ($subject->checkDbUsage() && $this->helper->getIsEnable()) {
            $file = $subject->getStorageDatabaseModel()->loadByFilename($subject->getMediaRelativePath($filename));
            if (!$file->getId()) {
                return false;
            }
            
            return $subject->getStorageFileModel()->saveFile($file->getData(), true);
        }

        return $proceed($filename);
    }

    /**
     * @param Database $subject
     * @param string $result
     * @return string
     */
    public function afterGetMediaRelativePath(Database $subject, $result)
    {
        $newMediaRelativePath = $result;
        if ($this->helper->getIsEnable()) {
            $prefixToRemove = 'pub/media/';
            if (substr($result, 0, strlen($prefixToRemove)) == $prefixToRemove) {
                $newMediaRelativePath = substr($result, strlen($prefixToRemove));
            }
        }

        return $newMediaRelativePath;
    }

    /**
     * Check file storage is database or S3
     *
     * @param Database $subject
     * @param bool $result
     * @return bool
     */
    public function afterCheckDbUsage(Database $subject, $result)
    {
        if (!$result) {
            $result = $this->helper->checkMediaStorageIsS3();
        }

        return $result;
    }

    /**
     * @param Database $subject
     * @param \Closure $proceed
     * @return Magento\MediaStorage\Model\File\Storage\Database
     */
    public function aroundGetStorageDatabaseModel(Database $subject, $proceed)
    {
        if ($this->storageModel === null) {
            if ($subject->checkDbUsage() && $this->helper->checkMediaStorageIsS3()) {
                $this->storageModel = $this->s3StorageFactory->create();
            } else {
                $this->storageModel = $this->dbStorageFactory->create();
            }
        }

        return $this->storageModel;
    }

    /**
     * @param Database $subject
     * @param \Closure $proceed
     * @param string $folderName
     */
    public function aroundDeleteFolder(Database $subject, $proceed, $folderName)
    {
        if ($this->helper->checkMediaStorageIsS3()) {
            $storageModel = $subject->getStorageDatabaseModel();
            $storageModel->deleteDirectory($folderName);
        } else {
            $proceed($folderName);
        }
    }

    /**
     * Removes any forward slashes from the file name.
     *
     * @param Database $subject
     * @param string $result
     * @return string
     */
    public function afterSaveUploadedFile(Database $subject, $result)
    {
        return ltrim($result, '/');
    }
}
