<?php
namespace Riki\FairAndSeasonalGift\Ui\Component\Listing\Column;

class Membership extends \Magento\Ui\Component\Listing\Columns
{
    /**
     * @var \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership
     */
    protected $_membershipHelper;
    /**
     * Membership constructor.
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $membership
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $membership,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->_membershipHelper = $membership;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items']) && is_array($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if( !empty($item['mem_ids']) ){
                    $optionArray = explode(',', $item['mem_ids']);
                    $value = '';
                    foreach ($optionArray as $vl) {
                        $opt = $this->_membershipHelper->getOptionValue($vl);
                        if( !empty($opt) ){
                            $value .= $opt .'<br>';
                        }
                    }
                    $item['mem_ids'] = $value;
                }
            }
        }
        return $dataSource;
    }
}
