const answerOptions = [
    {label: "Ja, meestal wel", value: 5},
    {label: "Soms, maar ik zou dat graag meer willen.", value: 3},
    {label: "Soms, maar ik vind dit niet belangrijk.", value: 4},
    {label: "Nee, maar ik wil dat wel graag.", value: 1},
    {label: "Nee en dat hoeft voor mij ook niet.", value: 3}
];

const questionTexts = [
    "1. Ik kan de opdrachten in mijn opleiding uitvoeren.",
    "2. Ik heb er vertrouwen in dat ik de leerstof begrijp.",
    "3. Ik krijg de kans om mezelf te ontwikkelen.",
    "4. Met de feedback die ik krijg kan ik mijn prestaties verbeteren.",
    "5. Ik heb keuzes in de manier waarop ik mijn lesdoelen bereik.",
    "6. Ik mag meedenken over de invulling van de lessen.",
    "7. Ik mag zelf beslissen hoe ik mijn opdrachten maak.",
    "8. De docenten ondersteunen mijn keuzes.",
    "9. Ik kan mijn medestudenten om hulp vragen.",
    "10. Mijn docenten tonen oprechte interesse in mij als persoon.",
    "11. Ik heb een sterke band met mijn medestudenten.",
    "12. Ik voel dat ik erbij hoor op school.",
    "13. Ik ben enthousiast over de lessen en activiteiten in mijn opleiding.",
    "14. Ik ben actief betrokken bij groepsopdrachten en samenwerkingen.",
    "15. Ik ben blij met het beroep waarvoor ik word opgeleid.",
    "16. Ik zet door zelfs als ik een opdracht moeilijk vind."
];

const qContainer = document.getElementById('questions');

for (let i = 0; i < questionTexts.length; i++) {
    const qnum = i + 1;
    const div = document.createElement('div');
    div.className = 'vraag';
    div.innerHTML = `<p>${questionTexts[i]} *</p>`;
    for (let opt of answerOptions) {
        div.insertAdjacentHTML(
            'beforeend',
            `<label><input type="radio" name="q${qnum}" value="${opt.value}" required> ${opt.label}</label><br>`
        );
    }
    qContainer.appendChild(div);
}

document.getElementById('surveyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!e.target.checkValidity()) {
        alert("Beantwoord alle verplichte vragen (met *).");
        return;
    }

    const form = new FormData(e.target);
    const btn = e.target.querySelector("button[type=submit]");
    btn.disabled = true;
    btn.textContent = "Bezig...";

    const responses = {};
    for (let i = 1; i <= 16; i++) {
        responses[i] = parseInt(form.get("q" + i), 10);
    }
    form.set("responses", JSON.stringify(responses));

    const resp = await fetch("../save.php", {
        method: "POST",
        body: form
    });

    const data = await resp.json();

    if (data.success) {
        const name = encodeURIComponent(form.get("student_name"));
        window.location.href = "../grafiek.php?name=" + name;
    } else {
        alert("Fout: " + (data.error || "Onbekend probleem"));
        btn.disabled = false;
        btn.textContent = "Verzenden";
    }
});

async function loadClasses() {
    const resp = await fetch("../get_classes.php");
    const data = await resp.json();
    const select = document.getElementById("student_klas");
    select.innerHTML = "<option value=''>-- Kies je klas --</option>";
    if (data.success && Array.isArray(data.classes)) {
        for (const c of data.classes) {
            const opt = document.createElement("option");
            opt.value = c;
            opt.textContent = c;
            select.appendChild(opt);
        }
    }
}

loadClasses();
