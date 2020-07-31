<?php


namespace Nestle\Migration\Command;


use Magento\Framework\App\ObjectManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCommand extends \Symfony\Component\Console\Command\Command
{
    const ENTITY_ID = "entity_id";

    protected function configure()
    {
        $options = [
            new InputOption(
                self::ENTITY_ID,
                null,
                InputOption::VALUE_OPTIONAL,
                'entity id',
                false
            )
        ];
        $this->setName('nestle:migration-command')->setDescription('Migration command')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \Magento\Framework\App\ObjectManager::getInstance()->get("Nestle\Debugging\Helper\DebuggingHelper")
            ->inClass($this)
            ->logServerIp()
            ->logBacktrace()
            ->save();
        if ($entityId = $input->getOption(self::ENTITY_ID)) {
            /** @var \Riki\Shipment\Model\Order\ShipmentBuilder\Creator $creator */
            $creator = ObjectManager::getInstance()->create("Riki\Shipment\Model\Order\ShipmentBuilder\Creator");
            /** @var \Magento\Framework\App\State $appState */
            $appState = ObjectManager::getInstance()->create("Magento\Framework\App\State");
            $appState->setAreaCode("crontab");
            /** @var  \Riki\Shipment\Model\Order\ShipmentBuilder\ProfileBuilder $messageData */
            $messageData = ObjectManager::getInstance()->create("Riki\Shipment\Model\Order\ShipmentBuilder\ProfileBuilder");
            $order = new \Magento\Framework\DataObject();
            $order->setOrderId($entityId);
            $messageData->setItems([
                $order
            ]);
            $creator->createShipmentFromQueue($messageData);
        } else {
            $output->writeln("Please define entity_id");
        }

    }
}
