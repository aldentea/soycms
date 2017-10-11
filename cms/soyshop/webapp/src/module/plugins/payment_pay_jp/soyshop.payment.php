<?php

SOY2::import("module.plugins.payment_pay_jp.util.PayJpUtil");
class PayJpPayment extends SOYShopPayment{

	function onSelect(CartLogic $cart){
		$module = new SOYShop_ItemModule();
		$module->setId("payment_pay_jp");
		$module->setType("payment_module");//typeを指定しておくといいことがある
		$module->setName("クレジット支払");
		$module->setIsVisible(false);
		$module->setPrice(0);

		$cart->addModule($module);

		//属性の登録
		if(PayJpUtil::isTestMode()){
			$cart->setOrderAttribute("payment_pay_jp", "支払方法", "クレジットカード支払い(PAY.JP※テストモード)");
		}else{
			$cart->setOrderAttribute("payment_pay_jp", "支払方法", "クレジットカード支払い");
		}

		//登録されているカード情報で支払(標準で1)
		$isRepeatCharge = (isset($_POST["payment_pay_jp_repeat_charge"])) ? (int)$_POST["payment_pay_jp_repeat_charge"] : 1;
		$cart->setAttribute("payment_pay_jp_repeat_charge", $isRepeatCharge);
	}

	function getName(){
		if(PayJpUtil::isTestMode()){
			return "クレジットカード支払い(PAY.JP※テストモード)";
		}else{
			return "クレジットカード支払い";
		}

	}

	function getDescription(){
		$html = array();
		$html[] = SOYShop_DataSets::get("payment_pay_jp.description", "クレジットカードで支払います。");

		//カートを表示している顧客の情報で、カードのトークンが登録されているか？調べる(メールアドレスから顧客情報をたどる)
		$mailAddress = $this->getCart()->getCustomerInformation()->getMailAddress();
		try{
			$userId = SOY2DAOFactory::create("user.SOYShop_UserDAO")->getByMailAddress($mailAddress)->getId();
		}catch(Exception $e){
			$userId = null;
		}

		if(isset($userId)){
			$token = self::getTokenAttributeByUserId($userId)->getValue();
			if(isset($token) && strlen($token)){
				$config = PayJpUtil::getConfig();
				if(isset($config["select"]) && $config["select"] == 1){
					$isRepeatCharge = $this->getCart()->getAttribute("payment_pay_jp_repeat_charge");
					if(is_null($isRepeatCharge)) $isRepeatCharge = 1;

					$html[] = "<input type=\"hidden\" name=\"payment_pay_jp_repeat_charge\" value=\"0\">";

					if($isRepeatCharge){
						$html[] = "<br><label><input type=\"checkbox\" name=\"payment_pay_jp_repeat_charge\" value=\"1\" checked=\"checked\">登録されているカード情報で支払する</label>";
					}else{
						$html[] = "<br><label><input type=\"checkbox\" name=\"payment_pay_jp_repeat_charge\" value=\"1\">登録されているカード情報で支払する</label>";
					}

				}
			}
		}

		return implode("\n", $html);
	}

	function hasOptionPage(){
		return true;
	}

	function getOptionPage(){
		$cart = $this->getCart();

		//戻る
		if(isset($_GET["back"])){
			$cart->setAttribute("page", "Cart04");
			soyshop_redirect_cart();
			exit;
		}

		//秘密鍵の登録がなければエラー
		$config = self::getConfig();
		if(!strlen($config["key"])){
			throw new Exception("秘密鍵が設定されていません。");
		}

		// トークンを保持していれば、ここで注文を終わらせてしまう
		$userId = $cart->getCustomerInformation()->getId();
		$token = self::getTokenAttributeByUserId($userId)->getValue();

		if(isset($token)){
			//二回目の購入のチェックがあるか？
			$isRepeatCharge = $cart->getAttribute("payment_pay_jp_repeat_charge");
			if($isRepeatCharge){
				self::prepare();

				$myCard = array(
					'customer' => $token,
					'amount' => $cart->getTotalPrice(),
					'currency' => 'jpy',
					"capture" => PayJpUtil::isCapture()
				);

				try{
					$charge = \Payjp\Charge::create($myCard);
					//エラーがなければ注文確定
					self::orderComplete($charge->id);
					soyshop_redirect_cart();
					exit;
				} catch (Exception $e) {
					//何もしない
				} finally {
					//何もしない
				}

				//使用できなかったから、tokenを削除する
				try{
					self::userAttrDao()->delete($userId, "payment_pay_jp_token");
				}catch(Exception $e){
					//
				}
			}
		}

		//出力
		SOY2::import("module.plugins.payment_pay_jp.option.PayJpOptionPage");
		$form = SOY2HTMLFactory::createInstance("PayJpOptionPage");
		$form->setCart($cart);
		$form->execute();
		echo $form->getObject();
	}

	function onPostOptionPage(){

		if(soy2_check_token()){
			self::prepare();

			$card = "";
			foreach($_POST["card"] as $c){
				$card .= $c;
			}

			$myCard = array(
				"number" => $card,
				"cvc" => $_POST["cvc"],
				"exp_month" => $_POST["month"],
				"exp_year" => (20 . $_POST["year"])
			);

			//セッションに入れる
			PayJpUtil::save("myCard", $myCard);
			PayJpUtil::save("name", $_POST["name"]);
			$isMember = (isset($_POST["member"]) && $_POST["member"] == 1) ? 1 : 0;
			PayJpUtil::save("member", $isMember);

			//期限切れの場合 APIへの接続回数を減らすために事前にチェック
			$expYear = $myCard["exp_year"];
			$expMonth = $myCard["exp_month"] + 1;
			if($expMonth > 12){
				$expYear += 1;
				$expMonth = 1;
			}
			$expire = mktime(0, 0, 0, $expMonth, 1, $expYear);
			if($expire < time()){
				PayJpUtil::save("errorCode", "expired_card");
				soyshop_redirect_cart("error");
				exit;
			}

			//カード番号のトークンを作成
			try{
				$res = \Payjp\Token::create(array("card" => $myCard));
			} catch (Error\Card $e) {
				self::redirectCartOnError($e->getJsonBody());
			} catch (Error\InvalidRequest $e) {
				self::redirectCartOnError($e->getJsonBody());
			} catch (Error\Authentication $e) {
				self::redirectCartOnError($e->getJsonBody());
			} catch (Error\Api $e) {
				self::redirectCartOnError($e->getJsonBody());
			} catch (\Payjp\Error\Base $e) {
				self::redirectCartOnError($e->getJsonBody());
			} catch (Exception $e) {
				self::redirectCartOnError($e->getJsonBody());
			} finally {
				//何もしない
			}

			$token = $res->id;
			$cart = $this->getCart();

	 		//仮入金モード(captureで指定)
		 	$chargeCard = array(
				'card' => $token,
				'amount' => $cart->getTotalPrice(),
				'currency' => 'jpy',
				"capture" => PayJpUtil::isCapture()
			);

			//作成したカード番号のトークンで購入
			try{
				$charge = \Payjp\Charge::create($chargeCard);
			} catch (Error\Card $e) {
				self::redirectCartOnError($e->getJsonBody());
			} catch (Error\InvalidRequest $e) {
				self::redirectCartOnError($e->getJsonBody());
			} catch (Error\Authentication $e) {
				self::redirectCartOnError($e->getJsonBody());
			} catch (Error\Api $e) {
				self::redirectCartOnError($e->getJsonBody());
			} catch (\Payjp\Error\Base $e) {
				self::redirectCartOnError($e->getJsonBody());
			} catch (Exception $e) {
				self::redirectCartOnError($e->getJsonBody());
			} finally {
				//何もしない
			}

			//tokenを更新して注文完了
			self::orderComplete($charge->id);
		}
	}

	private function orderComplete($payjsId){
		$cart = $this->getCart();

		$orderId = $cart->getAttribute("order_id");
		$order = self::getOrderById($orderId);

		//支払を完了する
		$order->setAttribute("payment_pay_jp.id", array(
			"name" => "PAY.JP決済: ID",
			"value" => $payjsId,
			"readonly" => true,
			"hidden" => true,
		));

		//会員情報を登録
		$isMember = PayJpUtil::get("member");
		if($isMember){
			self::saveTokenByUserId($cart->getCustomerInformation());
		}

		//仮入金の場合は支払待ちにする(本売上のみステータスを変更)
		if(PayJpUtil::isCapture()){
			$order->setPaymentStatus(SOYShop_Order::PAYMENT_STATUS_CONFIRMED);
		}
		self::orderDao()->updateStatus($order);
		$cart->setAttribute("page", "Complete");

		//セッションのクリア
		PayJpUtil::clear("myCard");
		PayJpUtil::clear("name");
		PayJpUtil::clear("member");
		PayJpUtil::clear("errorCode");
	}

	private function prepare(){
		$config = self::getConfig();

		//PAY.JPのlibを読み込む
		require_once(dirname(__FILE__) . "/payjp/init.php");
		\Payjp\Payjp::setApiKey($config["key"]);
	}

	private function getConfig(){
		static $config;
		if(is_null($config)){
			$conf = PayJpUtil::getConfig();
			if(isset($conf["sandbox"]) && $conf["sandbox"] == 1){
				$config = $conf["test"];
			}else{
				$config = $conf["public"];
			}
		}
		return $config;
	}

	private function redirectCartOnError($body=null){
		//原因不明のエラー
		if(is_null($body) || !isset($body["error"])){
			PayJpUtil::save("errorCode", "other");
			soyshop_redirect_cart("error");
			exit;
		}

		$err = $body["error"];

		PayJpUtil::save("errorCode", $err["code"]);
		soyshop_redirect_cart("error");
		exit;
	}

	private function getOrderById($orderId){
		try{
			return self::orderDao()->getById($orderId);
		}catch(Exception $e){
			return new SOYShop_Order();
		}
	}

	private function saveTokenByUserId(SOYShop_User $user){
		$myCard = PayJpUtil::get("myCard");
		if(is_null($myCard)) return;

		$customer["card"] = $myCard;
		$customer["card"]["name"] = PayJpUtil::get("name");
		$customer["email"] = $user->getMailAddress();

		$token = null;
		try{
			$res = \Payjp\Customer::create($customer);
			$token = $res->id;
			//エラーがなければ注文確定
		} catch (Exception $e) {
			//何もしない
		} finally {
			//何もしない
		}

		if(isset($token)){
			$attr = self::getTokenAttributeByUserId($user->getId());
			$attr->setValue($token);

			try{
				self::userAttrDao()->insert($attr);
			}catch(Exception $e){
				try{
					self::userAttrDao()->update($attr);
				}catch(Exception $e){
					var_dump($e);
				}
			}
		}else{
			try{
				self::userAttrDao()->delete($user->getId(), "payment_pay_jp_token");
			}catch(Exception $e){
				//
			}
		}
	}

	private function getTokenAttributeByUserId($userId){
		try{
			return self::userAttrDao()->get($userId, "payment_pay_jp_token");
		}catch(Exception $e){
			$attr = new SOYShop_UserAttribute();
			$attr->setUserId($userId);
			$attr->setFieldId("payment_pay_jp_token");
			return $attr;
		}
	}

	private function orderDao(){
		static $dao;
		if(is_null($dao)) $dao = SOY2DAOFactory::create("order.SOYShop_OrderDAO");
		return $dao;
	}

	private function userAttrDao(){
		static $dao;
		if(is_null($dao)) $dao = SOY2DAOFactory::create("user.SOYShop_UserAttributeDAO");
		return $dao;
	}
}

SOYShopPlugin::extension("soyshop.payment",			"payment_pay_jp", "PayJpPayment");
SOYShopPlugin::extension("soyshop.payment.option",	"payment_pay_jp", "PayJpPayment");
