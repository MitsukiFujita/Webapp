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
$item_table = "tops_data";

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
	else if($in["state"] == "color") { color_update(); }
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
	global $item_kind;

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

	if(isset($_GET["item_kind"])){$item_kind = $_GET["item_kind"];}
	if(isset($_in["item_kind"])){$item_kind = $_in["item_kind"];}
	if(isset($_POST["item_kind"])){$item_kind = $_POST["item_kind"];}

	return $in;
}

#-----------------------------------------------------------
# 商品登録
#-----------------------------------------------------------
function item_insert(){
	global $in;
	global $db;
	global $tmpl_dir;
	global $item_kind;

	#エラーチェック
	$error_notes="";
	if($in["item_name"] == ""){
		$error_notes.="・商品名が未入力です。<br>";
	}
	if($in["item_price"] == ""){
		$error_notes.="・値段が未入力です。<br>";
	}
	if($in["color_name"] == ""){
		$error_notes.="・色が未入力です。<br>";
	}
	if(item_check()){
	$error_notes.="・登録済みの商品です。<br>";
	}
	
	#エラーが存在する場合
	if($error_notes != "") {
		error($error_notes);
	}

	# プリペアードステートメントを準備
	if($item_kind=="トップス"){$stmt = $db->prepare('INSERT INTO tops_data (item_name, item_price) VALUES (:item_name, :item_price)');}
	if($item_kind=="ボトムス"){$stmt = $db->prepare('INSERT INTO bottoms_data (item_name, item_price) VALUES (:item_name, :item_price)');}
	if($item_kind=="アクセサリー"){$stmt = $db->prepare('INSERT INTO accessory_data (item_name, item_price) VALUES (:item_name, :item_price)');}

	# 変数を束縛する
	$stmt->bindParam(':item_name', $item_name);
	$stmt->bindParam(':item_price', $item_price);

	# 変数に値を設定し、SQLを実行
	$item_name = $in["item_name"];
	$item_price = $in["item_price"];
	$stmt->execute();

	# プリペアードステートメントを準備
	if($item_kind=="トップス"){$stmt = $db->prepare ('SELECT * FROM tops_data ORDER BY item_id DESC LIMIT 1');}
	if($item_kind=="ボトムス"){$stmt = $db->prepare ('SELECT * FROM bottoms_data ORDER BY item_id DESC LIMIT 1');}
	if($item_kind=="アクセサリー"){$stmt = $db->prepare ('SELECT * FROM accessory_data ORDER BY item_id DESC LIMIT 1');}
	
	$stmt->execute();
	$row = $stmt->fetch();
	$item_id = $row['item_id'];

	if($item_kind=="トップス"){$stmt = $db->prepare('INSERT INTO tops_color (item_id, color_name) VALUES (:item_id, :color_name)');}
	if($item_kind=="ボトムス"){$stmt = $db->prepare('INSERT INTO bottoms_color (item_id, color_name) VALUES (:item_id, :color_name)');}
	if($item_kind=="アクセサリー"){$stmt = $db->prepare('INSERT INTO accessory_color (item_id, color_name) VALUES (:item_id, :color_name)');}

	# 変数を束縛する
	$stmt->bindParam(':item_id', $item_id);
	$stmt->bindParam(':color_name', $color_name);

	# 変数に値を設定し、SQLを実行
	$color_name = $in["color_name"];
	$stmt->execute();
}

#すでに存在する商品の名前にならないかチェックし、重複があるとTRUEを返す
function item_check(){
	global $in;
	global $item_kind;
	global $db;

	$flag =FALSE;

	if($item_kind=="トップス"){$query = "SELECT * FROM tops_data WHERE item_flag = 1";}
	if($item_kind=="ボトムス"){$query = "SELECT * FROM bottoms_data WHERE item_flag = 1";}
	if($item_kind=="アクセサリー"){$query = "SELECT * FROM accessory_data WHERE item_flag = 1";}

	# プリペアードステートメントを準備
	$stmt = $db->prepare($query);
	$item_id = $in["item_id"];
	$stmt->execute();

	while($row = $stmt->fetch()){
		if($in["item_name"] == $row['item_name']){
			$flag = TRUE;
			break;
		}
	}
	return $flag;
}

#-----------------------------------------------------------
# 色編集
#-----------------------------------------------------------
function color_update(){
	global $in;
	global $db;
	global $tmpl_dir;
	global $item_kind;

	#エラーチェック
	$error_notes="";
	if($in["item_id"] == ""){
		$error_notes.="・編集する商品を選択してください。<br>";
	}
	if($in["color_name"] == ""){
		$error_notes.="・色が未入力です。<br>";
	}
	if(color_check()){
		$error_notes.="・登録されている色です。<br>";
	}
	
	#エラーが存在する場合
	if($error_notes != "") {
		error($error_notes);
	}

	if($item_kind=="トップス"){$stmt = $db->prepare('INSERT INTO tops_color (item_id, color_name) VALUES (:item_id, :color_name)');}
	if($item_kind=="ボトムス"){$stmt = $db->prepare('INSERT INTO bottoms_color (item_id, color_name) VALUES (:item_id, :color_name)');}
	if($item_kind=="アクセサリー"){$stmt = $db->prepare('INSERT INTO accessory_color (item_id, color_name) VALUES (:item_id, :color_name)');}

	# 変数を束縛する
	$stmt->bindParam(':item_id', $item_id);
	$stmt->bindParam(':color_name', $color_name);

	# 変数に値を設定し、SQLを実行
	$item_id = $in["item_id"];
	$color_name = $in["color_name"];
	$stmt->execute();

}

#指定した商品にすでに存在する色にならないかチェックし、重複があるとTRUEを返す
function color_check(){
	global $in;
	global $item_kind;
	global $db;

	$flag =FALSE;

	if($item_kind=="トップス"){$query = "SELECT * FROM tops_color WHERE item_id = :item_id";}
	if($item_kind=="ボトムス"){$query = "SELECT * FROM bottoms_color WHERE item_id = :item_id";}
	if($item_kind=="アクセサリー"){$query = "SELECT * FROM accessory_color WHERE item_id = :item_id";}

	# プリペアードステートメントを準備
	$stmt = $db->prepare($query);
	$stmt->bindParam(':item_id', $item_id);
	$item_id = $in["item_id"];
	$stmt->execute();

	while($row = $stmt->fetch()){
		if($in["color_name"] == $row['color_name']){
			$flag = TRUE;
			break;
		}
	}
	return $flag;
}

#-----------------------------------------------------------
# 商品編集
#-----------------------------------------------------------
function item_update(){
	global $in;
	global $db;
	global $tmpl_dir;
	global $item_kind;

	#エラーチェック
	$error_notes="";
	if($in["item_id"] == ""){
		$error_notes.="・編集する商品を選択してください。<br>";
	}
	if($in["item_name"] == ""){
		$error_notes.="・商品名が未入力です。<br>";
	}
	if($in["item_price"] == ""){
		$error_notes.="・値段が未入力です。<br>";
	}
	if(rename_item_check()){
		$error_notes.="・登録済みの商品です。<br>";
	}

	#エラーが存在する場合
	if($error_notes != "") {
		error($error_notes);
	}

	# プリペアードステートメントを準備
	if($item_kind=="トップス"){$stmt = $db->prepare('UPDATE tops_data SET item_name = :item_name, item_price = :item_price, item_exist = :item_exist where item_id = :item_id');}
	if($item_kind=="ボトムス"){$stmt = $db->prepare('UPDATE bottoms_data SET item_name = :item_name, item_price = :item_price, item_exist = :item_exist where item_id = :item_id');}
	if($item_kind=="アクセサリー"){$stmt = $db->prepare('UPDATE accessory_data SET item_name = :item_name, item_price = :item_price, item_exist = :item_exist where item_id = :item_id');}

	# 変数を束縛する
	$stmt->bindParam(':item_id', $item_id);
	$stmt->bindParam(':item_name', $item_name);
	$stmt->bindParam(':item_price', $item_price);
	$stmt->bindParam(':item_exist', $item_exist);

	# 変数に値を設定し、SQLを実行
	$item_id = $in["item_id"];
	$item_name = $in["item_name"];
	$item_price = $in["item_price"];
	$item_exist = $in["item_exist"];
	$stmt->execute();
}

#すでに存在する商品の名前にならないかチェックし、重複があるとTRUEを返す
function rename_item_check(){
	global $in;
	global $item_kind;
	global $db;

	$flag =FALSE;

	if($item_kind=="トップス"){$query = "SELECT * FROM tops_data WHERE item_flag = 1";}
	if($item_kind=="ボトムス"){$query = "SELECT * FROM bottoms_data WHERE item_flag = 1";}
	if($item_kind=="アクセサリー"){$query = "SELECT * FROM accessory_data WHERE item_flag = 1";}

	$stmt = $db->prepare($query);
	$item_id = $in["item_id"];
	$stmt->execute();

	while($row = $stmt->fetch()){
		if($in["item_id"] == $row['item_id']) continue;#自分の名前はチェックを飛ばす
		if($in["item_name"] == $row['item_name']){
			$flag = TRUE;
			break;
		}
	}
	return $flag;
}

#-----------------------------------------------------------
# 商品削除
#-----------------------------------------------------------
function item_delete(){
	global $in;
	global $db;
	global $tmpl_dir;
	global $item_kind;

	#エラーチェック
	$error_notes="";
	if($in["item_id"] == ""){
		$error_notes.="・削除する商品を選択してください。<br>";
	}
	
	#エラーが存在する場合
	if($error_notes != "") {
		error($error_notes);
	}

	# プリペアードステートメントを準備
	if($item_kind=="トップス"){$stmt = $db->prepare('UPDATE tops_data SET item_flag = 0 WHERE item_id = :item_id');}
	if($item_kind=="ボトムス"){$stmt = $db->prepare('UPDATE bottoms_data SET item_flag = 0 WHERE item_id = :item_id');}
	if($item_kind=="アクセサリー"){$stmt = $db->prepare('UPDATE accessory_data SET item_flag = 0 WHERE item_id = :item_id');}

	# 変数を束縛する
	$stmt->bindParam(':item_id', $item_id);

	# 変数に値を設定し、SQLを実行
	$item_id = $in["item_id"];
	$stmt->execute();
}

#-----------------------------------------------------------
# 商品一覧
#-----------------------------------------------------------
function item_search(){
	global $in;
	global $db;
	global $tmpl_dir;
	global $item_kind;

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

		#　商品ごとに、存在する色を並べる
		$color_name="";
		while($row2 = $stmt2->fetch()){
			$color_name .= "$row2[color_name],";
		}
		$color_name= substr($color_name, 0, -1);

		#商品の有無を数字から漢字に変換
		if($row['item_exist']==1) {$item_exist="有";}
		if($row['item_exist']==0) {$item_exist="無";}
		
		$item_data .= "<tr>";
		$item_data .= "<td class=\"form-left\">$item_id</td>";
		$item_data .= "<td class=\"form-left\">$row[item_name]</td>";
		$item_data .= "<td class=\"form-left\">$color_name</td>";
		$item_data .= "<td class=\"form-left\">$row[item_price]</td>";
		$item_data .= "<td class=\"form-left\">$item_exist</td>";
		$item_data .= "<td><a href=\"$script_name?mode=item&item_id=$item_id&item_kind=$item_kind\">編集</a></td>";
		$item_data .= "<td><a href=\"$script_name?mode=item&color_flag=1&item_id=$item_id&item_kind=$item_kind\">色追加</a></td>";
		$item_data .= "<td><a href=\"$script_name?mode=item&state=delete&item_id=$item_id&item_kind=$item_kind\">削除</a></td>";
		$item_data .= "</tr>\n";
	}

	if($in["item_id"] != ""){
		# 選択した商品IDに対応する情報を取得
		if($item_kind=="トップス"){$stmt = $db->prepare('SELECT * FROM tops_data WHERE item_id = :item_id');}
		if($item_kind=="ボトムス"){$stmt = $db->prepare('SELECT * FROM bottoms_data WHERE item_id = :item_id');}
		if($item_kind=="アクセサリー"){$stmt = $db->prepare('SELECT * FROM accessory_data WHERE item_id = :item_id');}

		$stmt->bindParam(':item_id', $item_id);
		$item_id = $in["item_id"];
		$stmt->execute();
		$row = $stmt->fetch();
		$item_name = $row["item_name"];
		$item_price = $row["item_price"];
		$item_exist = $row["item_exist"];
		$color_name = $row["color_name"];

		#現在の状態をラジオボタンでチェック済みにしておく
		if($item_exist==1) {
			$item_exist="有";
			$checked1="checked";
		}else if($item_exist==0) {
			$item_exist="無";
			$checked0="checked";
		}

		# 色取得用のSQLを作成
		if($item_kind=="トップス"){$query = "SELECT * FROM tops_color WHERE item_id = :item_id ORDER BY color_name";}
		if($item_kind=="ボトムス"){$query = "SELECT * FROM bottoms_color WHERE item_id = :item_id ORDER BY color_name";}
		if($item_kind=="アクセサリー"){$query = "SELECT * FROM accessory_color WHERE item_id = :item_id ORDER BY color_name";}

		#　商品ごとに、存在する色を並べる
		$stmt = $db->prepare($query);
		$stmt->bindParam(':item_id', $item_id);
		$stmt->execute();	
		$color_name="";
		while($row = $stmt->fetch()){
			$color_name .= "$row[color_name],";
		}
		$color_name= substr($color_name, 0, -1);
		
		# 掲示板テンプレート読み込み
		if($in["color_flag"]==1){
			$tmpl = page_read("color_edit");
		}else{
			$tmpl = page_read("item_edit");
		}

		# 文字変換
		$tmpl = str_replace("!item_id!",$in["item_id"],$tmpl);
		$tmpl = str_replace("!item_name!",$item_name,$tmpl);
		$tmpl = str_replace("!item_price!",$item_price,$tmpl);
		$tmpl = str_replace("!item_data!",$item_data,$tmpl);
		$tmpl = str_replace("!color_name!",$color_name,$tmpl);
		$tmpl = str_replace("!item_exist!",$item_exist,$tmpl);
		$tmpl = str_replace("!checked1!",$checked1,$tmpl);
		$tmpl = str_replace("!checked0!",$checked0,$tmpl);
		$tmpl = str_replace("!item_kind!",$item_kind,$tmpl);
	}else{
		# 掲示板テンプレート読み込み
		$tmpl = page_read("managementlist");
		# 文字変換
        $tmpl = str_replace("!item_data!",$item_data,$tmpl);
        $tmpl = str_replace("!item_kind!",$item_kind,$tmpl);
	}
	echo $tmpl;
	exit;
}

#-----------------------------------------------------------
# エラー画面
#-----------------------------------------------------------
function error($errmes){
	global $tmpl_dir;
	global $item_kind;

	$msg = $errmes;

	# エラーテンプレート読み込み
	$tmpl = page_read("error");

	# 文字置き換え
	$tmpl = str_replace("!message!","$msg",$tmpl);
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
