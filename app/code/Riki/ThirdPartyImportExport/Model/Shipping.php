<?php
namespace Riki\ThirdPartyImportExport\Model;


class Shipping extends \Magento\Framework\Model\AbstractModel
{
    protected $_items;

    /**
     * @var ResourceModel\Shipping\Detail\CollectionFactory
     */
    protected $_detailCollectionFactory;

    /**
     * @var \Riki\ThirdPartyImportExport\Helper\Validator
     */
    protected $_validator;

    /**
     * Shipping constructor.
     * @param ResourceModel\Shipping\Detail\CollectionFactory $detailCollectionFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping\Detail\CollectionFactory $detailCollectionFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_detailCollectionFactory = $detailCollectionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping');
    }

    /**
     * Get shipping name
     *
     * @return string
     */
    public function getShippingName()
    {
        return  $this->getData('address_last_name') . ' ' . $this->getData('address_first_name') . ' 様'; // @codingStandardsIgnoreLine
    }

    /**
     * Get shipping address
     *
     * @return string
     */
    public function getShippingAddress()
    {
        $address = $this->getData('postal_code');
        $address = '〒 ' . substr($address, 0, 3) . '-' . substr($address, 3); // format zip code xxx-xxxx // @codingStandardsIgnoreLine

        $address = trim($address, ", ") . ', ' . $this->getData('address1');
        $address = trim($address, ", ") . ', ' . $this->getData('address2');
        $address = trim($address, ", ") . ', ' . $this->getData('address3');
        $address = trim($address, ", ") . ', ' . $this->getData('address4');

        return trim($address, ", ");
    }


    /**
     * Get detail collection
     *
     * @return mixed
     */
    public function getItems($isHanpukai = false)
    {
        if ($this->_items) {
            return $this->_items;
        }

        $this->_items = $this->_detailCollectionFactory->create();
        if (!$isHanpukai) {
            $this->_items->addFieldToFilter('shipping_no', $this->getId())
                ->addFieldToFilter('sku_code', ['neq' => 'HANPUKAIDISCOUNT']);
        } else {
            $this->_items->addFieldToFilter('shipping_no', $this->getId());
        }

        return $this->_items;
    }

    /**
     * Get wrapping fee
     *
     * @return number
     */
    public function getWrappingFee()
    {
        $fee = 0;

        $items = $this->getItems();
        foreach ($items as $item) {
            if ($item->isIgnore()) {
                continue;
            }
            $fee += $item->getData('purchasing_amount') * $item->getData('gift_price');
        }

        return $fee;
    }

    /**
     * Shipping is return
     *
     * @return bool
     */
    public function isReturn()
    {
        return intval($this->getData('return_item_type')) === 1;
    }
}
