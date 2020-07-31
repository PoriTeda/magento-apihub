<?php

namespace Riki\Catalog\Plugin;

class RemoveExportExcelProduct
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
     * check permission and remove xml export
     *
     * @param \Magento\Ui\Component\ExportButton $subject
     */
    public function beforePrepare(\Magento\Ui\Component\ExportButton $subject)
    {
        $arrListingNeedRemove = ['product_listing', 'subscription_course_listing'];
        if(in_array($subject->getContext()->getNamespace(), $arrListingNeedRemove)){
            $config = $subject->getData('config');

            if(!$this->_authorization->isAllowed('Magento_Catalog::actions_export')){
                unset($config['options']);
            }else{
                unset($config['options']['xml']);
            }

            $subject->setData('config',$config);
        }
    }
}
