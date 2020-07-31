<?php

namespace Riki\Catalog\Plugin\Catalog\Block\Adminhtml\Product;

class Edit
{
    const CREATE_PROUDUCT_ACTION = 'new';
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Edit constructor.
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Framework\App\RequestInterface $request
    ){
        $this->_authorization = $authorization;
        $this->request = $request;
    }

    /**
     * check permission to update product
     *
     * @param \Magento\Catalog\Block\Adminhtml\Product\Edit $subject
     * @param $result
     * @return mixed
     */
    public function afterSetLayout(
        \Magento\Catalog\Block\Adminhtml\Product\Edit $subject,
        $result
    ){
        if ($this->request->getActionName() == self::CREATE_PROUDUCT_ACTION) {
            if(!$this->_authorization->isAllowed('Magento_Catalog::actions_create')) {
                $subject->getToolbar()->unsetChild('save-split-button');
                return $result;
            }
        }
        if(!$this->_authorization->isAllowed('Magento_Catalog::actions_edit')){
            $subject->getToolbar()->unsetChild('save-split-button');
        }else{
            $options = $subject->getToolbar()->getChildData('save-split-button', 'options');

            if(!$this->_authorization->isAllowed('Magento_Catalog::actions_create') && is_array($options)){
                foreach($options as $index  => $option){
                    if(in_array($option['id'], ['new-button', 'duplicate-button'])){
                        unset($options[$index]);
                    }
                }

                $subject->getToolbar()->getChildBlock('save-split-button')->setData('options', $options);
            }
        }

        return $result;
    }
}
