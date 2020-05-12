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
	item_search_customer();
}catch (PDOException $e) {
	die ("PDO Error:" . $e->getMessage());
}

#-----------------------------------------------------------
# 商品一覧
#-----------------------------------------------------------

function item_search_customer(){
	global $in;
	global $db;
	global $tmpl_dir;


	# 自身のパス
	$script_name=$_SERVER['SCRIPT_NAME'];

	$row = get_data_from_name($in['category_name'],1);
	$category_id = $row["category_id"];

	# SQLを作成
	$query = "SELECT * FROM item_data WHERE item_flag = 1 and category_id = :category_id order by item_name";
	
	# プリペアードステートメントを準備
	$stmt = $db->prepare($query);
	$stmt->bindParam(':category_id', $category_id);
	$stmt->execute();

	$item_data = "";
	$item_name ="";
	while($row = $stmt->fetch()){
		if ($item_name == $row["item_name"]){continue;}
		$item_name = $row["item_name"];

		$color_list="<select name='color_list'>";

		$query2 = "SELECT * FROM item_data WHERE  item_name = :want_name";	
		$stmt2 = $db->prepare($query2);
		$stmt2->bindParam(':want_name', $item_name);
		$stmt2->execute();

		while($row2 = $stmt2->fetch()){
			$row3 = get_data_from_id($row2["color_id"],2);
			$color_list.= "<option value='$row2[item_id]'>$row3[color_name]</option>";
		}
		$color_list .= "</select>";

		$item_number = $row['item_number'];
		if( $item_number == 0 ){$item_number = "sold out";}


		$item_data .= "<tr>";
		$item_data .= "<td class=\"form-left\">$in[category_name]</td>";
		$item_data .= "<td class=\"form-left\">$item_name</td>";
		$item_data .= "<td class=\"form-left\">$color_list</td>";
		$item_data .= "<td class=\"form-left\">$row[item_price]</td>";
		$item_data .= "<td class=\"form-left\">$item_number</td>";
		$item_data .= "<form action='customer.php' method='post'>";
		$item_data .= "<td class=\"form-left\"><input type='text' value=''>個</td>";
		$item_data .= "<td class=\"form-left\"><input type='submit' value='購入'></td>";
		$item_data .= "<input type='hidden' name='category_name' value='$in[category_name]'>";
		$item_data .= "<input type='hidden' name='item_id' value='$in[item_id]'></form>";
		$item_data .= "</tr>\n";
	}
	# 掲示板テンプレート読み込み
	$tmpl = page_read("list");

	$category_data =category_list();

	# 文字変換
	$tmpl = str_replace("!item_data!",$item_data,$tmpl);
	$tmpl = str_replace("!category_name!",$in['category_name'],$tmpl);
	$tmpl = str_replace("!category_data!",$category_data,$tmpl);
	
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

