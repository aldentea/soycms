<?php

class ExpandSeatUtil {

	public static function get($app, $key){
		return self::_get($app, $key);
	}

	public static function set($app, $key, $values){
		$v = self::_get($app, $key);
		switch($key){
			case "representative":
				foreach($values as $k => $val){
					$v[$k] = $val;
				}
				break;
			case "companion":
				foreach($values as $i => $val){
					foreach($val as $k => $vv){
						$v[$i][$k] = $vv;
					}
				}
		}

		$app->setAttribute($key, soy2_serialize($v));
	}

	private static function _get($app, $key){
		$v = $app->getAttribute($key);
		return (isset($v) && strlen($v)) ? soy2_unserialize($v) : array("rain" => "", "shoes" => "");
	}

	public static function getScheduleIdByReserveId($reserveId){
		SOY2::import("module.plugins.reserve_calendar.domain.SOYShopReserveCalendar_ScheduleDAO");
		return (int)SOY2DAOFactory::create("SOYShopReserveCalendar_ScheduleDAO")->getScheduleByReserveId($reserveId)->getId();
	}

	public static function extractSeatCompositionByOrderId($orderId){
		try{
			$attrs = SOY2DAOFactory::create("order.SOYShop_OrderDAO")->getById($orderId)->getAttributeList();
		}catch(Exception $e){
			$attrs = array();
		}

		if(!count($attrs)) return array(0, 0);

		$v = "";
		foreach($attrs as $key => $attr){
			if(strpos($key, "reserve_manager_composition") === false)  continue;
			$v = $attr["value"];
		}
		if(!strlen($v)) return array(0, 0);

		preg_match_all('/([0-9]*?)人/', $v, $tmps);
		if(!isset($tmps[1]) || !isset($tmps[1][1])) return null;

		$adultSeat = (int)$tmps[1][1];
		$childSeat = (isset($tmps[1][2])) ? (int)$tmps[1][2] : 0;

		return array($adultSeat, $childSeat);
	}

	public static function buildBreakdown($scheduleId, $adultSeat, $childSeat){
		static $logic;
		if(is_null($logic)){
			$logic = SOY2Logic::createInstance("module.plugins.reserve_calendar.logic.Schedule.ScheduleLogic");
		}

		$schPrice = (int)$logic->getScheduleById($scheduleId)->getPrice();
		$str = "内訳<br>&nbsp;大人" . $adultSeat . "名 " . number_format($adultSeat * $schPrice) . "円";

		if($childSeat > 0){
			//子供料金
			$childPrice = self::_getChildPrice($scheduleId);
			$str .= "<br>&nbsp;子供" . $childSeat . "名 ";
			if(!is_null($childPrice) && is_numeric($childPrice) && $childPrice >= 0){
				$str .= number_format($childSeat * $childPrice) . "円";
			}else{
				$str .= number_format($childSeat * $schPrice) . "円";
			}
		}

		return $str;
	}

	private static function _getChildPrice($scheduleId){
		static $dao;
		if(is_null($dao)){
			SOY2::import("module.plugins.reserve_calendar.domain.SOYShopReserveCalendar_SchedulePriceDAO");
			$dao = SOY2DAOFactory::create("SOYShopReserveCalendar_SchedulePriceDAO");
		}
		try{
			return $dao->get($scheduleId, "child_price")->getPrice();
		}catch(Exception $e){
			return null;
		}
	}
}
