<?php
namespace Riki\Customer\Model;


class Address extends \Magento\Customer\Model\Address
{
    /**
     * Get full customer name
     *
     * @return string
     */
    public function getName()
    {

        $name = '';

        $attributesToValue = [
            'prefix'    =>  $this->getPrefix(),
            'lastname'    =>  $this->getLastname(),
            'lastnamekana'    =>  $this->getLastnamekana(),
            'middlename'    =>  $this->getMiddlename(),
            'firstname'    =>  $this->getFirstname(),
            'firstnamekana'    =>  $this->getFirstnamekana(),
            'suffix'    =>  $this->getSuffix(),
        ];

        $attributesToSortOrder = [];

        foreach($attributesToValue as $code    =>  $value){

            $sortOrder = 0;

            if(
                $this->_eavConfig->getAttribute('customer_address', $code) &&
                $this->_eavConfig->getAttribute('customer_address', $code)->getSortOrder()
            ){
                $sortOrder = $this->_eavConfig->getAttribute('customer_address', $code)->getSortOrder();
            }

            $attributesToSortOrder[$code] = $sortOrder;
        }

        asort($attributesToSortOrder);

        foreach($attributesToSortOrder as $code =>  $sortOrder){
            if(
                $this->_eavConfig->getAttribute('customer_address', $code) &&
                $this->_eavConfig->getAttribute('customer_address', $code)->getIsVisible() &&
                isset($attributesToValue[$code]) &&
                $attributesToValue[$code]
            )
                $name .= $attributesToValue[$code];
        }

        return $name;
    }
    /**
     * Init indexing process after customer save
     *
     * @return void
     */
    public function reindex()
    {
//        $om = \Magento\Framework\App\ObjectManager::getInstance();
//        $customerItem = $om->get('\Riki\Customer\Model\Queue\Schema\CustomerQueueSchemaFactory')->create();
//        $customerItem->setCustomerId($this->getCustomerId());
//        $om->get('\Magento\Framework\MessageQueue\PublisherInterface')->publish('customer.reindex.grid', $customerItem);

    }
}