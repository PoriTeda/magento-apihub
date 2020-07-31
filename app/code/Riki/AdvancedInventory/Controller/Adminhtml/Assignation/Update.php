<?php
namespace Riki\AdvancedInventory\Controller\Adminhtml\Assignation;

use Magento\Framework\Exception\LocalizedException;

class Update extends \Wyomind\AdvancedInventory\Controller\Adminhtml\Assignation\Update
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
     * Update constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Wyomind\Core\Helper\Data $helperCore
     * @param \Wyomind\AdvancedInventory\Helper\Data $helperData
     * @param \Wyomind\AdvancedInventory\Model\AssignationFactory $modelAssignationFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Wyomind\Core\Helper\Data $helperCore,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Wyomind\AdvancedInventory\Model\AssignationFactory $modelAssignationFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    )
    {
        $this->authSession = $authSession;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context, $resultPageFactory, $helperCore, $helperData, $modelAssignationFactory, $coreRegistry);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        parse_str($params['data'], $data);

        $data['updated_by'] = $this->authSession->getUser()->getUserName();

        $params['data'] = http_build_query($data);

        $this->getRequest()->setParams($params);

        try {
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
