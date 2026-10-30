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
  const typeInput = document.getElementById("type_name");
  function fillTemplateType() {
    const option = templateSelect.selectedOptions[0];
    if (option.dataset.type) {
      typeInput.value = option.dataset.type;
    }
  }
  templateSelect.addEventListener("change", fillTemplateType);
  // Coming from "Repeat" on the history page the template is already picked
  if (typeInput.value === "") {
    fillTemplateType();
  }
}

// History page charts: each chart reads its data-labels and data-values
if (typeof Chart !== "undefined") {
  const appColors = getComputedStyle(document.documentElement);
  const accent = appColors.getPropertyValue("--app-accent").trim();
  const accentSoft = appColors.getPropertyValue("--app-accent-soft").trim();
  const dark = appColors.getPropertyValue("--app-dark").trim();

  Chart.defaults.font.family = '"Roboto Flex", sans-serif';
  Chart.defaults.maintainAspectRatio = false;
  Chart.defaults.plugins.legend.display = false;

  function chartData(element) {
    return {
      labels: JSON.parse(element.dataset.labels),
      values: JSON.parse(element.dataset.values).map(Number),
    };
  }

  // Draw one chart, unit is added to the tooltip (e.g. "lbs")
  function drawChart(canvas, type, data, label, unit, options) {
    return new Chart(canvas, {
      type: type,
      data: {
        labels: data.labels,
        datasets: [{
          label: label,
          data: data.values,
          backgroundColor: type === "line" ? accentSoft : accent,
          borderColor: accent,
          borderRadius: 4,
          fill: type === "line",
          pointBackgroundColor: accent,
          tension: 0.3,
        }],
      },
      options: Object.assign({
        plugins: {
          tooltip: {
            backgroundColor: dark,
            callbacks: {
              label: function (item) {
                return " " + item.formattedValue + " " + unit;
              },
            },
          },
        },
      }, options),
    });
  }

  const weeklyChart = document.getElementById("weekly_chart");
  if (weeklyChart) {
    drawChart(weeklyChart, "bar", chartData(weeklyChart), "Workouts", "workouts", {
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
    });
  }

  const bodyweightChart = document.getElementById("bodyweight_chart");
  if (bodyweightChart) {
    drawChart(bodyweightChart, "line", chartData(bodyweightChart), "Bodyweight", "lbs");
  }

  const muscleChart = document.getElementById("muscle_chart");
  if (muscleChart) {
    drawChart(muscleChart, "bar", chartData(muscleChart), "Sets", "sets", {
      indexAxis: "y",
      scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
    });
  }

  // Heaviest set: redraw for whichever exercise is picked
  const progressSelect = document.getElementById("progress_exercise");
  if (progressSelect) {
    const progressChart = drawChart(document.getElementById("progress_chart"), "line",
      chartData(progressSelect.selectedOptions[0]), "Heaviest set", "lbs");
    progressSelect.addEventListener("change", function () {
      const data = chartData(progressSelect.selectedOptions[0]);
      progressChart.data.labels = data.labels;
      progressChart.data.datasets[0].data = data.values;
      progressChart.update();
    });
  }
}

// Output "completed" to the console
console.log("program completed");