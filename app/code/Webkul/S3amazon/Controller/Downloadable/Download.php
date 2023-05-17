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
namespace Webkul\S3amazon\Controller\Downloadable;

class Download extends \Magento\Downloadable\Controller\Download\Link
{
    /**
     * Prepare response to output resource contents
     *
     * @param string $path         Path to resource
     * @param string $resourceType Type of resource (see Magento\Downloadable\Helper\Download::LINK_TYPE_* constants)
     * @return void
     */
    protected function _processDownload($path, $resourceType)
    {
        $s3helper = $this->_objectManager->get(\Webkul\S3amazon\Helper\Data::class);
        if ($s3helper->getIsEnable() && $s3helper->checkMediaStorageIsS3()) {
            $contentType = \GuzzleHttp\Psr7\mimetype_from_filename($path);
            $key = substr($path, strripos($path, $this->_getLink()->getBasePath()));
            $fileInfo = $this->_objectManager->get(\Magento\Framework\Filesystem\Io\File::class)->getPathInfo($key);
            $fileName = $fileInfo['basename'];
            $client = $s3helper->getClient();
            $bucket = $s3helper->getConfigValue('s3_amazon/general_settings/bucket');
            $content = '';

            if ($client->doesObjectExist($bucket, $key)) {
                $object = $client->getObject([
                    'Bucket' => $bucket,
                    'Key' => $key,
                ]);
                $content = (string)$object['Body'];
                $fileSize = (string)$object['ContentLength'];
            }

            /** @var HttpResponse $response */
            $response = $this->getResponse();
            $downloader = $this->_objectManager->get(\Magento\Framework\App\Response\Http\FileFactory::class);

            return $downloader->create(
                $fileName,
                $content
            );
        } else {
            parent::_processDownload($path, $resourceType);
        }
    }
}
