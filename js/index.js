let questionsData = [];
let choicesData = [];

async function loadQuestionsAndChoices() {
    try {
        const resp = await fetch(BASE_URL + "/get_questions.php");
        const text = await resp.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("get_questions returned non-JSON response:", text);
            document.getElementById('message').textContent = "Fout bij het laden van vragen: server returned invalid JSON (check console)";
            alert("Fout bij het laden van vragen: server returned invalid JSON. Zie console voor details.");
            return false;
        }

        if (!data.success) {
            console.error("get_questions responded with error:", data);
            const errMsg = data.error || "Onbekende serverfout";
            document.getElementById('message').textContent = "Fout bij het laden van vragen: " + errMsg;
            alert("Fout bij het laden van vragen: " + errMsg);
            return false;
        }

        questionsData = data.questions;
        choicesData = data.choices;

        renderQuestions();
        return true;
    } catch (error) {
        console.error("Network or fetch error while loading questions:", error);
        document.getElementById('message').textContent = "Kan vragen niet laden: netwerkfout (check console)";
        alert("Kan vragen niet laden: netwerkfout. Zie console voor details.");
        return false;
    }
}

// Render questions dynamically using question.id as the input identifier
function renderQuestions() {
    const qContainer = document.getElementById('questions');
    qContainer.innerHTML = ''; // Clear existing content

    for (let question of questionsData) {
        const div = document.createElement('div');
        div.className = 'vraag';
        // show the human-facing question number but use id for inputs
        div.innerHTML = `<p>${question.question_number}. ${question.question_text} *</p>`;

        // Add all answer choices
        for (let choice of choicesData) {
            div.insertAdjacentHTML(
                'beforeend',
                `<label><input type="radio" name="q${question.id}" value="${choice.choice_value}" required> ${choice.choice_text}</label><br>`
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

        // Build responses keyed by question.id (idquestions)
        const responses = {};
        for (let question of questionsData) {
        const qid = question.id;
        const value = form.get("q" + qid);
        responses[qid] = value !== null ? parseInt(value, 10) : null;
    }
        form.set("responses", JSON.stringify(responses));

        try {
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
    } catch (err) {
        console.error("Error saving responses:", err);
        alert("Fout bij verzenden. Controleer console.");
        btn.disabled = false;
        btn.textContent = "Verzenden";
    }
    });

    async function loadClasses() {
        try {
        const resp = await fetch(BASE_URL + "/get_classes.php");
        const text = await resp.text();

        let data;
        try {
        data = JSON.parse(text);
    } catch (e) {
        console.error("get_classes returned non-JSON response:", text);
        document.getElementById('message').textContent = "Fout bij het laden van klassen: server returned invalid JSON (check console)";
        return;
    }

        const select = document.getElementById("student_klas");
        select.innerHTML = "<option value=''>-- Kies je klas --</option>";
        if (data.success && Array.isArray(data.classes)) {
        for (const c of data.classes) {
        const opt = document.createElement("option");
        opt.value = c.name; // existing backend expects class_name as value
        opt.textContent = c.name;
        select.appendChild(opt);
    }
    } else {
        console.error("get_classes error:", data);
        document.getElementById('message').textContent = "Fout bij het laden van klassen: " + (data.error || "server error");
    }
    } catch (error) {
        console.error("Network error loading classes:", error);
        document.getElementById('message').textContent = "Kan klassen niet laden: netwerkfout";
    }
    }

    // Initialize on page load
    window.addEventListener('DOMContentLoaded', async function() {
        await loadQuestionsAndChoices();
        await loadClasses();
    });
