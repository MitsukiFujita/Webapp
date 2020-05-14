<?php
error_reporting(E_ALL & ~E_NOTICE);
require "function_list.php";

#-----------------------------------------------------------
# 基本設定
#-----------------------------------------------------------

#データベース情報
$testuser ="testuser";
$testpass ="testpass";
$host ="localhost";
$datebase ="shop_item_v2";
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
	else if($in["state"] == "stock") { stock_update(); }
	else if($in["state"] == "delete") { item_delete(); }
	else if($in["state"] == "variation") { variation_edit(); }
	item_search_manage();
}catch (PDOException $e) {
	die ("PDO Error:" . $e->getMessage());
}


#-----------------------------------------------------------
# 商品登録
#-----------------------------------------------------------
function item_insert(){
	global $in;
	global $db;
	global $tmpl_dir;

	$item_price = $in["item_price"];
	
	#エラーチェック
	$error_notes="";
	if($in["item_name"] == ""){
		$error_notes.="・商品名が未入力です。<br>";
	}
	if($in["item_price"] == ""){
		$error_notes.="・値段が未入力です。<br>";
	}else if(!preg_match("/^[0-9]+$/", $in["item_price"])){
		$error_notes.="・値段の入力は半角数字で自然数のみ受け付けています<br>";
	}
	if(item_check()){
	$error_notes.="・登録済みの商品です。<br>";
	}
	#エラーが存在する場合
	if($error_notes != "") {
		error($error_notes);
	}

	# プリペアードステートメントを準備
	$stmt = $db->prepare('INSERT INTO item_data (item_name, item_price, color_id, category_id) VALUES (:item_name, :item_price, :color_id, :category_id)');

	# 変数を束縛する
	$stmt->bindParam(':item_name', $item_name);
	$stmt->bindParam(':item_price', $item_price);
	$stmt->bindParam(':color_id', $color_id);
	$stmt->bindParam(':category_id', $category_id);

	# 変数に値を設定し、SQLを実行
	$item_name = $in["item_name"];
	$item_price = $in["item_price"];
	$color_id = $in["color_id"];
	$category_id = $in["category_id"];
	$stmt->execute();
}

#すでに存在する商品の名前にならないかチェックし、重複があるとTRUEを返す
function item_check(){
	global $in;
	global $db;

	$flag =FALSE;

	$query = "SELECT * FROM item_data/* WHERE item_flag = 1*/";

	# プリペアードステートメントを準備
	$stmt = $db->prepare($query);
	$item_id = $in["item_id"];
	$stmt->execute();

	while($row = $stmt->fetch()){
		if($in["item_id"] == $row["item_id"])continue;
		if($in["category_id"] != $row["category_id"])continue;
		if($in["item_name"] != $row['item_name'])continue;
		if($in["color_id"] != $row['color_id'])continue;
		$flag =TRUE;
		break;
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
	}else if(!preg_match("/^[0-9]+$/", $in["item_price"])){
		$error_notes.="・値段の入力は半角数字で自然数のみ受け付けています<br>";
	}
	if(item_check()){
		$error_notes.="・登録済みの商品です。<br>";
	}


	#エラーが存在する場合
	if($error_notes != "") {
		error($error_notes);
	}

	# プリペアードステートメントを準備
	$stmt = $db->prepare('UPDATE item_data SET item_name = :item_name, item_price = :item_price, category_id=:category_id, color_id=:color_id where item_id = :item_id');

	# 変数を束縛する
	$stmt->bindParam(':item_id', $item_id);
	$stmt->bindParam(':item_name', $item_name);
	$stmt->bindParam(':item_price', $item_price);
	$stmt->bindParam(':category_id', $category_id);
	$stmt->bindParam(':color_id', $color_id);

	# 変数に値を設定し、SQLを実行
	$item_id = $in["item_id"];
	$item_name = $in["item_name"];
	$item_price = $in["item_price"];
	$category_id = $in["category_id"];
	$color_id = $in["color_id"];
	$stmt->execute();
}

#-----------------------------------------------------------
# 商品削除
#-----------------------------------------------------------
function item_delete(){
	global $in;
	global $db;

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
	$stmt = $db->prepare('UPDATE item_data SET item_flag = 0 WHERE item_id = :item_id');

	# 変数を束縛する
	$stmt->bindParam(':item_id', $item_id);

	# 変数に値を設定し、SQLを実行
	$item_id = $in["item_id"];
	$stmt->execute();
}

#-----------------------------------------------------------
# 商品一覧
#-----------------------------------------------------------
function item_search_manage(){
	global $in;
	global $db;
	global $tmpl_dir;
	
	$category_name = $in['category_name'];

	# 自身のパス
	$script_name=$_SERVER['SCRIPT_NAME'];

	$row = get_data_from_name($category_name,1);
	$category_id = $row["category_id"];

	# SQLを作成
	$query = "SELECT * FROM item_data WHERE item_flag = 1 and category_id = :category_id order by item_name";
	
	# プリペアードステートメントを準備
	$stmt = $db->prepare($query);
	$stmt->bindParam(':category_id', $category_id);
	$stmt->execute();

	$item_data = "";	
	while($row = $stmt->fetch()){
		$item_id = $row['item_id'];
		$item_stock = $row['item_stock'];
		if( $item_stock == 0 ){$item_stock = "sold out";}

		$row2=get_data_from_id($row["color_id"],2);

		$item_data .= "<tr>";
		$item_data .= "<td class=\"form-left\">$in[category_name]</td>";
		$item_data .= "<td class=\"form-left\">$row[item_name]</td>";
		$item_data .= "<td class=\"form-left\">$row2[color_name]</td>";
		$item_data .= "<td class=\"form-left\">$row[item_price]</td>";
		$item_data .= "<td class=\"form-left\">$item_stock</td>";
		$item_data .= "<td><a href=\"$script_name?mode=item&item_id=$item_id&category_name=$category_name\">編集</a></td>";
		$item_data .= "<td><a href=\"$script_name?mode=item&change_stock_flag=1&item_id=$item_id&category_name=$category_name\">在庫操作</a></td>";
		$item_data .= "<td><a href=\"$script_name?mode=item&state=delete&item_id=$item_id&category_name=$category_name\">削除</a></td>";
		$item_data .= "</tr>\n";
	}

	if($in["item_id"] != ""){
		# 選択した商品IDに対応する情報を取得
		$stmt = $db->prepare('SELECT * FROM item_data WHERE item_id = :item_id');

		$stmt->bindParam(':item_id', $item_id);
		$item_id = $in["item_id"];
		$stmt->execute();
		$row = $stmt->fetch();

		$item_name = $row["item_name"];
		$item_price = $row["item_price"];
		$item_exist = $row["item_exist"];
		$create_date = $row["create_date"];


		$row2 = get_data_from_id($row["color_id"],2);
		$color_name =$row2["color_name"];


		if($in["change_stock_flag"]==1){
			$tmpl = page_read("stock_edit");

			$row = get_data_from_name($category_name,1);
			$category_id = $row["category_id"];
		
			# SQLを作成
			$query = "SELECT * FROM item_update_data where item_id =:item_id";
			
			# プリペアードステートメントを準備
			$stmt = $db->prepare($query);
			$stmt->bindParam(':item_id', $item_id);
			$stmt->execute();
		
			$stock_data = "";	
			while($row = $stmt->fetch()){
				$stock_data .= "<tr>";
				$stock_data .= "<td class=\"form-left\">$row[update_id]</td>";
				$stock_data .= "<td class=\"form-left\">$row[update_time]</td>";
				$stock_data .= "<td class=\"form-left\">$row[item_increase]</td>";
				$stock_data .= "<td class=\"form-left\">$row[item_id]</td>";
				$sctok_data .= "</tr>\n";
			}
		}else{
			$tmpl = page_read("item_edit");
		}

		$category_data =category_list();
		$category_list = pulldown_list(1,$category_id);
		$color_list = pulldown_list(2,$row["color_id"]);

		# 文字変換
		$tmpl = str_replace("!category_data!",$category_data,$tmpl);
		$tmpl = str_replace("!category_list!",$category_list,$tmpl);
		$tmpl = str_replace("!item_id!",$item_id,$tmpl);
		$tmpl = str_replace("!item_name!",$item_name,$tmpl);
		$tmpl = str_replace("!item_price!",$item_price,$tmpl);
		$tmpl = str_replace("!item_data!",$item_data,$tmpl);
		$tmpl = str_replace("!color_list!",$color_list,$tmpl);
		$tmpl = str_replace("!color_name!",$color_name,$tmpl);
		$tmpl = str_replace("!create_date!",$create_date,$tmpl);
		$tmpl = str_replace("!category_name!",$category_name,$tmpl);
		$tmpl = str_replace("!stock_data!",$stock_data,$tmpl);
	}else if($in["manage"] != ""){

		if($in["manage"] == "色"){
		$query = "SELECT * FROM item_color";
		$id_value = "color_id";
		$name_value = "color_name";
		}else{
		$query = "SELECT * FROM item_category";
		$id_value = "category_id";
		$name_value = "category_name";
		}
		# プリペアードステートメントを準備
		$stmt = $db->prepare($query);
		$stmt->execute();

		$value_data ="";
		while($row = $stmt->fetch()){
			$value_data .= "<tr>";
			$value_data.= "<td class=\"form-left\">$row[$id_value]</td>";
			$value_data .= "<td class=\"form-left\">$row[$name_value]</td>";
			$value_data .= "</tr>\n";
		}

		$tmpl = page_read("variation_edit");

		# 文字変換
		$tmpl = str_replace("!value!",$in["manage"],$tmpl);
		$tmpl = str_replace("!value_data!",$value_data,$tmpl);
		$tmpl = str_replace("!category_name!",$category_name,$tmpl);
		$tmpl = str_replace("!name_value!",$name_value,$tmpl);
	
	}else{
	# 掲示板テンプレート読み込み
	$tmpl = page_read("managementlist");

	$category_data =category_list();
	$category_list = pulldown_list(1,$category_id);
	$color_list = pulldown_list(2);

	# 文字変換
	$tmpl = str_replace("!item_data!",$item_data,$tmpl);
	$tmpl = str_replace("!category_name!",$category_name,$tmpl);
	$tmpl = str_replace("!category_data!",$category_data,$tmpl);
	$tmpl = str_replace("!color_list!",$color_list,$tmpl);
	$tmpl = str_replace("!category_list!",$category_list,$tmpl);
	}
	echo $tmpl;
	exit;

}



#-----------------------------------------------------------
# カテゴリ・色追加
#-----------------------------------------------------------
function variation_edit(){
	global $in;
	global $db;

	if($in["manage"] == "色"){
		$query = "SELECT * FROM item_color";
		$name_value = "color_name";
		}else{
		$query = "SELECT * FROM item_category";
		$name_value = "category_name";
	}

	$stmt = $db->prepare($query);
	$stmt->execute();

	$variation_name = $in["variation_name"];

	$error_notes="";
	if($variation_name == ""){
			$error_notes.="・名前が入力されていません<br>";
	}else{
		while($row = $stmt->fetch()){
			#エラーチェック
			if($variation_name == $row[$name_value]){
				$error_notes.="・既に登録されている名前です。<br>";
			}
		}
	}
	
	#エラーが存在する場合
	if($error_notes != "") {
		error($error_notes);
	}

	if($in["manage"] == "色"){
		$stmt = $db->prepare('INSERT INTO item_color (color_name) VALUES (:variation_name)');
	}else{
		$stmt = $db->prepare('INSERT INTO item_category (category_name) VALUES (:variation_name)');
	}

	# 変数を束縛する
	$stmt->bindParam(':variation_name',$variation_name);

	# 変数に値を設定し、SQLを実行
	$stmt->execute();

	
}