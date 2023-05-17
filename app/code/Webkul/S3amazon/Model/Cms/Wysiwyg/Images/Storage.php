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
namespace Webkul\S3amazon\Model\Cms\Wysiwyg\Images;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Webkul\S3amazon\Helper\Data;
use Magento\MediaStorage\Model\File\Storage\Directory\DatabaseFactory;

/**
 * Plugin for Storage.
 *
 * @see Storage
 */
class Storage
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
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private $directory;

    /**
     * @var DatabaseFactory
     */
    private $directoryDatabaseFactory;

    /**
     * @param Data $helper
     * @param Database $database
     * @param \Magento\Framework\Filesystem $filesystem
     * @param DatabaseFactory $directoryDatabaseFactory
     * @param \Magento\Cms\Helper\Wysiwyg\Images $cmsWysiwygImages
     */
    public function __construct(
        Data $helper,
        Database $database,
        \Magento\Framework\Filesystem $filesystem,
        DatabaseFactory $directoryDatabaseFactory,
        \Magento\Cms\Helper\Wysiwyg\Images $cmsWysiwygImages
    ) {
        $this->helper = $helper;
        $this->database = $database;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->directoryDatabaseFactory = $directoryDatabaseFactory;
        $this->_cmsWysiwygImages = $cmsWysiwygImages;
    }

    /**
     * @param \Magento\Cms\Model\Wysiwyg\Images\Storage $subject
     * @param string $path
     * @return array
     */
    public function beforeGetDirsCollection(\Magento\Cms\Model\Wysiwyg\Images\Storage $subject, $path)
    {
        $this->createSubDirectories($path);
        return [$path];
    }

    /**
     * @param string $path
     * @return null
     */
    protected function createSubDirectories($path)
    {
        if ($this->database->checkDbUsage()) {
            $subDirectories = $this->directoryDatabaseFactory->create();
            $directories = $subDirectories->getSubdirectories($path);

            foreach ($directories as $directory) {
                if (!empty($directory['name'])) {
                    $this->directory->create($directory['name']);
                }
            }
        }
    }

    /**
     * @param Magento\Cms\Model\Wysiwyg\Images\Storage $subject
     * @param string $result
     * @return mixed
     */
    public function afterResizeFile(\Magento\Cms\Model\Wysiwyg\Images\Storage $subject, $result)
    {
        if ($this->helper->checkMediaStorageIsS3()) {
            $thumbnailPath = $this->database->getMediaRelativePath($result);
            $this->database->getStorageDatabaseModel()->saveFile($thumbnailPath);
        }

        return $result;
    }

    /**
     * @param Magento\Cms\Model\Wysiwyg\Images\Storage $subject
     * @param string $result
     * @return string
     */
    public function afterGetThumbsPath(\Magento\Cms\Model\Wysiwyg\Images\Storage $subject, $result)
    {
        return rtrim($result, '/');
    }

    /**
     * @param Storage $subject
     * @param string $result
     * @return string
     */
    public function afterGetThumbnailUrl(\Magento\Cms\Model\Wysiwyg\Images\Storage $subject, $result)
    {
        return str_replace($this->_cmsWysiwygImages->getBaseUrl().'/', $this->_cmsWysiwygImages->getBaseUrl(), $result);
    }
}
