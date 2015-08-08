<?php
/*
Plugin Name: QuizLord
Plugin URI: 
Description: One Plugin to Rule Them All
Author: Nahapet N.
Author URI: 
Version: 2.0
*/
session_start();

function hex2rgba($color, $opacity = false) {
 
	$default = 'rgb(0,0,0)';
	if(empty($color))
          return $default;
        if ($color[0] == '#' ) {
        	$color = substr( $color, 1 );
        }
        if (strlen($color) == 6) {
                $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
                $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
                return $default;
        }
        $rgb =  array_map('hexdec', $hex);
        if($opacity){
        	if(abs($opacity) > 1)
        		$opacity = 1.0;
        	$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
        	$output = 'rgb('.implode(",",$rgb).')';
        }
        return $output;
}

function ql_create_database(){
	global $wpdb;
	
	$ql_quizzes_table = $wpdb->prefix."ql_quizzes";
	$ql_questions_table = $wpdb->prefix."ql_questions";
	$ql_answers_table = $wpdb->prefix."ql_answers";
	$ql_results_table = $wpdb->prefix."ql_results";
	
	$ql_quizzes_sql = "CREATE TABLE IF NOT EXISTS `".$ql_quizzes_table."` (
	  `id` int(10) NOT NULL AUTO_INCREMENT,
	  `name` varchar(100) NOT NULL,
	  `description` text NOT NULL,
	  `random` tinyint(4) NOT NULL DEFAULT '0',
	  `time` int(15) NOT NULL DEFAULT '0',
	  `skip` tinyint(4) NOT NULL DEFAULT '0',
	  `resume` tinyint(4) NOT NULL DEFAULT '1',
	  `right_color` varchar(7) NOT NULL DEFAULT '#00FF00',
	  `wrong_color` varchar(7) NOT NULL DEFAULT '#FF0000',
	  `numbering_type` varchar(15) NOT NULL,
	  `numbering_mark` varchar(3) NOT NULL,
	  `show_type` varchar(15) NOT NULL,
	  `back_button` tinyint(4) NOT NULL DEFAULT '0',
	  `autoload` tinyint(4) NOT NULL DEFAULT '0',
	  `check_continue` tinyint(4) NOT NULL DEFAULT '1',
	  `times_taken` int(7) NOT NULL,
	  `avg_points` int(15) NOT NULL DEFAULT '0',
	  `avg_percent` float NOT NULL DEFAULT '0',
	  PRIMARY KEY (`id`)
	);";
	
	$ql_update_sql = "DELIMITER $$
DROP PROCEDURE IF EXISTS upgrade_database_1_0_to_2_0 $$
CREATE PROCEDURE upgrade_database_1_0_to_2_0()
BEGIN

	IF NOT EXISTS( (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
    	AND COLUMN_NAME='date' AND TABLE_NAME='wp_ql_results') ) THEN
	ALTER TABLE `wp_ql_results` ADD `date` int(10);
	
END IF;
ALTER TABLE `wp_ql_quizzes` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `wp_ql_questions` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `wp_ql_answers` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE `wp_ql_results` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
END $$

CALL upgrade_database_1_0_to_2_0() $$
DELIMITER ;";

	$ql_questions_sql = "CREATE TABLE IF NOT EXISTS `".$ql_questions_table."` (
	  `id` int(14) NOT NULL AUTO_INCREMENT,
	  `quiz_id` int(10) NOT NULL,
	  `order` int(4) NOT NULL,
	  `title` varchar(100) NOT NULL,
	  `text` text NOT NULL,
	  `right_message` text NOT NULL,
	  `wrong_message` text NOT NULL,
	  `answer_random` tinyint(4) NOT NULL DEFAULT '0',
	  `answer_type` varchar(10) NOT NULL,
	  `points` int(20) NOT NULL,
	  PRIMARY KEY (`id`)
	);";
	
	$ql_answers_sql = "CREATE TABLE IF NOT EXISTS `".$ql_answers_table."` (
	  `id` int(17) NOT NULL AUTO_INCREMENT,
	  `text` varchar(200) NOT NULL,
	  `question_id` int(14) NOT NULL,
	  `order` int(4) NOT NULL,
	  `right_wrong` tinyint(4) NOT NULL,
	  PRIMARY KEY (`id`)
	);";
	
	$ql_results_sql = "CREATE TABLE IF NOT EXISTS `".$ql_results_table."` (
	    `id` int(17) NOT NULL AUTO_INCREMENT,
	  `user_id` int(10) NOT NULL,
	  `question_id` int(14) NOT NULL,
	  `quiz_id` int(10) NOT NULL,
	  `user_answer_numbers` varchar(15) NOT NULL,
	  `right_answer_numbers` varchar(15) NOT NULL,
	  `correctness_value` float NOT NULL,
	  `points` int(15) NOT NULL,
	  `completed` tinyint(4) NOT NULL,
	  `date` int(10) NOT NULL,
	  PRIMARY KEY (`id`)
	);";
	
	$wpdb->query($ql_quizzes_sql);
	$wpdb->query($ql_questions_sql);
	$wpdb->query($ql_answers_sql);
	$wpdb->query($ql_results_sql);
	$wpdb->query($ql_update_sql);
}

register_activation_hook(__FILE__, 'ql_create_database');


function ql_add_admin_menu(){
	add_menu_page('QuizLord', 'QuizLord', 'manage_options', 'quizlord', 'ql_show_quizzes', plugins_url('quizlord/styles/images/icon.png' ), 100);
}

add_action('admin_menu', 'ql_add_admin_menu');


function ql_load_scripts(){
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_style('jquery-ui', plugin_dir_url(__FILE__). 'styles/jquery-ui.min.css');
	wp_enqueue_script('ql-js', plugin_dir_url(__FILE__). 'js/ql.js');
	wp_enqueue_style('ql-css', plugin_dir_url(__FILE__). 'styles/style.css');
	
	wp_enqueue_script('ql-color', plugin_dir_url(__FILE__). 'js/jscolor.js');
}

add_action('admin_enqueue_scripts', 'ql_load_scripts');


function ql_load_client_scripts() {
	//wp_enqueue_script('jquery');
	wp_enqueue_script('ql_ajax', plugins_url('js/ql-ajax.js', __FILE__), array('jquery'));
	wp_enqueue_style('ql-css', plugin_dir_url(__FILE__). 'styles/style.css');
	//wp_localize_script('ql_ajax', 'MyAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
	if(is_singular()){
		wp_localize_script('ql_ajax', 'MyAjax', array('ajaxurl' => admin_url('admin-ajax.php'), 'singul' => is_single));
	}
	else{
		wp_localize_script('ql_ajax', 'MyAjax', array('ajaxurl' => admin_url('admin-ajax.php'), 'singul' => not_single));
	}
}

add_action('wp_enqueue_scripts', 'ql_load_client_scripts');


function ql_show_quizzes(){
	global $wpdb;
	$ql_quizzes = $wpdb->get_results("select * from ".$wpdb->prefix."ql_quizzes");
	foreach($ql_quizzes as $qlq){ $ql_ids[] = $qlq->id; }
	
	$ql_allquestions = $wpdb->get_results("select * from ".$wpdb->prefix."ql_questions");
	foreach($ql_allquestions as $qlqt){ $ql_qtids[] = $qlqt->id; }
	ob_start(); 
	
	if((empty($_GET['id']) || !in_array($_GET['id'], $ql_ids)) && 
	(empty($_GET['questionid']) || (!empty($_GET['questionid']) && !in_array($_GET['questionid'], $ql_qtids)))):
	?>
	
	
	<div id="tabs">
	<ul>
		<li><a href="#tabs-1">Show quizzes</a></li>
    	<li><a href="#tabs-2">Add a quiz</a></li>
	</ul>
	<div id="tabs-1">
	
	
	<h2>Show quizzes</h2>
	<form action="<?php echo admin_url('admin.php'); ?>" method="post">
	<input type="hidden" name="action" value="ql_quiz_delete">
	<table id="show" style="width: 900px;">
	<tr class="first"><td><input type="checkbox" name="delall" id="delall">ID</td><td>Name</td>
	<td>Times taken</td>
	<td>Average points</td>
	<td>Average results</td>
	<td>Shortcode</td>
	<td>Question count</td></tr>
	
	<?php
	foreach($ql_quizzes as $qlq): ?>
	<tr><td><input type="checkbox" value="<?php echo $qlq->id; ?>" name="del<?php echo $qlq->id; ?>" id="del<?php echo $qlq->id; ?>"><?php echo $qlq->id; ?></td>
	<td><a href="<?php echo admin_url("admin.php?page=".$_GET["page"])."&id=".$qlq->id; ?>"><?php echo $qlq->name; ?></a></td>
	<td><?php echo $qlq->times_taken; ?></td>
	<td><?php echo $qlq->avg_points; ?></td>
	<td><?php echo round($qlq->avg_percent, 2)."%"; ?></td>
	<td class="qlqid"><?php echo "[quizlord id=\"".$qlq->id."\"]"; ?></td>
	<td><?php echo $wpdb->query($wpdb->prepare("select * from ".$wpdb->prefix."ql_questions where quiz_id = %d",  $qlq->id )); ?></td>
	</tr>
	<?php endforeach; ?>
	</table>
	<p><input type="submit" class="button-primary" name="elete" value="Delete"></p>
	</form>
	
	
	</div>
	<div id="tabs-2">
	
	
	<h2>Add a quiz</h2>
	<form action="<?php echo admin_url('admin.php'); ?>" method="post">
		<input type="hidden" name="action" value="ql_insert">
		<p><label for="title">Title <i>(required)</i></label><br><input name="title" id="title"></p>
		
		<p><label for="description">Description</label><br>
		<textarea name="description" id="description" cols="65" rows="5"></textarea></p>
		
		<p><label for="time">Time (0 for unlimited)</label><br>
		<input type="number" name="time" id="time" min="0" value="0"></p>

		<p>Numbering type<br><select name="numbtype" id="numbtype">
			<option value="numerical">Numerical</option>
			<option value="alphabetical">Alphabetical</option>
			<option value="none">None</option>
		</select></p>
		
		<p><label for="numbmark">Numbering mark</label><br>
		<input name="numbmark" id="numbmark" maxlength="1"></p>
		
		<p>Right color: <input class="color" name="rightcolor" id="rightcolor" value="00ff00"></p>
		<p>Wrong color: <input class="color" name="wrongcolor" id="wrongcolor" value="ff0000"></p>
		<p>Show type<br><select name="showtype" id="showtype">
			<option value="paginated">Each question on its own page</option>
			<option value="allonone">All questions on one page</option>
		</select></p>
		
		<table style="width: 250px"><tr><td><label for="random">Random questions</label></td>
		<td><input type="checkbox" name="random" id="random"></td></tr>
		<tr><td><label for="skip">Skip question</label></td>
		<td><input type="checkbox" name="skip" id="skip"></td></tr>
		<tr><td><label for="resume">Resume question</label></td>
		<td><input type="checkbox" name="resume" id="resume"></td></tr>
		<tr><td><label for="backbtn">Back button</label></td>
		<td><input type="checkbox" name="backbtn" id="backbtn"></td></tr>
		<tr><td><label for="autoload">Autoload</label></td>
		<td><input type="checkbox" name="autoload" id="autoload"></td></tr>
		<tr><td><label for="checkcnt">Check-continue</label></td>
		<td><input type="checkbox" name="checkcnt" id="checkcnt"></td></tr></table>
		
		<p><input type="submit" class="button-primary" name="addquiz" id="addquiz" 
		value="Save"></p>
	</form>
	<div id="dialog" style="display:none;">Please enter quiz title!</div>
	</div>
	
	
	</div>
	<?php elseif(!empty($_GET['questionid']) && in_array($_GET['questionid'], $ql_qtids)): 
	//$ql_qtrow = $wpdb->get_row("select * from ".$wpdb->prefix."ql_questions where id = ".$_GET['questionid']);
	$ql_intid = (int) $_GET['questionid'];
	$ql_qtrow = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."ql_questions where id = %d", $ql_intid));
	
	$ql_quizintid = $ql_qtrow->quiz_id;
	$ql_quizrow = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."ql_quizzes where id = %d", $ql_quizintid));
	
	$wpdb->query($wpdb->prepare("select * from ".$wpdb->prefix."ql_answers where question_id = %d", $ql_intid));
	$ql_anscount = $wpdb->num_rows; ?>
	<div id="tabs">
	<ul>
		<li><a href="#tabs-6">Show/Edit question</a></li>	
	</ul>
	<div id="tabs-6">
	<h2>Show/Edit question</h2><h4><a href="<?php echo admin_url("admin.php?page=quizlord&id=$ql_quizintid"); ?>">← Back to quiz "<?php echo $ql_quizrow->name; ?>"</a></h4>
	<form action="<?php echo admin_url('admin.php'); ?>" method="post">
		<input type="hidden" name="action" value="ql_question_update">
		<input type="hidden" name="questionid" value="<?php echo $_GET['questionid']; ?>">
		<input type="hidden" name="quizid" value="<?php echo $ql_qtrow->quiz_id; ?>">
		<input type="hidden" name="order" value="<?php echo $ql_qtrow->order; ?>">
		<p><label for="title">Title</label><br><input name="title" id="title" value="<?php echo $ql_qtrow->title; ?>"></p>
		<p><label for="text">Question <i>(required)</i></label><br>
		<textarea name="text" id="text" cols="65" rows="5"><?php echo $ql_qtrow->text; ?></textarea></p>
		<p><label for="rightmsg">Correct message</label><br>
		<textarea name="rightmsg" id="rightmsg" cols="65" rows="5"><?php echo $ql_qtrow->right_message; ?></textarea></p>
		<p><label for="wrongmsg">Incorrect message</label><br>
		<textarea name="wrongmsg" id="wrongmsg" cols="65" rows="5"><?php echo $ql_qtrow->wrong_message; ?></textarea></p>
		<p><label for="ansrand">Random order    </label><input type="checkbox" name="ansrand" id="ansrand" <?php if($ql_qtrow->answer_random == 1){echo "checked";} ?>></p>
		<p>Answer type<br><select name="anstype" id="anstype">
			<option value="single">Sigle choice</option>
			<option value="multiple">Multiple choice</option>
		</select></p>
		<p><label for="points">Points</label><br>
		<input type="number" name="points" id="points" min="0"  value="<?php echo $ql_qtrow->points; ?>"></p>
		<div class="answers" id="answers">
		<?php for($i = 1; $i < $ql_anscount+1; $i++){
		$ql_answer = $wpdb->get_var($wpdb->prepare("select text from ".$wpdb->prefix.
		"ql_answers where `order` = %d and question_id = %d", $i, $ql_intid));
		$ql_right = $wpdb->get_var($wpdb->prepare("select `right_wrong` from ".$wpdb->prefix."ql_answers where `order` = %d and question_id = %d", $i, $ql_intid));
		echo "<p><label class='answer' for='answer$i'>Answer $i</label><br><input name='answer$i' id='answer$i' class='answer' value='$ql_answer'><button class='delete button'><span class='ui-icon ui-icon-trash'></span></button><button class='addanswer button'><span class='ui-icon ui-icon-plus'></span></button>";
		if($ql_qtrow->answer_type == 'single'){
			echo "<input type='radio' class='rw' id='rw$i' name='rw' value='answer$i'";
			if($ql_right == 1){ echo "checked"; }
			echo "></p>";
		}
		else {
			echo "<input type='checkbox' class='rw' id='rw$i' name='rw[]' value='answer$i'";
			if($ql_right == 1){ echo "checked"; }
			echo "></p>";
		}
		} ?>
		</div>
		<p><input type="submit" class="button-primary" name="addquestion" id="addquestion" 
		value="Update"></p>
	</form>
	<div id="dialog2" style="display:none;">Please enter question text!</div>
	<div id="dialog3" style="display:none;">Please fill all answers!</div>
	<div id="dialog4" style="display:none;">Please select correct answer(s)!</div>
	</div>
	</div>
	<?php 
	else: $ql_intid = (int) $_GET['id'];
	$ql_row = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."ql_quizzes where id = %d", $ql_intid)); ?>
	
	<div id="tabs">
	<ul>
		<li><a href="#tabs-3">Show questions</a></li>
    	<li><a href="#tabs-4">Add a question</a></li>
    	<li><a href="#tabs-5">Edit quiz</a></li>
	</ul>
	<div id="tabs-3">
	
	
	<h2><?php echo $ql_row->name; ?></h2><h4><a href="<?php echo admin_url('admin.php?page=quizlord'); ?>">← Back to quiz list</a></h4>
	<form action="<?php echo admin_url('admin.php'); ?>" method="post">
	<input type="hidden" name="action" value="ql_question_delete">
	<table id="show" style="width: 800px;">
	<tr class="first"><td><input type="checkbox" name="delall" id="delall">Order</td><td>Title</td>
	<td>Question</td>
	<td>Points</td></tr>
	<?php 
	$ql_questions = $wpdb->get_results($wpdb->prepare("select * from ".$wpdb->prefix.
	"ql_questions where quiz_id = %d order by `order`", $ql_intid));
	foreach($ql_questions as $qlqt): ?>
	<tr><td><input type="checkbox" name="del<?php echo $qlqt->id; ?>" id="del<?php echo $qlqt->id; ?>" value="<?php echo $qlqt->id; ?>"><?php echo $qlqt->order; ?></td><td><a href="<?php echo admin_url("admin.php?page=".$_GET["page"])."&questionid=".$qlqt->id; ?>"><?php echo strlen($qlqt->title) > 35 ? substr($qlqt->title, 0, 35)."..." : $qlqt->title; ?></a></td><td><a href="<?php echo admin_url("admin.php?page=".$_GET["page"])."&questionid=".$qlqt->id; ?>"><?php echo strlen($qlqt->text) > 35 ? substr($qlqt->text, 0, 35)."..." : $qlqt->text; ?></a></td><td><?php echo $qlqt->points; ?></td></tr>
	<?php endforeach; ?>
	</table>
	<p><input type="submit" class="button-primary" name="elete" value="Delete"></p>
	</form>
	
	</div>
	<div id="tabs-4">
	
	
	<h2>Add a question</h2>
	<form action="<?php echo admin_url('admin.php'); ?>" method="post">
		<input type="hidden" name="action" value="ql_question_insert">
		<input type="hidden" name="quizid" value="<?php echo $_GET['id']; ?>">
		<input type="hidden" name="order" value="">
		<p><label for="title">Title</label><br><input name="title" id="title"></p>
		<p><label for="text">Question <i>(required)</i></label><br>
		<textarea name="text" id="text" cols="65" rows="5"></textarea></p>
		<p><label for="rightmsg">Correct message</label><br>
		<textarea name="rightmsg" id="rightmsg" cols="65" rows="5"></textarea></p>
		<p><label for="wrongmsg">Incorrect message</label><br>
		<textarea name="wrongmsg" id="wrongmsg" cols="65" rows="5"></textarea></p>
		<p><label for="ansrand">Random order    </label><input type="checkbox" name="ansrand" id="ansrand"></p>
		<p>Answer type<br><select name="anstype" id="anstype">
			<option value="single">Sigle choice</option>
			<option value="multiple">Multiple choice</option>
		</select></p>
		<p><label for="points">Points</label><br>
		<input type="number" name="points" id="points" min="0" value="1"></p>
		<div class="answers" id="answers">
		<?php for($i = 1; $i < 5; $i++){
		echo "<p><label class='answer' for='answer$i'>Answer $i</label><br><input name='answer$i' id='answer$i' class='answer'><button class='delete button'><span class='ui-icon ui-icon-trash'></span></button><button class='addanswer button'><span class='ui-icon ui-icon-plus'></span></button><input type='radio' class='rw' id='rw$i' name='rw' value='answer$i'></p>";
		} ?>
		</div>
		<p><input type="submit" class="button-primary" name="addquestion" id="addquestion" 
		value="Add"></p>
	</form>
	<div id="dialog2" style="display:none;">Please enter question text!</div>
	<div id="dialog3" style="display:none;">Please fill all answers!</div>
	<div id="dialog4" style="display:none;">Please select correct answer(s)!</div>
	
	
	</div>
	<div id="tabs-5">
	
	
	<h2>Edit quiz</h2>
	<form action="<?php echo admin_url('admin.php'); ?>" method="post">
		<input type="hidden" name="action" value="ql_update">
		<input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
		<p><label for="title">Title <i>(required)</i></label><br><input name="title" id="title" value="<?php echo $ql_row->name; ?>"></p>
		
		<p><label for="description">Description</label><br>
		<textarea name="description" id="description" cols="65" rows="5"><?php echo $ql_row->description; ?></textarea></p>
		
		<p><label for="time">Time (0 for unlimited)</label><br>
		<input type="number" name="time" id="time" min="0" value="<?php echo $ql_row->time; ?>"></p>

		<p>Numbering type<br><select name="numbtype" id="numbtype">
			<option value="numerical">Numerical</option>
			<option value="alphabetical">Alphabetical</option>
			<option value="none">None</option>
		</select></p>
		
		<p><label for="numbmark">Numbering mark</label><br>
		<input name="numbmark" id="numbmark" maxlength="1"  value="<?php echo $ql_row->numbering_mark; ?>"></p>
		
		<p>Right color: <input class="color" name="rightcolor" id="rightcolor" value="<?php echo substr($ql_row->right_color, 1); ?>"></p>
		<p>Wrong color: <input class="color" name="wrongcolor" id="wrongcolor" value="<?php echo substr($ql_row->wrong_color, 1); ?>"></p>
		<p>Show type<br><select name="showtype" id="showtype">
			<option value="paginated">Each question on its own page</option>
			<option value="allonone">All questions on one page</option>
		</select></p>
		
		<table style="width: 250px"><tr><td><label for="random">Random questions</label></td>
		<td><input type="checkbox" name="random" id="random" <?php if($ql_row->random == 1){echo "checked";}?>></td></tr>
		<tr><td><label for="skip">Skip question</label></td>
		<td><input type="checkbox" name="skip" id="skip" <?php if($ql_row->skip == 1){echo "checked";}?>></td></tr>
		<tr><td><label for="resume">Resume question</label></td>
		<td><input type="checkbox" name="resume" id="resume" <?php if($ql_row->resume == 1){echo "checked";}?>></td></tr>
		<tr><td><label for="backbtn">Back button</label></td>
		<td><input type="checkbox" name="backbtn" id="backbtn" <?php if($ql_row->back_button == 1){echo "checked";}?>></td></tr>
		<tr><td><label for="autoload">Autoload</label></td>
		<td><input type="checkbox" name="autoload" id="autoload" <?php if($ql_row->autoload == 1){echo "checked";}?>></td></tr>
		<tr><td><label for="checkcnt">Check-continue</label></td>
		<td><input type="checkbox" name="checkcnt" id="checkcnt" <?php if($ql_row->check_continue == 1){echo "checked";}?>></td></tr></table>
		
		<p><input type="submit" class="button-primary" name="addquiz" id="addquiz" 
		value="Update"></p>
	</form>
	<div id="dialog" style="display:none;">Please enter quiz title!</div>
	</div>
	</div>
	<?php endif;
	echo ob_get_clean();
}

function ql_insert_quiz_data(){
	global $wpdb;
	if(!empty($_POST['title'])){
		$ql_title = $_POST['title'];
		$ql_description = $_POST['description'];
		$ql_time = $_POST['time'];
		$ql_rightcolor = "#".$_POST['rightcolor'];
		$ql_wrongcolor = "#".$_POST['wrongcolor'];
		$ql_numbtype = $_POST['numbtype'];
		$ql_numbmark = $_POST['numbmark'];
		$ql_showtype = $_POST['showtype'];
		
		$ql_random = isset($_POST['random']) ? 1 : 0;
		$ql_skip = isset($_POST['skip']) ? 1 : 0;
		$ql_resume = isset($_POST['resume']) ? 1 : 0;
		$ql_backbtn = isset($_POST['backbtn']) ? 1 : 0;
		$ql_autoload = isset($_POST['autoload']) ? 1 : 0;
		$ql_checkcnt = isset($_POST['checkcnt']) ? 1 : 0;
		
		$wpdb->insert($wpdb->prefix.'ql_quizzes',
		array(
			'name' => $ql_title,
			'description' => $ql_description,
			'time' => $ql_time,
			'right_color' => $ql_rightcolor,
			'wrong_color' => $ql_wrongcolor,
			'numbering_type' => $ql_numbtype,
			'numbering_mark' => $ql_numbmark,
			'show_type' => $ql_showtype,
			'random' => $ql_random,
			'skip' => $ql_skip,
			'resume' => $ql_resume,
			'autoload' => $ql_autoload,
			'back_button' => $ql_backbtn,
			'check_continue' => $ql_checkcnt
		));
	}
	wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
}

add_action('admin_action_ql_insert', 'ql_insert_quiz_data');


function ql_update_quiz_data(){
	global $wpdb;
	if(!empty($_POST['title'])){
		$ql_id = $_POST['id'];
		$ql_title = $_POST['title'];
		$ql_description = $_POST['description'];
		$ql_time = $_POST['time'];
		$ql_rightcolor = "#".$_POST['rightcolor'];
		$ql_wrongcolor = "#".$_POST['wrongcolor'];
		$ql_numbtype = $_POST['numbtype'];
		$ql_numbmark = $_POST['numbmark'];
		$ql_showtype = $_POST['showtype'];
		
		$ql_random = isset($_POST['random']) ? 1 : 0;
		$ql_skip = isset($_POST['skip']) ? 1 : 0;
		$ql_resume = isset($_POST['resume']) ? 1 : 0;
		$ql_backbtn = isset($_POST['backbtn']) ? 1 : 0;
		$ql_autoload = isset($_POST['autoload']) ? 1 : 0;
		$ql_checkcnt = isset($_POST['checkcnt']) ? 1 : 0;
		
		$wpdb->update($wpdb->prefix.'ql_quizzes', 
		array( 
			'name' => $ql_title,
			'description' => $ql_description,
			'time' => $ql_time,
			'right_color' => $ql_rightcolor,
			'wrong_color' => $ql_wrongcolor,
			'numbering_type' => $ql_numbtype,
			'numbering_mark' => $ql_numbmark,
			'show_type' => $ql_showtype,
			'random' => $ql_random,
			'skip' => $ql_skip,
			'resume' => $ql_resume,
			'autoload' => $ql_autoload,
			'back_button' => $ql_backbtn,
			'check_continue' => $ql_checkcnt 
		), 
		array('id' => $ql_id));
	}
	wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
}

add_action('admin_action_ql_update', 'ql_update_quiz_data');


function ql_insert_question_data(){
	global $wpdb;
	$ql_intqid = (int) $_POST['quizid'];
	$ql_last_order = $wpdb->get_row($wpdb->prepare("select `order` from ".$wpdb->prefix."ql_questions 
	where quiz_id = %d order by `order` desc limit 1", $ql_intqid));
	
	if(!empty($_POST['text'])){
		$ql_order = ($ql_last_order == null) ? 1 : $ql_last_order->order + 1;
		$ql_quizid = $_POST['quizid'];
		$ql_title = $_POST['title'];
		$ql_text = $_POST['text'];
		$ql_rightmsg = $_POST['rightmsg'];
		$ql_wrongmsg = $_POST['wrongmsg'];
		$ql_ansrand = isset($_POST['ansrand']) ? 1 : 0;
		$ql_anstype = $_POST['anstype'];
		$ql_points = $_POST['points'];
		
		$wpdb->insert($wpdb->prefix.'ql_questions',
		array(
			'quiz_id' => $ql_quizid,
			'order' => $ql_order,
			'title' => $ql_title,
			'text' => $ql_text,
			'right_message' => $ql_rightmsg,
			'wrong_message' => $ql_wrongmsg,
			'right_message' => $ql_rightmsg,
			'answer_random' => $ql_ansrand,
			'answer_type' => $ql_anstype,
			'points' => $ql_points
		));
		$ql_questionid = $wpdb->insert_id;
		foreach($_POST as $key => $val){
			if(strpos($key, 'answer') !== false){
				if($key == $_POST['rw'] || (is_array($_POST['rw']) && in_array($key, $_POST['rw']))) {
					$rw = 1;
				}
				else{
					$rw = 0;
				}
				$wpdb->insert($wpdb->prefix.'ql_answers',
				array(
					'text' => $val,
					'question_id' => $ql_questionid,
					'order' => substr($key, -1),
					'right_wrong' => $rw
				));
			}
		}
	}
	wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
}

add_action('admin_action_ql_question_insert', 'ql_insert_question_data');


function ql_update_question_data(){
	global $wpdb;
	
	if(!empty($_POST['text'])){
		$ql_questionid = (int) $_POST['questionid'];
		$ql_order = $ql_order = $_POST['order'];
		$ql_quizid = $_POST['quizid'];
		$ql_title = $_POST['title'];
		$ql_text = stripslashes($_POST['text']);
		$ql_rightmsg = stripslashes($_POST['rightmsg']);
		$ql_wrongmsg = stripslashes($_POST['wrongmsg']);
		$ql_ansrand = isset($_POST['ansrand']) ? 1 : 0;
		$ql_anstype = $_POST['anstype'];
		$ql_points = $_POST['points'];
		
		$wpdb->update($wpdb->prefix.'ql_questions',
		array(
			'order' => $ql_order,
			'title' => $ql_title,
			'text' => $ql_text,
			'right_message' => $ql_rightmsg,
			'wrong_message' => $ql_wrongmsg,
			'right_message' => $ql_rightmsg,
			'answer_random' => $ql_ansrand,
			'answer_type' => $ql_anstype,
			'points' => $ql_points
		),
		array('id' => $ql_questionid));
		
		$wpdb->query($wpdb->prepare("delete from ".$wpdb->prefix."ql_answers where question_id = %d", $ql_questionid));
		foreach($_POST as $key => $val){
			if(strpos($key, 'answer') !== false){
				if($key == $_POST['rw'] || (is_array($_POST['rw']) && in_array($key, $_POST['rw']))) {
					$rw = 1;
				}
				else{
					$rw = 0;
				}
				$wpdb->insert($wpdb->prefix.'ql_answers',
				array(
					'text' => $val,
					'question_id' => $ql_questionid,
					'order' => substr($key, -1),
					'right_wrong' => $rw
				));
			}
		}
	}
	wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
}

add_action('admin_action_ql_question_update', 'ql_update_question_data');


function ql_delete_quiz(){
	global $wpdb;
	
	$ql_del_ids = "";
	foreach($_POST as $key => $val){
		if(strpos($key, 'del') !== false){
			$ql_del_ids .= $val.",";
		}
	}
	$ql_del_ids = substr($ql_del_ids, 0, -1);
	
	$wpdb->query($wpdb->prepare("delete from ".$wpdb->prefix."ql_quizzes where id in (%s)", $ql_del_ids));
	
	$ql_del_questions = $wpdb->get_results($wpdb->prepare("select id from ".$wpdb->prefix."ql_questions where quiz_id in (%s)", $ql_del_ids));
	
	
	$ql_del_question_ids = "";
	foreach($ql_del_questions as $qldq){
		$ql_del_question_ids .= $qldq->id.",";
	}
	$ql_del_question_ids = substr($ql_del_question_ids, 0, -1);
	
	
	$wpdb->query($wpdb->prepare("delete from ".$wpdb->prefix."ql_answers where question_id in (%s)", $ql_del_question_ids));
	$wpdb->query($wpdb->prepare("delete from ".$wpdb->prefix."ql_questions where quiz_id in (%s)", $ql_del_ids));
	$wpdb->query($wpdb->prepare("delete from ".$wpdb->prefix."ql_results where quiz_id in (%s)", $ql_del_ids));
	
	wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
}

add_action('admin_action_ql_quiz_delete', 'ql_delete_quiz');


function ql_delete_question(){
	global $wpdb;
	
	foreach($_POST as $key => $val){
		if(strpos($key, 'del') !== false){
			$ql_del_ids[] = (int) $val;
		}
	}
	
	$ql_quizid = $_POST['quizid'];
	
	foreach($ql_del_ids as $qld){
		$qlord = $wpdb->get_var($wpdb->prepare("select `order` from ".$wpdb->prefix."ql_questions where id = %d", $qld));
		
		$wpdb->query($wpdb->prepare("delete from ".$wpdb->prefix."ql_questions where id = %d", $qld));
		$wpdb->query($wpdb->prepare("update ".$wpdb->prefix."ql_questions set `order` = `order` - 1 
		where `order` > %s and quiz_id = $ql_quizid", $qlord));
		$wpdb->query($wpdb->prepare("delete from ".$wpdb->prefix."ql_answers where question_id = %d", $qld));
		$wpdb->query($wpdb->prepare("delete from ".$wpdb->prefix."ql_results where question_id = %d", $qld));
	}	
	
	wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
}

add_action('admin_action_ql_question_delete', 'ql_delete_question');


function ql_arraytostr($ql_arr){
	$qlt = '';
	if(is_array($ql_arr)){
		foreach($ql_arr as $qla){
			$qlt .= $qla." ";
		}
		$qlt = trim($qlt);
		return $qlt;
	}
	else{
		return $ql_arr;
	}
}

function ql_custom_shortcode($atts) {
	global $wpdb;
	$atts =  shortcode_atts(
		array('id' => ''), $atts, 'quizlord');
	
	$ql_userid = get_current_user_id();
	$ql_quizid = isset($_POST['quizid']) ? (int) $_POST['quizid'] : (int) $atts['id'];
	$ql_quiz = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."ql_quizzes where id = %d", $ql_quizid));
	$ql_all_questions = $wpdb->get_results($wpdb->prepare("select * from ".$wpdb->prefix."ql_questions where quiz_id = %d", $ql_quizid));
	$ql_alphabet = array('0','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	
	$ql_finished = $wpdb->get_var($wpdb->prepare("select completed from ".$wpdb->prefix."ql_results 
	where quiz_id = %d and user_id = %d order by id desc limit 1", $ql_quizid, $ql_userid));
	$ql_resume_id = $wpdb->get_var($wpdb->prepare("select question_id from ".$wpdb->prefix."ql_results 
	where quiz_id = %d and user_id = %d order by id desc limit 1", $ql_quizid, $ql_userid));
	$ql_resume_order = $wpdb->get_var($wpdb->prepare("select `order` from ".$wpdb->prefix."ql_questions 
	where id = %d order by id desc limit 1", $ql_resume_id));

	if($ql_quiz->show_type == 'paginated'):
		if($_POST['send']=='Back'){
			$ql_question_number = isset($_POST['number']) ? $_POST['number']-2 : (($ql_quiz->autoload == 1) ? 1 : 0);
		  $ql_order = ($ql_quiz->random == 0) ? (isset($_POST['order']) ? $_POST['order']-2 : (($ql_quiz->autoload == 1) ? 1 : 0)) 
		  : rand(1, count($ql_all_questions)-1);
		}
		else if($_POST['send']=='Check'){
			$ql_question_number = isset($_POST['number']) ? $_POST['number']-1 : (($ql_quiz->autoload == 1) ? 1 : 0);
		  $ql_order = isset($_POST['order']) ? $_POST['order']-1 : (($ql_quiz->autoload == 1) ? 1 : 0);
		}
		else if($_POST['send']=='Resume'){
			$ql_question_number = ($ql_quiz->autoload == 1) 
			? ($ql_finished === '0' ? $ql_resume_order+1 : 1) : ($ql_finished === '0' ? $ql_resume_order : 0);
			$ql_order = ($ql_quiz->random == 0) ? (($ql_quiz->autoload == 1) 
			? ($ql_finished === '0' ? $ql_resume_order+1 : 1) : ($ql_finished === '0' ? $ql_resume_order : 0))
			: rand(1, count($ql_all_questions)-1);
		}
		else{
			$ql_question_number = isset($_POST['number']) ? ($_POST['send'] == 'Start' ? 1 : $_POST['number'])
			: (($ql_quiz->autoload == 1) ? 1 : 0);
			$ql_order = ($ql_quiz->random == 0) ? (isset($_POST['order']) ? 
			($_POST['send'] == 'Start' ? 1 : $_POST['order']) : (($ql_quiz->autoload == 1) ? 1 : 0)) 
			: rand(1, count($ql_all_questions)-1);
			if($_POST['send'] == 'Start') $ql_resume_order = 0;
		}
		
		$ql_order = (int) $ql_order;
		$ql_right_answers = '';
		$ql_user_answers = '';
		$ql_completed = 0;
		
		$ql_overall_points = 0;
		foreach($ql_all_questions as $qlqu){
			$ql_overall_points += $qlqu->points;
		}

		
	/*------------------------------------------------------------------------------------------------*/
		$ql_current_question = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."ql_questions 
				where quiz_id = %d and `order` = %d", $ql_quizid, $ql_order));
		$ql_previous_question = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."ql_questions 
				where quiz_id = %d and `order` = %d", $ql_quizid, $ql_order-1));
		
		$ql_cintid = $ql_current_question->id;
		$ql_pintid = $ql_previous_question->id;
		
		$ql_current_question_options = $wpdb->get_results($wpdb->prepare("select * from ".$wpdb->prefix."ql_answers 
				where question_id = %d", $ql_cintid));
				
		$ql_right_answers_order = $wpdb->get_results($wpdb->prepare("select `order` from ".$wpdb->prefix."ql_answers 
				where question_id = %d and right_wrong = 1", $ql_pintid));
		$ql_right_answers_order2 = $wpdb->get_results($wpdb->prepare("select `order` from ".$wpdb->prefix."ql_answers 
				where question_id = %d and right_wrong = 1", $ql_cintid));
		$ql_prev_id = $_POST['qlo'];
	/*------------------------------------------------------------------------------------------------*/
		if((isset($_POST['number']) || $ql_quiz->autoload == 1) && 
		($ql_question_number != $ql_resume_order || $_POST['send'] == 'Back' || $_POST['send'] == 'Resume' || $_POST['send'] == 'Check' || $_POST['send'] == 'Next') && !($ql_quiz->autoload == 1 && !isset($_POST['send']) && $ql_finished === '0')):
		$ql_correct = $wpdb->get_var($wpdb->prepare("select correctness_value from ".$wpdb->prefix."ql_results 
			where question_id = %d and user_id = %d order by id desc limit 1", $ql_cintid, $ql_userid));
		
		if($ql_question_number <= count($ql_all_questions)):
		ob_start(); ?>
		<div class="ql-question">
		<h3 class="ql-name"><?php echo stripslashes($ql_quiz->name); ?></h3>
		<h3 class="ql-number"><?php echo "Question $ql_order of ".count($ql_all_questions); ?></h3>
		<?php if(is_singular() || $_POST['singul'] == 'is_single'): ?>
		<h5 class="ql-text"><?php echo stripslashes($ql_current_question->text); ?></h5>
		<form action="" method="POST" id="ql-start">
			<input type="hidden" name="action" value="ql_custom_shortcode">
			<input type="hidden" name="quizid" value="<?php echo $ql_quizid; ?>">
			<input type="hidden" name="order" 
			value="<?php echo /*($ql_quiz->random == 0) ?*/ $ql_order+1 /*: rand(1, count($ql_all_questions)-1)*/; ?>">
			<input type="hidden" name="number" value="<?php echo $ql_question_number + 1; ?>">
			<input type="hidden" name="skip" value="<?php echo $ql_quiz->skip; ?>" id="skip">			
			<input type="hidden" name="checkcnt" value="<?php echo $ql_quiz->check_continue; ?>" id="checkcnt">
			<input type="hidden" name="qlo" value="<?php echo $ql_current_question->id; ?>">
			<?php $i = 1;
			foreach($ql_current_question_options as $qcqo){
				if($ql_current_question->answer_type == 'single'){
					if($ql_quiz->check_continue != 1 || $_POST['send'] != 'Check'){
						echo "<p class='ql-option'><input type='radio' name='qlo$ql_current_question->id' 
						id='c$qcqo->id' class='qlo' value='$qcqo->order'>";
					}
					if(isset($_POST['send']) && $_POST['send'] == 'Check'){
			
						//foreach($ql_right_answers_order2 as $qrao){
						$ql_right_answers3 = $ql_right_answers_order2[0]->order;
						//}
						$ql_user_answers3 = ql_arraytostr( $_POST['qlo'.$ql_prev_id]);
						$ql_correctness_value2 = (trim($ql_right_answers3) == trim($ql_user_answers3));
						if($ql_correctness_value2 == 1){
							if($i == $ql_right_answers3){
								$coll = hex2rgba($ql_quiz->right_color, 0.5);
								echo "<p style='background: $coll;padding: 5px;margin-bottom: 5px;'>";
							}
						}
						else{
							if($i == $ql_user_answers3){
								$coll = hex2rgba($ql_quiz->wrong_color, 0.5);
								echo "<p style='background: $coll;padding: 5px;margin-bottom: 5px;'>";
							}
							if($i == $ql_right_answers3){
								$coll = hex2rgba($ql_quiz->right_color, 0.5);
								echo "<p style='background: $coll;padding: 5px;margin-bottom: 5px;'>";
							}
						}
					}
					
				}
				else{
					if($ql_quiz->check_continue != 1 || $_POST['send'] != 'Check'){
						echo "<p class='ql-option'><input type='checkbox' name='qlo$ql_current_question->id[]'
					 	id='c$qcqo->id' class='qlo' value='$qcqo->order'>";
					}
				}
				if(!(isset($_POST['send']) && $_POST['send'] == 'Check')){
				echo "<label for='c$qcqo->id'>";}else{
					echo "<label for='c$qcqo->id' style='display:block'>";
				}
				if ($ql_quiz->numbering_type == 'numerical') echo $i.$ql_quiz->numbering_mark." ";
				if ($ql_quiz->numbering_type == 'alphabetical') echo $ql_alphabet[$i].$ql_quiz->numbering_mark." "; 
				echo $qcqo->text."</label></p>";
				$i++;
			} ?>
			<p><b class="ql-points"><?php echo "Points: $ql_current_question->points"; ?></b></p>
			<?php if($_POST['send'] == 'Check'){
				foreach($_POST as $key => $val){
					if(strpos($key, 'qlo') !== false){
						$val = ql_arraytostr($val);
						echo "<input type='hidden' name='$key' value='$val'>";
					}
				}
			} ?>
			<?php if($ql_question_number < count($ql_all_questions)): ?>
			<?php if($ql_quiz->check_continue != 1 || $_POST['send'] == 'Check'): ?>
			<input type="submit" name="next" value="Next" id="next" class="ql-btn">
			<?php endif; ?>
				<?php if($ql_quiz->back_button == 1 && $ql_question_number > 1): ?>
				<br><input type="submit" name="back" value="Back" id="back" class="ql-btn">
				<?php endif; ?>
			<?php else: if($ql_quiz->check_continue != 1 || $_POST['send'] == 'Check'): ?>
			<input type="submit" name="finish" value="Finish" id="finish" class="ql-btn">
			<?php endif; endif;?>
			<?php if($ql_quiz->check_continue == 1 && $_POST['send'] != 'Check'): ?>
			<input type="submit" name="check" value="Check" id="check" class="ql-btn">
			<?php endif; ?>
		</form>
		<?php 
		if(isset($_POST['send']) && $_POST['send'] == 'Check'){
			
			foreach($ql_right_answers_order2 as $qrao){
				$ql_right_answers2 .= $qrao->order." ";
			}
			$ql_user_answers2 = ql_arraytostr( $_POST['qlo'.$ql_prev_id]);
			$ql_correctness_value2 = (trim($ql_right_answers2) == trim($ql_user_answers2));
			
			if($ql_user_answers2 == ''){
				echo "<div style='color: blue'>No answer selected!</div>";
			}
			elseif($ql_correctness_value2 == true){
				echo "<div style='color: $ql_quiz->right_color'>Correct<br>$ql_current_question->right_message</div>";
			}
			else{
				echo "<div style='color: $ql_quiz->wrong_color'>Incorrect<br>$ql_current_question->wrong_message</div>";
			}
		} ?>
		</div> 
		<?php if($ql_quiz->time != 0 && $ql_question_number == 1 && $_POST['send'] != 'Back' && $_POST['send'] != 'Check'): ?>
		<p class="ql-time" id="ql-time"><?php echo $ql_quiz->time; ?></p>
		<?php endif; endif;?>
		
		<?php echo ob_get_clean();
		endif;
		if($ql_question_number > 1 && $_POST['send'] != 'Check'):
			foreach($ql_right_answers_order as $qrao){
				$ql_right_answers .= $qrao->order." ";
			}
			$ql_user_answers = isset($_POST['qlo'.$ql_prev_id]) ? ql_arraytostr($_POST['qlo'.$ql_prev_id]) : -1;
			$ql_correctness_value = isset($_POST['qlo'.$ql_prev_id]) ? (trim($ql_right_answers) == trim($ql_user_answers)) : -1;
			if($_POST['send'] == 'Resume'){
				$_SESSION['ql_points'] = $wpdb->get_var($wpdb->prepare("select points from ".$wpdb->prefix."ql_results 
			where question_id = %d and user_id = %d order by id desc limit 1", $ql_cintid, $ql_userid));
			}
			if($ql_correctness_value == 1){
				$_SESSION['ql_points'] = $_SESSION['ql_points'] + $ql_previous_question->points;
			}
			if($_POST['send'] == 'Back'){
				$_SESSION['ql_points'] = $_SESSION['ql_points'] - $ql_current_question->points;
			}
			if($ql_question_number == count($ql_all_questions)+1){
				$ql_completed = 1;
			}
		endif;
		if($ql_question_number > 1 && $_POST['send'] != 'Check' && $ql_question_number > 1 
		&& $_POST['send'] != 'Back' && $_POST['send'] != 'Resume'):
			$ql_date = time();
			//var_dump($ql_date);
			$wpdb->insert($wpdb->prefix.'ql_results',
				array(
					'user_id' => $ql_userid,
					'question_id' => $ql_prev_id,
					'quiz_id' => $ql_quizid,
					'user_answer_numbers' => trim($ql_user_answers),
					'right_answer_numbers' => trim($ql_right_answers),
					'correctness_value' => $ql_correctness_value,
					'points' => $_SESSION['ql_points'],
					'completed' => $ql_completed,
					'date' => $ql_date
				));
		endif;
		endif;
		if($ql_question_number < 1 || ($ql_finished === '0' && $ql_quiz->resume == 1 
		&& $ql_question_number == $ql_resume_order && $_POST['send'] != 'Back' && $_POST['send'] != 'Resume' && $_POST['send'] != 'Check' && $_POST['send'] != 'Next') || ($ql_quiz->autoload == 1 && !isset($_POST['send']) && $ql_finished === '0')):
		$_SESSION['ql_points'] = 0;
		ob_start(); ?>
		<div class="ql-question">
		<h3 class="ql-name"><?php echo stripslashes($ql_quiz->name); ?></h3>
		<?php if(is_singular()): ?>
		<h5 class='ql-description'><?php echo stripslashes($ql_quiz->description); ?></h5>
		<form action="" method="POST" id="ql-start">
			<input type="hidden" name="action" value="ql_custom_shortcode">
			<input type="hidden" name="quizid" value="<?php echo $ql_quizid; ?>">
			<input type="hidden" name="order" value="<?php echo $ql_order + 1; ?>">
			<input type="hidden" name="number" value="<?php echo $ql_question_number + 1; ?>">
			<input type="hidden" name="skip" value="<?php echo $ql_quiz->skip; ?>" id="skip">
			<input type="submit" name="start" value="Start" id="start" class="ql-btn">
			<?php if($ql_quiz->resume == 1 && $ql_quiz->time == 0 && $ql_finished === '0' && $ql_quiz->random == 0): ?>
			<input type="submit" name="resume" value="Resume" id="resume" class="ql-btn">
			<?php endif; ?>
		</form>
		</div>
		<?php if($ql_quiz->time != 0): ?>
		<div id="ql-time" style="display: none;"><?php echo $ql_quiz->time; ?></div>
		<?php endif; endif; ?>
		
		<?php echo ob_get_clean();
		endif;
		
		$ql_score = round(($_SESSION['ql_points']/$ql_overall_points)*100, 2);
		
		if($ql_question_number > count($ql_all_questions)){
			
			$wpdb->query($wpdb->prepare("update ".$wpdb->prefix."ql_quizzes set times_taken = times_taken+1 where id = %d", $ql_quizid));
			$ql_tt = $wpdb->get_var($wpdb->prepare("select times_taken from ".$wpdb->prefix."ql_quizzes where id = %d", $ql_quizid));
			$ql_sess = $_SESSION['ql_points'];
			
			$wpdb->query($wpdb->prepare("update ".$wpdb->prefix."ql_quizzes set avg_points = 
				(avg_points * %d + %d) / %d where id = %d", $ql_tt - 1, $ql_sess, $ql_tt, $ql_quizid));
		
			$wpdb->query($wpdb->prepare("update ".$wpdb->prefix."ql_quizzes set avg_percent = 
				(avg_percent * %d + %d) / %d where id = %d", $ql_tt - 1, $ql_score, $ql_tt, $ql_quizid));
		
			echo "<div class='ql-finished'><h3 class='ql-score'>Overall points: $_SESSION[ql_points] of $ql_overall_points<br>Score: $ql_score%</h2>";
			foreach($ql_all_questions as $qlaq){
				$ql_correct = $wpdb->get_var($wpdb->prepare("select correctness_value from ".$wpdb->prefix."ql_results 
				where question_id = %d and user_id = %d order by id desc limit 1", $qlaq->id, $ql_userid));
				$ql_right_num = $wpdb->get_var($wpdb->prepare("select right_answer_numbers from ".$wpdb->prefix."ql_results 
				where question_id = %d  and user_id = %d order by id desc limit 1", $qlaq->id, $ql_userid));
				$ql_user_num = $wpdb->get_var($wpdb->prepare("select user_answer_numbers from ".$wpdb->prefix."ql_results 
				where question_id = %d  and user_id = %d order by id desc limit 1", $qlaq->id, $ql_userid));
				echo "<div class='ql-fsingle'><h5 class='ql-text'>".stripslashes($qlaq->text)."</h5>";
				if($ql_correct == 1){
					echo "<p style='color: $ql_quiz->right_color'>Right</p><p style='color: $ql_quiz->right_color'>$qlaq->right_message</p></div>";
				}
				elseif($ql_correct == 0){
					echo "<p style='color: $ql_quiz->wrong_color'>Wrong</p><span style='color: $ql_quiz->wrong_color'><strike>$ql_user_num</strike>
					</span><span style='color: $ql_quiz->right_color'>$ql_right_num</span></p><p style='color: $ql_quiz->wrong_color'>$qlaq->wrong_message</p></div>";
				}
				else{
					echo "<p style='color: blue'>Not answered</p></div>";
				}
			}
			echo "<a href=''><input type='button' name='restart' id='restart' class='next' value='Restart quiz'></a></div>";
		}
		ob_start(); ?>
		
		<?php echo ob_get_clean();
	else:
	
	if(!isset($_POST['send'])):
	
	ob_start(); ?>
	<div class="ql-question">
	<h3 class="ql-name"><?php echo stripslashes($ql_quiz->name); ?></h3>
	<?php if(is_singular()): ?>
	<h5 class="ql-description"><?php echo stripslashes($ql_quiz->description); ?></h5>
	<form method="post">
	<input type="hidden" name="action" value="ql_custom_shortcode">
	<input type="hidden" name="quizid" value="<?php echo $ql_quizid; ?>">
	<input type="hidden" name="skip" value="<?php echo $ql_quiz->skip; ?>" id="skip">
	<?php foreach($ql_all_questions as $qlaq): ?>
		<div class="ql-single">
			<?php 
			echo "<h5 class='ql-text'>".$qlaq->text."</h5>";
			$qlaq_options = $wpdb->get_results($wpdb->prepare("select * from ".$wpdb->prefix."ql_answers 
				where question_id = %d", $qlaq->id));
			$i = 1;
			foreach($qlaq_options as $qcqo){
				if($qlaq->answer_type == 'single'){
					echo "<p class='ql-option'><input type='radio' name='qlo$qlaq->id' id='c$qcqo->id' class='qlo' value='$qcqo->order'>";
				}
				else{
					echo "<p class='ql-option'><input type='checkbox' name='qlo$qlaq->id[]' id='c$qcqo->id' class='qlo' value='$qcqo->order'>";
				}
				echo "<label for='c$qcqo->id'>";
				if ($ql_quiz->numbering_type == 'numerical') echo $i.$ql_quiz->numbering_mark." ";
				if ($ql_quiz->numbering_type == 'alphabetical') echo $ql_alphabet[$i].$ql_quiz->numbering_mark." "; 
				echo $qcqo->text."</label></p>";
				$i++;
			} ?>
			<input type="hidden" name="qlk<?php echo $qlaq->id; ?>" value="-1">
			<p><b class="ql-points"><?php echo "Points: $qlaq->points"; ?></b></p>
		</div>
	<?php endforeach; ?>
	<input type="submit" name="finish" value="Finish" id="finish">
	</form>
	</div>
	<?php if($ql_quiz->time != 0): ?>
	<p class="ql-time" id="ql-time"><?php echo $ql_quiz->time; ?></p>
	<?php endif; endif;?>
	<?php
	echo ob_get_clean();
	else: 
	$ql_points = 0;
	$i = 0;
	
	$ql_overall_points = 0;
	foreach($ql_all_questions as $qlqu){
		$ql_overall_points += $qlqu->points;
	}
	
	foreach($_POST as $key => $val){
		
		if(strpos($key, 'qlk') !== false){
			$ql_key = intval(substr($key, 3));
			$ql_right = $wpdb->get_results($wpdb->prepare("select `order` from ".$wpdb->prefix."ql_answers 
			where question_id = %d and right_wrong = 1", $ql_key));
			$ql_single = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."ql_questions 
			where id = %d", $ql_key));
	
			$ql_right_answers = '';
			
			foreach($ql_right as $qlr){
				$ql_right_answers .= $qlr->order." ";
			}
			
			if(isset($_POST['qlo'.substr($key, 3)])){
				$ql_user_answers = ql_arraytostr($_POST['qlo'.substr($key, 3)]);
				$ql_correctness_value = (trim($ql_right_answers) == trim($ql_user_answers));
			}
			else{
				$ql_user_answers = -1;
				$ql_correctness_value = -1;
			}
			if($ql_correctness_value == 1){
				$ql_points += $ql_single->points;
			}
			$i++;
			
			$ql_score = ($ql_points / $ql_overall_points)*100;
			if($i == count($ql_all_questions)){
				$ql_completed = 1;
				
				$wpdb->query($wpdb->prepare("update ".$wpdb->prefix."ql_quizzes set times_taken = times_taken+1 where id = %d", $ql_quizid));
				$ql_tt = $wpdb->get_var($wpdb->prepare("select times_taken from ".$wpdb->prefix."ql_quizzes where id = %d", $ql_quizid));
			
				$wpdb->query($wpdb->prepare("update ".$wpdb->prefix."ql_quizzes set avg_points = 
				(avg_points * %d + %d) / %d where id = %d", $ql_tt-1, $ql_points, $ql_tt, $ql_quizid));
			
				$wpdb->query($wpdb->prepare("update ".$wpdb->prefix."ql_quizzes set avg_percent = 
				(avg_percent * %d + %d) / %d where id = %d", $ql_tt-1, $ql_score, $ql_tt, $ql_quizid));
			}
			
			$wpdb->insert($wpdb->prefix.'ql_results',
				array(
					'user_id' => $ql_userid,
					'question_id' => substr($key, 3),
					'quiz_id' => $ql_quizid,
					'user_answer_numbers' => trim($ql_user_answers),
					'right_answer_numbers' => trim($ql_right_answers),
					'correctness_value' => $ql_correctness_value,
					'points' => $ql_points,
					'completed' => $ql_completed
				));	
		}	
	}
	echo "<div class='ql-finished'><h3 class='ql-score'>Overall points: $ql_points of $ql_overall_points<br>Score: ".round($ql_score, 2)."%</h3>";
	foreach($ql_all_questions as $qlaq){
			echo "<div class='ql-fsingle'><h5 class='ql-text'>".$qlaq->text."</h5>";

			$ql_correct = $wpdb->get_var($wpdb->prepare("select correctness_value from ".$wpdb->prefix."ql_results 
			where question_id = %d  and user_id = %d order by id desc limit 1", $qlaq->id, $ql_userid));
			$ql_right_num = $wpdb->get_var($wpdb->prepare("select right_answer_numbers from ".$wpdb->prefix."ql_results 
			where question_id = %d  and user_id = %d order by id desc limit 1", $qlaq->id, $ql_userid));
			$ql_user_num = $wpdb->get_var($wpdb->prepare("select user_answer_numbers from ".$wpdb->prefix."ql_results
			 where question_id = %d  and user_id = %d order by id desc limit 1", $qlaq->id, $ql_userid));
			
			if($ql_correct == 1){
				echo "<div style='color: $ql_quiz->right_color'>Correct<br>".$qlaq->right_message."</div></div>";
			}
			elseif($ql_correct == 0){
				echo "<div><span style='color: $ql_quiz->wrong_color'><strike>$ql_user_num</strike></span><span style='color: $ql_quiz->right_color'>$ql_right_num</span></p><p style='color: $ql_quiz->wrong_color'>Incorrect<br>".$qlaq->wrong_message."</div></div>";
			}
			else{
				echo "<div style='color: blue'>Not answered</div></div>";
			}
		}
		echo "<a href=''><input type='button' name='submit' class='next' id='restart' value='Restart quiz'></a></div>";
	endif;
	endif;
}

add_shortcode('quizlord', 'ql_custom_shortcode');
add_action('wp_ajax_ql_custom_shortcode', 'ql_custom_shortcode');
add_action('wp_ajax_nopriv_ql_custom_shortcode', 'ql_custom_shortcode');