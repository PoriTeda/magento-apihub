<?php

namespace Riki\SerialCode\Plugin;

class RemoveExportExcelOption
{
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization
    ){
        $this->_authorization = $authorization;
    }

    public function beforePrepare(\Magento\Ui\Component\ExportButton $subject)
    {
        if('serial_code_listing' == $subject->getContext()->getNamespace()){
            $config = $subject->getData('config');

            if(!$this->_authorization->isAllowed('Riki_SerialCode::serial_code_export')){
                unset($config['options']);
            }else{
                if(isset($config['options']['xml']))
                    unset($config['options']['xml']);
            }

            $subject->setData('config',$config);
        }
    }
}
