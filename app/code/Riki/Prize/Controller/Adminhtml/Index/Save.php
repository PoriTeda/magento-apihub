<?php
namespace Riki\Prize\Controller\Adminhtml\Index;

class Save extends \Riki\Prize\Controller\Adminhtml\Action
{
    /**
     * @var \Riki\Prize\Model\PrizeFactory
     */
    protected $prizeFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Validator\Factory
     */
    protected $validatorFactory;

    /**
     * Save constructor.
     *
     * @param \Magento\Framework\Validator\Factory $validatorFactory
     * @param \Riki\Prize\Model\PrizeFactory $prizeFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\Validator\Factory $validatorFactory,
        \Riki\Prize\Model\PrizeFactory $prizeFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->validatorFactory = $validatorFactory;
        $this->prizeFactory = $prizeFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }


    /**
     * Save winner prize
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $redirectBack = $this->getRequest()->getParam('back', false);
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $this->_redirect('prize/index/new');
        }
        try {
            /** @var \Riki\Prize\Model\Prize $model */
            $model = $this->prizeFactory->create();
            if (!empty($data['prize_id'])) {
                $model = $model->load($data['prize_id']);
                if (!$model->getId()) {
                    $this->messageManager->addError(__('This prize no longer exists.'));
                    /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath('*/*/');
                    return $resultRedirect;
                }
            }
            $model->setData($data);
            $validator = $this->validatorFactory->createValidator('prize', 'save');
            if (!$validator->isValid($model)) {
                foreach ($validator->getMessages() as $type =>  $typeMessages) {

                    if (is_array($typeMessages)) {
                        foreach ($typeMessages as $message) {
                            $this->messageManager->addError($message);
                        }
                    } else {
                        $this->messageManager->addError($typeMessages);
                    }
                }
                $redirectBack = true;
                $this->_session->setFormData($data);
            } else {
                $model->save();
                $this->messageManager->addSuccess(__('The prize has been saved successfully.'));
            }
        } catch (\Exception $e) {
            if ($e instanceof \Magento\Framework\Exception\LocalizedException) {
                $this->messageManager->addError($e->getMessage());
            } else {
                $this->messageManager->addException($e, __('An error occurs.'));
            }

            $redirectBack = true;
            $this->_session->setFormData($data);
        }

        return $redirectBack
            ? $this->_redirect('prize/index/edit', [
                'id' => $model->getId()
            ])
            : $this->_redirect('prize/index');
    }
}
