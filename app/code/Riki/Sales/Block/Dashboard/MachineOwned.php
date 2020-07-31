<?php
namespace Riki\Sales\Block\Dashboard;
use \Magento\Catalog\Block\Product\Context;


class MachineOwned extends \Magento\Catalog\Block\Product\AbstractProduct
{

    /**
     * @var \Riki\Sales\Model\ProductMachineOwner
     */
    protected $_productMachineOwner;

    public function __construct(
        Context $context,
        \Riki\Sales\Model\ProductMachineOwner $productMachineOwner,
        array $data = []
    )
    {
        $this->_productMachineOwner = $productMachineOwner;
        parent::__construct($context, $data);
    }


    public function getListProductItem(){
        $page =1;
        $data = $this->_productMachineOwner->getListProductMachineOwner($page);
        return $data;
    }



}