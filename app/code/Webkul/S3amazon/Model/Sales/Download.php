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
namespace Webkul\S3amazon\Model\Sales;

use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Framework\Filesystem;
use Webkul\S3amazon\Helper\Data;

/**
 * Plugin for Default Download.
 */
class Download
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Database
     */
    private $database;

    /**
     * @param Data $helper
     * @param Database $database
     * @param Filesystem $filesystem
     * @param FileFactory $fileFactory
     * @param FileIo $fileIo
     */
    public function __construct(
        Data $helper,
        Database $database,
        Filesystem $filesystem,
        FileFactory $fileFactory,
        FileIo $fileIo,
        $rootDirBasePath = DirectoryList::MEDIA
    ) {
        $this->helper = $helper;
        $this->database = $database;
        $this->rootDirBasePath = $rootDirBasePath;
        $this->fileIo = $fileIo;
        $this->_rootDir = $filesystem->getDirectoryWrite($this->rootDirBasePath);
        $this->_fileFactory = $fileFactory;
    }

    /**
     * @param \Magento\Sales\Model\Download $subject
     * @param \Closure $proceed
     * @param mixed $info
     * @return mixed
     */
    public function aroundDownloadFile(\Magento\Sales\Model\Download $subject, \Closure $proceed, $info)
    {
        if ($this->helper->checkMediaStorageIsS3()) {
            $s3Storage = $this->database->getStorageDatabaseModel();
            $relativePath = $info['order_path'];
            $fileExist = false;

            if (!empty($relativePath)) {
                if (!$s3Storage->fileExists($relativePath)) {
                    if ($s3Storage->fileExists($info['quote_path'])) {
                        $relativePath = $info['quote_path'];
                        $fileExist = true;
                    }
                } else {
                    $fileExist = true;
                }
            }

            if (!empty($relativePath) && $fileExist) {
                $fileInfo = $this->fileIo->getPathInfo($relativePath);
                $fileName = $fileInfo['basename'];
                $file = $s3Storage->loadByFilename($relativePath);
                
                return $this->_fileFactory->create(
                    $fileName,
                    $file->getContent()
                );
            } else {
                throw new LocalizedException(
                    __('Path "%1" is not part of allowed directory "%2"', $relativePath, $this->rootDirBasePath)
                );
            }
        } else {
            return $proceed($info);
        }
    }
}
