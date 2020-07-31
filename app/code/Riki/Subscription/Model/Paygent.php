<?php
namespace Riki\Subscription\Model;

use Magento\Framework\DataObject;

class Paygent
{
    protected $_paymentType = '02';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlInterface;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customerModel;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
    /**
     * @var \Bluecom\Paygent\Model\Processor\Cclink
     */
    protected $_cclink;
    /**
     * @var \Bluecom\Paygent\Model\Processor\Cc
     */
    protected $_cc;
    /**
     * @var \Bluecom\Paygent\Helper\Data
     */
    protected $paygentHelper;
    /**
     * @var \Bluecom\Paygent\Logger\Logger
     */
    protected $paygentLogger;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $_profileFactory;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Bluecom\Paygent\Model\Processor\Cclink $cclink,
        \Bluecom\Paygent\Model\Processor\Cc $cc,
        \Bluecom\Paygent\Helper\Data $paygentHelper,
        \Bluecom\Paygent\Logger\Logger $paygentLogger,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\Profile\Data $helperProfile
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_urlInterface = $urlInterface;
        $this->_customerSession = $customerSession;
        $this->_customerModel = $customerModel;
        $this->_messageManager = $messageManager;
        $this->_cclink = $cclink;
        $this->_cc = $cc;
        $this->paygentHelper = $paygentHelper;
        $this->paygentLogger = $paygentLogger;
        $this->_profileFactory = $profileFactory;
        $this->helperProfile  = $helperProfile;
    }

    /**
     * Validate Card
     *
     * @param $profileId
     * @param $is_backend
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateCard($profileId, $isHanpukai = false)
    {
        $tradingId = $profileId.strtotime(date('Y-m-d H:i:s'));
        //init redirect link of paygent
        $obj = new DataObject();
        $obj->setTradingId($tradingId);
        $obj->setPaymentType($this->_paymentType);
        $obj->setId(1);
        $obj->setSeqMerchantId($this->_scopeConfig->getValue('payment/paygent/merchant_id'));
        $obj->setMerchantName(mb_convert_kana($this->_scopeConfig->getValue('payment/paygent/merchant_name'), 'ASKV', 'utf-8'));
        $obj->setPaymentDetail($this->_scopeConfig->getValue('payment/paygent/payment_detail'));
        $obj->setPaymentDetailKana(mb_convert_kana($this->_scopeConfig->getValue('payment/paygent/payment_detail_kana'), 'rnkh', 'utf-8'));
        $obj->setPaymentTermDay($this->_scopeConfig->getValue('payment/paygent/payment_term_day'));
        if (!$isHanpukai) {
            $obj->setReturnUrl($this->_urlInterface->getUrl('subscriptions/profile/index'));
        } else {
            $obj->setReturnUrl($this->_urlInterface->getUrl('subscriptions/profile/hanpukai/'));
        }

        $obj->setIsbtob('1');
        $obj->setInformUrl($this->_scopeConfig->getValue('payment/paygent/use_http_inform')
            ? str_replace('https', 'http', $this->_urlInterface->getUrl('subscriptions/paygent/response'))
            : $this->_urlInterface->getUrl('subscriptions/paygent/response'));

        $obj->setPaymentClass($this->_scopeConfig->getValue('payment/paygent/paymentclass'));
        $obj->setUseCardConfNumber($this->_scopeConfig->getValue('payment/paygent/use_cvv'));
        $obj->setThreedsecureRyaku($this->_scopeConfig->getValue('payment/paygent/use_3dsecure'));
        //generate Hash string
        $obj->setHc($this->paygentHelper->generateHash($obj));
        //send request to paygent and get link type redirect
        $result = $this->paygentHelper->executeCallPaygent($obj);

        //save trading for version profile
        $versionId = $this->helperProfile->checkProfileHaveVersion($profileId);
        if ($versionId) {
            $profileId =  $versionId;
        }
        //load profile
        $profileModel = $this->_profileFactory->create()->load($profileId);

        if ($result['result'] == 0) {
            //redirect url received from paygent
            $this->_customerSession->setData('verify_url', $result['url']);

            if ($profileModel->getId()) {
                $profileModel->setRandomTrading($tradingId);
                try {
                    $profileModel->save();
                } catch (\Exception $e) {
                    $this->paygentLogger->critical($e);
                }
            }
        } else {
            $errorCode = $result['response_code'];
            $errorDetail = $result['response_detail'];

            if ($errorCode == '6001' || $errorCode == '6002') {
                //re-init when exist trading id
                $newTradingId = $profileId.strtotime(date('Y-m-d H:i:s'));

                if ($profileModel->getId()) {
                    $profileModel->setRandomTrading($newTradingId);
                    try {
                        $profileModel->save();
                    } catch (\Exception $e) {
                        $this->paygentLogger->critical($e);
                    }
                }

                $this->validateCard($newTradingId, $isHanpukai);
            } elseif ($errorCode == '6003') {
                //link redirect still not expire
                $this->_customerSession->setData('verify_url', $result['url']);
            } else {
                $message = sprintf(
                    'Authorization process has an error. error code is %s, error detail is %s.',
                    $errorCode,
                    $errorDetail
                );
                throw new \Magento\Framework\Exception\LocalizedException(__($message));
            }
            return $this;
        }
    }

    /**
     * Refund
     *
     * @param $result
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void($result)
    {
        $processor = $this->_cclink->init();

        //telegram 021 for cancel authorize
        $processor->setParam('telegram_kind', '021');
        $processor->setParam('payment_id', $result['payment_id']);
        $processor->setParam('trading_id', $result['trading_id']);

        $result = $processor->process();
        $processor->getResult();
        $paymentObject = $processor->getPaymentObject();
        
        $status = null;

        if ($result == '1') {
            $status = $paymentObject->getResultStatus();
        } else {
            $this->_messageManager->addError(__('Network Error.'));
            throw new \Magento\Framework\Exception\LocalizedException(__('Network Error.'));
        }

        if ($status == '0') {
            //void success
            return true;
        } else {
            $errorCode = $paymentObject->getResponseCode();
            $errorDetail = $paymentObject->getResponseDetail();

            $message = sprintf(
                'Subscription Profile void failure. error code is %s, error detail is %s',
                $errorCode,
                mb_convert_encoding($errorDetail, 'utf-8', 'sjis')
            );

            $this->_messageManager->addError(__($message));

            throw new \Magento\Framework\Exception\LocalizedException(
                __($message)
            );
        }
    }

    /**
     * Capture
     *
     * @param $result
     * @return $this|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture($result)
    {
        $processor = $this->_cclink->init();
        $processor->setParam('telegram_kind', '022');
        $processor->setParam('payment_id', $result['payment_id']);
        $processor->setParam('trading_id', $result['trading_id']);

        $result = $processor->process();
        $paymentObject = $processor->getPaymentObject();

        if ($this->_scopeConfig->getValue('payment/paygent/debug')) {
            //write log to file when enable debug flag
            $this->paygentLogger->info($paymentObject->getResponseCode());
            $this->paygentLogger->info($paymentObject->getResponseDetail());
        }


        $status = null;

        if ($result == '1') {
            $status = $paymentObject->getResultStatus();
        } else {
            $this->_messageManager->addError(__('Network Error.'));
            throw new \Magento\Framework\Exception\LocalizedException(__('Network Error.'));
        }

        if ($status == '0') {
            //capture success
            return true;
        } else {
            $errorCode = $paymentObject->getResponseCode();
            $errorDetail = $paymentObject->getResponseDetail();

            $message = sprintf(
                'Subscription Profile capture failure. error code is %s, error detail is %s',
                $errorCode,
                mb_convert_encoding($errorDetail, 'utf-8', 'sjis')
            );
            $this->_messageManager->addError(__($message));

            throw new \Magento\Framework\Exception\LocalizedException(__($message));
        }
        return $this;
    }

    public function validateCardApi($profileId, $returnUrl)
    {
        $tradingId = $profileId.strtotime(date('Y-m-d H:i:s'));
        //init redirect link of paygent
        $obj = new DataObject();
        $obj->setTradingId($tradingId);
        $obj->setPaymentType($this->_paymentType);
        $obj->setId(1);
        $obj->setSeqMerchantId($this->_scopeConfig->getValue('payment/paygent/merchant_id'));
        $obj->setMerchantName(mb_convert_kana($this->_scopeConfig->getValue('payment/paygent/merchant_name'), 'ASKV', 'utf-8'));
        $obj->setPaymentDetail($this->_scopeConfig->getValue('payment/paygent/payment_detail'));
        $obj->setPaymentDetailKana(mb_convert_kana($this->_scopeConfig->getValue('payment/paygent/payment_detail_kana'), 'rnkh', 'utf-8'));
        $obj->setPaymentTermDay($this->_scopeConfig->getValue('payment/paygent/payment_term_day'));
        $obj->setReturnUrl($returnUrl);

        $obj->setIsbtob('1');
        $obj->setInformUrl($this->_scopeConfig->getValue('payment/paygent/use_http_inform')
            ? str_replace('https', 'http', $this->_urlInterface->getUrl('subscriptions/paygent/response'))
            : $this->_urlInterface->getUrl('subscriptions/paygent/response'));

        $obj->setPaymentClass($this->_scopeConfig->getValue('payment/paygent/paymentclass'));
        $obj->setUseCardConfNumber($this->_scopeConfig->getValue('payment/paygent/use_cvv'));
        $obj->setThreedsecureRyaku($this->_scopeConfig->getValue('payment/paygent/use_3dsecure'));
        //generate Hash string
        $obj->setHc($this->paygentHelper->generateHash($obj));
        //send request to paygent and get link type redirect
        $result = $this->paygentHelper->executeCallPaygent($obj);

        //save trading for version profile
        $versionId = $this->helperProfile->checkProfileHaveVersion($profileId);
        if ($versionId) {
            $profileId =  $versionId;
        }
        //load profile
        $profileModel = $this->_profileFactory->create()->load($profileId);

        if ($result['result'] == 0) {
            //redirect url received from paygent
            if ($profileModel->getId()) {
                $profileModel->setRandomTrading($tradingId);
                try {
                    $profileModel->save();
                } catch (\Exception $e) {
                    $this->paygentLogger->critical($e);
                }
            }
            return $result['url'];
        } else {
            $errorCode = $result['response_code'];
            $errorDetail = $result['response_detail'];

            if ($errorCode == '6001' || $errorCode == '6002') {
                //re-init when exist trading id
                $newTradingId = $profileId.strtotime(date('Y-m-d H:i:s'));

                if ($profileModel->getId()) {
                    $profileModel->setRandomTrading($newTradingId);
                    try {
                        $profileModel->save();
                    } catch (\Exception $e) {
                        $this->paygentLogger->critical($e);
                    }
                }

                $this->validateCardApi($newTradingId, $returnUrl);
            } elseif ($errorCode == '6003') {
                //link redirect still not expire
                return $result['url'];
            } else {
                $message = sprintf(
                    'Authorization process has an error. error code is %s, error detail is %s.',
                    $errorCode,
                    $errorDetail
                );
                throw new \Magento\Framework\Exception\LocalizedException(__($message));
            }
            return $this;
        }
    }
}
