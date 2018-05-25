<?php
/**
 * @entity shop.SOYShop_ItemAttribute
 */
abstract class SOYShop_ItemAttributeDAO extends SOY2DAO{

    abstract function insert(SOYShop_ItemAttribute $bean);

	/**
     * @query #itemId# = :itemId AND #fieldId# = :fieldId
     */
    abstract function update(SOYShop_ItemAttribute $bean);

    /**
     * @index fieldId
     */
    abstract function getByItemId($itemId);

	/**
	 * @return object
	 * @query #itemId# = :itemId AND #fieldId# = :fieldId
	 */
    abstract function get($itemId,$fieldId);

	/**
	 * @final
	 * isParentは親商品を調べるモードにするか？ isEmptyはitem_valueの値が空文字でも取得する
	 */
	function getOnLikeSearch($itemId, $like, $isParent = false, $isEmpty = true){
		//子商品の場合は親商品のものを調べる
		if($isParent){
			try{
				$results = $this->executeQuery("SELECT item_type FROM soyshop_item WHERE id = :itemId", array(":itemId" => $itemId));
			}catch(Exception $e){
				return array();
			}
			if(isset($results[0]["item_type"]) && is_numeric($results[0]["item_type"])) $itemId = $results[0]["item_type"];
		}

		//指定のキーワードで検索 SQLiteとMySQLで文字列検索の関数が異なるため、値の設定がない項目も一気に取得する
		try{
			$results = $this->executeQuery("SELECT * FROM soyshop_item_attribute WHERE item_id = :itemId AND item_field_id LIKE '" . $like . "'", array(":itemId" => $itemId));
		}catch(Exception $e){
			return array();
		}
		if(!count($results)) return array();

		$attrs = array();
		foreach($results as $res){
			if(!isset($res["item_field_id"]) || !strlen($res["item_field_id"])) continue;
			if(!$isEmpty && !strlen($res["item_value"])) continue;
			$attrs[$res["item_field_id"]] = $this->getObject($res);
		}

		return $attrs;
	}

    abstract function deleteByItemId($itemId);

    /**
     * @query #itemId# = :itemId AND #fieldId# = :fieldId
     */
    abstract function delete($itemId,$fieldId);

    abstract function deleteByFieldId($fieldId);
}
