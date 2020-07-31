<?php

namespace Riki\Customer\Controller\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\ValidatorException;

class SsoLogin extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Riki\Customer\Model\CheckSessionKSS
     */
    protected $checkSessionKss;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Riki\Customer\Model\ResourceModel\Customer
     */
    protected $customerResourceModel;
    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $rikiCustomerRepository;
    /**
     * @var \Riki\Customer\Model\Cookie
     */
    protected $cookie;
    /**
     * @var \Magento\Framework\Encryption\UrlCoder
     */
    protected $urlCoder;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $loadedCustomerByConsumerDbId = [];

    /**
     * SsoLogin constructor.
     * @param Context $context
     * @param \Riki\Customer\Model\CheckSessionKSS $checkSessionKss
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository
     * @param \Riki\Customer\Model\Cookie $cookie
     * @param \Magento\Framework\Encryption\UrlCoder $urlCoder
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        Context $context,
        \Riki\Customer\Model\CheckSessionKSS $checkSessionKss,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Customer\Model\ResourceModel\Customer $customerResourceModel,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepository,
        \Riki\Customer\Model\Cookie $cookie,
        \Magento\Framework\Encryption\UrlCoder $urlCoder,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);
        $this->checkSessionKss = $checkSessionKss;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->customerResourceModel = $customerResourceModel;
        $this->rikiCustomerRepository = $rikiCustomerRepository;
        $this->cookie = $cookie;
        $this->urlCoder = $urlCoder;
        $this->registry = $registry;
    }

    /**
     * @return $this|ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws NotFoundException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function execute()
    {
        $location = $this->getRequest()->getParam('location');
        $isMatomegai = false;
        if ($location == \Riki\Subscription\Controller\Profiles\Select::SHOP_SITE_KEY) {
            $isMatomegai = true;
        }
        // validate url reference
        $url = $this->getRequest()->getParam('uenc');
        if ($url) {
            $refererUrl = $this->urlCoder->decode($url);
            if (!filter_var($refererUrl, FILTER_VALIDATE_URL)) {
                throw new NotFoundException(__('Parameter is incorrect.'));
            }
        }

        $this->getResponse()->setNoCacheHeaders();

        if (!$this->scopeConfig->getValue(
            'sso_login_setting/sso_group/use_sso_login',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            return $this->_redirectInvalidRequest();
        }

        $ssoId = $this->getRequest()->getCookie(\Riki\Customer\Model\SsoConfig::SSO_SESSION_ID, null);

        if (!$ssoId) {
            return $this->_redirectInvalidRequest();
        }

        $checkSession = $this->checkSessionKss->checkSession($ssoId);
        $ssoConsumerDbId = isset($checkSession['consumerDbId']) ? $checkSession['consumerDbId'] : null;
        if (!$ssoConsumerDbId) {
            return $this->_redirectInvalidRequest();
        }

        if ($this->customerSession->isLoggedIn()
            && $this->customerSession->getCustomer()->getData('consumer_db_id') != $ssoConsumerDbId
        ) {
            $this->customerSession->logout();
            $this->cookie->sendInvalidatePrivateCache();

            return $this->resultRedirectFactory->create()->setPath('*/*/*', ['_current' => true]);
        }

        if ($this->customerSession->isLoggedIn()) {
            return $this->_redirectInvalidRequest();
        } else {
            $customer = null;

            try {
                $customer = $this->_updateCustomer($ssoConsumerDbId);
                $this->customerSession->unsHasMissingInformation();
            } catch (InputException $e) {// allow to login customer for case invalidate required field
                $this->messageManager->addError($e->getMessage());
                $this->customerSession->setHasMissingInformation(true);
            } catch (ValidatorException $e) {
                if ($e->getMessage() == 'The business code doesn\'t exist') {
                    throw $e;
                }
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('An error has occurred.'));
                return $this->resultRedirectFactory->create()->setRefererOrBaseUrl();
            }

            if ($customer === null) {
                $customer = $this->loadCustomer($ssoConsumerDbId);
            }

            if ($customer) {
                try {
                    // Add flag to check action customer sso login
                    $this->registry->register('is_customer_sso_login', true);

                    $this->loginCustomer($customer);
                    if ($isMatomegai) {
                        return $this->_forward('select','profiles','subscriptions',$this->getRequest()->getParams());
                    }
                    return $this->resultRedirectFactory->create()->setRefererOrBaseUrl();
                } catch (\Exception $e) {
                    $this->messageManager->addExceptionMessage($e, __('An error has occurred.'));
                }
            }
        }

        return $this->resultRedirectFactory->create()->setRefererOrBaseUrl();
    }

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return $this
     */
    protected function loginCustomer(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        $customer->setFlagSsoLoginAction(true);

        $this->_eventManager->dispatch(
            'riki_customer_customer_authenticated',
            ['customer' => $customer]
        );

        $this->customerSession->setCustomerDataAsLoggedIn($customer);
        $this->customerSession->regenerateId();

        $this->cookie->sendInvalidatePrivateCache(true);
        $this->cookie->sendNoCache();

        return $this;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function _redirectInvalidRequest()
    {
        return $this->resultRedirectFactory->create()->setRefererOrBaseUrl();
    }

    /**
     * @param $ssoConsumerDbId
     *
     * @return \Magento\Customer\Model\Data\Customer
     * @throws \Exception
     */
    protected function _updateCustomer($ssoConsumerDbId)
    {
        $customerApiData = $this->rikiCustomerRepository->prepareAllInfoCustomer($ssoConsumerDbId);

        if (!isset($customerApiData['customer_api']['email'])) {
            throw new LocalizedException(__('Invalid SSO session.'));
        }

        $customerDataModel = $this->loadCustomer($ssoConsumerDbId);

        $this->customerResourceModel->setNeedHandleDuplicateEmailException(true);

        if ($customerDataModel) {
            return $this->rikiCustomerRepository->createUpdateEcCustomer(
                $customerApiData,
                $ssoConsumerDbId,
                null,
                $customerDataModel
            );
        }

        return $this->rikiCustomerRepository->createUpdateEcCustomer($customerApiData, $ssoConsumerDbId);
    }

    /**
     * @param $consumerDbId
     * @return mixed
     */
    protected function loadCustomer($consumerDbId)
    {
        if (!isset($this->loadedCustomerByConsumerDbId[$consumerDbId])) {
            $this->loadedCustomerByConsumerDbId[$consumerDbId] = false;
            $filter = $this->searchCriteriaBuilder
                ->addFilter('consumer_db_id', $consumerDbId, 'eq')
                ->setPageSize(1)
                ->create();
            $customers = $this->customerRepository->getList($filter);

            $this->customerResourceModel->setNeedHandleDuplicateEmailException(true);
            if ($customers->getTotalCount() > 0) {
                foreach ($customers->getItems() as $customer) {
                    $this->loadedCustomerByConsumerDbId[$consumerDbId] = $customer;
                    break;
                }
            }
        }

        return $this->loadedCustomerByConsumerDbId[$consumerDbId];
    }
}