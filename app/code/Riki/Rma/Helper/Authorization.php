<?php
namespace Riki\Rma\Helper;

class Authorization extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    protected $userContext;

    /**
     * @var \Magento\Integration\Model\Oauth\Token
     */
    protected $oauthToken;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * Authorization constructor.
     *
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Integration\Model\Oauth\Token $oauthToken
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Integration\Model\Oauth\Token $oauthToken,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->authSession = $authSession;
        $this->functionCache = $functionCache;
        $this->oauthToken = $oauthToken;
        $this->userContext = $userContext;
        parent::__construct($context);
    }

    /**
     * Get token of current user
     *
     * @return string
     */
    public function getApiToken()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $userId = $this->userContext->getUserId();
        $userType = $this->userContext->getUserType();
        if ($userType == \Magento\Authorization\Model\UserContextInterface::USER_TYPE_ADMIN) {
            $token = $this->oauthToken->loadByAdminId($userId);
            if (!$token->getToken()) {
                $token = $this->oauthToken->createAdminToken($userId);
            }
        } elseif ($userType == \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER) {
            $token = $this->oauthToken->loadByCustomerId($userId);
            if (!$token->getToken()) {
                $token = $this->oauthToken->createCustomerToken($userId);
            }
        }

        $result = isset($token) ? $token->getToken() : '';
        $this->functionCache->store($result);

        return $result;
    }

    /**
     * Get current logged user
     *
     * @return \Riki\User\Model\User|null
     */
    public function getCurrentUser()
    {
        /** @var \Riki\User\Model\User $user */
        $user = $this->authSession->getUser();
        if (!$user instanceof \Riki\User\Model\User) {
            return null;
        }

        return $user;
    }
}