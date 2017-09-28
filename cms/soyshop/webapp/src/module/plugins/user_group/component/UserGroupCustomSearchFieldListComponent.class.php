<?php

class UserGroupCustomSearchFieldListComponent extends HTMLList{

	protected function populateItem($entity, $key){

		$this->addLabel("key", array(
			"text" => $key
		));

		$this->addLabel("label", array(
			"text" => (isset($entity["label"])) ? $entity["label"] : ""
		));

		$this->addLabel("type", array(
			"text" => (isset($entity["type"])) ? UserGroupCustomSearchFieldUtil::getTypeText($entity["type"]) : ""
		));

		$this->addLabel("display", array(
			"text" => "csf:id=\"" . $key . "\""
		));

		/* 高度な設定 */
		$this->addLink("toggle_config", array(
			"link" => "javascript:void(0)",
			"text" => "詳細設定",
			"onclick" => '$(\'#field_config_' . $key . '\').toggle();',
			"style" => (
				(isset($entity["option"]) && strlen($entity["option"])) ||
				(isset($entity["mapKey"]) && strlen($entity["mapKey"]))
			) ? "background-color:yellow;" : ""
		));

		/* 順番変更用 */
		$this->addInput("field_id", array(
			"name" => "field_id",
			"value" => $key,
		));

		$this->addModel("field_config", array(
			"attr:id" => "field_config_" . $key
		));

		/* 削除用 */
		$this->addInput("delete_submit", array(
			"name" => "delete_submit",
			"value" => $key,
			"attr:id" => "delete_submit_" . $key
		));

		$this->addLink("delete", array(
			"text"=>"削除",
			"link"=>"javascript:void(0);",
			"onclick"=>'if(confirm("delete \"' . $entity["label"] . '\"?")){$(\'#delete_submit_' . $key . '\').click();}return false;'
		));

		$this->addModel("with_options", array(
			"visible" => (isset($entity["type"])) ? self::checkDisplayOptionsForm($entity["type"]) : false
		));

		$this->addTextArea("option", array(
			"name" => "config[option]",
			"value" => (isset($entity["option"])) ? $entity["option"] : null
		));

		$this->addModel("with_map_key", array(
			"visible" => (isset($entity["type"]) && $entity["type"] == UserGroupCustomSearchFieldUtil::TYPE_MAP)
		));

		$this->addInput("map_key", array(
			"name" => "config[mapKey]",
			"value" => (isset($entity["mapKey"])) ? $entity["mapKey"] : null
		));

		$this->addInput("update_advance", array(
			"value"=>"設定保存",
			"onclick"=>'$(\'#update_advance_submit_' . $key . '\').click();return false;'
		));

		$this->addLink("setting_link", array(
			"link" => SOY2PageController::createLink("Config.Detail?plugin=user_group&collective&field_id=".$key)
		));

		$this->addInput("update_advance_submit", array(
			"name" => "update_advance",
			"value" => $key,
			"attr:id" => "update_advance_submit_" . $key
		));
	}

	private function checkDisplayOptionsForm($type){
		return ($type === UserGroupCustomSearchFieldUtil::TYPE_RADIO || $type === UserGroupCustomSearchFieldUtil::TYPE_CHECKBOX || $type === UserGroupCustomSearchFieldUtil::TYPE_SELECT);
	}
}
?>
