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
use Webkul\S3amazon\Helper\Data;
use Webkul\S3amazon\Service\ImportStaticContent;

/**
 * Class to deploy static view files on S3
 */
class StaticFilesDeploy extends Command
{
    /**
     * Magento\MediaStorage\Model\File\Storage $storageModel
     * Magento\MediaStorage\Helper\File\Storage $storageHelper
     */
    public function __construct(
        \Magento\MediaStorage\Model\File\Storage $storageModel,
        \Magento\MediaStorage\Helper\File\Storage $storageHelper,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        Data $helper,
        ImportStaticContent $importer
    ) {
        $this->storageModel = $storageModel;
        $this->storageHelper = $storageHelper;
        $this->_configWriter = $configWriter;
        $this->helper = $helper;
        $this->importer = $importer;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('import:static-content');
        $this->setDescription('Command to import satic view files to Amazon S3 bucket');

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
        if (!$this->helper->getIsEnableStaticAction()) {
            $output->writeln('');
            $output->writeln("<error>This option is not enabled, check module configuration settings.</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $output->writeln('');
        $output->writeln('<comment>Start Importing Static View Files</comment>');
        $output->writeln('');
        $flag = true;

        try {
            $this->importer->deploy($output);
            $this->removeStaticSign();
        } catch (\Exception $e) {
            $flag = false;
            $output->writeln('');
            $output->writeln("<error>{$e->getMessage()}</error>");
        } catch (\Error $e) {
            $flag = false;
            $output->writeln('');
            $output->writeln("<error>{$e->getMessage()}</error>");
        } catch (LocalizedException $e) {
            $flag = false;
            $output->writeln('');
            $output->writeln("<error>{$e->getMessage()}</error>");
        }

        $output->writeln('');
        $output->writeln('');
        if ($flag) {
            $output->writeln('<info>Static View Files Imported Successfully</info>');
        } else {
            $output->writeln('<error>Unable To Import</error>');
        }

        $output->writeln('');
        
        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    /**
     * @return null
     */
    private function removeStaticSign()
    {
        $this->_configWriter->save(
            'dev/static/sign',
            0,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }
}
