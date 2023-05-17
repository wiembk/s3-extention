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
namespace Webkul\S3amazon\Model\Downloadable\Link;

use Webkul\S3amazon\Helper\Data;
use Magento\Downloadable\Api\Data\LinkInterface;

/**
 * Plugin for Link.
 */
class ContentValidator
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param Magento\Downloadable\Model\Link\ContentValidator $subject
     * @param LinkInterface $link
     * @param bool $validateLinkContent
     * @param bool $validateSampleContent
     * @return array
     */
    public function beforeIsValid(
        \Magento\Downloadable\Model\Link\ContentValidator $subject,
        LinkInterface $link,
        $validateLinkContent = true,
        $validateSampleContent = true
    ) {
        $s3Status = $this->helper->checkMediaStorageIsS3();
        if ($validateLinkContent && $s3Status) {
            if ($link->getLinkType() != 'url' && !$link->getLinkFileContent()
                && $link->getBasePath() && $link->getLinkFile()
            ) {
                $validateLinkContent = false;
            }
        }

        if ($validateSampleContent && $s3Status) {
            if ($link->getSampleType() != 'url' && !$link->getSampleFileContent()
                && $link->getBaseSamplePath() && $link->getSampleFile()
            ) {
                $validateSampleContent = false;
            }
        }

        return [$link, $validateLinkContent, $validateSampleContent];
    }
}
