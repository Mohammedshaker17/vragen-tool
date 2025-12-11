/* JavaScript voor views/index.php - dynamically loads questions and answer choices from database */

let questionsData = [];
let choicesData = [];

async function loadQuestionsAndChoices() {
    try {
        const resp = await fetch(BASE_URL + "/get_questions.php");
        const data = await resp.json();

        if (!data.success) {
            alert("Fout bij het laden van vragen");
            return false;
        }

        questionsData = data.questions;
        choicesData = data.choices;

        renderQuestions();
        return true;
    } catch (error) {
        console.error("Error loading questions:", error);
        alert("Kan vragen niet laden");
        return false;
    }
}

function renderQuestions() {
    const qContainer = document.getElementById('questions');
    qContainer.innerHTML = '';

    for (let question of questionsData) {
        const div = document.createElement('div');
        div.className = 'vraag';
        div.innerHTML = `<p>${question.question_number}. ${question.question_text} *</p>`;

        for (let choice of choicesData) {
            div.insertAdjacentHTML(
                'beforeend',
                `<label><input type="radio" name="q${question.question_number}" value="${choice.choice_value}" required> ${choice.choice_text}</label><br>`
            );
        }

        qContainer.appendChild(div);
    }
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
    for (let question of questionsData) {
        const qnum = question.question_number;
        responses[qnum] = parseInt(form.get("q" + qnum), 10);
    }
    form.set("responses", JSON.stringify(responses));

    const resp = await fetch(BASE_URL + "/save.php", {
        method: "POST",
        body: form
    });

    const data = await resp.json();

    if (data.success) {
        const name = encodeURIComponent(form.get("student_name"));
        window.location.href = BASE_URL + "/grafiek.php?name=" + name;
    } else {
        alert("Fout: " + (data.error || "Onbekend probleem"));
        btn.disabled = false;
        btn.textContent = "Verzenden";
    }
});

async function loadClasses() {
    const resp = await fetch(BASE_URL + "/get_classes.php");
    const data = await resp.json();
    const select = document.getElementById("student_klas");
    select.innerHTML = "<option value=''>-- Kies je klas --</option>";
    if (data.success && Array.isArray(data.classes)) {
        for (const c of data.classes) {
            const opt = document.createElement("option");
            opt.value = c.name;
            opt.textContent = c.name;
            select.appendChild(opt);
        }
    }
}

window.addEventListener('DOMContentLoaded', async function () {
    await loadQuestionsAndChoices();
    await loadClasses();
});