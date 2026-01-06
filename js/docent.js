/* JavaScript for docent view - assigns distinct colors per person */

let radarChart = null;

function colorForName(name) {
    // Simple string-to-hue hash -> returns border and background colors
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const hue = Math.abs(hash) % 360;
    const border = `hsl(${hue},70%,45%)`;
    const bg = `hsla(${hue},70%,45%,0.12)`;
    return { border, bg };
}

async function loadChart(selectedNames) {
    const datasets = [];
    const openResponses = []; // collect {name, open_text, created_at}
    const dims = ["C", "A", "R", "E"];
    const labels = ["Competentie", "Autonomie", "Relatie", "Engagement"];

    for (const name of selectedNames) {
        const resp = await fetch(BASE_URL + "/get_scores.php?name=" + encodeURIComponent(name));
        let data;
        try {
            data = await resp.json();
        } catch (e) {
            continue;
        }
        if (!data || !data.success) continue;

        if (name === "__average__") {
            const color = "rgb(30,120,130)";
            const bgColor = "rgba(30,120,130,0.15)";
            datasets.push({
                label: "Gemiddelde klas",
                data: dims.map(d => data.overall[d] || 0),
                borderColor: color,
                backgroundColor: bgColor,
                pointBackgroundColor: color,
                borderWidth: 2,
                fill: true
            });
        } else {
            const cols = colorForName(name);
            datasets.push({
                label: name,
                data: dims.map(d => data.individual[d] || 0),
                borderColor: cols.border,
                backgroundColor: cols.bg,
                pointBackgroundColor: cols.border,
                borderWidth: 2,
                fill: true
            });

            if (data.open_text && data.open_text.trim() !== "") {
                openResponses.push({
                    name: name,
                    text: data.open_text.trim(),
                    created_at: data.created_at || ''
                });
            }
        }
    }

    const ctx = document.getElementById("radarChart");
    if (radarChart) radarChart.destroy();
    radarChart = new Chart(ctx, {
        type: "radar",
        data: { labels, datasets },
        options: {
            plugins: { legend: { position: "bottom" } },
            scales: {
                r: {
                    angleLines: { display: true },
                    suggestedMin: 0,
                    suggestedMax: 5,
                    ticks: { stepSize: 0.5, beginAtZero: true },
                    pointLabels: { font: { size: 12 } }
                }
            },
            elements: { line: { tension: 0.2 } },
            maintainAspectRatio: false
        }
    });

    renderOpenResponses(openResponses);
}

function renderOpenResponses(list) {
    const container = document.getElementById("openResponses");
    if (!container) return;
    container.innerHTML = '';

    if (!list || list.length === 0) {
        const p = document.createElement('p');
        p.style.color = '#666';
        p.textContent = 'Geen open antwoorden voor de geselecteerde studenten.';
        container.appendChild(p);
        return;
    }

    for (const item of list) {
        const div = document.createElement('div');
        div.className = 'open-item';
        const strong = document.createElement('strong');
        strong.textContent = item.name + (item.created_at ? (' — ' + item.created_at) : '');
        const p = document.createElement('p');
        p.textContent = item.text;
        div.appendChild(strong);
        div.appendChild(p);
        container.appendChild(div);
    }
}

window.addEventListener("DOMContentLoaded", function () {
    const checkboxes = document.querySelectorAll("#studentCheckboxes input[type=checkbox]");
    checkboxes.forEach(cb => {
        cb.checked = (cb.value === "__average__");
    });
    loadChart(["__average__"]);
});

document.getElementById("studentCheckboxes").addEventListener("change", function () {
    const checked = Array.from(this.querySelectorAll("input[type=checkbox]:checked")).map(cb => cb.value);
    loadChart(checked);
});