<?php

namespace Riki\Customer\Model\GridIndexer;

use Magento\Framework\MessageQueue\MergerInterface;

class Merger implements MergerInterface
{
    protected $itemsBuilder;

    /**
     * Merger constructor.
     *
     * @param ItemsBuilder $itemsBuilder
     */
    public function __construct(ItemsBuilder $itemsBuilder)
    {
        $this->itemsBuilder = $itemsBuilder;
    }

    /**
     * @inheritdoc
     */
    public function merge(array $messageList)
    {
        $customerIds = [];

        $mergedMessages = [];
        foreach ($messageList as $topic => $messages) {
            foreach ($messages as $messageId => $message) {
                $customerIds[$messageId] = $message->getCustomerId();
            }
            $mergedMessages[$topic][] = $this->itemsBuilder->build(array_unique($customerIds));
        }

        return $mergedMessages ;
    }
}
