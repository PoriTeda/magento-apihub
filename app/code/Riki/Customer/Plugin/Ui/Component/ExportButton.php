<?php

namespace Riki\Customer\Plugin\Ui\Component;

class ExportButton
{
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @param \Magento\Framework\AuthorizationInterface $authorization
     */
    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization
    ){
        $this->_authorization = $authorization;
    }

    /**
     * check export permission
     *
     * @param \Magento\Ui\Component\ExportButton $subject
     */
    public function beforePrepare(\Magento\Ui\Component\ExportButton $subject)
    {
        if('customer_listing' == $subject->getContext()->getNamespace()){
            $config = $subject->getData('config');

            if(!$this->_authorization->isAllowed('Riki_Customer::export')){
                unset($config['options']);
            }

            $subject->setData('config',$config);
        }
    }
}
