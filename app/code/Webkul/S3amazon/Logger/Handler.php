<?php
/**
 * Webkul S3amazon Logger.
 * @category  Webkul
 * @package   Webkul_S3amazon
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\S3amazon\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level.
     *
     * @var int
     */
    public $loggerType = \Monolog\Logger::INFO;

    /**
     * File name.
     *
     * @var string
     */
    public $fileName = '/var/log/s3amazon.log';
}
