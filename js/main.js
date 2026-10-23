/**
 * Javascript Main File
 *
 * @author    Joshua Connor <connorj4@southernct.edu>
 * @copyright 2026 
 * @date      2026-02-24
 * @version   1.0
 * @param     none
 * @return    void
 */

// Output "program started" to the console
console.log("program started");

// go back to the prevous page
function goBack() {
  window.history.back();
}

// Add-set form: only show weight/reps for strength and time/distance for cardio
const exerciseSelect = document.getElementById("exercise_id");
if (exerciseSelect) {
  function showSetFields() {
    const option = exerciseSelect.selectedOptions[0];
    const category = option ? option.dataset.category : undefined;
    document.querySelectorAll("[data-fields]").forEach(function (row) {
      row.hidden = category !== undefined && row.dataset.fields !== category;
    });
  }
  exerciseSelect.addEventListener("change", showSetFields);
  showSetFields();
}

// Log workout form: picking a past workout fills in its type
const templateSelect = document.getElementById("template_id");
if (templateSelect) {
  templateSelect.addEventListener("change", function () {
    const option = templateSelect.selectedOptions[0];
    if (option.dataset.type) {
      document.getElementById("type_name").value = option.dataset.type;
    }
  });
}

// Output "completed" to the console
console.log("program completed");