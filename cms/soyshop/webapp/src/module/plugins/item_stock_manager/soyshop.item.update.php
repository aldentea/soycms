<?php
SOY2::imports("module.plugins.item_stock_manager.domain.*");
class ItemStockManagerUpdate extends SOYShopItemUpdateBase{

	function addHistory(SOYShop_Item $item, $oldStock){

		$newStock = (isset($_POST["Item"]["stock"])) ? (int)$_POST["Item"]["stock"] : (int)$item->getStock();
		if($oldStock != $newStock){
			$logMessage = "在庫数を" . $oldStock."から" . $newStock . "に変更しました";

			$dao = SOY2DAOFactory::create("SOYShop_StockHistoryDAO");

			$obj = new SOYShop_StockHistory();
			$obj->setItemId($item->getId());
			$obj->setUpdateStock($newStock - $oldStock);
			$obj->setMemo($logMessage);

			try{
				$dao->insert($obj);
			}catch(Exception $e){
				//
			}
		}
	}

	function display(SOYShop_Item $item){
		$dao = SOY2DAOFactory::create("SOYShop_StockHistoryDAO");
		$dao->setLimit(5);

		try{
			return $dao->getByItemId($item->getId());
		}catch(Exception $e){
			return array();
		}
	}
}

SOYShopPlugin::extension("soyshop.item.update","item_stock_manager","ItemStockManagerUpdate");
