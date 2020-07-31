<?php
namespace Bluecom\Paygent\Model\ResourceModel\Error;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'error_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Bluecom\Paygent\Model\Error',
            'Bluecom\Paygent\Model\ResourceModel\Error'
        );
    }
    

}