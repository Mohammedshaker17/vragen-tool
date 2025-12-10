let radarChart = null;

async function loadChart(selectedNames) {
    // Remove this line:
    // if (!selectedNames || selectedNames.length === 0) selectedNames = ["__average__"];
    const datasets = [];
    const dims = ["C", "A", "R", "E"];
    const labels = ["Competentie", "Autonomie", "Relatie", "Engagement"];

    for (const name of selectedNames) {
        const resp = await fetch(BASE_URL + "/get_scores.php?name=" + encodeURIComponent(name));
        const data = await resp.json();
        if (!data.success) continue;

        let color, bgColor, label;
        if (name === "__average__") {
            color = "rgb(30,120,130)";
            bgColor = "rgba(30,120,130,0.15)";
            label = "Gemiddelde klas";
            datasets.push({
                label,
                data: dims.map(d => data.overall[d] || 0),
                borderColor: color,
                backgroundColor: bgColor,
                pointBackgroundColor: color,
                borderWidth: 2,
                fill: true
            });
        } else {
            color = "magenta";
            bgColor = "rgba(255,0,255,0.12)";
            label = name;
            datasets.push({
                label,
                data: dims.map(d => data.individual[d] || 0),
                borderColor: color,
                backgroundColor: bgColor,
                pointBackgroundColor: color,
                borderWidth: 2,
                fill: true
            });
        }
    }

    const ctx = document.getElementById("radarChart");
    if (radarChart) radarChart.destroy();
    radarChart = new Chart(ctx, {
        type: "radar",
        data: {labels, datasets},
        options: {
            plugins: {legend: {position: "bottom"}},
            scales: {
                r: {
                    angleLines: {display: true},
                    suggestedMin: 0,
                    suggestedMax: 5,
                    ticks: {stepSize: 0.5, beginAtZero: true},
                    pointLabels: {font: {size: 12}}
                }
            },
            elements: {line: {tension: 0.2}},
            maintainAspectRatio: false
        }
    });
}

// On page load, only "Gemiddelde klas" is checked
window.addEventListener("DOMContentLoaded", function () {
    const checkboxes = document.querySelectorAll("#studentCheckboxes input[type=checkbox]");
    checkboxes.forEach(cb => {
        cb.checked = (cb.value === "__average__");
    });
    loadChart(["__average__"]);
});

// Update chart on checkbox change
document.getElementById("studentCheckboxes").addEventListener("change", function () {
    const checked = Array.from(this.querySelectorAll("input[type=checkbox]:checked")).map(cb => cb.value);
    loadChart(checked); // No fallback!
});
