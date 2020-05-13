<?php
error_reporting(E_ALL & ~E_NOTICE);

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
	if(isset($_GET["category_name"])){$in["category_name"] = $_GET["category_name"];}
	if(isset($_POST["category_name"])){$in["category_name"] = $_POST["category_name"];}

	return $in;
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
	$tmpl = str_replace("!message!","$msg",$tmpl);
	echo $tmpl;
	exit;
}

#-----------------------------------------------------------
# カテゴリの一覧を取得、ulとliで並べる
#-----------------------------------------------------------
function category_list(){
	global $db;
	global $tmpl_dir;

	# 自身のパス
	$script_name=$_SERVER['SCRIPT_NAME'];

	# SQLを作成
	$query = "SELECT * FROM item_category";

	# プリペアードステートメントを準備
	$stmt = $db->prepare($query);
	$stmt->execute();

	$category_data = "<ul>";
	while($row = $stmt->fetch()){
		$category_name = $row['category_name'];
		$category_data .= "<li><input type='submit' name='category_name' value='$category_name'></li>";
    }
    $category_data .= "</ul>\n";

    return $category_data;
}

function pulldown_list($type,$select_id=-1,$limit=0,$name=""){
	global $db;
	global $tmpl_dir;

	# 自身のパス
	$script_name=$_SERVER['SCRIPT_NAME'];
	if($type==0){
		$get_list="<select name='$name'>";
		for($i=0;$i<$limit;$i++){
			if( $i == $select_id ){
				$get_list .= "<option value='$i' selected >$i</option>";
				continue;
			}
			$get_list .= "<option value='$i'>$i</option>";
    	}
		$get_list .= "</select>\n";
	}else{
	# SQLを作成
		if($type==1){
			$query = "SELECT * FROM item_category";
			$get_list="<select name='category_id'>";
			$want_name='category_name';
			$want_id='category_id';
		}else if($type==2){
			$query = "SELECT * FROM item_color";
			$get_list="<select name='color_id'>";
			$want_name='color_name';
			$want_id='color_id';
		}else{return -1;}


		# プリペアードステートメントを準備
		$stmt = $db->prepare($query);
		$stmt->execute();

		while($row = $stmt->fetch()){
			if( $row[$want_id] == $select_id ){
				$get_list .= "<option value='$row[$want_id]' selected >$row[$want_name]</option>";
				continue;
			}
			$get_list .= "<option value='$row[$want_id]'>$row[$want_name]</option>";
    	}
		$get_list .= "</select>\n";
	}

    return $get_list;
}

#-----------------------------------------------------------
# 読み込み
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

#-----------------------------------------------------------
# 名前からデータを受け取る
#-----------------------------------------------------------
function get_data_from_name($name,$type){
	global $in;
	global $db;

	if($type==1){$query = "SELECT * FROM item_category WHERE  category_name = :want_name";}
	else if($type==2){$query = "SELECT * FROM item_color WHERE  color_name = :want_name";}
	else if($type==3){$query = "SELECT * FROM item_data WHERE ( item_name = :want_name and category_id = :category_id ) and color_id = :color_id";}
	else{return -1;}

	$stmt = $db->prepare($query);

	$stmt->bindParam(':want_name', $name);

	if($type==3){
		$stmt->bindParam(':color_id', $color_id);
		$stmt->bindParam(':category_id', $category_id);
		$color_id = $in['color_id'];
		$category_id = $in['category_id'];
	}

	$stmt->execute();
	$row = $stmt->fetch();
	return $row;
}

#-----------------------------------------------------------
# idからデータを受け取る
#-----------------------------------------------------------
function get_data_from_id($id,$type){
	global $in;
	global $db;

	if($type==1){$query = "SELECT * FROM item_category WHERE category_id = :id";}
	else if($type==2){$query = "SELECT * FROM item_color WHERE color_id = :id";}
	else if($type==3){$query = "SELECT * FROM item_data WHERE item_id = :id";}
	else{return -1;}

	$stmt = $db->prepare($query);
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	$row = $stmt->fetch();
	return $row;
}

#-----------------------------------------------------------
# 在庫の操作
#-----------------------------------------------------------
function stock_update(){
	global $in;
	global $db;
	global $tmpl_dir;

	$item_id = $in["item_id"];
	$item_increase = $in["item_increase"];
	$row = get_data_from_id($item_id,3);
	$item_stock = $row["item_stock"];

	$error_notes="";
	if(!preg_match("/^-?[0-9]+$/", $item_increase)){
		$error_notes.="・半角で数値を入力してください<br>";
	}else{
		if($item_increase =='0'){$error_notes.="・入力がありません<br>";}
		$item_stock = $item_stock+$item_increase;
	}
	#エラーチェック
	if($in["item_id"] == ""){
		$error_notes.="・編集する商品を選択してください。<br>";
	}
	if($item_stock < 0){
		$error_notes.="・在庫数が足りません。<br>";
	}

	#エラーが存在する場合
	if($error_notes != "") {
		error($error_notes);
	}

	# プリペアードステートメントを準備
	$stmt = $db->prepare('INSERT INTO item_update_data (item_id, item_increase) VALUES (:item_id, :item_increase)');

	# 変数を束縛する
	$stmt->bindParam(':item_id', $item_id);
	$stmt->bindParam(':item_increase', $item_increase);
	
	$stmt->execute();

	$stmt = $db->prepare('UPDATE item_data SET item_stock = :item_stock where item_id = :item_id');

	# 変数を束縛する
	$stmt->bindParam(':item_id', $item_id);
	$stmt->bindParam(':item_stock', $item_stock);

	$stmt->execute();

}