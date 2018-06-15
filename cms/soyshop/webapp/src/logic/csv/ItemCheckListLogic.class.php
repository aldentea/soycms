<?php

class ItemCheckListLogic extends SOY2LogicBase {

	function __construct(){

	}

	function save($item, $type = "item"){
		SOYShop_DataSets::put($type . "_csv_item_check", $item);
	}

	function buildJSCode($type = "item"){
		$checks = SOYShop_DataSets::get($type . "_csv_item_check", null);
		if(is_null($checks)) return "";

		$scripts = array();
		$scripts[] = "<script>";

		//すべてのチェックを外す
		$scripts[] = "$('input[name^=\"item[\"]').prop('checked', false);";

		//ダミーにチェックを入れる
		$scripts[] = "$('input[name$=\"_dummy\"]').prop('checked', true);";

		//一つずつチェックを入れていく
		foreach($checks as $key => $val){
			if(is_string($val) && $val != 1) continue;

			//多言語プラグインの対応
			if(is_array($val) && count($val)){
				foreach($val as $k => $v){
					if(is_string($v) && $v != 1) continue;
					$scripts[] = "if($('input[name=\"item[" . $key . "][" . $k . "]\"]')) $('input[name=\"item[" . $key . "][" . $k . "]\"]').prop('checked', true);";
				}
			}else{
				$scripts[] = "if($('input[name=\"item[" . $key . "]\"]')) $('input[name=\"item[" . $key . "]\"]').prop('checked', true);";
			}
		}

		$scripts[] = "";
		$scripts[] = "function toggleItemCheck(ele){";
		$scripts[] = "	if($(ele).prop('checked')){";
		//すべてのチェックを付ける
		$scripts[] = "		$('input[name^=\"item[\"]').prop('checked', true);";
		$scripts[] = "	} else {";
		//すべてのチェックを外す
		$scripts[] = "		$('input[name^=\"item[\"]').prop('checked', false);";
		//ダミーにチェックを入れる
		$scripts[] = "		$('input[name$=\"_dummy\"]').prop('checked', true);";
		$scripts[] = "	}";
		$scripts[] = "}";

		$scripts[] = "</script>";

		return implode("\n", $scripts);
	}
}
