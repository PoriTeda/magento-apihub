<?php

namespace Riki\Customer\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;

class CustomerMembership extends \Magento\Ui\Component\Listing\Columns\Column
{

    protected $_customerMembershipOptionObj;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $membership,
        array $components = [],
        array $data = []
    ) {
        $this->_customerMembershipOptionObj = $membership;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $membershipNames = [];
        $availableOptions = $this->_customerMembershipOptionObj->toOptionArray();
        foreach ($availableOptions as $membership) {
            $membershipNames[$membership['value']] = $membership['label'];
        }
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $memberships = [];
                if(isset($item[$fieldName]) && !empty($item[$fieldName])){
                    foreach ($item[$fieldName] as $membershipId) {
                        if(isset($membershipNames[$membershipId]))
                            $memberships[] = $membershipNames[$membershipId];
                    }
                    $item[$fieldName] = implode(', ', $memberships);
                }
            }
        }

        return $dataSource;
    }
}
