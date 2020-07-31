<?php

namespace Riki\Sales\Plugin\Adminhtml;

class SaveUpdatedAdmin
{
    /**
     * SaveUpdatedAdmin constructor.
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
    \Magento\Backend\Model\Auth\Session $authSession
    ) {
        $this->authSession = $authSession;
    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order $subject
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    public function beforeSave(\Magento\Sales\Model\ResourceModel\Order $subject,\Magento\Framework\Model\AbstractModel $object)
    {
        if($object->hasData('updated_by') && $this->authSession->getUser()){
            $object->setData('updated_by',$this->authSession->getUser()->getUserName());
        }
    }
}
