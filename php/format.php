<?php
//======================================================================
// Formatting helpers shared by the workout pages
//======================================================================

/* Show 95.00 as 95 and 3.10 as 3.1 */
function show_number($value) {
    return $value === null ? '' : rtrim(rtrim($value, '0'), '.');
}

/* Facility, duration and bodyweight joined into one line */
function show_workout_details($facility_name, $duration_minutes, $bodyweight_lbs) {
    $details = array();
    if ($facility_name) { $details[] = htmlspecialchars($facility_name); }
    if ($duration_minutes) { $details[] = $duration_minutes . ' min'; }
    if ($bodyweight_lbs) { $details[] = 'Bodyweight ' . show_number($bodyweight_lbs) . ' lbs'; }
    return implode(' &middot; ', $details);
}

/* Strength sets show weight x reps, cardio sets show time and distance */
function show_set_result($set) {
    if ($set['category'] == 'Strength') {
        return ($set['weight_lbs'] === null ? 'Bodyweight' : show_number($set['weight_lbs']) . ' lbs') . ' &times; ' . $set['reps'];
    }
    $parts = array();
    if ($set['duration_minutes'] !== null) { $parts[] = show_number($set['duration_minutes']) . ' min'; }
    if ($set['distance_miles'] !== null) { $parts[] = show_number($set['distance_miles']) . ' mi'; }
    return implode(' &middot; ', $parts);
}

?>
