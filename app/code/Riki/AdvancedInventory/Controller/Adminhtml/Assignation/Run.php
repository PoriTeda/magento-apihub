<?php
namespace Riki\AdvancedInventory\Controller\Adminhtml\Assignation;

use Magento\Framework\Exception\LocalizedException;

class Run extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Assignation\Run
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * Run constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Wyomind\Core\Helper\Data $helperCore
     * @param \Wyomind\AdvancedInventory\Helper\Data $helperData
     * @param \Wyomind\AdvancedInventory\Model\AssignationFactory $modelAssignationFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Wyomind\Core\Helper\Data $helperCore,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Wyomind\AdvancedInventory\Model\AssignationFactory $modelAssignationFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        $this->authSession = $authSession;
        $this->jsonHelper = $jsonHelper;

        parent::__construct(
            $context,
            $resultPageFactory,
            $helperCore,
            $helperData,
            $modelAssignationFactory,
            $coreRegistry
        );
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        try {
            $this->_coreRegistry->unregister('assignation_update_by');
            $this->_coreRegistry->register('assignation_update_by', $this->authSession->getUser()->getUserName());
            return parent::execute();
        } catch (LocalizedException $e) {
            $errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $errorMessage = __('Assignation data is invalid, please try again!');
        }

        return $this->getResponse()->representJson($this->jsonHelper->jsonEncode(['message'  =>  $errorMessage]));
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Sales::actions_edit');
    }
}
