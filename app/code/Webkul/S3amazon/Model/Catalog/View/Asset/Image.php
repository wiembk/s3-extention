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
namespace Webkul\S3amazon\Model\Catalog\View\Asset;

use Magento\Catalog\Model\Product\Media\ConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\View\Asset\ContextInterface;
use Magento\Framework\View\Asset\LocalInterface;

class Image extends \Magento\Catalog\Model\View\Asset\Image
{
    /**
     * Image type of image (thumbnail,small_image,image,swatch_image,swatch_thumb)
     *
     * @var string
     */
    private $sourceContentType;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $contentType = 'image';

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * Misc image params depend on size, transparency, quality, watermark etc.
     *
     * @var array
     */
    private $miscParams;

    /**
     * @var ConfigInterface
     */
    private $mediaConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Image constructor.
     *
     * @param ConfigInterface $mediaConfig
     * @param ContextInterface $context
     * @param EncryptorInterface $encryptor
     * @param string $filePath
     * @param array $miscParams
     */
    public function __construct(
        ConfigInterface $mediaConfig,
        ContextInterface $context,
        EncryptorInterface $encryptor,
        $filePath,
        array $miscParams,
        \Webkul\S3amazon\Helper\Data $helper,
        \Webkul\S3amazon\Model\MediaStorage\File\Storage\S3storage $s3storage,
        \Magento\Catalog\Helper\ImageFactory $helperImageFactory,
        \Magento\Framework\View\Asset\Repository $assetRepos
    ) {
        parent::__construct(
            $mediaConfig,
            $context,
            $encryptor,
            $filePath,
            $miscParams
        );
        if (isset($miscParams['image_type'])) {
            $this->sourceContentType = $miscParams['image_type'];
            unset($miscParams['image_type']);
        } else {
            $this->sourceContentType = $this->contentType;
        }
        $this->mediaConfig = $mediaConfig;
        $this->context = $context;
        $this->filePath = $filePath;
        $this->miscParams = $miscParams;
        $this->encryptor = $encryptor;
        $this->helper = $helper;
        $this->s3storage = $s3storage;
        $this->helperImageFactory = $helperImageFactory;
        $this->assetRepos = $assetRepos;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        if ($this->helper->getIsEnable() && $this->helper->checkMediaStorageIsS3()) {
            $imageUrl = parent::getUrl();
            $origUrl = $this->getPath();
            $sourceFile = $this->getSourceFile();
            $filePath = $this->getFilePath();
            $catalogUrl = substr($sourceFile, 0, strpos($sourceFile, $filePath));
            $key = substr($imageUrl, strpos($imageUrl, $catalogUrl));

            if (!$this->s3storage->fileExists($key)) {
                $this->s3storage->saveFile($key);

                if (!$this->s3storage->fileExists($key)) {
                    $imagePlaceholder = $this->helperImageFactory->create();
                    return $this->assetRepos->getUrl($imagePlaceholder->getPlaceholder('small_image'));
                }
            }
        }
        
        return parent::getUrl();
    }
}
