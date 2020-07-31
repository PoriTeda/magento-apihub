<?php
namespace Riki\Rma\Controller\Adminhtml\Returns;

class MassAction extends \Riki\Rma\Controller\Adminhtml\Returns
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_return';

    /**
     * @var \Riki\Rma\Model\Config\Source\Rma\MassAction
     */
    protected $massActionOptions;

    /**
     * @var \Riki\Rma\Model\MassActionValidator
     */
    protected $massActionValidator;

    /**
     * @var \Riki\Rma\Model\ResourceModel\RequestedMassActionFactory
     */
    protected $massActionResourceModelFactory;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * MassAction constructor.
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Rma\Model\RmaManagement $rmaManagement
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Riki\Rma\Model\Config\Source\Rma\MassAction $massActionOptions
     * @param \Riki\Rma\Model\MassActionValidator $massActionValidator
     * @param \Magento\Backend\Model\Auth\Session $authSession
     */
    public function __construct(
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Riki\Rma\Model\RmaManagement $rmaManagement,
        \Magento\Backend\App\Action\Context $context,
        \Riki\Rma\Model\Config\Source\Rma\MassAction $massActionOptions,
        \Riki\Rma\Model\MassActionValidator $massActionValidator,
        \Magento\Backend\Model\Auth\Session $authSession
    ) {
        $this->massActionOptions = $massActionOptions;
        $this->massActionValidator = $massActionValidator;
        $this->massActionResourceModelFactory = $massActionValidator->getMassActionResourceModelFactory();
        $this->authSession = $authSession;

        parent::__construct(
            $rmaRepository,
            $searchHelper,
            $logger,
            $registry,
            $rmaManagement,
            $context
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $result = $this->initRedirectResult();
        $result->setUrl($this->getUrl('adminhtml/rma/'));

        $request = $this->getRequest();

        $action = $request->getParam('action');
        $ids = $request->getParam('entity_ids', []);

        if (!empty($ids) &&
            in_array($action, array_keys($this->massActionValidator->getAllowedActions()))
        ) {
            /** @var \Riki\Rma\Model\ResourceModel\RequestedMassAction $massActionResourceModel */
            $massActionResourceModel = $this->massActionResourceModelFactory->create();

            $username = $this->authSession->getUser()->getUserName();

            $actionLabel = $this->massActionOptions->getLabel($action);

            $successItems = [];

            try {
                $this->massActionValidator->initData($ids, $action)->execute();
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }

            foreach ($this->massActionValidator->getErrorMessages() as $errorMessage) {
                $this->messageManager->addError($errorMessage);
            }

            foreach ($this->massActionValidator->getValidIds() as $rmaId) {
                $successItems[] = [
                    'action'    =>  $action,
                    'rma_id'    =>  $rmaId,
                    'requested_by'    =>  $username,
                    'status'    =>  \Riki\Rma\Model\RequestedMassAction::INIT_STATUS
                ];
            }

            if ($successCount = count($successItems)) {
                try {
                    $massActionResourceModel->getConnection()->insertMultiple(
                        $massActionResourceModel->getMainTable(),
                        $successItems
                    );
                    $this->messageManager->addSuccess(__('%1 item(s) are waiting for %2', $successCount, $actionLabel));
                } catch (\Exception $e) {
                    $this->messageManager->addError(__('Something went wrong. Please try again.'));
                }
            }
        } else {
            $this->messageManager->addError(__('Request data is invalid.'));
        }

        return $result;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        $action = $this->getRequest()->getParam('action');

        $options = $this->massActionOptions->optionList();

        if (isset($options[$action])) {
            return $this->_authorization->isAllowed($options[$action]['resource']);
        }

        return $this->_authorization->isAllowed('Magento_Rma::magento_rma');
    }
}