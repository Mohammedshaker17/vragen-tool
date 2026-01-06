<?php
// File: `views/admin.php`
/* Admin page: create classes, create/edit/delete docent accounts, attach multiple classes to docents. */

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: " . url('login.php'));
    exit;
}

// Ensure teacher_classes mapping table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS teacher_classes (
        user_id INT NOT NULL,
        class_id INT NOT NULL,
        PRIMARY KEY (user_id, class_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create class
        if (isset($_POST['action']) && $_POST['action'] === 'create_class' && !empty($_POST['class_name'])) {
            $stmt = $pdo->prepare("INSERT INTO classes (class_name) VALUES (?)");
            $stmt->execute([trim($_POST['class_name'])]);
            header("Location: " . url("views/admin.php"));
            exit;
        }

        // Create teacher
        if (isset($_POST['action']) && $_POST['action'] === 'create_teacher') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $classes_ids = isset($_POST['classes_id']) ? (array)$_POST['classes_id'] : [];

            if ($username === '' || $password === '') {
                throw new Exception("Gebruikersnaam en wachtwoord zijn verplicht");
            }

            // Ensure username unique
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $check->execute([$username]);
            if ($check->fetch()) {
                throw new Exception("Gebruikersnaam bestaat al");
            }

            // Normalize class ids
            $classes_ids = array_values(array_filter(array_map(function ($v) {
                return (int)$v > 0 ? (int)$v : null;
            }, $classes_ids)));

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'docent')");
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT)
            ]);
            $user_id = $pdo->lastInsertId();

            if (!empty($classes_ids)) {
                $ins = $pdo->prepare("INSERT INTO teacher_classes (user_id, class_id) VALUES (?, ?)");
                foreach ($classes_ids as $cid) {
                    $ins->execute([(int)$user_id, (int)$cid]);
                }
            }
            $pdo->commit();
            header("Location: " . url("views/admin.php"));
            exit;
        }

        // Edit teacher (safe, validates username uniqueness, updates mapping, optional password)
        if (isset($_POST['action']) && $_POST['action'] === 'edit_teacher') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $classes_ids = isset($_POST['classes_id']) ? (array)$_POST['classes_id'] : [];

            if ($user_id <= 0) {
                throw new Exception("Ongeldig gebruikers-id");
            }
            if ($username === '') {
                throw new Exception("Gebruikersnaam mag niet leeg zijn");
            }

            // Normalize class ids to integers and remove empties
            $classes_ids = array_values(array_filter(array_map(function($v){
                return (int)$v > 0 ? (int)$v : null;
            }, $classes_ids)));

            // Ensure username uniqueness (excluding current user)
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
            $check->execute([$username, $user_id]);
            if ($check->fetch()) {
                throw new Exception("Gebruikersnaam bestaat al");
            }

            $pdo->beginTransaction();

            // Build update query dynamically (only include password if provided)
            $params = [$username];
            $sql = "UPDATE users SET username = ?";

            if ($password !== '') {
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Replace teacher_classes entries
            $del = $pdo->prepare("DELETE FROM teacher_classes WHERE user_id = ?");
            $del->execute([$user_id]);

            if (!empty($classes_ids)) {
                $ins = $pdo->prepare("INSERT INTO teacher_classes (user_id, class_id) VALUES (?, ?)");
                foreach ($classes_ids as $cid) {
                    $ins->execute([$user_id, $cid]);
                }
            }

            $pdo->commit();
            header("Location: " . url("views/admin.php"));
            exit;
        }

        // Delete teacher
        if (isset($_POST['action']) && $_POST['action'] === 'delete_teacher') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) throw new Exception("Ongeldig gebruikers-id");
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM teacher_classes WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            $pdo->commit();
            header("Location: " . url("views/admin.php"));
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Fout: " . $e->getMessage();
    }
}

// Fetch classes and teachers for rendering
$classes = [];
try {
    $stmt = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name ASC");
    $classes = $stmt->fetchAll();
} catch (Exception $e) {
    $classes = [];
}

$teachers = [];
try {
    // Fetch teachers and their classes (names and ids, '||' separated)
    $stmt = $pdo->query("
        SELECT u.id, u.username,
               GROUP_CONCAT(c.class_name ORDER BY c.class_name SEPARATOR '||') AS classes,
               GROUP_CONCAT(c.id ORDER BY c.class_name SEPARATOR '||') AS class_ids
        FROM users u
        LEFT JOIN teacher_classes tc ON u.id = tc.user_id
        LEFT JOIN classes c ON tc.class_id = c.id
        WHERE u.role = 'docent'
        GROUP BY u.id
        ORDER BY u.username ASC
    ");
    $teachers = $stmt->fetchAll();
} catch (Exception $e) {
    $teachers = [];
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Admin - Docenten beheren</title>
    <form method="post" action="<?php echo url('logout.php'); ?>" style="margin-bottom:12px;">
        <button type="submit">Uitloggen</button>
    </form>
    <link rel="stylesheet" href="<?php echo url('css/admin.css'); ?>">
    <style>
        .container { max-width: 1100px; margin: 20px auto; padding: 12px; }
        table { width:100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 8px 10px; border: 1px solid #e0e0e0; text-align: left; }
        .actions button { margin-right: 6px; }
        .inline-form-row { display:none; }
        .classes-list { display:flex; flex-wrap:wrap; gap:8px; }
        .small-btn { padding:6px 10px; font-size:0.95rem; }
    </style>
</head>
<body>
<div class="container">
    <h1>Admin - Docenten beheren</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <section>
        <h2>Nieuwe klas aanmaken</h2>
        <p>Een klas bestaat uit max 10 characters</p>
        <form method="post" style="display:flex; gap:8px; align-items:center;">
            <input type="hidden" name="action" value="create_class">
            <input type="text" name="class_name" placeholder="Klasnaam (bv. ICT1A)" required>
            <button type="submit">Maak klas</button>
        </form>
    </section>

    <section style="margin-top:18px;">
        <h2>Nieuwe docent aanmaken</h2>
        <form method="post" id="createTeacherForm">
            <input type="hidden" name="action" value="create_teacher">
            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                <label>
                    Gebruikersnaam:<br>
                    <input type="text" name="username" required>
                </label>
                <label>
                    Wachtwoord:<br>
                    <input type="password" name="password" required>
                </label>
                <div style="flex-basis:100%;"></div>
                <div>
                    <strong>Toewijzen aan klassen</strong><br>
                    <div class="classes-list">
                        <?php foreach ($classes as $c): ?>
                            <label style="display:flex; align-items:center; gap:6px;">
                                <input type="checkbox" name="classes_id[]" value="<?php echo (int)$c['id']; ?>">
                                <?php echo htmlspecialchars($c['class_name']); ?>
                            </label>
                        <?php endforeach; ?>
                        <?php if (empty($classes)): ?><div style="color:#666;">Geen klassen aanwezig. Maak eerst een klas aan.</div><?php endif; ?>
                    </div>
                </div>
                <div style="flex-basis:100%;"></div>
                <button type="submit">Maak docent</button>
            </div>
        </form>
    </section>

    <section style="margin-top:24px;">
        <h2>Bestaande docenten</h2>
        <table>
            <thead>
            <tr>
                <th>Gebruikersnaam</th>
                <th>Klassen</th>
                <th>Acties</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($teachers)): ?>
                <tr><td colspan="3" style="color:#666;">Geen docenten gevonden.</td></tr>
            <?php else: ?>
                <?php foreach ($teachers as $t): ?>
                    <tr data-userid="<?php echo (int)$t['id']; ?>">
                        <td><?php echo htmlspecialchars($t['username']); ?></td>
                        <td><?php echo htmlspecialchars($t['classes'] ? str_replace('||', ', ', $t['classes']) : '—'); ?></td>
                        <td class="actions">
                            <button type="button" class="small-btn editBtn"
                                    data-userid="<?php echo (int)$t['id']; ?>"
                                    data-username="<?php echo htmlspecialchars($t['username'], ENT_QUOTES); ?>"
                                    data-classids="<?php echo htmlspecialchars($t['class_ids'] ?? '', ENT_QUOTES); ?>">
                                Bewerk
                            </button>

                            <form method="post" style="display:inline;" onsubmit="return confirm('Weet je het zeker?');">
                                <input type="hidden" name="action" value="delete_teacher">
                                <input type="hidden" name="user_id" value="<?php echo (int)$t['id']; ?>">
                                <button type="submit" class="small-btn">Verwijder</button>
                            </form>
                        </td>
                    </tr>

                    <!-- Inline edit form row -->
                    <tr class="inline-form-row" id="inline-form-<?php echo (int)$t['id']; ?>">
                        <td colspan="3">
                            <form method="post" id="editForm-<?php echo (int)$t['id']; ?>">
                                <input type="hidden" name="action" value="edit_teacher">
                                <input type="hidden" name="user_id" value="<?php echo (int)$t['id']; ?>">
                                <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                                    <label>
                                        Gebruikersnaam:<br>
                                        <input type="text" name="username" value="<?php echo htmlspecialchars($t['username']); ?>" required>
                                    </label>
                                    <label>
                                        Nieuw wachtwoord:<br>
                                        <input type="password" name="password" placeholder="Laat leeg om onveranderd te laten">
                                    </label>
                                    <div style="flex-basis:100%;"></div>
                                    <div>
                                        <strong>Toewijzen aan klassen</strong><br>
                                        <div class="classes-list edit-classes-container">
                                            <?php foreach ($classes as $c): ?>
                                                <label style="display:flex; align-items:center; gap:6px;">
                                                    <input type="checkbox" name="classes_id[]" value="<?php echo (int)$c['id']; ?>">
                                                    <?php echo htmlspecialchars($c['class_name']); ?>
                                                </label>
                                            <?php endforeach; ?>
                                            <?php if (empty($classes)): ?><div style="color:#666;">Geen klassen aanwezig.</div><?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="flex-basis:100%;"></div>
                                    <div style="display:flex; gap:8px;">
                                        <button type="submit" class="small-btn">Opslaan</button>
                                        <button type="button" class="small-btn cancelEdit">Annuleer</button>
                                    </div>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>

<script>
    // Toggle inline edit form and populate values (use class IDs for accurate checkbox matching)
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            const uid = this.dataset.userid;
            const uname = this.dataset.username || '';
            const classIdsRaw = this.dataset.classids || '';
            const inlineRow = document.getElementById('inline-form-' + uid);
            const form = document.getElementById('editForm-' + uid);
            if (!inlineRow || !form) return;

            // Toggle visibility
            const isVisible = inlineRow.style.display === 'table-row';
            // hide any other open forms
            document.querySelectorAll('.inline-form-row').forEach(r => r.style.display = 'none');

            if (!isVisible) {
                inlineRow.style.display = 'table-row';
                // populate username
                form.querySelector('input[name="username"]').value = uname;
                // clear password
                form.querySelector('input[name="password"]').value = '';
                // uncheck all class checkboxes then check ones that belong by ID
                const selectedIds = classIdsRaw ? classIdsRaw.split('||') : [];
                form.querySelectorAll('.edit-classes-container input[type="checkbox"]').forEach(cb => {
                    cb.checked = selectedIds.includes(cb.value);
                });
                // scroll to inline form for UX
                inlineRow.scrollIntoView({behavior: "smooth", block: "center"});
            } else {
                inlineRow.style.display = 'none';
            }
        });
    });

    // cancel button hides the inline edit form
    document.querySelectorAll('.cancelEdit').forEach(btn => {
        btn.addEventListener('click', function () {
            const row = this.closest('.inline-form-row');
            if (row) row.style.display = 'none';
        });
    });

    // Improve UX: when create teacher form submitted, disable button to prevent double submits
    const createForm = document.getElementById('createTeacherForm');
    if (createForm) {
        createForm.addEventListener('submit', function (e) {
            const btn = this.querySelector('button[type=submit]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Bezig...';
            }
        });
    }

    // When inline edit forms are submitted, disable submit to prevent double submits
    document.querySelectorAll('form[id^="editForm-"]').forEach(f => {
        f.addEventListener('submit', function () {
            const btn = this.querySelector('button[type=submit]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Opslaan...';
            }
        });
    });
</script>
</body>
</html>