<?php

define('ROOT_DIR',     '../');
define('LIB_DIR',      '../../data/app/lib');
define('TEMPLATE_DIR', '../../data/app/template');
define('UPIMG_SHOHIN', ROOT_DIR . '/up_img/shohin/image0/');

include(LIB_DIR . '/common/simple_html_dom.php');

require_once(LIB_DIR . '/require.inc.php');

$db = new DB;
$results = array();

// ページ固有の情報 ----------------

require_once(LIB_DIR . '/' . SITE_TAG . '/metainfo.inc.php');

ini_set("max_execution_time",0);

$item_list = $db -> get_item_list();
$item = mysqli_fetch_assoc($item_list);

$maker_key= Array(
		1 => "EYD",
		2 => "KING",
		3 => "SGW",
		4 => "NKK",
		5 => "SNT",
		6 => "",
		7 => "",
		8 => "",
		9 => "",
		10 => "OPN",
		11 => "",
		12 => "OKM",
		13 => "",
		14 => "",
		15 => "",
		16 => "KMR",
	);

$site_name = "kinkoya";
$base_url = "http://www.kinkoya.jp/asp/item_scr_order.asp?ItemCD=KIN-";

$i=0;

$y = date("Y");
$m = date("m");
$d = date("d");

do{	
	echo $i.":".$item['maker_code']."<BR>";

	if($maker_key[$item['maker_code']]){
		$retArr = Array(Array());
		$url1 = $base_url.$maker_key[$item['maker_code']]."-".str_replace("-","",$item['item_code']);
		//echo "url1 : ".$url1."<BR>";
		$html = file_get_html($url1);
		if(!$html->find('.sPrice')){
			$html->clear();
			unset($html);
			$url2 = $base_url.$maker_key[$item['maker_code']]."-".$item['item_code'];
			//echo "url2 : ".$url2."<BR>";
			$html = file_get_html($url2);
		}
		//echo $html -> plaintext;
	
		// 全てのクラスを取得
		if($html){
			foreach($html->find('.sPrice') as $element){
				$price_span = $element->plaintext;
				preg_match_all ("/(\d+)/is",$price_span,$retArr);
				//print_r($retArr);
			}
		}
		$c_price = $retArr[0][0].$retArr[0][1];
		if($retArr[0][2]){
			$c_price .= $retArr[0][2];
		}
		
		if($item['price3']){
			$m_price = $item['price3'];
		}else{
			$m_price = $item['price2'];
		}
		$difference = $m_price - $c_price;
		
		if($c_price){
			//echo $item['item_code']." -> ".$c_price."<BR>";
			$set_item = array('site_name'=>$site_name, 'item_code'=> $item['item_code'], 'price'=>$c_price,'difference'=>$difference,'date'=>$y."-".$m."-".$d);
			$db -> set_price($set_item);
			$set_item = array('site_name'=>'ekinko', 'item_code'=> $item['item_code'], 'price'=>$m_price,'difference'=>"0",'date'=>$y."-".$m."-".$d);
			$db -> set_price($set_item);
		}		
		$html->clear();
		unset($html);
	}else{
		//echo "no maker :".$maker_key[$item['maker_code']]."<BR>";
	}
	$i++;
}while($item = mysqli_fetch_assoc($item_list));

echo "end!";


?>