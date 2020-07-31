<?php

namespace Riki\Customer\Model\CustomerSegment;

class CustomerEventListBuilder
{
    /**
     * @var \Riki\Customer\Api\CustomerSegment\CustomerEventInterfaceFactory
     */
    protected $customerEventFactory;

    /**
     * @var \Riki\Customer\Api\CustomerSegment\CustomerEventListInterfaceFactory
     */
    protected $customerEventListFactory;

    public function __construct(
        \Riki\Customer\Api\CustomerSegment\CustomerEventInterfaceFactory $customerEventInterfaceFactory,
        \Riki\Customer\Api\CustomerSegment\CustomerEventListInterfaceFactory $customerEventListInterfaceFactory
    ) {
        $this->customerEventFactory = $customerEventInterfaceFactory;
        $this->customerEventListFactory = $customerEventListInterfaceFactory;
    }

    /**
     * @param array $data
     * @return \Riki\Customer\Api\CustomerSegment\CustomerEventListInterface
     */
    public function build(array $data)
    {
        $objectItems = [];

        foreach ($data as $dt) {
            /** @var \Riki\Customer\Api\CustomerSegment\CustomerEventInterface $item */
            $item = $this->customerEventFactory->create();
            $item->setCustomerId($dt['customerId']);
            $item->setEventName($dt['eventName']);
            $objectItems[] = $item;
        }

        /** @var \Riki\Customer\Api\CustomerSegment\CustomerEventListInterface $CustomerEventList */
        $CustomerEventList = $this->customerEventListFactory->create();
        $CustomerEventList->setItems($objectItems);

        return $CustomerEventList;
    }
}
