<?php
/*
Plugin Name: Split Site
Plugin URI: http://www.example.com/plugin
Description: PCとスマートフォンを振り分けるためのプラグイン
Author: hy_it
Version: 0.1
Author URI: http://www.example.com
*/

ob_start();

add_action('after_setup_theme', 'mytheme_setup_theme');
function mytheme_setup_theme()
{
	add_action('template_redirect', 'mytheme_template_redirect');
}

function mytheme_template_redirect()
{

	if (is_home()) {

		// ここにホームの表示前に行う処理を記述

	} else if (is_category()) {

		// ここにカテゴリーアーカイブページの表示前に行う処理を記述

	} else if (is_single()) {

		// ここに投稿ページの表示前に行う処理を記述
		$file_directory = __DIR__ . "/setting";
		$file_read = @fopen($file_directory . "/setting.csv", 'r');
		if ($file_read === false) {
			@fclose($file_read);
			$file_write = @fopen($file_directory . "/setting.csv", 'w');
			@fclose($file_write);
			$file_read = @fopen($file_directory . "/setting.csv", 'r');
		}

		if ($file_read) {
			$table_obj = new Table();
			while (!feof($file_read)) {
				$line_read = @fgets($file_read);
				$for_exist = explode(",", $line_read);
				if (array_key_last($for_exist) > 0) {
					list($transfer_setting_name, $transfer_source_url, $transfer_target_url_pc, $transfer_switch) = $for_exist;
					if (!empty($transfer_source_url)) {
						$table_obj = new Table();
						$table_obj->setting_name = $transfer_setting_name;
						$table_obj->target_url_pc = $transfer_target_url_pc;

						$table_obj->switch = $transfer_switch;
						$table[$transfer_source_url] = $table_obj;
					}
				}
			}
		}
		@fclose($file_read);

		$ip_read = @fopen($file_directory . "/ip.csv", 'r');
		if ($ip_read) {
			$ip_patern =  @fgets($ip_read);
		}
		@fclose($ip_read);

		$now_url = get_the_permalink();
		if (isset($table[$now_url])) {
			$target_url = $table[$now_url]->target_url_pc;
			if ($table[$now_url]->switch == 1) {

				$status = 302;
				if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
					$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
				} else {
					$ip_address = $_SERVER['REMOTE_ADDR'];
				}
				if ($ip_address == "::1") $ip_address = "127.0.0.1";
				$k = 0;
				$regexList = explode("$/", $ip_patern);
				$i = 0;
				foreach ($regexList as $regex) {
					$regex .= "$/";
					$regex = str_ireplace("comma", ",", $regex);
					$regex = str_ireplace('\\\\', '\\', $regex);
					if (preg_match($regex, $ip_address)) { //IPが一致し、そのIPに対して決定がTRUEの場合、リダイレクトしません。
						$k = 1;
						break;
					} else {
						$k = 0;
					}
					$i++;
				}

				if ($k == 0) {
					wp_redirect($target_url, $status);
					exit;
				}
			}
		}
	}
}

add_action('admin_menu', 'add_pages');
function add_pages()
{
	$g_action_mode = 1;
	add_menu_page('Split Site', 'Split Site', 'level_8', __FILE__, 'split_site', '', 26);
}

function split_site()
{
	$file_directory = __DIR__ . "/setting";
	if (@file_exists($file_directory) === false) {
		@mkdir($file_directory, 0777);
	}

	$ip_read = @fopen($file_directory . "/ip.csv", 'r');
	if ($ip_read == false) {
		$ip_write = @fopen($file_directory . "/ip.csv", 'w');
		@fclose($ip_write);
		$ip_read = @fopen($file_directory . "/ip.csv", 'r');
	}

	$ip_patern = @fgets($ip_read);
	$ip_patern = str_ireplace('\\\\', '\\', $ip_patern);
	$ip_patern = str_ireplace("comma", ",", $ip_patern);
	$ip_patern = str_ireplace("comma", ",", $ip_patern);
	@fclose($ip_read);

	$file_read = @fopen($file_directory . "/setting.csv", 'r');
	if ($file_read === false) {
		$file_write = @fopen($file_directory . "/setting.csv", 'w');
		@fclose($file_write);
		$file_read = @fopen($file_directory . "/setting.csv", 'r');
	}

	if ($file_read) {
		while (!feof($file_read)) {
			$line_read = @fgets($file_read);
			if ($line_read && count(explode(',', $line_read)) > 2) {
				list($transfer_setting_name, $transfer_source_url, $transfer_target_url_pc, $transfer_switch) = explode(',', $line_read);
				if (!empty($transfer_source_url)) {
					$table_obj = new Table();
					$table_obj->setting_name = $transfer_setting_name;
					$table_obj->target_url_pc = $transfer_target_url_pc;
					$table_obj->switch = $transfer_switch;
					$table[$transfer_source_url] = $table_obj;
				}
			}
		}
	}
	@fclose($file_read);
?>
	<div class="row">
		<div class="col-md-8">
			<div class="row ">
				<div class="col-md-12 setting-form">
					<form name="form_SPLIT" action="" method="post">
						<h1>サイト転送設定</h1>
						<p>1.転送設定</p>
						<div class="row">
							<div class="col-md-1 label-text">転送設定名：</div>
							<div class="col-md-11">
								<input id="TRANSFER_SETTING_NAME" name="TRANSFER_SETTING_NAME" type="text" size="50" />
							</div>
						</div>
						<p>2.転送URL</p>
						<div class="row">
							<div class="col-md-1 label-text">転送元URL：</div>
							<div class="col-md-11"><input id="TRANSFER_SOURCE_URL" name="TRANSFER_SOURCE_URL" type="text" size="100" value="" /></div>
						</div>
						<div class="row">
							<div class="col-md-1 label-text">転送先URL：</div>
							<div class="col-md-11"><input id="TRANSFER_TARGET_URL_PC" name="TRANSFER_TARGET_URL_PC" type="text" size="100" value="" /></div>
						</div>
						<p>3.転送有無</p>
						<div class="row">
							<div class="col-md-6">
								<input type="radio" id="TRANSFER_SWITCH1" value="1" name="t_check" /><label for="TRANSFER_SWITCH1">転送有</label>
								<input type="radio" id="TRANSFER_SWITCH2" value="0" name="t_check" /><label for="TRANSFER_SWITCH2">転送無</label>
								<input type="hidden" name="TRANSFER_SWITCH" id="TRANSFER_SWITCH" />
								<input type="hidden" name="TRANSFER_IPs" id="TRANSFER_IPs" />
							</div>
							<div class="col-md-6">
								<input type="button" name="Split_Site_Send" onclick="save_ip_setting()" class="button-primary" value="転送設定" />
							</div>
						</div>
					</form>
				</div>
			</div>
			<div class="row ">
				<div class="col-md-12 table-form">
					<?php
					$source_location = '';
					if (isset($_POST['TRANSFER_SOURCE_URL'])) $source_location = $_POST['TRANSFER_SOURCE_URL'];
					if (isset($source_location)) {
						if (!empty($source_location)) {
							$table_obj = new Table();
							$table_obj->setting_name = $_POST['TRANSFER_SETTING_NAME'];
							$table_obj->target_url_pc = $_POST['TRANSFER_TARGET_URL_PC'];
							$table_obj->switch = $_POST['TRANSFER_SWITCH'];
							$table[$source_location] = $table_obj;
							$file_write = @fopen($file_directory . "/setting.csv", 'w');

							foreach ($table as $table_el_key => $table_el) {
								if (!empty($table_el->target_url_pc) && $table_el->setting_name) {
									$line_write = $table_el->setting_name;
									$line_write .= "," . $table_el_key;
									$line_write .= "," . $table_el->target_url_pc;
									$line_write .= "," . $table_el->switch;
									@fwrite($file_write, $line_write . "\n");
								}
							}
							@fclose($file_write);
						}
					}

					if (isset($_POST['TRANSFER_IPs'])) {
						$transfer_ips = str_ireplace('\\\\', '\\', $_POST['TRANSFER_IPs']);
						$ip_write = @fopen($file_directory . "/ip.csv", 'w');
						@fwrite($ip_write, $transfer_ips . "\n");
						@fclose($ip_write);
						$ip_read = @fopen($file_directory . "/ip.csv", 'r');
						$ip_patern = @fgets($ip_read);
						$ip_patern = str_ireplace('\\\\', '\\', trim($ip_patern));
						$ip_patern = str_ireplace("comma", ",", $ip_patern);
						$ip_patern = str_ireplace("comma", ",", $ip_patern);
					}
					?>

					<form action="" method="post" name="form_TABLE">
						<div class="row">
							<div class="col-md-12">
								<p>設定一覧</p>
								<table class="table1" border=1>
									<tr>
										<th>No</th>
										<th>転送設定名</th>
										<th>転送元URL</th>
										<th>転送先URL</th>
										<th>転送有無</th>
									</tr>
									<?php
									$loop_setting = 1;
									if (!empty($table)) {
										foreach ($table as $table_el_key => $table_el) {
											if (!empty($table_el->target_url_pc)) {
												echo "<tr contentEditable=\"true\">";
												echo "<td id=\"id_" . $loop_setting . "_0" . "\">" . $loop_setting . "</td>";
												echo "<td id=\"id_" . $loop_setting . "_1" . "\">" . $table_el->setting_name . "</td>";
												echo "<td id=\"id_" . $loop_setting . "_2" . "\">" . $table_el_key . "</td>";
												echo "<td id=\"id_" . $loop_setting . "_3" . "\">" . $table_el->target_url_pc . "</td>";
												if ($table_el->switch == 1) {
													$table_switch = "有";
												} else {
													$table_switch = "無";
												}
												echo "<td id=\"id_" . $loop_setting . "_4" . "\">" . $table_switch . "</td>";
												echo "</tr>";
												$loop_setting++;
											}
										}
									}
									for ($loop_setting2 = $loop_setting; $loop_setting2 <= 100; $loop_setting2++) {
										echo "<tr contentEditable=\"true\">";
										echo "<td id=\"id_" . $loop_setting . "_0" . "\">" . $loop_setting . "</td>";
										echo "<td id=\"id_" . $loop_setting . "_1" . "\"></td>";
										echo "<td id=\"id_" . $loop_setting . "_2" . "\"></td>";
										echo "<td id=\"id_" . $loop_setting . "_3" . "\"></td>";
										echo "<td id=\"id_" . $loop_setting . "_4" . "\"></td>";
										echo "</tr>";
										$loop_setting++;
									}
									?>
								</table>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="col-md-4" style="position: relative;">
			<div class="ip-box">
				<div class="row">
					<p style="padding-left: 10px; margin-right:10px;border-bottom:2px solid black; width:100%;">設定IP一覧</p>
				</div>
				<div class="row">
					<span style="font-size: 0.8rem; margin-left: 200px;">/^ ... $/ &nbsp;形式で入力してください</span>
				</div>
				<div class="row">
					<div class="ip-config" name="ip_config" id="ip_config">
						<?php
						$ip_patern = explode("$/", $ip_patern);
						$en_patern = "";
						$i = 0;
						foreach ($ip_patern as $item) {
							if ($item != "" && $item != " ") {
								$en_patern .= $item . "$/";
								if ($i < count($ip_patern) - 2) $en_patern .= "\n";
							}
							$i++;
						}
						?>
						<textarea rows="20" id="IP_ITEMS" class='ip-item'><?= $en_patern ?></textarea>
					</div>
				</div>
				<div class="row ip-save-div">
					<button onclick="save_ip_setting()" class="ip-save-btn">IP設定保存</button>
				</div>
			</div>
		</div>
	</div>


	<style type="text/css">
		/* * {
			border: 1px black solid;
		} */

		.setting-form,
		.table-form {
			padding-left: 20px;
		}

		.setting-form div.row {
			padding-top: 10px;
		}

		textarea {
			white-space: pre-wrap;
		}

		.table-form {
			margin-top: 50px;
			margin-bottom: 0px;
			border-top: 3px solid black;
		}

		.ip-box {
			position: absolute;
			border: 1px gray solid;
			left: 10px;
			right: 20px;
			top: 10px;
			background-color: white;
		}

		.ip-save-div {
			justify-content: space-around;
			padding: 10px 0;
		}

		.ip-save-btn {
			border-radius: 10px;
			background-color: green;
			color: white;
			border: none;
			padding: 10px;
		}

		p {
			margin-top: 20px;
			font-size: 1.5rem;
			font-weight: 800;
			margin-bottom: 0px;
		}

		.label-text {
			padding-top: 5px;
			padding-left: 10px;
		}

		.table1 {
			border-collapse: collapse;
			border: 3px #FFFFFF solid;
			width: 100% !important;
		}

		.table1 th {
			border: 3px #FFFFFF solid;
			background-color: #1F97DF;
			color: #FFFFFF;
		}

		.table1 td {
			border: 3px #FFFFFF solid;
			text-align: left;
		}

		.row {
			width: 100%;
			display: flex;
		}

		.col-md-1 {
			width: 8.333333333333333%;
		}

		.col-md-2 {
			width: 16.66666666666667%;
		}

		.col-md-3 {
			width: 25%;
		}

		.col-md-4 {
			width: 33.33333333333333%;
		}

		.col-md-5 {
			width: 41.66666666666667%;
		}

		.col-md-6 {
			width: 50%;
		}

		.col-md-7 {
			width: 58.333333333333333%;
		}

		.col-md-8 {
			width: 66.66666666666667%;
		}

		.col-md-9 {
			width: 75%;
		}

		.col-md-10 {
			width: 83.333333333333333%;
		}

		.col-md-11 {
			width: 91.66666666666667%;
		}

		.col-md-12 {
			width: 100%;
		}

		.ip-config {
			padding: 10px 10px;
			display: block;
		}

		.new_ip_container {
			margin-top: 0.1rem;
		}

		.button-primary {
			padding: 5px 30px !important;
			font-size: 1rem !important;
		}

		.ip-item {
			width: 100%;
			min-width: 27.2vw;
		}

		.hidden_td {
			display: none;
		}

		.add-btn {
			color: white;
			background-color: orange;
			padding-top: 0px;
			font-size: 1.3rem;
			border: none;
			border-radius: 0 4px 4px 0;
		}

		.del-btn {
			border: none;
			background-color: red;
			color: white;
		}
	</style>

	<script type="text/javascript">
		var str_setting_count = <?php echo $loop_setting; ?>;

		var loop_setting;
		var loop_setting_in;
		var el, el_click;
		var el_col_in = {};

		for (loop_setting = 1; loop_setting < str_setting_count; loop_setting++) {
			for (loop_setting_in = 1; loop_setting_in <= 6; loop_setting_in++) {
				el = document.getElementById("id_" + loop_setting + "_" + loop_setting_in);
				el_col_in[(loop_setting - 1) * 6 + (loop_setting_in - 1)] = el;
			}
		}
		k = 0;
		for (j = 0; j < 6; j++) {
			l = 0;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 0 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 1 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 2 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 3 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 4 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 5 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 6 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 7 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 8 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 9 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 10 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 11 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 12 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 13 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 14 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 15 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 16 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 17 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 18 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 19 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 20 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 21 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 22 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 23 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 24 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 25 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 26 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 27 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 28 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 29 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 30 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 31 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 32 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 33 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 34 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 35 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 36 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 37 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 38 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 39 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 40 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 41 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 42 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 43 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 44 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 45 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 46 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 47 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 48 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 49 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 50 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 51 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 52 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 53 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 54 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 55 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 56 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 57 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 58 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 59 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 60 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 61 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 62 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 63 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 64 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 65 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 66 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 67 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 68 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 69 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 70 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 71 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 72 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 73 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 74 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 75 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 76 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 77 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 78 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 79 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 80 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 81 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 82 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 83 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 84 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 85 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 86 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 87 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 88 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 89 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 90 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 91 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 92 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 93 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 94 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 95 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 96 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 97 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 98 * 6;
				id_Click_in();
			};
			l++;
			el_col_in[j + 6 * l].onclick = function id_Click() {
				k = 99 * 6;
				id_Click_in();
			};
		}

		function id_Click_in() {
			document.forms.form_SPLIT.TRANSFER_SETTING_NAME.value = el_col_in[k + 0].innerText;
			document.forms.form_SPLIT.TRANSFER_SOURCE_URL.value = el_col_in[k + 1].innerText;
			document.forms.form_SPLIT.TRANSFER_TARGET_URL_PC.value = el_col_in[k + 2].innerText;
			if (el_col_in[k + 3].innerText == "有") {
				document.getElementById("TRANSFER_SWITCH1").checked = true;
			} else if (el_col_in[k + 3].innerText == "無") {
				document.getElementById("TRANSFER_SWITCH2").checked = true;
			}
		}

		function trm_ip(ip) {
			ip = ip.split('').filter(e => e.trim().length).join('');
			return ip;
		}

		function save_ips() {
			var id_eles = document.getElementById('IP_ITEMS').value;
			var new_str = id_eles.split(",");
			new_str = new_str.join("comma");
			document.getElementById("TRANSFER_IPs").value = trm_ip(new_str);
		}

		function save_ip_setting() {
			(document.getElementById('TRANSFER_SWITCH1').checked) ? document.getElementById('TRANSFER_SWITCH').value = 1: document.getElementById('TRANSFER_SWITCH').value = 0
			save_ips();
			document.forms.form_SPLIT.submit();
		}
	</script>
<?php
}
class Table
{
	public $setting_name = "";
	public $target_url_pc = "";
	public $target_ips = "";
	public $ip_check = "";
	public $switch = "";
}
?>