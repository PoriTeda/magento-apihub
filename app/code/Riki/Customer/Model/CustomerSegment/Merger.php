<?php

namespace Riki\Customer\Model\CustomerSegment;

class Merger implements \Magento\Framework\MessageQueue\MergerInterface
{
    /**
     * @var \Riki\Customer\Model\CustomerSegment\CustomerEventListBuilder
     */
    protected $customerEventListBuilder;

    /**
     * Merger constructor.
     *
     * @param CustomerEventListBuilder $customerEventListBuilder
     */
    public function __construct(
        \Riki\Customer\Model\CustomerSegment\CustomerEventListBuilder $customerEventListBuilder
    ) {
        $this->customerEventListBuilder = $customerEventListBuilder;
    }

    /**
     * @inheritdoc
     */
    public function merge(array $messageList)
    {
        $data = [];
        $mergedMessages = [];
        foreach ($messageList as $topic => $messages) {
            foreach ($messages as $messageId => $message) {
                $customerId = $message->getCustomerId();
                $eventName = $message->getEventName();
                $key = $customerId . '' . $eventName;
                $data[$key] = [
                    'customerId' => $customerId,
                    'eventName' => $eventName
                ];
            }

            $mergedMessages[$topic][] = $this->customerEventListBuilder->build($data);
        }

        return $mergedMessages ;
    }
}
