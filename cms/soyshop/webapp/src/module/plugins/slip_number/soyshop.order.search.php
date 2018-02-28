<?php

class SlipNumberSearch extends SOYShopOrderSearch{

	function setParameter($params){
		$params = self::getParameters($params);
		if(count($params)){
			$binds = array();
			$q = array();
			$i = 0;
			foreach($params as $param){
				$param = trim($param);
				if(strlen($param)){
					$q[] = "order_value1 LIKE :slipNumber" . $i;
					$binds[":slipNumber" . $i] = "%" . $param . "%";
					$i++;
				}
			}

			if(count($q)){
				$queries = array();
				$queries[] = "id IN (SELECT order_id FROM soyshop_order_attribute WHERE order_field_id = 'slip_number_plugin' AND (" . implode(" OR ", $q) . "))";
				return array("queries" => $queries, "binds" => $binds);
			}
		}
	}

	function searchItems($params){
		$html = "<input type=\"text\" name=\"search[customs][slip_number]\" value=\"" . self::getParameter($params) . "\" placeholder=\"スペース区切りで複数ワードで検索できます。\" style=\"width:95%;\">";
		return array("label" => "発送伝票番号", "form" => $html);
	}

	private function getParameter($param){
		return (!is_array($param) && is_string($param) && strlen($param)) ? $param : null;
	}

	private function getParameters($param){
		$str = str_replace("　", " ", self::getParameter($param));
		return (strlen($str)) ? explode(" ", $str) : array();
	}
}
SOYShopPlugin::extension("soyshop.order.search", "slip_number", "SlipNumberSearch");
