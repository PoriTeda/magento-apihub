<?php

namespace Riki\ImportExport\Model\ResourceModel\Import\Customer;

class Storage extends \Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\Storage
{
    /**
     * @var $_customerData
     */
    public $_customerData = [];
    /**
     * Load needed data from customer collection
     *
     * @return void
     */
    public function load()
    {
        if ($this->_isCollectionLoaded == false) {
            $connection = $this->_customerCollection->getConnection();
            $select =$connection->select()
                ->from($this->_customerCollection->getResource()->getEntityTable(), ['entity_id', 'website_id', 'consumer_db_id','email']);
            $customers = $connection->fetchAll($select);
            foreach ($customers as $data) {
                $consumerId = $data['consumer_db_id'];
                $consumerEmail = $data['email'];

                if ($consumerId && !isset($this->_customerIds[$consumerId])) {
                    $this->_customerIds[$consumerId] = [];
                    $this->_customerData[$consumerEmail] = [];
                }

                $this->_customerIds[$consumerId][$data['website_id']] = $data['entity_id'];
                $this->_customerData[$consumerEmail][$data['website_id']] = $data['entity_id'];
            }

            $this->_isCollectionLoaded = true;
        }
    }

    /**
     * @inheritdoc
     */
    public function addCustomer(\Magento\Framework\DataObject $customer): \Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\Storage
    {
        $consumerId = $customer->getConsumerDbId();

        if ($consumerId && !isset($this->_customerIds[$consumerId])) {
            $this->_customerIds[$consumerId] = [];
        }

        $this->_customerIds[$consumerId][$customer->getWebsiteId()] = $customer->getId();

        return $this;
    }
}
