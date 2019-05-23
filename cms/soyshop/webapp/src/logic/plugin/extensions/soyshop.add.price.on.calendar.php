<?php
class SOYShopAddPriceOnCalendarBase implements SOY2PluginAction{

	/**
	 * @return string
	 */
	function getForm(){}

	function doPost($scheduleId){}

	/**
	 * @return array("label" => string, "price" => integer)
	 */
	function list($scheduleId){}
}

class SOYShopAddPriceOnCalendarDeletageAction implements SOY2PluginDelegateAction{

	private $mode;
	private $scheduleId;
	private $_list;

	function run($extetensionId,$moduleId,SOY2PluginAction $action){
		switch($this->mode){
			case "list":
				$list = $action->list($this->scheduleId);
				if(isset($list) && count($list)){
					$this->_list[$moduleId] = $list;
				}
				break;
			default:
				if(strtolower($_SERVER['REQUEST_METHOD']) == "post"){
					$action->doPost($this->scheduleId);
				}else{
					echo $action->getForm();
				}
		}
	}

	function setMode($mode){
		$this->mode = $mode;
	}
	function setScheduleId($scheduleId){
		$this->scheduleId = $scheduleId;
	}
	function getList(){
		return $this->_list;
	}
}
SOYShopPlugin::registerExtension("soyshop.add.price.on.calendar", "SOYShopAddPriceOnCalendarDeletageAction");
