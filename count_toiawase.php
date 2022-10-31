<?
define('ROOT_DIR',     '../');

define('LIB_DIR',      '../../data/app/lib');
define('TEMPLATE_DIR', '../../data/app/template');

require_once(LIB_DIR . '/require.inc.php');
$month = date("Y-m");
if($_GET["month"]){
	$month = $_GET["month"];
}

$db = new DB;

$query = '
SELECT
  LEFT(`cv_date`,10) AS day,
  sum(`cv_type`="1") AS "お問い合わせ",
  sum(`cv_type`="2") AS "お見積り",
  sum(`cv_type`="3") AS "簡単お問い合わせ"
FROM(
	SELECT
		`cv_date`,
		`cv_type`,
	 	CONCAT(IFNULL(name, ""), IFNULL(companyname, "")) AS name,
		`mail`,
		(LAG(`mail`, 1) OVER (ORDER BY `id`))AS prev_mail
	FROM
		`cv_data`
	WHERE
		(
		addr is NULL OR (
		addr not like "%test%" and
		addr not like "%てすと%" and
		addr not like "%テスト%" and
		addr not like "%【WEBSTEST】%" and
		addr not like "%【WEBS-TEST】%")
		) and
		(name IS NULL OR (
		 name not like "%test%" and
		 name not like "%てすと%" and
		 name not like "%【WEBSTEST】%" and
		 name not like "%【WEBS-TEST】%" and
		 name not like "%【WEB戦略室】%" and
		 name not like "%テスト%" and
		 mail not like "%officebusters%")
		 )  and	
		 (companyname IS NULL OR (
		 companyname not like "%test%" and
		 companyname not like "%てすと%" and
		 companyname not like "%【WEBSTEST】%" and
		 companyname not like "%【WEBS-TEST】%" and
		 companyname not like "%【WEB戦略室】%" and
		 companyname not like "%テスト%")
		 )  and
		cv_date like "%'.$month.'%"

) AS t
WHERE `mail` <> prev_mail OR prev_mail is NULL 
and	`mail` is not null
GROUP BY day
ORDER BY day ASC
';

$res = $db -> execute2($query);
$cv_count = array();
while ($row = mysqli_fetch_assoc($res)) {
  array_push($cv_count, $row);
}



?>
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<title>お問合せカウンター</title>
<meta name="robots" content="noindex,nofollow" />
<style>
.cv_count_table{border-collapse: collapse; width: 100%; min-width: 1000px; table-layout: fixed; margin: 20px 0 50px 0;}
.cv_count_table tr th,.cv_count_table tr td{border: 1px solid #aaaaaa; padding: 5px 0; text-align: center; }
/*販売合計と買取合計の列をピンク色の背景にする*/
.cv_count_table tr td:nth-child(12),.cv_count_table tr td:nth-child(15),.cv_count_table tr td:nth-child(17){background-color:#FCC; font-weight: bold;}
/*モバイル販売とログイン販売の列を黄色の背景にする*/
.cv_count_table tr td:nth-child(13),
.cv_count_table tr td:nth-child(14),
.cv_count_table tr td:nth-child(18),
.cv_count_table tr td:nth-child(19),
.cv_count_table tr td:nth-child(20),
.cv_count_table tr td:nth-child(21){background-color:#FFC;}
/*曜日のフォーマットを使って土日だけ文字を青くする*/
.cv_count_table tr:nth-child(7n+<?php echo $saturday; ?>) td,
.cv_count_table tr:nth-child(7n+<?php echo $sunday; ?>) td{color:#002fff;}
.cv_count_table tr th,.cv_count_table tr.total td{ background-color: #CCCCFF; color:#000;}
.month_pick{width: 900px;}
.month_pick hr{margin:2px 0; background-color: #eee;}
.month_pick dt,.month_pick dd{display: inline-block;}
</style>
</head>
<body>
<? require_once('menu.php'); ?>
<dl class="month_pick">
<?php
$start    = new DateTime('2012-01-01');
$end      = new DateTime(); // 今日まで
$interval = new DateInterval('P1M'); // 1か月間隔

$period = new DatePeriod($start, $interval, $end);

foreach ($period as $date) {
	if($date->format('m')=='01'){
		echo '<hr><dt>'.$date->format('Y').':</dt>';
	}
	echo '<dd><a href="?month='.$date->format('Y-m').'">'.$date->format('n').'月</a></dd>';
}
?>
<hr>
</dl>
<?php if($cv_count){ ?>
  <div>
    <h1><?php echo $month; ?>のお問合せ数推移</h1>
    <table class="cv_count_table">
    	<tr>
    		<th>day</th>
    		<th>お問い合わせ</th>
    		<th>お見積り</th>
    		<th>簡単お問い合わせ</th>
    	</tr>
	<?php 
	//日付順にソートする
	asort($cv_count);
    foreach($cv_count as $key => $val){ 
    ?>
    	<tr>
	    	<td><?php echo $val['day']; ?></td>
	    	<td><?php echo $val['お問い合わせ']; ?></td>
	    	<td><?php echo $val['お見積り']; ?></td>
	    	<td><?php echo $val['簡単お問い合わせ']; ?></td>	    	
    	</tr>
	<?php } ?>
    	<tr class="total">
	    	<td>合計</td>
	    	<td><?php echo array_sum(array_column($cv_count, 'お問い合わせ')); ?></td>
	    	<td><?php echo array_sum(array_column($cv_count, 'お見積り')); ?></td>
	    	<td><?php echo array_sum(array_column($cv_count, '簡単お問い合わせ')); ?></td>    	

    	</tr>
    </table>
  </div>
<?php } ?>
	
</body>
</html>

