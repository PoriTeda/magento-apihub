<?php
namespace Riki\Subscription\Controller\Profile\Payment\Method;

class Edit extends \Riki\Subscription\Controller\Profile
{
    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Controller\ResultInterface|bool
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id', 0);

        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->profileFactory->create()->load($id);
        if (!$profile->getId()) {
            $this->_forward('no-route');
            return false;
        }

        if($this->_profileData->isTmpProfileId($id,$profile)){
            $this->_forward('no-route');
            return false;
        }

        if ($profile->getCustomerId() != $this->customerSession->getCustomerId()) {
            $this->_forward('no-route');
            return false;
        }

        $postValues = $this->getRequest()->getPostValue();
        if (isset($postValues['payment_method'])
            && isset($postValues['selected_payment_method'])
            && $postValues['payment_method'] == $postValues['selected_payment_method']
        ) {
            $this->_forward('payment_method_save', null, null, ['id' => $id]);
            return true;
        }

        $result = $this->initPageResult();
        $result->getConfig()->getTitle()->set(__('Changing payment method'));

        /** @var \Riki\Subscription\Block\Frontend\Profile\Payment\Method\Edit $block */
        $block = $this->_view->getLayout()->getBlock('subscriptions.profile.payment.method.edit');
        if ($block instanceof \Riki\Subscription\Block\Frontend\Profile\Payment\Method\Edit) {
            $block->setData('profile', $profile);
        }

        return $result;
    }
}