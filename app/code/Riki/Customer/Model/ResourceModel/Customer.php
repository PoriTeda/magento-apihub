<?php
Namespace Riki\Customer\Model\ResourceModel;

use Magento\Framework\Config\Dom\ValidationException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\ValidatorException;


class Customer extends \Magento\Customer\Model\ResourceModel\Customer
{

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;
    /**
     * @var \Riki\Customer\Model\ShoshaFactory
     */
    protected $_modelShoshaFactory;
    /**
     * @var bool
     */
    protected $needToHandleDuplicateEmailException = false;

    /**
     * Customer constructor.
     * @param \Magento\Eav\Model\Entity\Context $context
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Validator\Factory $validatorFactory
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param array $data
     */
    public function __construct(
        \Magento\Eav\Model\Entity\Context $context,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Validator\Factory $validatorFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Riki\Customer\Model\ShoshaFactory $modelShoshaFactory,
        array $data = []
    )
    {
        $this->eventManager = $eventManager;
        $this->_modelShoshaFactory = $modelShoshaFactory;

        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $scopeConfig,
            $validatorFactory,
            $dateTime,
            $storeManager,
            $data
        );
    }

    /**
     * @return array
     */
    protected function _getDefaultAttributes()
    {
        return [
            'created_at',
            'updated_at',
            'increment_id',
            'store_id',
            'website_id',
            'flag_export_bi'
        ];
    }

    /**
     * @param \Magento\Framework\DataObject $customer
     * @return $this
     * @throws AlreadyExistsException
     * @throws ValidatorException
     */
    protected function _beforeSave(\Magento\Framework\DataObject $customer){
        if($customer->hasData('flag_export_bi')){
            $customer->setData('flag_export_bi',0);
        }

        /** @var \Magento\Customer\Model\Customer $customer */
        if ($customer->getStoreId() === null) {
            $customer->setStoreId($this->storeManager->getStore()->getId());
        }
        $customer->getGroupId();

        $this->walkAttributes('backend/beforeSave', [$customer]);
        if (!$customer->getEmail()) {
            throw new ValidatorException(__('Please enter a customer email.'));
        }
        if (!$this->checkShoshaCustomer($customer) && !empty($customer->getData('shosha_business_code'))) {
            throw new ValidatorException(__('The business code doesn\'t exist'));
        }

        if ($this->isDuplicatedEmail($customer)) { // custom
            if ($this->getNeedHandleDuplicateEmailException()) {
                $this->eventManager->dispatch(
                    'customer_email_duplicate_exception',
                    [
                        'customer'     => $customer
                    ]
                );
            } else {
                throw new AlreadyExistsException(
                    __('A customer with the same email already exists in an associated website.')
                );
            } // end custom
        }

        // set confirmation key logic
        if ($customer->getForceConfirmed() || $customer->getPasswordHash() == '') {
            $customer->setConfirmation(null);
        } elseif (!$customer->getId() && $customer->isConfirmationRequired()) {
            $customer->setConfirmation($customer->getRandomConfirmationKey());
        }
        // remove customer confirmation key from database, if empty
        if (!$customer->getConfirmation()) {
            $customer->setConfirmation(null);
        }

        $this->_validate($customer);

        return $this;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return string
     */
    private function isDuplicatedEmail(\Magento\Customer\Model\Customer $customer)
    {
        $connection = $this->getConnection();
        $bind = ['email' => $customer->getEmail()];

        $select = $connection->select()->from(
            $this->getEntityTable(),
            [$this->getEntityIdField()]
        )->where(
            'email = :email'
        );
        if ($customer->getSharingConfig()->isWebsiteScope()) {
            $bind['website_id'] = (int)$customer->getWebsiteId();
            $select->where('website_id = :website_id');
        }
        if ($customer->getId()) {
            $bind['entity_id'] = (int)$customer->getId();
            $select->where('entity_id != :entity_id');
        }

        return $connection->fetchOne($select, $bind);
    }

    /**
     * @param $value
     * @return $this
     */
    public function setNeedHandleDuplicateEmailException($value)
    {
        $this->needToHandleDuplicateEmailException = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function getNeedHandleDuplicateEmailException()
    {
        $result = $this->needToHandleDuplicateEmailException;
        $this->needToHandleDuplicateEmailException = false;
        return $result;
    }

    public function checkShoshaCustomer(\Magento\Framework\DataObject $customer)
    {
        $shoshaBusinessCode = $customer->getData('shosha_business_code');
        if ($customer && $shoshaBusinessCode != '') {

            $aShoshaCollections = $this->_modelShoshaFactory->create()->getCollection()->addFieldToFilter('shosha_business_code', $shoshaBusinessCode);
            $aShoshaItem = null;

            foreach ($aShoshaCollections as $aShoshaCollectionItem) {
                $aShoshaItem = $aShoshaCollectionItem;
            }
            if (!$aShoshaItem) {
                return false;
            }

        }
        return true;
    }
}