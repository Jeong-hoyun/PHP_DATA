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
$item = mysql_fetch_assoc($item_list);

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
	echo $i." : ";

	if($maker_key[$item['maker_code']]){
		$retArr = Array(Array());
		$url1 = $base_url.$maker_key[$item['maker_code']]."-".str_replace("-","",$item['item_code']);
		//echo "url1 : ".$url1."<BR>";
		$html = file_get_html($url1);
		if(!$html->find('.orderJancd')){
			$html->clear();
			unset($html);
			$url2 = $base_url.$maker_key[$item['maker_code']]."-".$item['item_code'];
			//echo "url2 : ".$url2."<BR>";
			$html = file_get_html($url2);
		}
		//echo $html -> plaintext;
	
		// 全てのクラスを取得
		if($html){
			foreach($html->find('.orderJancd') as $element){
				$jan_p_tag = $element->plaintext;
				#echo $jan_p_tag;
				preg_match_all ("/(\d+)/is",$jan_p_tag,$retArr);
				#print_r($retArr);
				echo $item['item_code']." : ".$retArr[0][0]." : <b>".strlen($retArr[0][0])."</b><BR>";
				if(strlen($retArr[0][0])!=13){
					echo "<div style='font-size:26px; color:red;'>".$item['item_id']."</div>";
				}
				$db -> set_jan_code($item['item_id'], $retArr[0][0]);
			}
		}
	
		$html->clear();
		unset($html);
	}else{
		//echo "no maker :".$maker_key[$item['maker_code']]."<BR>";
	}
	$i++;
	if($i>10){
		#break;	
	}
}while($item = mysql_fetch_assoc($item_list));

echo "end!";


?>