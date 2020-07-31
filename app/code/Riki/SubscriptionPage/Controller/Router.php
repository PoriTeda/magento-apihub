<?php
namespace Riki\SubscriptionPage\Controller;

class Router implements \Magento\Framework\App\RouterInterface
{
    /* @var \Magento\Framework\App\ActionFactory */
    protected $actionFactory;

    /**
     * Response
     *
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $_response;

    /**
     * @param \Magento\Framework\App\ActionFactory $actionFactory
     * @param \Magento\Framework\App\ResponseInterface $response
     */
    public function __construct(
        \Magento\Framework\App\ActionFactory $actionFactory,
        \Magento\Framework\App\ResponseInterface $response
    ) {
        $this->actionFactory = $actionFactory;
        $this->_response = $response;
    }

    /**
     * Validate and Match
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    public function match(\Magento\Framework\App\RequestInterface $request)
    {
        $identifier = trim($request->getPathInfo(), '/');
        if(strpos($identifier, 'subscription/hanpukai/view') !== false) {
            if ($request->getActionName() == 'noroute') {
                return;
            }
            $request->setModuleName('subscription-page')->setControllerName('view')->setActionName('index');
        } else {
            //There is no match
            return false;
        }

        /*
         * We have match and now we will forward action
         */
        return $this->actionFactory->create(
            'Magento\Framework\App\Action\Forward',
            ['request' => $request]
        );
    }
}