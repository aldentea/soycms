<?php

class SearchLogic extends SOY2LogicBase{

    private $where = array();
    private $binds = array();
    private $entryDao;

    function __construct(){
        SOY2::import("site_include.plugin.CustomSearchField.util.CustomSearchFieldUtil");
        $this->entryDao = SOY2DAOFactory::create("cms.EntryDAO");

    }

    /**
     * @params int current:現在のページ, int limit:一ページで表示する商品
     * @return array<SOYShop_Item>
     */
    function search($labelId, $limit=null){
        self::setCondition();

        $sql = "SELECT DISTINCT s.entry_id, s.*, ent.* " .
                "FROM Entry ent ".
				"INNER JOIN EntryLabel lab ".
	            "ON ent.id = lab.entry_id ".
                "INNER JOIN EntryCustomSearch s ".
                "ON ent.id = s.entry_id ";
        $sql .= self::buildWhere();    //カウントの時と共通の処理は切り分ける
        //$sort = self::buildOrderBySQLOnSearchPage($obj->getPageObject());
        //if(isset($sort)) $sql .= $sort;
		$this->binds[":labelId"] = $labelId;

        //表示件数
		if(isset($limit) && is_numeric($limit) && $limit > 0){
			$sql .= " LIMIT " . (int)$limit;

			//OFFSET
	        $offset = $limit * ($current - 1);
	        if($offset > 0) $sql .= " OFFSET " . $offset;
		}

        try{
            $res = $this->entryDao->executeQuery($sql, $this->binds);
        }catch(Exception $e){
			return array();
        }

        if(!count($res)) return array();

        $entries = array();
        foreach($res as $v){
            $entries[] = $this->entryDao->getObject($v);
        }

        return $entries;
    }

    function getTotal(){
        self::setCondition();

        $sql = "SELECT COUNT(ent.id) AS total " .
                "FROM Entry ent ".
                "INNER JOIN EntryCustomSearch s ".
                "ON ent.id = s.ent_id ";
        $sql .= self::buildWhere();    //カウントの時と共通の処理は切り分ける

        try{
            $res = $this->itemDao->executeQuery($sql, $this->binds);
        }catch(Exception $e){
            return 0;
        }

        return (isset($res[0]["total"])) ? (int)$res[0]["total"] : 0;
    }

    private function buildWhere(){
        $where = "WHERE lab.label_id = :labelId ".
				"AND ent.openPeriodStart < :now ".
                "AND ent.openPeriodEnd > :now ".
                "AND ent.isPublished = 1 ";

        foreach($this->where as $key => $w){
            if(!strlen($w)) continue;
            $where .= "AND " . $w . " ";
        }
        return $where;
    }

    private function setCondition(){
        if(!count($this->where)){
            //記事検索
			if(isset($_GET["q"]) && strlen($_GET["q"])){
				$this->where["query"] = " (ent.title LIKE :query OR ent.content LIKE :query OR ent.more LIKE :query) ";
				$this->binds[":query"] = "%" . trim($_GET["q"]) . "%";
			}

            foreach(CustomSearchFieldUtil::getConfig() as $key => $field){

                //まずは各タイプのfield SQLでkeyを指定する場合、s.を付けること。soyshop_custom_searchのaliasがs
                switch($field["type"]){
                    //文字列の場合
                    case CustomSearchFieldUtil::TYPE_STRING:
                    case CustomSearchFieldUtil::TYPE_TEXTAREA:
                    case CustomSearchFieldUtil::TYPE_RICHTEXT:
						if(isset($_GET["c_search"][$key])){
							//文字列として検索
							if(is_string($_GET["c_search"][$key]) && strlen($_GET["c_search"][$key])){
								//否定として検索
								if(isset($field["denial"]) && $field["denial"] == 1){
									$this->where[$key] = "s." . $key . " != :" . $key;
									$this->binds[":" . $key] = trim($_GET["c_search"][$key]);
								}else{
									$this->where[$key] = "s." . $key . " LIKE :" . $key;
									$this->binds[":" . $key] = "%" . trim($_GET["c_search"][$key]) . "%";
								}
							//配列として検索
							}else if(is_array($_GET["c_search"][$key]) && count($_GET["c_search"][$key])){
								$w = array();
	                            foreach($_GET["c_search"][$key] as $i => $v){
	                                if(!strlen($v)) continue;
	                                $w[] = "s." . $key . " LIKE :" . $key . $i;
	                                $this->binds[":" . $key . $i] = "%" . trim($v) . "%";
	                            }
	                            if(count($w)) $this->where[$key] = "(" . implode(" OR ", $w) . ")";
							}
						}
                        break;

                    //範囲の場合
                    case CustomSearchFieldUtil::TYPE_RANGE:
						//配列で渡す
						if(isset($_GET["c_search"][$key]) && is_array($_GET["c_search"][$key])){
							$cnt = 0;
							$rWhere = array();
							$cnds = $_GET["c_search"][$key];
							for($i = 0; $i < count($cnds); $i++){
								$ws = "";
								$we = "";
								if(isset($cnds[$i]["start"][0]) && is_numeric($cnds[$i]["start"][0])){
									$symbol = (isset($cnds[$i]["start"][1]) && $cnds[$i]["start"][1]) ? ">=" : ">";
									$ws = "s." . $key . " " . $symbol . " :" . $key . "_start_" . $i;
		                            $this->binds[":" . $key . "_start_" . $i] = (int)$cnds[$i]["start"][0];
								}

								if(isset($cnds[$i]["end"][0]) && is_numeric($cnds[$i]["end"][0])){
									$symbol = (isset($cnds[$i]["end"][1]) && $cnds[$i]["end"][1]) ? "<=" : "<";
									$we = "s." . $key .  " " . $symbol . " :" . $key . "_end_" . $i;
									$this->binds[":" . $key . "_end_" . $i] = (int)$cnds[$i]["end"][0];
								}

								if(strlen($ws) && strlen($we)){
		                            $rWhere[] = "(" . $ws . " AND " . $we . ")";
		                        }else if(strlen($ws) || strlen($we)){
		                            $rWhere[] = $ws . $we;
		                        }
							}
							if(count($rWhere)) $this->where[$key] = "(" . implode(" OR ", $rWhere) . ")";

						//通常の検索 @ToDo >= or <=の対応を考える
						}else{
							$ws = "";$we = "";    //whereのスタートとエンド
	                        if(isset($_GET["c_search"][$key . "_start"]) && strlen($_GET["c_search"][$key . "_start"]) && is_numeric($_GET["c_search"][$key . "_start"])){
	                            $ws = "s." . $key . " >= :" . $key . "_start";
	                            $this->binds[":" . $key . "_start"] = (int)$_GET["c_search"][$key . "_start"];
	                        }
	                        if(isset($_GET["c_search"][$key . "_end"]) && strlen($_GET["c_search"][$key . "_end"]) && is_numeric($_GET["c_search"][$key . "_end"])){
	                            $we = "s." . $key .  " <= :" . $key . "_end";
	                            $this->binds[":" . $key . "_end"] = (int)$_GET["c_search"][$key . "_end"];
	                        }
	                        if(strlen($ws) && strlen($we)){
	                            $this->where[$key] = "(" . $ws . " AND " . $we . ")";
	                        }else if(strlen($ws) || strlen($we)){
	                            $this->where[$key] = $ws . $we;
	                        }
						}

                        break;

                    //チェックボックスの場合
                    case CustomSearchFieldUtil::TYPE_CHECKBOX:
                        if(isset($_GET["c_search"][$key]) && count($_GET["c_search"][$key])){
                            $w = array();
                            foreach($_GET["c_search"][$key] as $i => $v){
                                if(!strlen($v)) continue;
                                $w[] = "s." . $key . " LIKE :" . $key . $i;
                                $this->binds[":" . $key . $i] = "%" . trim($v) . "%";
                            }
                            if(count($w)) $this->where[$key] = "(" . implode(" OR ", $w) . ")";
                        }
                        break;

                    //数字、ラジオボタン、セレクトボックス
                    default:
                        if(isset($_GET["c_search"][$key]) && strlen($_GET["c_search"][$key])){
                            $this->where[$key] = "s." . $key . " = :" . $key;
                            $this->binds[":" . $key] = $_GET["c_search"][$key];
                        }
                }
            }

            $this->binds[":now"] = time();
        }
    }

    // private function buildOrderBySQLOnSearchPage(SOYShop_SearchPage $obj){
    //     return self::buildOrderBySQLCommon($obj->getPage()->getId());
    // }
	//
    // private function buildOrderBySQLOnListPage(SOYShop_ListPage $obj){
    //     $orderSql = self::buildOrderBySQLCommon($obj->getPage()->getId());
    //     if(is_null($orderSql)){
    //         $sort = SOY2Logic::createInstance("logic.shop.item.SearchItemUtil", array("sort" => $obj))->getSortQuery();
    //         $orderSql = " ORDER BY i." . $sort . " ";
    //     }
    //     return $orderSql;
    // }
	//
    // private function buildOrderBySQLCommon($pageId){
    //     $session = SOY2ActionSession::getUserSession();
    //     if(isset($_GET["sort"]) || isset($_GET["csort"])){
    //         $custom_search_sort = null;
    //     }else{
    //         $custom_search_sort = $session->getAttribute("soyshop_" . SOYSHOP_ID . "_custom_search" . $pageId);
    //     }
	//
    //     //カスタムソート
    //     if(isset($_GET["custom_search_sort"])){
    //         $custom_search_sort = ($_GET["custom_search_sort"] != "reset") ? htmlspecialchars($_GET["custom_search_sort"], ENT_QUOTES, "UTF-8") : null;
    //         //存在するフィールドか調べる
    //         $dao = new SOY2DAO();
    //         try{
    //             $dao->executeQuery("SELECT item_id FROM soyshop_custom_search WHERE " . $custom_search_sort . "= '' LIMIT 1");
    //         }catch(Exception $e){
    //             $custom_search_sort = null;
    //         }
    //         $session->setAttribute("soyshop_" . SOYSHOP_ID . "_custom_search" . $pageId, $custom_search_sort);
    //     }
	//
    //     if(isset($custom_search_sort)){
    //         $suffix = $session->getAttribute("soyshop_" . SOYSHOP_ID . "_suffix" . $pageId);
    //         if(isset($_GET["r"])){
    //             $suffix = ($_GET["r"] == 1) ? " DESC" : " ASC";
    //             $session->setAttribute("soyshop_" . SOYSHOP_ID . "_suffix" . $pageId, $suffix);
    //         }
	//
    //         return " ORDER BY s." . $custom_search_sort . " IS NULL ASC, s." . $custom_search_sort . $suffix;
    //     }
	//
    //     return null;
    // }

	/** 商品一覧ページ用 **/
    function getEntryList($key, $value, $current, $offset, $limit){

        $confs = CustomSearchFieldUtil::getConfig();
        if(!isset($confs[$key])) return array();

        $binds = array(":now" => time());

        $sql = "SELECT ent.* " .
                "FROM Entry ent ".
                "INNER JOIN EntryCustomSearch s ".
                "ON ent.id = s.entry_id ";
        $sql .= self::buildListWhere();    //カウントの時と共通の処理は切り分ける
        switch($confs[$key]["type"]){
            case CustomSearchFieldUtil::TYPE_CHECKBOX:
                $sql .= "AND s." . $key . " LIKE :" . $key;
                $binds[":" . $key] = "%" . trim($value) . "%";
                break;
            default:
                $sql .= "AND s." . $key . " = :" . $key;
                $binds[":" . $key] = trim($value);
        }

        //$sql .= self::buildOrderBySQLOnListPage($obj);
		if(isset($limit) && is_numeric($limit) && $limit > 0){
			$sql .= " LIMIT " . $limit;

	        //OFFSET
	        $offset = $limit * ($current - 1);
	        if($offset > 0) $sql .= " OFFSET " . $offset;
		}

        try{
            $res = $this->entryDao->executeQuery($sql, $binds);
        }catch(Exception $e){
            return array();
        }

        if(count($res) === 0) return array();

        $entries = array();
        foreach($res as $obj){
            if(!isset($obj["id"])) continue;
            $entries[] = $this->entryDao->getObject($obj);
        }

        return $entries;
    }

	private function buildListWhere(){
		return "WHERE ent.openPeriodStart < :now ".
			"AND ent.openPeriodEnd > :now ".
			"AND ent.isPublished = 1 ";
    }
}
