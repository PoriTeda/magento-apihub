<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile\Edit;

class LoadBlock extends \Magento\Backend\App\Action
{
    /**
     * Loading page block
     *
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $request = $this->getRequest();
        $asJson = $request->getParam('json');
        $block = $request->getParam('block');

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        if ($asJson) {
            $resultPage->addHandle('profile_profile_edit_load_block_json');
        } else {
            $resultPage->addHandle('profile_profile_edit_load_block_plain');
        }

        if ($block) {
            $blocks = explode(',', $block);
            if ($asJson && !in_array('message', $blocks)) {
                $blocks[] = 'message';
            }

            foreach ($blocks as $block) {
                $resultPage->addHandle('profile_profile_edit_load_block_' . $block);
            }
        }
        $result = $resultPage->getLayout()->renderElement('content');

        return $this->resultFactory->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_RAW
        )->setContents($result);
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::profile_edit');
    }
}
