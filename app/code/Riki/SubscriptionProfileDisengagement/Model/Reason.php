<?php
namespace Riki\SubscriptionProfileDisengagement\Model;

use Riki\SubscriptionProfileDisengagement\Model\Reason as ReasonModel;

class Reason extends \Magento\Framework\Model\AbstractModel
{
    const VISIBILITY_BACKEND = 1;
    const VISIBILITY_FRONTEND = 2;
    const VISIBILITY_BOTH = 3;
    const VISIBILITY_BACKEND_TITLE = 'Backend';
    const VISIBILITY_FRONTEND_TITLE = 'Frontend';
    const VISIBILITY_BOTH_TITLE = 'Both';
    const STATUS_ACTIVE = 1;
    const STATUS_DISABLED = 0;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_backendAuthSession;

    /**
     * Reason constructor.
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_backendAuthSession = $backendAuthSession;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Riki\SubscriptionProfileDisengagement\Model\ResourceModel\Reason');
    }

    /**
     * {@inheritdoc}
     */
    public function setFromData($data = [])
    {
        foreach ($data as $key => $value) {
            $this->setData($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFromData()
    {
        return $this->getData();
    }

    /**
     * validate reason id
     *
     * @return bool
     */
    public function isValidCode(){
        $codes = $this->getCollection()->addFieldToSelect('code')
        ->addFieldToFilter('deleted', 0);
        foreach($codes as $code){
            if($this->getCode() == $code->getCode())
                return false;
        }
        return true;
    }

    /**
     * @param array $reasonIds
     * @param array $visibilities
     * @return \Magento\Framework\DataObject[]
     */
    public function getDisengagementReasons($reasonIds = [], $visibilities = [])
    {
        $reasonCollection = $this->getCollection();
        if (!empty($reasonIds)) {
            $reasonCollection->addFieldToFilter('id', ['in' => $reasonIds]);
        }
        if (!empty($visibilities)) {
            $reasonCollection->addFieldToFilter('visibility', ['in' =>$visibilities]);
        }
        $reasonCollection->addFieldToFilter(
            'status',
            self::STATUS_ACTIVE
        );
        return $reasonCollection->getItems();
    }
}
