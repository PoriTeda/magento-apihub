<?php
namespace Riki\MasterDataHistory\Model;
class Wrapping extends \Magento\GiftWrapping\Model\Wrapping
{
    protected $_eventPrefix = 'gift_wrapping';
    protected $_eventObject = 'giftwrapping';
}