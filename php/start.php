<?php
/**
 * Create the database and tables for the login system
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026 
 * @date      2026-02-24
 * @version   1.0
 */

error_reporting(-1);
ini_set('display_errors', 'On');

/* the first connection to the database */
DEFINE('DB_HOST', "localhost");
DEFINE('DB_USER', "root");
DEFINE('DB_PASSWORD', ""); //Note: this should be your root password

// Establish the initial database connection
try {
  $db_connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD)
    OR die("Connection failed: " . $db_connection->connect_error);
} catch (Exception $e) {
  echo 'Caught exception: ',  $e->getMessage(), nl2br("\r\n");
}

/* Check if database is there or will create it */
$create_stmt = "CREATE DATABASE IF NOT EXISTS login_db";

/* Check if database drop was sucessful */
if(mysqli_query($db_connection, $create_stmt)) {
	echo nl2br("Database was successfully created.\r\n");
} else {
	echo "Error dropping database: " . mysqli_error() . nl2br("\r\n");
}
$prep_stmt = $db_connection -> prepare($create_stmt);
$prep_stmt->execute();
$prep_stmt->close();

/* Change to the created database */
$db_connection->select_db("login_db");

/* Drop all tables for clean install */
$db_connection->query('SET foreign_key_checks = 0');
if ($result = $db_connection->query("SHOW TABLES")) {
  while($row = $result->fetch_array(MYSQLI_NUM)) {
    $db_connection->query('DROP TABLE IF EXISTS '.$row[0]);
	}
	echo "Tables removed successfully." . nl2br("\r\n");
} else {
	echo "No tables were removed." . nl2br("\r\n");
}
$db_connection->query('SET foreign_key_checks = 1');


/* Salt used for seasoning */
$salt = 'authentication';

//-----------------------------------------------------
// Create Database Tables
//-----------------------------------------------------
echo "Table creation started." . nl2br("\r\n");

/* Below Are Tables That Have Only Primary Keys */
/* ------------------------------------------ */

/* Role */
$create_roles = $db_connection->prepare(
	"CREATE OR REPLACE TABLE Roles(
        role_id int NOT NULL AUTO_INCREMENT,
        role_type varchar(255) NOT NULL,
        PRIMARY KEY(role_id));");
$create_roles->execute();
$create_roles->close();

/* Contacts */
$create_contacts = $db_connection->prepare(
	"CREATE OR REPLACE TABLE Contacts(
        contact_id int NOT NULL AUTO_INCREMENT,
        last_name varchar(255) NOT NULL,
        first_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(255) NOT NULL,
        street_1 varchar(255),
        street_2 varchar(255),
        city varchar(255),
        state_code varchar(255),
        post_code int(5),
        updated timestamp,
        PRIMARY KEY(contact_id));");
$create_contacts->execute();
$create_contacts->close();

/* Below Are Tables That Have Foreign Keys */
/* ------------------------------------------ */

/* Users */
$create_users = $db_connection->prepare(
	"CREATE OR REPLACE TABLE Users(
        user_id int NOT NULL AUTO_INCREMENT,
        role_id int NOT NULL,
        contact_id int NOT NULL,
	    creation_date timestamp,
	    PRIMARY KEY(user_id),
        FOREIGN KEY(role_id) REFERENCES Roles(role_id),
        FOREIGN KEY(contact_id) REFERENCES Contacts(contact_id));");
$create_users->execute();
$create_users->close();

/* Credentials */
$create_credentials = $db_connection->prepare(
	"CREATE OR REPLACE TABLE Credentials(
        username varchar(255) NOT NULL,
	    user_id int NOT NULL,
        password_salted varchar(255) NOT NULL,
	    PRIMARY KEY(username),
        FOREIGN KEY(user_id) REFERENCES Users(user_id));");
$create_credentials->execute();
$create_credentials->close();

/* Workout Tables */
/* ------------------------------------------ */

/* Equipment */
$create_equipment = $db_connection->prepare(
	"CREATE OR REPLACE TABLE Equipment(
        equipment_id int NOT NULL AUTO_INCREMENT,
        brand_name varchar(255) NOT NULL,
        machine_type varchar(255) NOT NULL,
        PRIMARY KEY(equipment_id));");
$create_equipment->execute();
$create_equipment->close();

/* Muscle Groups */
$create_muscle_groups = $db_connection->prepare(
	"CREATE OR REPLACE TABLE Muscle_Groups(
        muscle_group_id int NOT NULL AUTO_INCREMENT,
        group_name varchar(255) NOT NULL,
        PRIMARY KEY(muscle_group_id));");
$create_muscle_groups->execute();
$create_muscle_groups->close();

/* Exercises - equipment is optional (push-ups, running, etc.) */
$create_exercises = $db_connection->prepare(
	"CREATE OR REPLACE TABLE Exercises(
        exercise_id int NOT NULL AUTO_INCREMENT,
        equipment_id int NULL,
        exercise_name varchar(255) NOT NULL,
        category ENUM('Cardio', 'Strength') NOT NULL,
        PRIMARY KEY(exercise_id),
        FOREIGN KEY(equipment_id) REFERENCES Equipment(equipment_id));");
$create_exercises->execute();
$create_exercises->close();

/* Exercise Muscle Map - which muscle groups each exercise targets */
$create_exercise_muscle_map = $db_connection->prepare(
	"CREATE OR REPLACE TABLE Exercise_Muscle_Map(
        exercise_id int NOT NULL,
        muscle_group_id int NOT NULL,
        PRIMARY KEY(exercise_id, muscle_group_id),
        FOREIGN KEY(exercise_id) REFERENCES Exercises(exercise_id) ON DELETE CASCADE,
        FOREIGN KEY(muscle_group_id) REFERENCES Muscle_Groups(muscle_group_id) ON DELETE CASCADE);");
$create_exercise_muscle_map->execute();
$create_exercise_muscle_map->close();

/* Workouts - one row per gym session */
$create_workouts = $db_connection->prepare(
	"CREATE OR REPLACE TABLE Workouts(
        workout_id int NOT NULL AUTO_INCREMENT,
        user_id int NOT NULL,
        workout_date date NOT NULL,
        duration_minutes int,
        facility_name varchar(255),
        workout_type ENUM('Cardio', 'Strength', 'Mixed') NOT NULL,
        notes text,
        PRIMARY KEY(workout_id),
        FOREIGN KEY(user_id) REFERENCES Users(user_id) ON DELETE CASCADE);");
$create_workouts->execute();
$create_workouts->close();

/* Sets - strength sets use weight/reps, cardio sets use duration/distance */
/* Deleting a workout also deletes its sets */
$create_sets = $db_connection->prepare(
	"CREATE OR REPLACE TABLE Sets(
        set_id int NOT NULL AUTO_INCREMENT,
        workout_id int NOT NULL,
        exercise_id int NOT NULL,
        set_number int NOT NULL,
        weight_lbs decimal(6,2),
        reps int,
        duration_minutes decimal(6,2),
        distance_miles decimal(6,2),
        PRIMARY KEY(set_id),
        FOREIGN KEY(workout_id) REFERENCES Workouts(workout_id) ON DELETE CASCADE,
        FOREIGN KEY(exercise_id) REFERENCES Exercises(exercise_id));");
$create_sets->execute();
$create_sets->close();

/* Status Display */
echo nl2br("The database tables were successfully created.\r\n");


//-----------------------------------------------------
// Populate Tables of Database
//-----------------------------------------------------

/* Roles */
$insert_role = $db_connection->prepare(
	"INSERT INTO Roles
    	(role_id, role_type) VALUES(?,?);");
$insert_role->bind_param("is", $role_id, $role_title);

$role_id = 1;
$role_title = "administrator";
$insert_role->execute();

$role_id = 2;
$role_title = "user";
$insert_role->execute();

$role_id = 3;
$role_title = "guest";
$insert_role->execute();

$insert_role->close();

/* Contacts */
$insert_contacts = $db_connection->prepare(
	"INSERT INTO Contacts
		(contact_id, last_name, first_name, email, phone, street_1, street_2, city, state_code, post_code, updated) VALUES(?,?,?,?,?,?,?,?,?,?,?);");
$insert_contacts->bind_param("issssssssis", $contact_id, $last_name, $first_name, $email, $phone, $street_1, $street_2, $city, $state_code, $post_code, $updated);
$contact_id = 1;
$last_name = "Doe";
$first_name = "John";
$email = "johndoe@example.com";
$phone = "123-456-7890";
$street_1 = "123 Main St";
$street_2 = "";
$city = "Anytown";
$state_code = "CA";
$post_code = 12345;
$updated = date("Y-m-d H:i:s");
$insert_contacts->execute();

$contact_id = 2;
$last_name = "Smith";
$first_name = "Jane";
$email = "janesmith@example.com";
$phone = "987-654-3210";
$street_1 = "456 Elm St";
$street_2 = "Apt 2";
$city = "Othertown";
$state_code = "NY";
$post_code = 54321;
$updated = date("Y-m-d H:i:s");
$insert_contacts->execute();

$contact_id = 3;
$last_name = "Brown";
$first_name = "Charlie";
$email = "charliebrown@example.com";
$phone = "555-555-5555";
$street_1 = "789 Oak St";
$street_2 = "";
$city = "Sometown";
$state_code = "TX";
$post_code = 67890;
$updated = date("Y-m-d H:i:s");
$insert_contacts->execute();

$insert_contacts->close();

/* Users */
$insert_users = $db_connection->prepare(
	"INSERT INTO Users
		(user_id, role_id, contact_id, creation_date) VALUES(?,?,?,?);");
$insert_users->bind_param("iiis", $user_id, $role_id, $contact_id, $creation_date);
$user_id = 1;
$role_id = 1;
$contact_id = 1;
$creation_date = date("Y-m-d H:i:s");
$insert_users->execute();

$user_id = 2;
$role_id = 2;
$contact_id = 2;
$creation_date = date("Y-m-d H:i:s");
$insert_users->execute();

$user_id = 3;
$role_id = 3;
$contact_id = 3;
$creation_date = date("Y-m-d H:i:s");
$insert_users->execute();

$insert_users->close();



/* Credentials */
$insert_credentials = $db_connection->prepare(
	"INSERT INTO Credentials
		(username, user_id, password_salted) VALUES(?,?,?);");
$insert_credentials->bind_param("sis", $username, $user_id, $password_salted);
$username = "johndoe";
$user_id = 1;
$password_salted = crypt("SCSU2024", $salt);
//$password_salted = password_hash("password123", PASSWORD_DEFAULT);
$insert_credentials->execute();

$user_id = 2;
$username = "janesmith";
$password_salted = crypt("SCSU2025", $salt);
//$password_salted = password_hash("mypassword", PASSWORD_DEFAULT);
$insert_credentials->execute();

$user_id = 3;
$username = "charliebrown";
$password_salted = crypt("SCSU2026", $salt);
//$password_salted = password_hash("charlie123", PASSWORD_DEFAULT);
$insert_credentials->execute();

$insert_credentials->close();




/* Equipment */
$insert_equipment = $db_connection->prepare(
	"INSERT INTO Equipment
		(equipment_id, brand_name, machine_type) VALUES(?,?,?);");
$insert_equipment->bind_param("iss", $equipment_id, $brand_name, $machine_type);
$equipment = array(
	1 => array("Rogue", "Barbell"),
	2 => array("PowerBlock", "Dumbbell"),
	3 => array("Life Fitness", "Cable Machine"),
	4 => array("Hammer Strength", "Leg Press"),
	5 => array("Precor", "Treadmill"),
);
foreach ($equipment as $equipment_id => $row) {
	list($brand_name, $machine_type) = $row;
	$insert_equipment->execute();
}
$insert_equipment->close();

/* Muscle Groups */
$insert_muscle_group = $db_connection->prepare(
	"INSERT INTO Muscle_Groups
		(muscle_group_id, group_name) VALUES(?,?);");
$insert_muscle_group->bind_param("is", $muscle_group_id, $group_name);
$muscle_groups = array(1 => "Chest", 2 => "Back", 3 => "Shoulders", 4 => "Biceps",
	5 => "Triceps", 6 => "Quads", 7 => "Hamstrings", 8 => "Glutes", 9 => "Core");
foreach ($muscle_groups as $muscle_group_id => $group_name) {
	$insert_muscle_group->execute();
}
$insert_muscle_group->close();

/* Exercises - equipment_id NULL means no equipment needed */
$insert_exercise = $db_connection->prepare(
	"INSERT INTO Exercises
		(exercise_id, equipment_id, exercise_name, category) VALUES(?,?,?,?);");
$insert_exercise->bind_param("iiss", $exercise_id, $equipment_id, $exercise_name, $category);
$exercises = array(
	1 => array(1, "Bench Press", "Strength"),
	2 => array(1, "Back Squat", "Strength"),
	3 => array(1, "Deadlift", "Strength"),
	4 => array(2, "Dumbbell Curl", "Strength"),
	5 => array(3, "Lat Pulldown", "Strength"),
	6 => array(3, "Tricep Pushdown", "Strength"),
	7 => array(4, "Leg Press", "Strength"),
	8 => array(null, "Push-Up", "Strength"),
	9 => array(5, "Treadmill Run", "Cardio"),
	10 => array(null, "Outdoor Run", "Cardio"),
);
foreach ($exercises as $exercise_id => $row) {
	list($equipment_id, $exercise_name, $category) = $row;
	$insert_exercise->execute();
}
$insert_exercise->close();

/* Exercise Muscle Map - exercise_id => muscle groups it targets */
$insert_map = $db_connection->prepare(
	"INSERT INTO Exercise_Muscle_Map
		(exercise_id, muscle_group_id) VALUES(?,?);");
$insert_map->bind_param("ii", $exercise_id, $muscle_group_id);
$exercise_muscles = array(
	1 => array(1, 3, 5),
	2 => array(6, 8, 9),
	3 => array(2, 7, 8),
	4 => array(4),
	5 => array(2, 4),
	6 => array(5),
	7 => array(6, 8),
	8 => array(1, 5, 9),
	9 => array(6, 7),
	10 => array(6, 7),
);
foreach ($exercise_muscles as $exercise_id => $group_ids) {
	foreach ($group_ids as $muscle_group_id) {
		$insert_map->execute();
	}
}
$insert_map->close();

/* Sample Workouts for janesmith (user_id 2) */
/* Dates are relative to today so the dashboard always has recent data */
$insert_workout = $db_connection->prepare(
	"INSERT INTO Workouts
		(workout_id, user_id, workout_date, duration_minutes, facility_name, workout_type, notes) VALUES(?,?,?,?,?,?,?);");
$insert_workout->bind_param("iisisss", $workout_id, $user_id, $workout_date, $duration_minutes, $facility_name, $workout_type, $notes);
$workouts = array(
	1 => array("-12 days", 60, "Campus Rec Center", "Strength", "Upper body day"),
	2 => array("-10 days", 30, "Campus Rec Center", "Cardio", null),
	3 => array("-8 days", 55, "Campus Rec Center", "Strength", "Legs felt heavy"),
	4 => array("-5 days", 65, "Campus Rec Center", "Strength", "New bench PR"),
	5 => array("-3 days", 45, "Downtown Gym", "Mixed", null),
	6 => array("-1 day", 50, "Campus Rec Center", "Strength", "Deadlift focus"),
);
$user_id = 2;
foreach ($workouts as $workout_id => $row) {
	list($days_ago, $duration_minutes, $facility_name, $workout_type, $notes) = $row;
	$workout_date = date("Y-m-d", strtotime($days_ago));
	$insert_workout->execute();
}
$insert_workout->close();

/* Sample Sets */
/* Strength: workout_id, exercise_id, weight_lbs, reps per set */
/* Cardio:   workout_id, exercise_id, duration_minutes, distance_miles */
$insert_set = $db_connection->prepare(
	"INSERT INTO Sets
		(workout_id, exercise_id, set_number, weight_lbs, reps, duration_minutes, distance_miles) VALUES(?,?,?,?,?,?,?);");
$insert_set->bind_param("iiididd", $workout_id, $exercise_id, $set_number, $weight_lbs, $reps, $set_duration, $distance_miles);
$strength_sets = array(
	array(1, 1, 95, array(10, 8, 8)),
	array(1, 5, 80, array(12, 10, 10)),
	array(1, 6, 40, array(12, 12)),
	array(3, 2, 115, array(8, 8, 6)),
	array(3, 7, 180, array(12, 10)),
	array(4, 1, 105, array(8, 6, 5)),
	array(4, 4, 25, array(10, 10)),
	array(4, 8, null, array(15, 12)),
	array(5, 2, 120, array(8, 8)),
	array(6, 3, 155, array(5, 5, 3)),
	array(6, 5, 85, array(10, 10)),
);
foreach ($strength_sets as $row) {
	list($workout_id, $exercise_id, $weight_lbs, $rep_list) = $row;
	$set_duration = null;
	$distance_miles = null;
	foreach ($rep_list as $index => $reps) {
		$set_number = $index + 1;
		$insert_set->execute();
	}
}
$cardio_sets = array(
	array(2, 10, 30, 3.1),
	array(5, 9, 20, 2.0),
);
foreach ($cardio_sets as $row) {
	list($workout_id, $exercise_id, $set_duration, $distance_miles) = $row;
	$set_number = 1;
	$weight_lbs = null;
	$reps = null;
	$insert_set->execute();
}
$insert_set->close();

/* Status Display */
echo nl2br("The database tables were successfully populated.\r\n");
/* Return to homepage after 5 seconds */
header( "refresh:10;url=/CSC540-Workout_Log" );

/* ALWAYS CLOSE THE DB CONNECTION */
$db_connection->close();

?>
