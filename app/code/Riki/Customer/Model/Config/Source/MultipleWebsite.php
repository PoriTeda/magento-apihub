<?php
namespace Riki\Customer\Model\Config\Source;


class MultipleWebsite extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const AMB_MEMBERSHIP = '3';
    /**
     * @var OptionFactory
     */
    protected $optionFactory;


    /**
     * @var StoreManager
     */
    protected $_storeManager;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->_storeManager = $storeManager;
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        /* your Attribute options list*/

        $this->_options = [];
        $websites = $this->_storeManager->getWebsites();
        foreach($websites as $website){
            $option = ['label'=>$website->getName(), 'value'=>$website->getId()];
            $this->_options[] = $option;
        }

        return $this->_options;
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
                'type' => Table::TYPE_INTEGER,
                'nullable' => true,
                'comment' => 'Custom Attribute Options  ' . $attributeCode . ' column',
            ],
        ];
    }

}
