<!doctype html>
<html lang="nl">
    <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Jouw grafiek</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="index.css">
</head>
<body>

<div class="container">
    <h1>Jouw persoonlijke grafiek</h1>

    <p>
        Naam: <strong id="studentName"></strong><br>
        <a href="views/index.php">Nieuw formulier invullen</a>
    </p>

    <label for="compareName">Vergelijk met:</label>
    <select id="compareName">
        <option value="">Niemand</option>
        <option value="__average__">Gemiddelde</option>
    </select>
    <span id="compareLabel"></span>

    <canvas id="radarChart"></canvas>
</div>

<script>
    const params = new URLSearchParams(window.location.search);
    const name = params.get("name");
    document.getElementById("studentName").textContent = name || "";

    let radarChart = null;

    async function fetchNames() {
    const resp = await fetch("get_scores.php?list=1");
    const data = await resp.json();
    if (data.success && Array.isArray(data.names)) {
    const select = document.getElementById("compareName");
    for (const n of data.names) {
    if (n !== name) {
    const opt = document.createElement("option");
    opt.value = n;
    opt.textContent = n;
    select.appendChild(opt);
}
}
}
}

    async function loadChart(compareWith = "") {
    const resp = await fetch("get_scores.php?name=" + encodeURIComponent(name));
    const data = await resp.json();

    if (!data.success) {
    document.getElementById("studentName").textContent = (name || "") + " (Geen data gevonden)";
    return;
}

    const dims = ["C", "A", "R", "E"];
    const labels = ["Competentie", "Autonomie", "Relatie", "Engagement"];
    const indValues = dims.map(d => data.individual?.[d] || 0);
    const overallValues = dims.map(d => data.overall?.[d] || 0);

    let datasets = [
{
    label: name,
    data: indValues,
    borderColor: "magenta",
    backgroundColor: "rgba(255,0,255,0.12)",
    pointBackgroundColor: "magenta",
    borderWidth: 2,
    fill: true
}
    ];

    if (compareWith === "__average__") {
    datasets.push({
    label: "Gemiddelde score",
    data: overallValues,
    borderColor: "rgb(30,120,130)",
    backgroundColor: "rgba(30,120,130,0.15)",
    pointBackgroundColor: "rgb(30,120,130)",
    borderWidth: 2,
    fill: true
});
    document.getElementById("compareLabel").textContent = "Vergelijkt met: Gemiddelde";
} else if (compareWith) {
    const resp2 = await fetch("get_scores.php?name=" + encodeURIComponent(compareWith));
    const data2 = await resp2.json();
    if (data2.success) {
    const compareValues = dims.map(d => data2.individual?.[d] || 0);
    datasets.push({
    label: compareWith,
    data: compareValues,
    borderColor: "green",
    backgroundColor: "rgba(0,200,0,0.12)",
    pointBackgroundColor: "green",
    borderWidth: 2,
    fill: true
});
    document.getElementById("compareLabel").textContent = "Vergelijkt met: " + compareWith;
} else {
    document.getElementById("compareLabel").textContent = "Geen data voor " + compareWith;
}
} else {
    document.getElementById("compareLabel").textContent = "";
}

    const ctx = document.getElementById("radarChart");

    if (radarChart !== null) {
    radarChart.destroy();
}

    radarChart = new Chart(ctx, {
    type: "radar",
    data: {
    labels: labels,
    datasets: datasets
},
    options: {
    plugins: {
    legend: {position: "bottom"}
},
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

    document.getElementById("compareName").addEventListener("change", function () {
    loadChart(this.value);
});

    fetchNames();
    loadChart();
</script>

</body>
</html>
