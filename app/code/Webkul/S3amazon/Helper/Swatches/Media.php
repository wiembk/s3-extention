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
namespace Webkul\S3amazon\Helper\Swatches;

use Magento\Framework\App\Filesystem\DirectoryList;

class Media
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
     * @var array
     */
    protected $swatchImageTypes = ['swatch_image', 'swatch_thumb'];

    /**
     * @param Data $helper
     * @param S3Storage $storageModel
     */
    public function __construct(
        \Webkul\S3amazon\Helper\Data $helper,
        \Webkul\S3amazon\Model\MediaStorage\File\Storage\S3storage $s3storage,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Magento\Framework\Image\Factory $imageFactory,
        \Magento\Framework\Filesystem\DriverInterface $filesystemDriver,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->helper = $helper;
        $this->s3storage = $s3storage;
        $this->fileStorageDb = $fileStorageDb;
        $this->mediaConfig = $mediaConfig;
        $this->imageFactory = $imageFactory;
        $this->filesystemDriver = $filesystemDriver;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * move image from tmp to catalog dir
     *
     * @param string $file
     * @return string path
     */
    public function aroundMoveImageFromTmp(\Magento\Swatches\Helper\Media $subject, $proceed, $file)
    {
        if (strrpos($file, '.tmp') == strlen($file) - 4) {
            $file = substr($file, 0, strlen($file) - 4);
        }
        $destinationFile = $this->getUniqueFileName($subject, $file);

        /** @var $storageHelper \Magento\MediaStorage\Helper\File\Storage\Database */
        $storageHelper = $this->fileStorageDb;

        if ($this->helper->checkMediaStorageIsS3()) {
            $this->mediaDirectory->renameFile(
                $this->mediaConfig->getTmpMediaPath($file),
                $subject->getAttributeSwatchPath($destinationFile)
            );
            $storageHelper->renameFile(
                $this->mediaConfig->getTmpMediaPath($file),
                $subject->getAttributeSwatchPath($destinationFile)
            );
        } else {
            $proceed($file);
        }

        return str_replace('\\', '/', $destinationFile);
    }

    /**
     * Generate swatch thumb and small swatch image
     *
     * @param string $imageUrl
     * @return $this
     */
    public function aroundGenerateSwatchVariations(\Magento\Swatches\Helper\Media $subject, $proceed, $imageUrl)
    {
        if (!$this->helper->checkMediaStorageIsS3()) {
            return $proceed($imageUrl);
        }
        $absoluteImagePath = $this->mediaDirectory->getAbsolutePath($subject->getAttributeSwatchPath($imageUrl));
        $absolutePath = $this->mediaDirectory->getAbsolutePath();
        $storageHelper = $this->fileStorageDb;
        foreach ($this->swatchImageTypes as $swatchType) {
            $imageConfig = $subject->getImageConfig();
            $swatchNamePath = $this->generateNamePath($subject, $imageConfig, $imageUrl, $swatchType);
            $image = $this->imageFactory->create($absoluteImagePath);
            $this->setupImageProperties($image);
            $image->resize($imageConfig[$swatchType]['width'], $imageConfig[$swatchType]['height']);
            $this->setupImageProperties($image, true);
            $image->save($swatchNamePath['path_for_save'], $swatchNamePath['name']);
            $key = str_replace($absolutePath, '', $swatchNamePath['path_for_save'].'/'.$swatchNamePath['name']);
            $storageHelper->saveFile($key);
        }
        return $this;
    }

    /**
     * Generate swatch path and name for saving
     *
     * @param array $imageConfig
     * @param string $imageUrl
     * @param string $swatchType
     * @return array
     */
    protected function generateNamePath($subject, $imageConfig, $imageUrl, $swatchType)
    {
        $fileName = $this->prepareFileName($imageUrl);
        $absolutePath = $this->mediaDirectory->getAbsolutePath($subject->getSwatchCachePath($swatchType));
        return [
            'path_for_save' => $absolutePath.$subject->getFolderNameSize($swatchType, $imageConfig).$fileName['path'],
            'name' => $fileName['name']
        ];
    }

    /**
     * Image url /m/a/magento.png return ['name' => 'magento.png', 'path => '/m/a']
     *
     * @param string $imageUrl
     * @return array
     */
    protected function prepareFileName($imageUrl)
    {
        $fileArray = explode('/', $imageUrl);
        $fileName = array_pop($fileArray);
        $filePath = implode('/', $fileArray);
        return ['name' => $fileName, 'path' => $filePath];
    }

    /**
     * Setup base image properties for resize
     *
     * @param \Magento\Framework\Image $image
     * @param bool $isSwatch
     * @return $this
     */
    protected function setupImageProperties(\Magento\Framework\Image $image, $isSwatch = false)
    {
        $image->quality(100);
        $image->constrainOnly(true);
        $image->keepAspectRatio(true);
        if ($isSwatch) {
            $image->keepFrame(true);
            $image->keepTransparency(true);
            $image->backgroundColor([255, 255, 255]);
        }
        return $this;
    }

    /**
     * Check whether file to move exists. Getting unique name
     *
     * @param <type> $file
     * @return string
     */
    protected function getUniqueFileName($subject, $file)
    {
        if ($this->fileStorageDb->checkDbUsage()) {
            $destFile = $this->fileStorageDb->getUniqueFilename(
                $this->mediaConfig->getBaseMediaUrlAddition(),
                $file
            );
        } else {
            $dir = $this->filesystemDriver->getParentDirectory($file);
            $destFile = $dir . '/' . \Magento\MediaStorage\Model\File\Uploader::getNewFileName(
                $this->mediaDirectory->getAbsolutePath($subject->getAttributeSwatchPath($file))
            );
        }

        return $destFile;
    }
}
