<?php

function flashMessage() {
	if (isset($_SESSION['success']) ) {
    echo('<p style="color:green">'.$_SESSION['success']."</p>\n");
    unset($_SESSION['success']);
	}

	if (isset($_SESSION['error']) ) {
	    echo('<p style="color:red">'.htmlentities($_SESSION['error'])."</p>\n");
	    unset($_SESSION['error']);
	}
}

function validateEdu() {

	for ($i=1; $i <=9 ; $i++) { 
		if (! isset($_POST['edu_year'.$i])) continue;
		if (! isset($_POST['school'.$i])) continue;
		$year = $_POST['edu_year'.$i];
		$desc = $_POST['school'.$i];

		if (strlen($_POST['edu_year'.$i]) == 0 || strlen($_POST['school'.$i]) == 0) {
				return "All fields are required";
			}

		if (! is_numeric($_POST['edu_year'.$i])) {
			return "Education year must be numeric";
		}
	}
	return true;
}

function validatePos() {
	for ($i=1; $i <=9 ; $i++) { 
		if (! isset($_POST['year'.$i])) continue;
		if (! isset($_POST['desc'.$i])) continue;
		$year = $_POST['year'.$i];
		$desc = $_POST['desc'.$i];
		if (strlen($year) == 0 || strlen($desc) == 0) {
			return "All fields are required";
		}

		if (! is_numeric($year)) {
			return "Position year must be numeric";
		}
	}
	return true;
}

function loadPos($pdo, $profile_id) {
	$stmt = $pdo->prepare('SELECT * FROM position 
		WHERE profile_id = :prof ORDER BY rank' );
	$stmt->execute(array(':prof' => $profile_id));
	$newPos = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	return $newPos;
}

//Insert the position entries
function InsertPos($pdo, $profile_id) {
    $rank = 1;
    for ($i=1 ; $i <= 9 ; $i++) { 
        if (! isset($_POST['year'.$i])) continue;
        if (! isset($_POST['desc'.$i])) continue;
        $year = $_POST['year'.$i];
        $desc = $_POST['desc'.$i];

        $stmt = $pdo->prepare('INSERT INTO position (profile_id, rank, year, description) VALUES (:pid, :rank, :year, :des)');
        $stmt->execute(array(
            ':pid' => $_REQUEST['profile_id'],
            ':rank' => $rank,
            ':year' => $year,
            ':des' => $desc));
        $rank++;
    }
}

function loadEdu($pdo, $profile_id) {
	$stmt = $pdo->prepare('SELECT year, name FROM education 
		JOIN institution ON education.institution_id = institution.institution_id
		WHERE profile_id = :prof ORDER BY rank' );
	$stmt->execute(array(':prof' => $profile_id));
	$educations = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $educations;
}

//Insert the education entries
function InsertEdu($pdo, $profile_id) {
	$rank = 1;
    for ($i=1 ; $i <= 9 ; $i++) { 
        if (! isset($_POST['edu_year'.$i])) continue;
        if (! isset($_POST['school'.$i])) continue;

        $year = $_POST['edu_year'.$i];
        $school = $_POST['school'.$i];

        //Look up the school if it is there.
        $institution_id = false;
        $stmt = $pdo->prepare('SELECT institution_id FROM institution
        	WHERE name = :name');
        $stmt->execute(array(':name' => $school));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) $institution_id = $row['institution_id'];

        //If there was no institution, insert new institution
        if ($institution_id === false) {
        	$stmt = $pdo->prepare('INSERT INTO institution (name) VALUES (:name)');
        	$stmt->execute(array(':name' => $school));
        	$institution_id = $pdo->lastInsertId();
        }

        // Insert into education table
        $stmt = $pdo->prepare('INSERT INTO education (profile_id, institution_id, rank, year) VALUES (:pid, :inst_id, :rank, :year)');
        $stmt->execute(array(
            ':pid' => $profile_id,
            ':inst_id' => $institution_id,
            ':rank' => $rank,
            ':year' => $year));
        $rank++;
    }
}