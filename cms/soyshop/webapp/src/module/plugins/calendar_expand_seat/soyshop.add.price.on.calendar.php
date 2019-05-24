<?php
class CalendarExpandSeatAddPriceOnCalendar extends SOYShopAddPriceOnCalendarBase{

	/**
	 * @return string
	 */
	function getForm(){
		$form = "子供料金：";
		$form .= "<input type=\"number\" name=\"ChildPrice\" value=\"\" style=\"width:90px;\"> 円";
		$form .= "<br>※無記入の場合は料金と同じになります";
		return $form;
	}

	function doPost($scheduleId){
		if(isset($_POST["ChildPrice"]) && is_numeric($_POST["ChildPrice"]) && (int)$_POST["ChildPrice"] >= 0){
			SOY2::import("module.plugins.reserve_calendar.domain.SOYShopReserveCalendar_SchedulePriceDAO");
			$dao = SOY2DAOFactory::create("SOYShopReserveCalendar_SchedulePriceDAO");

			$obj = new SOYShopReserveCalendar_SchedulePrice();
			$obj->setScheduleId($scheduleId);
			$obj->setLabel("子供料金");
			$obj->setFieldId("child_price");
			$obj->setPrice($_POST["ChildPrice"]);
			try{
				$dao->insert($obj);
			}catch(Exception $e){
				//
			}
		}
	}

	function list($scheduleId){
		SOY2::import("module.plugins.reserve_calendar.domain.SOYShopReserveCalendar_SchedulePriceDAO");
		try{
			$price = SOY2DAOFactory::create("SOYShopReserveCalendar_SchedulePriceDAO")->get($scheduleId, "child_price")->getPrice();
		}catch(Exception $e){
			return array();
		}

		return array("label" => "子供料金", "price" => $price);
	}

}
SOYShopPlugin::extension("soyshop.add.price.on.calendar", "calendar_expand_seat", "CalendarExpandSeatAddPriceOnCalendar");
