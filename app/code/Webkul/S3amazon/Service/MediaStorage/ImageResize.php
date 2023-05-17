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

namespace Webkul\S3amazon\Service\MediaStorage;

use Magento\Catalog\Model\Product\Media\ConfigInterface as MediaConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\Storage\SynchronizationFactory;
use Webkul\S3amazon\Helper\Data;

class ImageResize
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var MediaConfig
     */
    private $imageConfig;

    /**
     * @var Filesystem
     */
    private $mediaDirectory;

    /**
     * @var SynchronizationFactory
     */
    private $syncFactory;

    /**
     * @param Data $helper
     * @param SynchronizationFactory $syncFactory
     * @param MediaConfig $imageConfig
     * @param Filesystem $filesystem
     */
    public function __construct(
        Data $helper,
        SynchronizationFactory $syncFactory,
        MediaConfig $imageConfig,
        Filesystem $filesystem
    ) {
        $this->helper = $helper;
        $this->imageConfig = $imageConfig;
        $this->syncFactory = $syncFactory;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * @param Magento\MediaStorage\Service\ImageResize $subject
     * @param string $imageName
     * @return array
     */
    public function beforeResizeFromImageName(\Magento\MediaStorage\Service\ImageResize $subject, $imageName)
    {
        if (!$this->helper->checkMediaStorageIsS3()) {
            return [$imageName];
        }

        $filePath = $this->imageConfig->getMediaPath($imageName);
        if (!$this->mediaDirectory->isFile($filePath)) {
            $sync = $this->syncFactory->create(['directory' => $this->mediaDirectory]);
            $sync->synchronize($filePath);
        }
    }
}
