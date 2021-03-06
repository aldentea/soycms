<?php

SOY2::import("util.SOYShopPluginUtil");
function soyshop_shipping_schedule_notice_raw($html, $page){
	$obj = $page->create("soyshop_shipping_schedule_notice_each_items_raw", "HTMLTemplatePage", array(
		"arguments" => array("soyshop_shipping_schedule_notice_each_items_raw", $html)
	));

	if(SOYShopPluginUtil::checkIsActive("parts_shipping_schedule_notice_each_items") && SOYShopPluginUtil::checkIsActive("parts_calendar")){
		//本日の文言を取得
		$bizLogic = SOY2Logic::createInstance("module.plugins.parts_calendar.logic.BusinessDateLogic");

		$now = time();
		$idx = "";

		//本日が定休日であるか？
		if(!$bizLogic->checkRegularHoliday($now)){	//営業日
			$idx = "biz";
		}else{	//定休日
			$idx = "hol";
		}

		//今が午前であるか？
		if(date("H", $now) < 12){	//午前
			$idx .= "_am";
		}else{	//午後
			$idx .= "_pm";
		}

		SOY2::import("module.plugins.parts_shipping_schedule_notice.util.ShippingScheduleUtil");
		$config = ShippingScheduleUtil::getConfig();
		if(isset($config["notice"][$idx])){
			$wording = $config["notice"][$idx];

			//○日後の出荷
			$after = (isset($config["schedule"][$idx])) ? (int)$config["schedule"][$idx] : 1;

			//指定の日が定休日であるか？定休日であればその次の日に発送
			for(;;){
				if(!$bizLogic->checkRegularHoliday(time() + $after * 24 * 60 * 60)) break;
				$after++;
			}

			//置換文字列後に出力
			echo ShippingScheduleUtil::replace($wording, $after);
		}
	}
}
