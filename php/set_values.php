<?php
//======================================================================
// Set values from a posted form, shared by add_set.php and update_set.php
// The page that includes this defines form_error($message), which sends
// the user back with the error and stops.
//======================================================================

/* Empty number fields become NULL, anything else must be a number in range */
function number_or_null($field, $min, $max, $label) {
    $value = trim($_POST[$field] ?? '');
    if ($value === '') {
        return null;
    }
    if (!is_numeric($value) || $value < $min || $value > $max) {
        form_error($label . " must be a number between " . $min . " and " . $max . ".");
    }
    return $value;
}

/* Strength sets use weight/reps, cardio sets use duration/distance (the other two stay NULL) */
function set_values_from_post($category) {
    $values = array('weight_lbs' => null, 'reps' => null, 'duration_minutes' => null, 'distance_miles' => null);
    if ($category == 'Strength') {
        $values['weight_lbs'] = number_or_null('weight_lbs', 0, 2000, "Weight");
        $values['reps'] = number_or_null('reps', 1, 1000, "Reps");
        if ($values['reps'] === null || !ctype_digit($values['reps'])) {
            form_error("Please enter the number of reps as a whole number.");
        }
    } else {
        $values['duration_minutes'] = number_or_null('duration_minutes', 0, 1440, "Time");
        $values['distance_miles'] = number_or_null('distance_miles', 0, 500, "Distance");
        if ($values['duration_minutes'] === null && $values['distance_miles'] === null) {
            form_error("Please enter a time, a distance, or both.");
        }
    }
    return $values;
}

/* Equipment is optional, but when chosen it has to be built-in or one of this user's own that
   they haven't deleted. $keep_id is the set's current equipment, allowed even if since deleted. */
function set_equipment_from_post($db_connection, $user_id, $keep_id = null) {
    $equipment_id = ($_POST['equipment_id'] ?? '') === '' ? null : (int) $_POST['equipment_id'];
    if ($equipment_id === null || $equipment_id === $keep_id) {
        return $equipment_id;
    }
    $check_equipment = $db_connection->prepare(
        "SELECT equipment_id FROM Equipment
        WHERE equipment_id = ? AND (user_id IS NULL OR user_id = ?) AND is_archived = 0");
    $check_equipment->bind_param("ii", $equipment_id, $user_id);
    $check_equipment->execute();
    $check_equipment->store_result();
    $equipment_found = $check_equipment->num_rows > 0;
    $check_equipment->close();
    if (!$equipment_found) {
        form_error("Please choose equipment from the list.");
    }
    return $equipment_id;
}

?>
