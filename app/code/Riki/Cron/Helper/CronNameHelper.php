<?php

namespace Riki\Cron\Helper;


class CronNameHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

	/**
	 * Add key and value of cron name on magento
	 * ex : 'n98-magerun2/sys:cron:run/sales_clean_orders'=>'n98-run-sales_clean_orders'
	 */
	const LIST_CRON_NAME = [

	] ;

	/**
	 * Get cron name
	 *
	 * @param $keyCronName
	 * @return mixed
	 */
	public static function changeCronName($keyCronName) {
		//change cronName of magneto
		$cronNameMagento = self::LIST_CRON_NAME;
		if (isset($cronNameMagento[$keyCronName])) {
			return  $cronNameMagento[$keyCronName];
		}

		return $keyCronName;
	}




}