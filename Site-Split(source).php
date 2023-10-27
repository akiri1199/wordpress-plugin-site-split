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

	add_action( 'after_setup_theme', 'mytheme_setup_theme' );

	function mytheme_setup_theme() {
		add_action( 'template_redirect', 'mytheme_template_redirect' );
	}

	function mytheme_template_redirect() {

		if ( is_home() ) {

			// ここにホームの表示前に行う処理を記述

		} else if ( is_category() ) {

			// ここにカテゴリーアーカイブページの表示前に行う処理を記述

		} else if ( is_single() ) {

			// ここに投稿ページの表示前に行う処理を記述
			$file_directory = __DIR__."/setting";
			$file_read = @fopen($file_directory."/setting.csv", 'r');
			if($file_read === false) {
				@fclose($file_read);
				$file_write = @fopen($file_directory."/setting.csv", 'w');
				@fclose($file_write);
				$file_read = @fopen($file_directory."/setting.csv", 'r');
			}

			if($file_read) {
				while( !feof($file_read) ) {
					$line_read = @fgets( $file_read );
					list($transfer_setting_name, $transfer_source_url, $transfer_target_url_pc, $transfer_target_url_sp, $transfer_target_url_tab, $transfer_switch) = explode(',', $line_read);

					if(!empty($transfer_source_url)) {
						$table_obj = new Table();

						$table_obj->setting_name = $transfer_setting_name;
						$table_obj->target_url_pc = $transfer_target_url_pc;
						$table_obj->target_url_sp = $transfer_target_url_sp;
						$table_obj->target_url_tab = $transfer_target_url_tab;
						$table_obj->switch = $transfer_switch;

						$table[$transfer_source_url] = $table_obj;
					}
				}
			}

			@fclose($file_read);

			$now_url = get_the_permalink();
			$now_ua = @mb_strtolower($_SERVER['HTTP_USER_AGENT']);

			$ua_obj = new UserAgent();

			$ua_obj->set($now_ua);
			$now_device = $ua_obj->get();

			if($now_device == "mobile") {
				$target_url = $table[$now_url]->target_url_sp;
			} else if($now_device == "tablet") {
				$target_url = $table[$now_url]->target_url_tab;
			} else {
				$target_url = $table[$now_url]->target_url_pc;
			}

			if( $table[$now_url]->switch == 1 ) {
				$status = 302;

				if($now_url != $target_url) {
					wp_redirect( $target_url, $status );
					exit;
				}
			}
		}
	}

	add_action( 'admin_menu', 'add_pages' );

	function add_pages() {
		$g_action_mode = 1;
		add_menu_page('Split Site','Split Site', 'level_8',__FILE__, 'split_site', '', 26);
	}

	function split_site() {
		$file_directory = __DIR__."/setting";
		if(@file_exists($file_directory) === false){
			@mkdir($file_directory, 0777);
		}

		$file_read = @fopen($file_directory."/setting.csv", 'r');
		if($file_read === false) {
			@fclose($file_read);
			$file_write = @fopen($file_directory."/setting.csv", 'w');
			@fclose($file_write);
			$file_read = @fopen($file_directory."/setting.csv", 'r');
		}

		if($file_read) {
			while( !feof($file_read) ) {
				$line_read = @fgets( $file_read );
				list($transfer_setting_name, $transfer_source_url, $transfer_target_url_pc, $transfer_target_url_sp,  $transfer_target_url_tab, $transfer_switch) = explode(',', $line_read);

				if(!empty($transfer_source_url)) {
					$table_obj = new Table();

					$table_obj->setting_name = $transfer_setting_name;
					$table_obj->target_url_pc = $transfer_target_url_pc;
					$table_obj->target_url_sp = $transfer_target_url_sp;
					$table_obj->target_url_tab = $transfer_target_url_tab;
					$table_obj->switch = $transfer_switch;

					$table[$transfer_source_url] = $table_obj;
				}
			}
		}
		@fclose($file_read);
?>
		<form name="form_SPLIT" action="" method="post">
			<h1>サイト転送設定</h1>
			<font size="4"><b>1.転送設定</b></font><br>
			転送設定名：&emsp;&emsp;<input id="TRANSFER_SETTING_NAME" name="TRANSFER_SETTING_NAME" type="text" size="50" value="" /><br><br>

			<font size="4"><b>2.転送URL</b></font><br>
			転送元URL：&emsp;&emsp;&nbsp;<input id="TRANSFER_SOURCE_URL" name="TRANSFER_SOURCE_URL" type="text" size="100" value="" /><br><br>

			転送先URL(PC)：&nbsp;&nbsp;<input id="TRANSFER_TARGET_URL_PC" name="TRANSFER_TARGET_URL_PC" type="text" size="100" value="" /><br>
			転送先URL(SP)：&nbsp;&nbsp;<input id="TRANSFER_TARGET_URL_SP" name="TRANSFER_TARGET_URL_SP" type="text" size="100" value="" /><br>
			転送先URL(TAB)：<input id="TRANSFER_TARGET_URL_TAB" name="TRANSFER_TARGET_URL_TAB" type="text" size="100" value="" /><br><br>

			<font size="4"><b>3.転送有無</b></font><br>
			<input type="radio" name="TRANSFER_SWITCH" id="TRANSFER_SWITCH1" value="1" checked="checked" /><label for="TRANSFER_SWITCH1">転送有</label>
			<input type="radio" name="TRANSFER_SWITCH" id="TRANSFER_SWITCH2" value="0" /><label for="TRANSFER_SWITCH2">転送無</label><br><br>
				

			<input type="submit" name="Split_Site_Send" class="button-primary" value="転送設定" />
		</form>

		<style type="text/css">
			#setting-table {
				float: center;
			}
			.table1 {
				border-collapse: collapse;
				border: 3px #FFFFFF solid;
				min-width: 600px;
			}
			.table1 th {
				border: 3px #FFFFFF solid;
			 	background-color: #1F97DF;
				color: #FFFFFF;
			.table1 td {
				border: 3px #FFFFFF solid;
				text-align: left;
				font-weight: bold;
			}
		</style>
<?php
		$source_location = $_POST['TRANSFER_SOURCE_URL'];

		if(isset($source_location)) {
			if(!empty($source_location)) {
				$table_obj = new Table();

				$table_obj->setting_name = $_POST['TRANSFER_SETTING_NAME'];
				$table_obj->target_url_pc = $_POST['TRANSFER_TARGET_URL_PC'];
				$table_obj->target_url_sp = $_POST['TRANSFER_TARGET_URL_SP'];
				$table_obj->target_url_tab = $_POST['TRANSFER_TARGET_URL_TAB'];
				$table_obj->switch = $_POST['TRANSFER_SWITCH'];

				$table[$source_location] = $table_obj;

				$file_write = @fopen($file_directory."/setting.csv", 'w');

				foreach($table as $table_el_key => $table_el) {
					if(!empty($table_el->target_url_pc)) {
						$line_write = $table_el->setting_name;
						$line_write .= ",".$table_el_key;
						$line_write .= ",".$table_el->target_url_pc;
						$line_write .= ",".$table_el->target_url_sp;
						$line_write .= ",".$table_el->target_url_tab;
						$line_write .= ",".$table_el->switch;

						@fwrite($file_write, $line_write."\n");
					}
				}

				@fclose($file_write);

				echo "<p><b><font size='3'>&emsp;転送設定完了しました</font></b></p>";
			} else {
				echo "<p><b><font size='3'>&emsp;転送設定未入力です</font></b></p>";
			}
		}
?>
	        <form action="" method="post" name="form_TABLE" >
			<div id="setting-table">
				<br><br><font size="4"><b>設定一覧</b></font>
				<table class="table1" border=1>
					<tr>
						<th rowspan="2">No</th><th rowspan="2">転送設定名</th><th rowspan="2">転送元URL</th><th colspan="3">転送先URL</th><th rowspan="2">転送有無</th>
					</tr>
					<tr>
						<th>PC</th><th>スマートフォン</th><th>タブレット</th>
					</tr>
					<?php 
						$loop_setting = 1;
						if(!empty($table)) {
							foreach($table as $table_el_key => $table_el) {
								if(!empty($table_el->target_url_pc)) {
									echo "<tr contentEditable=\"true\">";
									echo "<td id=\"id_".$loop_setting."_0"."\">".$loop_setting."</td>";
									echo "<td id=\"id_".$loop_setting."_1"."\">".$table_el->setting_name."</td>";
									echo "<td id=\"id_".$loop_setting."_2"."\">".$table_el_key."</td>";
									echo "<td id=\"id_".$loop_setting."_3"."\">".$table_el->target_url_pc."</td>";
									echo "<td id=\"id_".$loop_setting."_4"."\">".$table_el->target_url_sp."</td>";
									echo "<td id=\"id_".$loop_setting."_5"."\">".$table_el->target_url_tab."</td>";
									if($table_el->switch == 1) {
										$table_switch = "有";
									} else {
										$table_switch = "無";
									}
									echo "<td id=\"id_".$loop_setting."_6"."\">".$table_switch."</td>";
									echo "</tr>";

									$loop_setting++;
								}
							}
						}
						for($loop_setting2 = $loop_setting; $loop_setting2 <= 100; $loop_setting2++) {
							echo "<tr contentEditable=\"true\">";
							echo "<td id=\"id_".$loop_setting."_0"."\">".$loop_setting."</td>";
							echo "<td id=\"id_".$loop_setting."_1"."\"></td>";
							echo "<td id=\"id_".$loop_setting."_2"."\"></td>";
							echo "<td id=\"id_".$loop_setting."_3"."\"></td>";
							echo "<td id=\"id_".$loop_setting."_4"."\"></td>";
							echo "<td id=\"id_".$loop_setting."_5"."\"></td>";
							echo "<td id=\"id_".$loop_setting."_6"."\"></td>";
							echo "</tr>";

							$loop_setting++;
						}
					?>
				</table><br>
			</div>
		</form>

		<script type="text/javascript">
			var str_setting_count = <?php echo $loop_setting; ?>;
			var loop_setting;
			var loop_setting_in;
			var el, el_click;
			var el_col_in = {};

			for(loop_setting = 1; loop_setting < str_setting_count; loop_setting++) {
				for(loop_setting_in = 1; loop_setting_in <= 6; loop_setting_in++) {
					el = document.getElementById("id_" + loop_setting + "_" + loop_setting_in);
					el_col_in[(loop_setting - 1) * 7 + (loop_setting_in - 1)] = el;
				}
			}
			
			k = 0;
			for (j = 0; j < 7; j++) {
				l = 0;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 0 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 1 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 2 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 3 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 4 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 5 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 6 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 7 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 8 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 9 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 10 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 11 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 12 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 13 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 14 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 15 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 16 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 17 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 18 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 19 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 20 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 21 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 22 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 23 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 24 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 25 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 26 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 27 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 28 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 29 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 30 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 31 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 32 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 33 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 34 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 35 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 36 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 37 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 38 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 39 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 40 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 41 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 42 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 43 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 44 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 45 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 46 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 47 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 48 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 49 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 50 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 51 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 52 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 53 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 54 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 55 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 56 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 57 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 58 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 59 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 60 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 61 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 62 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 63 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 64 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 65 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 66 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 67 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 68 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 69 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 70 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 71 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 72 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 73 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 74 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 75 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 76 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 77 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 78 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 79 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 80 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 81 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 82 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 83 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 84 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 85 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 86 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 87 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 88 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 89 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 90 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 91 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 92 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 93 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 94 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 95 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 96 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 97 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 98 * 7;
					id_Click_in();
				};
				l++;
				el_col_in[j + 7 * l].onclick = function id_Click() {
					k = 99 * 7;
					id_Click_in();
				};
			}

			function id_Click_in() {
				document.forms.form_SPLIT.TRANSFER_SETTING_NAME.value = el_col_in[k + 0].innerText;
				document.forms.form_SPLIT.TRANSFER_SOURCE_URL.value = el_col_in[k + 1].innerText;
				document.forms.form_SPLIT.TRANSFER_TARGET_URL_PC.value = el_col_in[k + 2].innerText;
				document.forms.form_SPLIT.TRANSFER_TARGET_URL_SP.value = el_col_in[k + 3].innerText;
				document.forms.form_SPLIT.TRANSFER_TARGET_URL_TAB.value = el_col_in[k + 4].innerText;
				if(el_col_in[k + 5].innerText == "有") {
					document.getElementsByName("TRANSFER_SWITCH")[0].checked = true;
				} else if (el_col_in[k + 5].innerText == "無") {
					document.getElementsByName("TRANSFER_SWITCH")[1].checked = true;
				}
			}
		</script>
<?php
	}
	class Table{
		public $setting_name = "";
		public $target_url_pc = "";
		public $target_url_sp = "";
		public $target_url_tab = "";
		public $switch = "";
	}

	class UserAgent{
		private $ua;
		private $device;

		public function set($l_ua){
			$this->ua = $l_ua;

			if(strpos($this->ua,'iphone') !== false){
				$this->device = 'mobile';
			}elseif(strpos($this->ua,'ipod') !== false){
				$this->device = 'mobile';
			}elseif((strpos($this->ua,'android') !== false) && (strpos($this->ua, 'mobile') !== false)){
				$this->device = 'mobile';
			}elseif((strpos($this->ua,'windows') !== false) && (strpos($this->ua, 'phone') !== false)){
				$this->device = 'mobile';
			}elseif((strpos($this->ua,'firefox') !== false) && (strpos($this->ua, 'mobile') !== false)){
				$this->device = 'mobile';
			}elseif(strpos($this->ua,'blackberry') !== false){
				$this->device = 'mobile';

			}elseif(strpos($this->ua,'ipad') !== false){
				$this->device = 'tablet';
			}elseif((strpos($this->ua,'windows') !== false) && (strpos($this->ua, 'touch') !== false && (strpos($this->ua, 'tablet pc') == false))){
				$this->device = 'tablet';
			}elseif((strpos($this->ua,'android') !== false) && (strpos($this->ua, 'mobile') === false)){
				$this->device = 'tablet';
			}elseif((strpos($this->ua,'firefox') !== false) && (strpos($this->ua, 'tablet') !== false)){
				$this->device = 'tablet';
			}elseif((strpos($this->ua,'kindle') !== false) || (strpos($this->ua, 'silk') !== false)){
				$this->device = 'tablet';
			}elseif((strpos($this->ua,'playbook') !== false)){
				$this->device = 'tablet';

			}else{
				$this->device = 'others';
			}
		}

		public function get(){
			return $this->device;
		}
	}
?>