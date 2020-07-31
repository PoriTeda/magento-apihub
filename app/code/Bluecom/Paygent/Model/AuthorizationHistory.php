<?php

namespace Bluecom\Paygent\Model;

class AuthorizationHistory
{
    /**
     * @var \Bluecom\Paygent\Model\Reauthorize
     */
    protected $reauthorizeFactory;
    /**
     * @var \Riki\Preorder\Model\OrderPreorder
     */
    protected $orderPreorder;
    /**
     * @var \Riki\Preorder\Model\OrderItemPreorder
     */
    protected $itemPreorder;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Bluecom\Paygent\Helper\Data
     */
    protected $paygentHelper;

    public function __construct(
        \Bluecom\Paygent\Model\ReauthorizeFactory $reauthorizeFactory,
        \Riki\Preorder\Model\OrderPreorder $orderPreorder,
        \Riki\Preorder\Model\OrderItemPreorder $itemPreorder,
        \Psr\Log\LoggerInterface $logger,
        \Bluecom\Paygent\Helper\Data $paygentHelper
    )
    {
        $this->reauthorizeFactory = $reauthorizeFactory;
        $this->orderPreorder = $orderPreorder;
        $this->itemPreorder = $itemPreorder;
        $this->logger = $logger;
        $this->paygentHelper = $paygentHelper;
    }

    public function saveAuthorizationTiming($order)
    {
        $rikiType = $this->paygentHelper->getRikiType($order);
        $isPreOrder = $availableDate = $reAuthorizationStatus = null;

        //spot order
        if ($rikiType == 'SPOT') {
            //check pre-order
            $preOrder = $this->checkIsPreOrder($order);
            if ($preOrder) {
                $availableDate = $preOrder['available_date_of_product'];
                $isPreOrder = $preOrder['is_preorder'];
            }
        }

        $data = [
            'order_id' => $order->getId(),
            'order_date' => $order->getCreatedAt(),
            'pre_order' => $isPreOrder,
            'available_date_of_product' => $availableDate,
            're_authorization_status' => $reAuthorizationStatus
        ];

        try {
            $this->reauthorizeFactory->create()
                ->setData($data)
                ->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return true;
    }

    /**
     * Check Order is pre-order
     *
     * @param $order
     *
     * @return array|bool
     */
    public function checkIsPreOrder($order)
    {
        $result = [];
        $isPre = $this->orderPreorder->getCollection()
            ->addFieldToFilter('order_id', $order->getId())
            ->setPageSize(1);
        if (!$isPre->getSize()) {
            return false;
        }
        $result['is_preorder'] = $isPre->getFirstItem()->getIsPreorder();
        //RIKI pre-order only contain 1 item
        $item = current($order->getAllItems());
        $result['available_date_of_product'] = $item->getProduct()->getFulfilmentDate();

        return $result;
    }
}
