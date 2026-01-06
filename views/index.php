<?php

require_once __DIR__ . '/../config.php';

if (isset($_COOKIE['submission_id'])) {
    $submission_id = (int)$_COOKIE['submission_id'];
    header("Location: " . url("grafiek.php?submission_id=$submission_id"));
    exit;
}
?> 
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Studentregie op leerproces</title>
    <link rel="stylesheet" href="<?php echo url('css/index.css'); ?>">
</head>
<body>
<div class="container">
    <h1>Studentregie op leerproces</h1>
    <p>We vinden het bij ons college belangrijk dat je steeds meer regie krijgt op je studie. Daarom hebben we een
        aantal vragen voor je. Er is geen goed of fout. We willen graag weten hoe belangrijk jij regie vindt, zodat we
        daar rekening mee kunnen houden. Je antwoorden helpen ons om het onderwijs beter en passender te maken.</p>

    <form id="surveyForm">
        <label for="student_name">Naam *</label><br>
        <input type="text" id="student_name" name="student_name" required><br>

        <label for="student_klas">Klas *</label><br>
        <select id="student_klas" name="student_klas" required></select><br>

        <div id="questions"></div>

        <label for="open_text">17. Heb je vragen of opmerkingen?</label><br>
        <textarea id="open_text" name="open_text" rows="3"></textarea><br>

        <button type="submit">Verzenden</button>
    </form>

    <p id="message"></p>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo url('js/index.js'); ?>"></script>
</body>
</html>