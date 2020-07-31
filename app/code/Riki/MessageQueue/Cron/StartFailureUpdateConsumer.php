<?php
namespace Riki\MessageQueue\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Riki\MessageQueue\Model\TriggerConsumerFactory;

class StartFailureUpdateConsumer
{
    /**
     * @var TriggerConsumerFactory
     */
    protected $triggerConsumerFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * StartFailureUpdateConsumer constructor.
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
        /** @var \Riki\MessageQueue\Model\TriggerConsumer $triggerConsumer */
        $triggerConsumer = $this->triggerConsumerFactory->create();
        $consumers = [
            'failureUpdate'
        ];

        $maxMessage = $this->getMaxMessage();

        foreach ($consumers as $consumer) {
            $triggerConsumer->addConsumer($consumer, $maxMessage);
        }

        $triggerConsumer->execute();
    }

    /**
     * @return int|null
     */
    private function getMaxMessage()
    {
        $value = $this->scopeConfig->getValue('riki_message_queue/failure_queue/max_message_consumer');

        return $value? intval($value) : null;
    }
}
