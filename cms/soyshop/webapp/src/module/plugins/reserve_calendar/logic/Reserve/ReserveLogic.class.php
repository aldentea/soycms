<?php

class ReserveLogic extends SOY2LogicBase{

	function __construct(){
		SOY2::import("module.plugins.reserve_calendar.domain.SOYShopReserveCalendar_ReserveDAO");
	}

	function getReservedSchedules($time = null, $limit = 16){
		if(is_null($time)) $time = time();

		return self::dao()->getReservedSchedules($time, $limit);
	}

	function getReservedListByScheduleId($scheduleId, $isTmp = false){
		return self::dao()->getReservedListByScheduleId($scheduleId, $isTmp);
	}

	function getReservedCountByScheduleId($scheduleId, $isTmp = false){
		return self::dao()->getReservedCountByScheduleId($scheduleId, $isTmp);
	}

	function getReservedSchedulesByPeriod($year = null, $month = null, $isTmp = false){
		//どちらかが指定されていない時は動きません
		if(is_null($year) || is_null($month)) return array();

		//schedule_idと予約数を返す
		return self::dao()->getReservedSchedulesByPeriod($year, $month, $isTmp);
	}

	//予約済みのスケジュールオブジェクトを取得する
	function getReservedScheduleListByUserIdAndPeriod($userId, $year = null, $month = null, $isTmp = false){
		//どちらかが指定されていない時は動きません
		if(is_null($year) || is_null($month)) return array();

		//schedule_idと予約数を返す
		return self::dao()->getReservedScheduleListByUserIdAndPeriod($userId, $year, $month, $isTmp);
	}

	function checkIsUnsoldSeatByScheduleId($scheduleId){
		//boolean
		return self::dao()->checkIsUnsoldSeatByScheduleId($scheduleId);
	}

	//管理画面で本登録
	function registration($reserveId){
		$resDao = self::dao();
		try{
			$reserve = $resDao->getById($reserveId);
		}catch(Exception $e){
			var_dump($e);
		}

		$resDao->begin();
		$reserve->setToken(null);
		$reserve->setTemp(SOYShopReserveCalendar_Reserve::NO_TEMP);
		$reserve->setTempDate(null);
		$reserve->setReserveDate(time());

		try{
			$resDao->update($reserve);
		}catch(Exception $e){
			var_dump($e);
		}

		//注文状態を受付中にする
		$orderDao = SOY2DAOFactory::create("order.SOYShop_OrderDAO");
		try{
			$order = $orderDao->getById($reserve->getorderId());
		}catch(Exception $e){
			var_dump($e);
		}
		$order->setStatus(SOYShop_Order::ORDER_STATUS_RECEIVED);
		try{
			$orderDao->update($order);
		}catch(Exception $e){
			var_dump($e);
		}

		$resDao->commit();
	}

	function getTokensByOrderId($orderId){
		return self::dao()->getTokensByOrderId($orderId);
	}

	/** マイページ **/

	//ページを開いているユーザの予約であるか調べる
	function checkReserveByUserId($reserveId, $userId){
		return self::dao()->checkReserveByUserId($reserveId, $userId);
	}

	private function dao(){
		static $dao;
		if(is_null($dao)) $dao = SOY2DAOFactory::create("SOYShopReserveCalendar_ReserveDAO");
		return $dao;
	}
}
