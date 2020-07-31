<?php
namespace Riki\Customer\Plugin\Customer\Block\Adminhtml\Edit;

class DeleteButton
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
     * check permission customer edit
     *
     * @param $subject
     * @param \Closure $proceed
     * @return array
     */
    public function aroundGetButtonData(
        $subject,
        \Closure $proceed
    ){
        if(!$this->_authorization->isAllowed('Riki_Customer::delete')){
            return [];
        }

        return $proceed();
    }
}