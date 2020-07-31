<?php

namespace Riki\SubscriptionEmail\Model\Order\Email;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magento\Sales\Model\Order\Email\Container\Template;

class SenderBuilder extends \Magento\Sales\Model\Order\Email\SenderBuilder
{
    /* @var \Riki\SubscriptionEmail\Helper\Data */
    protected $subEmailHelperData;

    protected $order;

    public function __construct(
        \Riki\SubscriptionEmail\Helper\Data $subEmailHelperData,
        Template $templateContainer,
        IdentityInterface $identityContainer,
        TransportBuilder $transportBuilder
    ){
        $this->subEmailHelperData = $subEmailHelperData;
        parent::__construct($templateContainer, $identityContainer, $transportBuilder);
    }

    public function setOrder($value)
    {
        $this->order = $value;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function sendSubscriptionProfileEmail($isHanpukaiSubscription = false)
    {

        $this->configureSubscriptionProfileEmailTemplate($isHanpukaiSubscription);
        $this->transportBuilder->addTo(
            $this->identityContainer->getCustomerEmail(),
            $this->identityContainer->getCustomerName()
        );

        $copyTo = $this->getListEmail();

        if (!empty($copyTo) && $this->getHelper()->getConfig(\Riki\SubscriptionEmail\Helper\Data::CONFIG_SUBSCRIPTION_PROFILE_EMAIL_SEND_COPY_METHOD) == 'bcc') {
            foreach ($copyTo as $email) {
                $this->transportBuilder->addBcc($email);
            }
        }

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
    }

    public function getListEmail()
    {
        $listEmail = $this->getHelper()->getConfig(\Riki\SubscriptionEmail\Helper\Data::CONFIG_SUBSCRIPTION_PROFILE_EMAIL_SEND_EMAIL_COPY_TO);
        return explode(',',trim($listEmail));
    }

    public function sendSubscriptionProfileCopyEmail($isHanpukaiSubscription = false)
    {
        $copyTo = $this->getListEmail();

        if (!empty($copyTo) && $this->getHelper()->getConfig(\Riki\SubscriptionEmail\Helper\Data::CONFIG_SUBSCRIPTION_PROFILE_EMAIL_SEND_COPY_METHOD) == 'copy') {
            foreach ($copyTo as $email) {
                $this->configureSubscriptionProfileEmailTemplate($isHanpukaiSubscription);
                $this->transportBuilder->addTo($email);
                $transport = $this->transportBuilder->getTransport();
                $transport->sendMessage();
            }
        }
    }

    protected function configureSubscriptionProfileEmailTemplate($isHanpukaiSubscription = false)
    {
        if($isHanpukaiSubscription == true) {
            $this->transportBuilder->setTemplateIdentifier($this->getHelper()->getHanpukaiEmailTemplate());
        } else {
            $this->transportBuilder->setTemplateIdentifier($this->getHelper()->getEmailTemplate());
        }
        $this->transportBuilder->setTemplateOptions($this->templateContainer->getTemplateOptions());
        $this->transportBuilder->setTemplateVars($this->templateContainer->getTemplateVars());
        $this->transportBuilder->setFrom($this->getHelper()->getConfig(\Riki\SubscriptionEmail\Helper\Data::CONFIG_SUBSCRIPTION_PROFILE_EMAIL_SENDER));
    }

    public function getHelper()
    {
        return $this->subEmailHelperData;
    }

    /**
     * @param null $IncrementId
     */
    public function send($IncrementId = null, $emailType = null)
    {
        $this->configureEmailTemplate();
        $this->transportBuilder->addTo(
            $this->identityContainer->getCustomerEmail(),
            $this->identityContainer->getCustomerName()
        );
        $copyTo = $this->identityContainer->getEmailCopyTo();
        if (!empty($copyTo) && $this->identityContainer->getCopyMethod() == 'bcc') {
            foreach ($copyTo as $email) {
                $this->transportBuilder->addBcc($email);
            }
        }
        if($this->getOrder()){
            $orderIncrementId = $this->getOrder()->getIncrementId();
        }else{
            $orderIncrementId = $IncrementId;
        }
        $emailType = $emailType ? $emailType : 'email_order';
        $transport = $this->transportBuilder->getTransport();
        $transport->setRelationEntityId($orderIncrementId);
        $transport->setRelationEntityType($emailType);
        $transport->sendMessage();
    }
}