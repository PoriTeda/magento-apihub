<?php
namespace Riki\GiftWrapping\Model\Config;

use Magento\Framework\DB\Ddl\Table;

class Giftwrapping extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var OptionFactory
     */
    protected $optionFactory;

    /**
     * @var \Magento\GiftWrapping\Model\WrappingFactory
     */
    protected $wrappingFactory;

    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory
     */
    protected $wrappingCollectionFactory;

    /**
     * @var WrappingSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \Magento\GiftWrapping\Model\ResourceModel\Wrapping
     */
    protected $resourceModel;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    public function __construct(
        \Magento\GiftWrapping\Model\WrappingFactory $wrappingFactory,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->wrappingFactory = $wrappingFactory;
        $this->wrappingCollectionFactory = $wrappingCollectionFactory;
        $this->resourceModel = $resource;
        $this->storeManager = $storeManager;
    }
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $giftCollection = $this->wrappingCollectionFactory->create()->addWebsitesToResult()->load();
        $arrayGift = array();
        if($giftCollection->getSize()){
            foreach($giftCollection as $gift){
                $this->_options[] = ['label'=> $gift->getGiftName(), 'value'=> $gift->getWrappingId()];
            }
        }
        return $this->_options ;
    }

    /**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string|bool
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    /**
     * Retrieve flat column definition
     *
     * @return array
     */
    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        return [
            $attributeCode => [
                'unsigned' => false,
                'default' => null,
                'extra' => null,
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'comment' => 'Custom Attribute Options  ' . $attributeCode . ' column',
            ],
        ];
    }

}
