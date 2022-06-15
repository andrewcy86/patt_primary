<?php

global $wpdb, $current_user, $wpscfunction;

$WP_PATH = implode("/", (explode("/", $_SERVER["PHP_SELF"], -8)));
require_once($_SERVER['DOCUMENT_ROOT'].$WP_PATH.'/wp/wp-load.php');
?>

<style>
* {
  box-sizing: border-box;
}

/* Create two unequal columns that floats next to each other */
.column {
  float: left;
  padding: 10px;
  height: 100%;
}

.left {
  width: 25%;
}

.right {
  width: 75%;
}

/* Clear floats after the columns */
.row:after {
  content: "";
  display: table;
  clear: both;
}
  
button {
  background: none!important;
  border: none;
  padding: 0!important;
  /*optional*/
  font-family: arial, sans-serif;
  /*input has OS specific font-family*/
  color: #069;
  text-decoration: underline;
  cursor: pointer;
}

.highlight {
  color: #FF0000;
  text-decoration: line-through;
  }

</style>

<?php

if(isset($_POST['postvarsobjects'])){
    $object_key = $_POST['postvarsobjects'];
    $str_arr = explode (",", $object_key); 
?>

<div class="row">
  <div class="column left">

<?php
$i = 0;
$root = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';
foreach ($str_arr as $value) {
	
  	$val_final = trim($value);
    $pieces = explode("/", $val_final);
    $office = $pieces[0];
    $obj_key = $pieces[1];

    echo "<button class='button_process' onclick='$(this).addClass(\"highlight\");$(\"#frame\").attr(\"src\", this.value);' id='button" . $i . "' value='" . $root . "arms-validation-test/?group_name=" . $office . "&object_key=" . $obj_key . "'>". $val_final . "</button><br />";

    $i++;
}
?>  
  </div>
  <div class="column right">
  <iframe id="frame" src="" width="100%" height="100%">
     </iframe>
  </div>
</div>
<?php
} else {
    echo "error";
}
?>
