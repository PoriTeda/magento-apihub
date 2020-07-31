<?php
namespace Riki\SpotOrderApi\Helper;

use Magento\Framework\App\Helper\Context;

class CheckRequestApi extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    protected $_userContextInterface;
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    /**
     * CheckRequestApi constructor.
     * @param Context $context
     */
    public function __construct(
        Context $context,
        \Magento\Authorization\Model\UserContextInterface $userContextInterface,
        \Magento\Framework\Webapi\Rest\Request $request
    )
    {
        parent::__construct($context);
        $this->_userContextInterface = $userContextInterface;
        $this->_request = $request;
    }

    /**
     * Check call api
     *
     * @return bool
     */
    public function checkCallApi()
    {
        try {
            $request = $this->_request->getRequestData();
            if (isset($request['call_spot_order_api'])) {
                return true;
            }
            if (isset($request['cartEstimation'])) {
                return true;
            }

/*          if($this->_userContextInterface->getUserType() != \Magento\Authorization\Model\UserContextInterface::USER_TYPE_ADMIN)
            {
                return false;
            }*/

            return false;
        } catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     * @param null $price
     * @return int|null
     */
    public function getPriceRequest($price=null)
    {
        $request = $this->_request->getRequestData();
        if($this->checkCallApi())
        {
            if (isset($request['cartItem']) && isset($request['cartItem']['price'])) {
                $price = (int)trim($request['cartItem']['price']) ;
            }
        }

        return $price;
    }

}