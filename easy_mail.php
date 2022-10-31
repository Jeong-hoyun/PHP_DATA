<?
define('ROOT_DIR',     '../');

define('LIB_DIR',      '../../data/app/lib');
define('TEMPLATE_DIR', '../../data/app/template');

require_once(LIB_DIR . '/require.inc.php');
////////////////////////////////////////////////////////////////////////////////
// IP to ADDRESS ///////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

$ipa = $_GET['ipa'];
#$ipa = $_SERVER['REMOTE_ADDR'];
#echo $ipa."<BR>";

$easy_type = $_GET['easy_type'];
$item_id = $_GET['item_id'];

if(!$easy_type){
	$easy_type = 0;
}

require_once(LIB_DIR . '/kinko/geoplugin.class.php');



function GetSQLValueString ($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") {
  $theValue = (!get_magic_quotes_gpc()) ? addslashes($theValue) : $theValue;

  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}

// If we wanted to change the base currency, we would uncomment the following line
// $geoplugin->currency = 'EUR';

function get_add_by_tel($tel){
	$tel = str_replace("-", "", $tel);
	if(substr($tel, 0, 3) == "090" || substr($tel, 0, 3) == "080" || substr($tel, 0, 3) == "070" || substr($tel, 0, 3) == "050"){
		return false;
	}
	$db = new DB;
	for($i = 5; $i>1; $i--){
		$code = substr($tel, 0, $i);
		$query_txt = "
			SELECT
				area_code, area_name
			FROM
				tel_area_codes
			WHERE
				del_flag = 0
				and
				area_code like '".$code."%'
			ORDER BY id ASC";
		#echo $query_txt."<BR>";
		$res = $db->execute($query_txt);
		$row = mysqli_fetch_assoc($res);

		if($row['area_name']){
			$add = $row['area_name'];
			return "電話番号から住所逆引き：".$add;
		}
	}
	return false;
}

if($add_by_tel = get_add_by_tel($_POST['tel'])){
	$add =  $add_by_tel;
}else{
	$geoplugin = new geoPlugin();
	$geoplugin->locate($ipa);

	function add_slash($w){
		return $ret = $w." / ";
	}
	$geo_data = "IPアドレスからの推測住所 : ";
	$geo_data .= $geoplugin->countryName;
	if($geoplugin->city){
		$geo_data = add_slash($geo_data);
		$geo_data .= $geoplugin->city;
	}
	if($geoplugin->region){
		$geo_data = add_slash($geo_data);
		$geo_data .= $geoplugin->region;
	}

	/* find places nearby */
	$nearby = $geoplugin->nearby();
	if ( isset($nearby[1]['geoplugin_place']) ) {
		$geo_data = add_slash($geo_data);
		$geo_data .= $nearby[1]['geoplugin_place'];
	}else if( isset($nearby[0]['geoplugin_place']) ) {
		foreach ( $nearby as $key => $array ) {
			if($array['geoplugin_place']){
				$geo_data = add_slash($geo_data);
				$geo_data .= $array['geoplugin_place'];
				break;
			}
		}
	}
	#echo $geo_data."<BR>";
	$add = $geo_data."(".$ipa.")";
}

////////////////////////////////////////////////////////////////////////////////////////////


$db = new DB;


// 共通部分の情報 ----------------

$banners_b = $db->get_banners_b(15);
$banners_c = $db->get_banners_c(15);
$b_categories = $db->get_kinko_subnavi_b_categories();
$key_type = $db->get_kinko_subnavi_key_type();
$use_type = $db->get_kinko_subnavi_use_type();
$maker = $db->get_kinko_subnavi_maker();



// ページ固有の情報 ----------------

require_once(LIB_DIR . '/' . SITE_TAG . '/metainfo.inc.php');

$results = $_SESSION['info_form_contents'];

$css_add = array();
$js_add = array();

mb_language("japanese");
mb_internal_encoding("UTF-8");

$to = $_POST['mail'] . ', ' . MAIL_TO;
$subject = "【e金庫本舗】スピードお問い合わせありがとうございます";
$from = $_POST['mail'];

$body = <<< END
{$_POST['name']} 様

e金庫本舗でございます。
この度はお問合せを頂き、誠にありがとうございます。 

■ご返答までの流れ
1)　お問合せ内容の確認
　　当方にて、お問合せ内容を確認し、必要に応じてメーカーに在庫数、
　　納品日（または最短納品日）、配送費、設置費等を確認の上、
　　担当者より連絡させて頂きます。
　　土日祝日が入る場合、確認に3-4日程度かかる場合がございますので
　　ご了承下さい。
2)　ご返答
　　担当者より原則メールにて返答させて頂きます。ご不明な点がござい
　　ましたらメールまたはお電話でお尋ねください。 

頂いたお問合せの内容は次の通りです。 

▼お客様情報
--------------------------------------------------------
会社名/お名前    :  {$_POST['name']}
E-mail    :  {$_POST['mail']}
電話番号  :  {$_POST['tel']}

■お問合せ商品：{$item_id}
■商品ページ：https://www.ekinko.com/detail.php?item={$item_id}&rname=reply
■自由記入欄:  {$_POST['free_form']}



引き続き、e金庫本舗をよろしくお願いいたします。

END;

$cv_date = date('Y/m/d H:i:s'); 
$body = mb_convert_kana($body, "KV", mb_detect_encoding($body));

// カレントの言語を日本語に設定する
mb_language("uni");
// 内部文字エンコードを設定する
mb_internal_encoding("UTF-8");

//$ret = mb_send_mail($to, $subject, $body, "From:".$from);
// お客様へのメール
if($_POST['mail']){
	mb_send_mail($_POST['mail'] , $subject, $body, "From:".MAIL_FROM);
}

$date = date("Y-m-d");
$csv_data = $date.",,メール（問い合わせ）,,,".$_POST['name'].",".$add.",".$_POST['tel'].",,".$_POST['mail'].",".$item_id.",土田,,お客様から返答待ち,,,,,,,,,,,,,e金庫本舗,,,,,,,,,".$_COOKIE['s_date'].",".$_COOKIE['s_engine'].",".$_COOKIE['s_keyword']."\n\n";

$search_data = "検索エンジン/キーワード : ".$_COOKIE['s_engine']."/".$_COOKIE['s_keyword']."(".$_COOKIE['s_date'].")\n\n";
$body = $csv_data.$search_data.$body;

// 運営へのメール
if($_POST['mail']){
	mb_send_mail(MAIL_TO, $subject, $body, "From:".$_POST['mail']);

$insertSQL = sprintf("INSERT INTO cv_data(
cv_date, cv_type, name, companyname, addr,
addr2, item_id,free_form,kana,tel,
mail, s_keyword, s_engine, s_date) VALUES (
      %s, %s, %s, %s, %s,
      %s, %s, %s, %s, %s,
      %s, %s, %s, %s)",
      GetSQLValueString($cv_date,"date"),
      GetSQLValueString(3, "int"),
      GetSQLValueString($_POST['name'], "text"),
      GetSQLValueString($_POST['companyname'], "text"),
      GetSQLValueString($_POST['addr_todouhuken'].$results['addr'], "text"),
      GetSQLValueString($_POST['addr2'], "text"),

      GetSQLValueString($item_id, "text"),
      GetSQLValueString($_POST['free_form'], "text"),
      GetSQLValueString($_POST['kana'], "text"),
      GetSQLValueString($_POST['tel'], "text"),

      GetSQLValueString($_POST['mail'], "text"),
      GetSQLValueString($_COOKIE['s_keyword'], "text"),
      GetSQLValueString($_COOKIE['s_engine'], "int"),
      GetSQLValueString($_COOKIE['s_date'], "text"));

    $db->execute($insertSQL);


}

unset($_SESSION['info_form_contents']);
$db->close();

echo "問い合わせをお受け致しました";
?>