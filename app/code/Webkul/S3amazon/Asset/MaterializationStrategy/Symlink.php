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
namespace Webkul\S3amazon\Asset\MaterializationStrategy;

use Webkul\S3amazon\Helper\Data;

/**
 * Plugin for symlink.
 */
class Symlink
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
     * Whether the strategy can be applied
     *
     * @param Magento\Framework\App\View\Asset\MaterializationStrategy\Symlink $subject
     * @return bool
     */
    public function afterIsSupported($subject, $result)
    {
        if ($this->helper->getIsEnableStaticAction()) {
            return false;
        }
    }
}
