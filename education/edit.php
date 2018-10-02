<?php
require_once "pdo.php";
require_once "util.php";

session_start();


if (! isset($_SESSION['user_id'])) {
	die("ACCSESS DENIED");
    return;
}

if (isset($_POST['cancel'])) {
    header('Location: index.php');
    return;
}

// Make sure the REQUEST parameter is present
if (! isset($_REQUEST['profile_id'])) {
    $_SESSION['error'] = "Missing profile_id";
    header('Location: index.php');
    return;
}


if (isset($_POST['save'])) {
	
	if (strlen($_POST['first_name']) < 1 || strlen($_POST['last_name']) < 1 || strlen($_POST['email']) < 1
     	|| strlen($_POST['headline']) < 1 || strlen($_POST['summary']) < 1) {
		$_SESSION['error'] = "All fields are required";
		header("Location: edit.php?profile_id=".$_REQUEST["profile_id"]);
		return;
	}

    $msg = validateEdu();
    if(is_string($msg)) {
        $_SESSION['error'] = $msg;
        header('Location: add.php');
        return;
    }

    $msg = validatePos();
    if(is_string($msg)) {
        $_SESSION['error'] = $msg;
        header("Location: edit.php?profile_id=".$_REQUEST["profile_id"]);
         return;
    }

	if (! preg_match("/@/", $_POST['email'])) {
		$_SESSION['error'] = 'Email address must contain @';
		header("Location: edit.php?profile_id=".$_REQUEST["profile_id"]);
		return;
	}
	

    $sql = "UPDATE profile SET first_name = :first_name,
        last_name = :last_name, email = :email,
        headline = :headline, summary = :summary
        WHERE profile_id = :profile_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        ':first_name' => $_POST['first_name'],
        ':last_name' => $_POST['last_name'],
        ':email' => $_POST['email'],
        ':headline' => $_POST['headline'],
        ':summary' => $_POST['summary'],
        ':profile_id' => $_POST['profile_id']));



    //Clear out the old position entries
    $stmt = $pdo->prepare('DELETE FROM position WHERE profile_id = :pid');
    $stmt->execute(array(':pid' => $_REQUEST['profile_id']));

    //Insert the position entries
    insertPos($pdo, $_REQUEST['profile_id']);

    //Clear out old Education entries
    $stmt = $pdo->prepare('DELETE FROM education WHERE profile_id = :pid');
    $stmt->execute(array(':pid' => $_REQUEST['profile_id']));

    //Insert the education entries
    insertEdu($pdo, $_REQUEST['profile_id']);

    //$_SESSION['green'] = 'Record edited';
    header( 'Location: index.php' );
    return;
}



$stmt = $pdo->prepare("SELECT * FROM profile where profile_id = :xyz");
$stmt->execute(array(":xyz" => $_REQUEST['profile_id']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $row === false ) {
    $_SESSION['error'] = 'Could not load profile';
    header( 'Location: index.php' );
    return;
}

$f = htmlentities($row['first_name']);
$l = htmlentities($row['last_name']);
$e = htmlentities($row['email']);
$h = htmlentities($row['headline']);
$s = htmlentities($row['summary']);
$profile_id = $_REQUEST['profile_id'];



?>


<!DOCTYPE html>
<html>
<head>
<title>Uwem Effiong Uke's Profile Edit</title>
<!-- bootstrap.php - this is HTML -->

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">

  <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css"> 

  <script src="https://code.jquery.com/jquery-3.2.1.js" integrity="sha256-DZAnKJ/6XZ9si04Hgrsxu/8s717jcIzLy3oi35EouyE=" crossorigin="anonymous"></script>
    <script src="education/js/jquery-3.3.1.min.js"></script>

  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js" integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30=" crossorigin="anonymous"></script>

</head>
<body>
<div class="container">
<h1>Editing Profile for <?= htmlentities($_SESSION['name']); ?></h1>
<?php 
// Flash pattern
flashMessage();
?>
<form method="post" action="edit.php">
<p>First Name:
<input type="text" name="first_name" size="60"
value="<?= $f ?>"></p>
<p>Last Name:
<input type="text" name="last_name" size="60"
value="<?= $l ?>"></p>
<p>Email:
<input type="text" name="email" size="30"
value="<?= $e ?>"></p>
<p>Headline:<br/>
<input type="text" name="headline" size="80"
value="<?= $h ?>"></p>
<p>Summary:<br/>
<textarea name="summary" value="" rows="8" cols="80"><?= $s ?>
</textarea></p>
<p>
<input type="hidden" name="profile_id"
value="<?= $profile_id ?>"></p>
<?php 

// Load up the position rows
$positions = loadPos($pdo, $_REQUEST['profile_id']);
$schools = loadEdu($pdo, $_REQUEST['profile_id']);

//Generating html forms with php for editing
$countEdu = 0;
echo('<p>Education: <input type="submit" id="addEdu" value="+">'."\n");
echo('<div id="edu_fields">'."\n");
if (count($schools) > 0) {
    foreach ($schools as $school){
        $countEdu++;
        echo ('<div id="edu'.$countEdu.'">');
        echo '<p>Year: <input type="text" name="edu_year'.$countEdu.'" value="'.$school['year'].'" />
        <input type="button" value="-" onclick="$(\'#edu'.$countEdu.'\').remove();return false;"></p>
        <p>School: <input type="text" size="80" name="school'.$countEdu.'" class="school" value="'.htmlentities($school['name']).'" />';
        echo "\n</div>\n";
    }
}
echo("</div></p>\n");

$pos = 0;
echo('<p>Position: <input type="submit" id="addPos" value="+">'."\n");
echo('<div id="position_fields">'."\n");
foreach ($positions as $position) {
    $pos++;
    echo('<div id="position'.$pos.'">'."\n");
    echo('<p>Year: <input type="text" name="year'.$pos.'"');
    echo('value="'.$position['year'].'"/>'."\n");
    echo('<input type="button" value="-" ');
    echo('onclick="$(\'#position'.$pos.'\').remove();return false;">'."\n");
    echo("</p>\n");
    echo('<textarea name="desc'.$pos.'" rows="8" cols="80">'."\n");
    echo(htmlentities($position['description'])."\n");
    echo("\n</textarea>\n</div>\n");
}
echo("</div></p>\n");

?>

<p>
<input type="submit" name="save" value="Save">
<input type="submit" name="cancel" value="Cancel">
</p>
</form>

<script src="https://code.jquery.com/jquery-3.2.1.js" integrity="sha256-DZAnKJ/6XZ9si04Hgrsxu/8s717jcIzLy3oi35EouyE=" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js" integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30=" crossorigin="anonymous"></script>
<script src="education/js/jquery-3.3.1.min.js"></script>
<script type="text/javascript">

countPos = <?= $pos ?>;
countEdu = <?= $countEdu ?>;
// http://stackoverflow.com/questions/17650776/add-remove-html-inside-div-using-javascript
$(document).ready(function(){
    window.console && console.log('Document ready called');
    $('#addPos').click(function(event){
        // http://api.jquery.com/event.preventdefault/
        event.preventDefault();
        if ( countPos >= 9 ) {
            alert("Maximum of nine position entries exceeded");
            return;
        }
        countPos++;
        window.console && console.log("Adding position "+countPos);
        $('#position_fields').append(
            '<div id="position'+countPos+'"> \
            <p>Year: <input type="text" name="year'+countPos+'" value="" /> \
            <input type="button" value="-" \
                onclick="$(\'#position'+countPos+'\').remove();return false;"></p> \
            <textarea name="desc'+countPos+'" rows="8" cols="80"></textarea>\
            </div>');
    });

    $('#addEdu').click(function(event){
        event.preventDefault();
        if ( countEdu >= 9 ) {
            alert("Maximum of nine education entries exceeded");
             return;
        }
        countEdu++;
        window.console && console.log("Adding education "+countEdu);

        // Grab #edu-template and append it to the #edu_fields div
        var source  = $("#edu-template").html();
        $('#edu_fields').append(source.replace(/@COUNT@/g,countEdu));

        // Add the event handler to the new ones
        $('.school').autocomplete({
            source: "school.php"
        });

    });

    $('.school').autocomplete({
        source: "school.php"
    });
});
</script>

<!-- HTML with Substitution hot spots -->
<script id="edu-template" type="text">
  <div id="edu@COUNT@">
    <p>Year: <input type="text" name="edu_year@COUNT@" value="" />
    <input type="button" value="-" onclick="$('#edu@COUNT@').remove();return false;"><br>
    <p>School: <input type="text" size="80" name="school@COUNT@" class="school" value="" />
    </p>
  </div>
</script>

</div>
</body>
</html>
