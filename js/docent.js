let radarChart = null;

async function loadChart(name) {
    if (!name) return;
    const resp = await fetch(BASE_URL + "/get_scores.php?name=" + encodeURIComponent(name));
    const data = await resp.json();

    if (!data.success) {
        console.error('Failed to load data');
        return;
    }

    const dims = ["C", "A", "R", "E"];
    const labels = ["Competentie", "Autonomie", "Relatie", "Engagement"];
    const indValues = dims.map(d => data.individual[d] || 0);
    const overallValues = dims.map(d => data.overall[d] || 0);

    let datasets = [{
        label: name,
        data: indValues,
        borderColor: "magenta",
        backgroundColor: "rgba(255,0,255,0.12)",
        pointBackgroundColor: "magenta",
        borderWidth: 2,
        fill: true
    }, {
        label: "Gemiddelde klas",
        data: overallValues,
        borderColor: "rgb(30,120,130)",
        backgroundColor: "rgba(30,120,130,0.15)",
        pointBackgroundColor: "rgb(30,120,130)",
        borderWidth: 2,
        fill: true
    }];

    const ctx = document.getElementById("radarChart");
    if (radarChart !== null) {
        radarChart.destroy();
    }

    radarChart = new Chart(ctx, {
        type: "radar",
        data: {labels: labels, datasets: datasets},
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

document.getElementById("selectStudent").addEventListener("change", function () {
    loadChart(this.value);
});