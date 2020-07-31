<?php
namespace Riki\Sales\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Riki\MessageQueue\Model\TriggerConsumerFactory;

class StartOrderCaptureConsumer
{
    /**
     * @var TriggerConsumer
     */
    protected $triggerConsumerFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * StartOrderCaptureConsumer constructor.
     * @param TriggerConsumerFactory $triggerConsumerFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        TriggerConsumerFactory $triggerConsumerFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->triggerConsumerFactory = $triggerConsumerFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     *
     */
    public function execute()
    {
        $prefixes = range("a", "z");

        $prefixes = array_slice($prefixes, 0, $this->getNumOfConsumer());

        $consumers = array_map(function ($prefix) {
            return sprintf('%sSalesOrderCapture', $prefix);
        }, $prefixes);

        /** @var \Riki\MessageQueue\Model\TriggerConsumer $triggerConsumer */
        $triggerConsumer = $this->triggerConsumerFactory->create();

        $maxMessage = $this->getMaxMessage();

        foreach ($consumers as $consumer) {
            $triggerConsumer->addConsumer($consumer, $maxMessage);
        }

        $triggerConsumer->execute();
    }

    /**
     * @return int
     */
    private function getMaxMessage()
    {
        $configValue = (int)$this->scopeConfig->getValue('paygent_config/capture/max_capture_consumer_message_number');

        return $configValue? $configValue : 1;
    }

    /**
     * @return int
     */
    private function getNumOfConsumer()
    {
        $configValue = (int)$this->scopeConfig->getValue('paygent_config/capture/capture_consumer_number');

        return $configValue? $configValue : 1;
    }
}
