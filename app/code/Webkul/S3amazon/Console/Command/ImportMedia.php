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
namespace Webkul\S3amazon\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class to import mediafiles on S3
 */
class ImportMedia extends Command
{
    /**
     * Magento\MediaStorage\Model\File\Storage $storageModel
     * Magento\MediaStorage\Helper\File\Storage $storageHelper
     */
    public function __construct(
        \Magento\MediaStorage\Model\File\Storage $storageModel,
        \Magento\MediaStorage\Helper\File\Storage $storageHelper,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        $this->storageModel = $storageModel;
        $this->storageHelper = $storageHelper;
        $this->_configWriter = $configWriter;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('import:media');
        $this->setDescription('Command to import media files to Amazon S3 bucket');

        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<comment>Start Importing Media Files</comment>');
        $output->writeln('');
        $flag = true;

        try {
            $this->syncMedia($output);
        } catch (\Exception $e) {
            $flag = false;
            $output->writeln('');
            $output->writeln("<error>{$e->getMessage()}</error>");
        } catch (\Error $e) {
            $flag = false;
            $output->writeln('');
            $output->writeln("<error>{$e->getMessage()}</error>");
        }

        $output->writeln('');
        $output->writeln('');
        if ($flag) {
            $output->writeln('<info>Media Files Imported Successfully</info>');
            $output->writeln('<info>Media Storage Set To AmazonS3</info>');
        } else {
            $output->writeln('<error>Unable To Import</error>');
        }

        $output->writeln('');
    }

    /**
     * @param OutputInterface $output
     */
    private function syncMedia($output)
    {
        $storageHelper = $this->storageHelper;

        $storageDest = 2;
        $connection = null;
        if ($storageDest == $this->storageHelper->getCurrentStorageCode()
            && $this->storageHelper->isInternalStorage()) {
            throw new LocalizedException(__("Unable To Found Destination Storage"));
        }
        
        $sourceModel = $this->storageModel->getStorageModel();
        $destinationModel = $this->storageModel->getStorageModel(
            $storageDest,
            ['connection' => $connection, 'init' => true]
        );

        if (!$sourceModel || !$destinationModel) {
            throw new LocalizedException(__("Unable To Found Storage Model"));
        }

        if (get_class($sourceModel) === get_class($destinationModel)) {
            throw new LocalizedException(__("Storage Model already set to S3 Storage"));
        }

        $offset = 0;
        $steps = $this->getTotalSteps($sourceModel);
        $progressBar = new ProgressBar($output, $steps);
        $progressBar->setBarWidth(50);
        $progressBar->setFormat('verbose');
        $progressBar->setProgressCharacter('<info>➤</info>');
        $progressBar->setBarCharacter('<info>=</info>');
        $progressBar->start();

        while (($files = $sourceModel->exportFiles($offset, 1)) !== false) {
            $progressBar->advance();
            $destinationModel->importFiles($files);
            $offset += count($files);
        }
        $progressBar->finish();
        unset($files);

        $flag = $this->storageModel->getSyncFlag();
        $flagData = [
            'source' => $sourceModel->getStorageName(),
            'destination' => $destinationModel->getStorageName(),
            'destination_storage_type' => $storageDest,
            'destination_connection_name' => (string)$destinationModel->getConnectionName(),
            'has_errors' => false,
            'timeout_reached' => false,
        ];
        $flag->setFlagData($flagData);
        $flag->setState(\Magento\MediaStorage\Model\File\Storage\Flag::STATE_FINISHED)->save();
        $this->setMediaStorage();
    }

    /**
     * @return null
     */
    private function setMediaStorage()
    {
        $this->_configWriter->save(
            'system/media_storage_configuration/media_storage',
            2,
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeId = 0
        );
    }

    /**
     * @return int
     */
    private function getTotalSteps($sourceModel)
    {
        $offset = 0;
        while (($files = $sourceModel->exportFiles($offset, 1)) !== false) {
            $offset += count($files);
        }

        return $offset;
    }
}
