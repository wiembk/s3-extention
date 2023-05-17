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
namespace Webkul\S3amazon\Model\Downloadable\Sample;

use Webkul\S3amazon\Helper\Data;
use Magento\Downloadable\Api\Data\SampleInterface;

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
     * @param SampleInterface $sample
     * @param bool $validateSampleContent
     * @return array
     */
    public function beforeIsValid(
        \Magento\Downloadable\Model\Sample\ContentValidator $subject,
        SampleInterface $sample,
        $validateSampleContent = true
    ) {
        if ($validateSampleContent && $this->helper->checkMediaStorageIsS3()) {
            if ($sample->getSampleType() != 'url' && !$sample->getLinkFileContent()
                && $sample->getBasePath() && $sample->getSampleFile()
            ) {
                $validateSampleContent = false;
            }
        }

        return [$sample, $validateSampleContent];
    }
}
