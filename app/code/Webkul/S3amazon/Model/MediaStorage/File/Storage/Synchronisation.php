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

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Webkul\S3amazon\Model\MediaStorage\File\Storage\S3storage;

/**
 * Plugin for File Storage Synchronization.
 */
class Synchronisation
{
    /**
     * @var S3storage
     */
    private $s3Storage;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $mediaDirectory;

    /**
     * @param S3storage $storage
     * @param Filesystem $filesystem
     */
    public function __construct(
        S3storage $storage,
        Filesystem $filesystem
    ) {
        $this->s3Storage = $storage;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }
    
    /**
     * @param Magento\MediaStorage\Model\File\Storage\Synchronization $subject
     * @param string $fileName
     * @return array
     */
    public function beforeSynchronize($subject, $fileName)
    {
        $s3Storage = $this->s3Storage;
        try {
            $s3Storage->loadByFilename($fileName);
        } catch (\Exception $e) {
            if (empty($s3Storage) || empty($s3Storage->getId())) {
                return [$fileName];
            }
        }
        
        if ($s3Storage->getId()) {
            $file = $this->mediaDirectory->openFile($fileName, 'w');
            try {
                $file->lock();
                $file->write($s3Storage->getContent());
                $file->unlock();
                $file->close();
            } catch (\Magento\Framework\Exception\FileSystemException $e) {
                $file->close();
            }
        }
        
        return [$fileName];
    }
}
