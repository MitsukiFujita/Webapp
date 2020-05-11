<?php
error_reporting(E_ALL & ~E_NOTICE);

#-----------------------------------------------------------
# 基本設定
#-----------------------------------------------------------

#データベース情報
$testuser ="testuser";
$testpass ="testpass";
$host ="localhost";
$datebase ="shop_item";

# テンプレートディレクトリ
$tmpl_dir = "./tmpl";

#-----------------------------------------------------------
# ページの表示
#-----------------------------------------------------------
parse_form();

try {
	$db = new PDO("mysql:host={$host}; dbname={$datebase}; charset=utf8", $testuser, $testpass);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	if($in["state"] == "insert") { item_insert(); }
	else if($in["state"] == "update") { item_update(); }
	else if($in["state"] == "delete") { item_delete(); }
	item_search();
}catch (PDOException $e) {
	die ("PDO Error:" . $e->getMessage());
}

#-----------------------------------------------------------
# フォーム受け取り
#-----------------------------------------------------------
function parse_form(){
	global $in;

	$param = array();
	if (isset($_GET) && is_array($_GET)) { $param += $_GET; }
	if (isset($_POST) && is_array($_POST)) { $param += $_POST; }
	
	foreach($param as $key => $val) {
		# 2次元配列から値を取り出す
		if(is_array($val)){
			$val = array_shift($val);
		}
		
		# 文字コードの処理
		$enc = mb_detect_encoding($val);
		$val = mb_convert_encoding($val,"UTF-8",$enc);
		
		# 特殊文字の処理
		$val = htmlentities($val,ENT_QUOTES, "UTF-8");

		$in[$key] = $val;
	}
	return $in;
}

#-----------------------------------------------------------
# 商品一覧
#-----------------------------------------------------------
function item_search(){
	global $in;
	global $db;
	global $tmpl_dir;

	if(isset($_GET["item_kind"])){$item_kind = $_GET["item_kind"];}
	if(isset($_in["item_kind"])){$item_kind = $_in["item_kind"];}
	if(isset($_POST["item_kind"])){$item_kind = $_POST["item_kind"];}


	# 自身のパス
	$script_name=$_SERVER['SCRIPT_NAME'];

	# SQLを作成
	if($item_kind=="トップス"){$query = "SELECT * FROM tops_data WHERE item_flag = 1";}
	if($item_kind=="ボトムス"){$query = "SELECT * FROM bottoms_data WHERE item_flag = 1";}
	if($item_kind=="アクセサリー"){$query = "SELECT * FROM accessory_data WHERE item_flag = 1";}
	
	# プリペアードステートメントを準備
	$stmt = $db->prepare($query);
	$stmt->execute();

	$item_data = "";	
	while($row = $stmt->fetch()){
		$item_id = $row['item_id'];

		# 色取得用のSQLを作成
		if($item_kind=="トップス"){$query2 = "SELECT * FROM tops_color WHERE item_id = :item_id ORDER BY color_name";}
		if($item_kind=="ボトムス"){$query2 = "SELECT * FROM bottoms_color WHERE item_id = :item_id ORDER BY color_name";}
		if($item_kind=="アクセサリー"){$query2 = "SELECT * FROM accessory_color WHERE item_id = :item_id ORDER BY color_name";}
		
		# プリペアードステートメントを準備
		$stmt2 = $db->prepare($query2);
		$stmt2->bindParam(':item_id', $item_id);
		$stmt2->execute();

		#色をカンマで区切って並べる
		$color_name="";
		while($row2 = $stmt2->fetch()){
			$color_name .= "$row2[color_name],";
		}
		$color_name= substr($color_name, 0, -1);

		if($row['item_exist']==1) {$item_exist="有";}
		if($row['item_exist']==0) {$item_exist="無";}

		$item_data .= "<tr>";
		$item_data .= "<td class=\"form-left\">$item_id</td>";
		$item_data .= "<td class=\"form-left\">$row[item_name]</td>";
		$item_data .= "<td class=\"form-left\">$color_name</td>";
		$item_data .= "<td class=\"form-left\">$row[item_price]</td>";
		$item_data .= "<td class=\"form-left\">$item_exist</td>";
		$item_data .= "</tr>\n";
	}
	# 掲示板テンプレート読み込み
	$tmpl = page_read("list");
	# 文字変換
	$tmpl = str_replace("!item_data!",$item_data,$tmpl);
	$tmpl = str_replace("!item_kind!",$item_kind,$tmpl);
	
	echo $tmpl;
	exit;

}
#-----------------------------------------------------------
# エラー画面
#-----------------------------------------------------------
function error($errmes){
	global $tmpl_dir;
	$msg = $errmes;

	# エラーテンプレート読み込み
	$tmpl = page_read("error");

	# 文字置き換え
	#$tmpl = str_replace("!message!","$msg",$tmpl);
	echo $tmpl;
	exit;
}

#-----------------------------------------------------------
# ページ読み取り
#-----------------------------------------------------------
function page_read($page){
	global $tmpl_dir;
	
	# テンプレート読み込み
	$conf = fopen( "$tmpl_dir/{$page}.tmpl", "r") or die;
	$size = filesize("$tmpl_dir/{$page}.tmpl");
	$tmpl = fread($conf, $size);
	fclose($conf);
	
	return $tmpl;
}
