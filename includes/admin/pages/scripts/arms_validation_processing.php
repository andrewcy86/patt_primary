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
</style>

<script
  src="https://code.jquery.com/jquery-3.6.0.min.js"
  integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
  crossorigin="anonymous"></script>

<?php

if(isset($_POST['postvarsobjects'])){
    $object_key = $_POST['postvarsobjects'];
    $str_arr = explode (",", $object_key); 
?>

<div class="row">
  <div class="column left">

<?php
$i = 0;

foreach ($str_arr as $value) {

    $pieces = explode("/", $value);
    $office = $value[0];
    $obj_key = $value[1];

    echo "<button id='button" + i + "' value='https://pattawsstg01.aws.epa.gov/arms-validation-test/?group_name=" . $office . "&object_key=" . $obj_key . "'>". $value . "</button><br />";

    $i++;
}
?>  
  </div>
  <div class="column right">
  <iframe id="frame" src="" width="100%" height="100%">
     </iframe>
  </div>
</div>

<script>
    $("button").click(function () {
        $("#frame").attr("src", this.value);
    });
</script>
<?php
} else {
    echo "error";
}
?>