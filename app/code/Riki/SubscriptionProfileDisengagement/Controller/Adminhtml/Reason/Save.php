<?php

namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason;

use Magento\Framework\Exception\LocalizedException;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends \Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Reason
{
    protected $_reasonCollectionFactory;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        \Riki\SubscriptionProfileDisengagement\Model\ReasonFactory $reasonFactory,
        \Riki\SubscriptionProfileDisengagement\Model\ResourceModel\Reason\CollectionFactory $reasonCollectionFactory
    ){
        $this->_reasonCollectionFactory = $reasonCollectionFactory;

        parent::__construct(
            $context,
            $coreRegistry,
            $resultPageFactory,
            $reasonFactory
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $result = $this->initRedirectResult();
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $result->setUrl($this->getUrl('*/reason'));
            return $result;
        }

        $result->setUrl($this->getUrl('*/reason/newAction'));
        try {
            $post = $request->getPostValue();
            if (isset($post['id']) && $post['id']) {
                $model = $this->_reasonFactory->create()->load($post['id']);
                $model->setData($post)->save();
            } else {
                $model = $this->_reasonCollectionFactory->create()->getReasonByCode(isset($post['code'])? $post['code']:'');

                if($model->getId()){
                    if($model->getStatus()){
                        $model = $this->_reasonFactory->create();
                        $model->setData($post)->save();
                    }else{
                        $model->setTitle(isset($post['title'])? $post['title']:'');
                        $model->setStatus(1)->save();
                    }
                }else{
                    $model = $this->_reasonFactory->create();
                    $model->setData($post)->save();
                }
            }

            $this->messageManager->addSuccess(__('The reason has been saved.'));

            $this->_getSession()->setFormData(false);

            if ($request->getParam('back') == 'edit') {
                $result->setUrl($this->getUrl('*/reason/edit', ['id' => $model->getId()]));
            }else{
                $result->setUrl($this->getUrl('*/*'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addError(nl2br($e->getMessage()));
            $this->_getSession()->setData('riki_spdisengagement_reason_form_data', $request->getParams());
        } catch (\Exception $e) {
            if($e->getCode() == 23000 && strpos($e->getMessage(), 'SUBSCRIPTION_DISENGAGEMENT_REASON_CODE') !== false){
                $this->messageManager->addError(__('This reason code already exists.'));
            }else{
                $this->messageManager->addException($e, __('Something went wrong while saving this reason.'));
            }

            $this->_getSession()->setData('riki_spdisengagement_reason_form_data', $request->getParams());
        }

        return $result;
    }

    /**
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionProfileDisengagement::reason_save');
    }
}
