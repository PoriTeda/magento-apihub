<?php
/**
 * Basic Setup Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\BasicSetup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\BasicSetup\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Riki\BasicSetup\Helper\Data as DataHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Riki\BasicSetup\Model\CategoryMigration;
use Magento\Framework\App\State;
/**
 * Class CategoryImport
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\BasicSetup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

class CategoryImport extends Command
{
    /**
     * @var DataHelper
     */
    protected $dataHelper;
    /**
     * @var DirectoryList
     */
    protected $categorySetup;
    /**
     * @var ResourceConnection
     */
    protected $setup;
    /**
     * @var State
     */
    protected $state;
    /**
     * AdminuserImport constructor.
     * @param DataHelper $data
     */
    public function __construct(
        DataHelper $data,
        CategoryMigration $categorySetup,
        ResourceConnection $setup,
        State $state
    )
    {
        $this->dataHelper = $data;
        $this->categorySetup = $categorySetup;
        $this->setup = $setup;
        $this->state = $state;
        parent::__construct();
    }
    /**
     * Set param name for CLI
     */
    protected function configure()
    {
        $options = [];
        $this->setName('riki:category:import')
            ->setDescription('A CLI Category migration')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode('adminhtml');
        $totalCategories = $this->categorySetup->categorySetup('0.1.0',$this->setup);
        $output->writeln("-----------------------------------------------");
        $output->writeln("Total ".$totalCategories. " categories were imported");
        $output->writeln("-----------------------------------------------");

    }


}