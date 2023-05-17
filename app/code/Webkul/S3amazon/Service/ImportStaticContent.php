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
namespace Webkul\S3amazon\Service;

use Webkul\S3amazon\Helper\Data;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Helper\ProgressBar;
use Magento\Framework\Exception\FileSystemException;

/**
 * class for Import Static Content on S3.
 */
class ImportStaticContent
{
    const DEFAULT_CACHE_TIME = 86400;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Webkul\S3amazon\Logger\Logger
     */
    protected $s3Logger;

    protected $bucket;

    protected $client;

    /**
     * @param Data $helper
     * @param Webkul\S3amazon\Logger\Logger $s3Logger
     * @param Magento\Framework\Filesystem\Driver\File $driverFile
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     * @param Magento\Framework\Filesystem\Io\File $fileIo
     */
    public function __construct(
        Data $helper,
        \Webkul\S3amazon\Logger\Logger $s3Logger,
        \Magento\Framework\Filesystem\Driver\File $driverFile,
        DirectoryList $directoryList,
        Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $fileIo
    ) {
        $this->helper = $helper;
        $this->s3Logger = $s3Logger;
        $this->driverFile = $driverFile;
        $this->directoryList = $directoryList;
        $this->pubStaticDir = $filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW);
        $this->fileIo = $fileIo;
    }
    
    /**
     * @param OutputInterface $output
     */
    public function deploy(OutputInterface $output)
    {
        $this->createStorageObject();
        if (!$this->checkAvailablity()) {
            throw new LocalizedException(
                __('Bucket not available')
            );
        }

        $cachingInfo = $this->helper->getFileCacheInfo();
        $collection = $this->getFilesCollection();

        $offset = 0;
        $steps = count($collection);

        if ($steps == 0) {
            throw new LocalizedException(
                __('Files not available, please deploy static content and then try again')
            );
        }

        $progressBar = new ProgressBar($output, $steps);
        $progressBar->setBarWidth(50);
        $progressBar->setFormat('verbose');
        $progressBar->setProgressCharacter('<info>➤</info>');
        $progressBar->setBarCharacter('<info>=</info>');
        $progressBar->start();

        foreach ($collection as $filePath) {
            $progressBar->advance();
            if ($this->driverFile->isFile($filePath)) {
                $absolutePath = $this->pubStaticDir->getAbsolutePath($filePath);
                $file = str_replace($this->directoryList->getPath(DirectoryList::STATIC_VIEW), "", $absolutePath);
                $key = ltrim($file, "/");
                $pathInfo = $this->fileIo->getPathInfo($key);
                $ext = '';
                if (isset($pathInfo['extension'])) {
                    $ext = $pathInfo['extension'];
                }
                $time = self::DEFAULT_CACHE_TIME;
                if (!empty($cachingInfo[$ext])) {
                    $time = $cachingInfo[$ext];
                } else {
                    $time = $cachingInfo['other'];
                }
                $this->client->putObject(
                    [
                        'Bucket' => $this->bucket,
                        'SourceFile' => $absolutePath,
                        'Key' => $key,
                        'ACL' => 'public-read',
                        'ContentType' => \GuzzleHttp\Psr7\mimetype_from_filename($key) ?? 'application/octet-stream',
                        'CacheControl' => "max-age={$time}"
                    ]
                );
            }
        }

        $this->emptyDir();
        $progressBar->finish();
    }

    /**
     * Deletes contents of specified directory
     *
     * @param string $code
     * @param string|null $subPath
     * @return string[]
     */
    private function emptyDir()
    {
        $messages = [];
        $subPath = null;
        $excludePatterns = ['#.htaccess#', '#deployed_version.txt#'];

        $dir = $this->pubStaticDir;
        $dirPath = $dir->getAbsolutePath();
        if (!$dir->isExist()) {
            $messages[] = "The directory '{$dirPath}' doesn't exist - skipping cleanup";
            return false;
        }
        foreach ($dir->search('*', $subPath) as $path) {
            if ($path !== '.' && $path !== '..') {
                $messages[] = $dirPath . $path;
                $match = false;
                foreach ($excludePatterns as $pattern) {
                    if (preg_match($pattern, $path)) {
                        $match = true;
                    }
                }
                if (!$match) {
                    try {
                        $dir->delete($path);
                    } catch (FileSystemException $e) {
                        $messages[] = $e->getMessage();
                    }
                }
            }
        }

        return $messages;
    }

    public function getFilesCollection(): array
    {
        $excludePatterns = ['#.htaccess#', '#deployed_version.txt#'];
        $directoryPath = $this->directoryList->getPath(DirectoryList::STATIC_VIEW);
        $allFiles = [];
        if (!$this->driverFile->isExists($directoryPath)) {
            return $allFiles;
        }

        $files = $this->driverFile->readDirectory($directoryPath);
        foreach ($files as $file) {
            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $file)) {
                    continue 2;
                }
            }

            if ($this->driverFile->isFile($file)) {
                $allFiles[] = $file;
            } else {
                $directoryData = $this->driverFile->readDirectoryRecursively($file);
                foreach ($directoryData as $dirData) {
                    if ($this->driverFile->isFile($dirData)) {
                        $allFiles[] = $dirData;
                    }
                }
            }
        }

        return $allFiles;
    }

    private function createStorageObject()
    {
        $access = $this->helper->encrypt(
            $this->helper->getConfigValue('s3_amazon/general_settings/access_key')
        );
        $secret = $this->helper->encrypt(
            $this->helper->getConfigValue('s3_amazon/general_settings/secret_key')
        );
        $region = $this->helper->getConfigValue('s3_amazon/staticfile_settings/region');
        $this->bucket = $this->helper->getConfigValue('s3_amazon/staticfile_settings/bucket');

        $options = [
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $access,
                'secret' => $secret
            ]
        ];

        $this->client = new \Aws\S3\S3Client($options);
        return $this->client;
    }

    private function checkAvailablity()
    {
        try {
            $result = $this->client->headBucket([
                'Bucket' => $this->bucket
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
