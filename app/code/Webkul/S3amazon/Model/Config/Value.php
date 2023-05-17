<?php declare(strict_types=1);
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_S3amazon
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\S3amazon\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Plugin for config value.
 */
class Value
{
    /**
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        ScopeConfigInterface $config
    ) {
        $this->_config = $config;
    }

    /**
     * Get old value from existing config
     * @param \Magento\Framework\App\Config\Value $subject
     * @param \Closure $proceed
     * @return string
     */
    public function aroundGetOldValue(\Magento\Framework\App\Config\Value $subject, \Closure $proceed)
    {
        if ($subject->getPath() == 's3_amazon/cache/cache_control') {
            $value = $this->_config->getValue(
                $subject->getPath(),
                $subject->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $subject->getScopeCode()
            );

            if (is_array($value)) {
                return '';
            } else {
                return (string)$value;
            }
        } else {
            return $proceed();
        }
    }
}
