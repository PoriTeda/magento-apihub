<?php
namespace Riki\Rma\Model;

class Reason extends \Magento\Framework\Model\AbstractModel implements \Riki\Rma\Api\Data\ReasonInterface
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendAuthSession;

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
        $this->backendAuthSession = $backendAuthSession;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Riki\Rma\Model\ResourceModel\Reason');
    }

    /**
     * Get description_en or description_jp depend on language
     *
     * @return string
     */
    public function getDescription()
    {
        $user = $this->backendAuthSession->getUser();
        if (!$user instanceof \Magento\User\Model\User) {
            return $this->getData('description_en');
        }
        if (!$user->getId()) {
            return $this->getData('description_en');
        }

        $locale = $user->getData('interface_locale');
        if ($locale == 'en_US') {
            return $this->getData('description_en');
        } elseif ($locale == 'ja_JP') {
            return $this->getData('description_jp');
        }

        return $this->getData('description_en');
    }

    /**
     * Load reason by code
     * @param $reasonCode
     * @return $this
     */
    public function loadByCode($reasonCode)
    {
        return $this->load($reasonCode, 'code');
    }
}
