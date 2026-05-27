<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
Auth::requireRole('admin');

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $whatsapp = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
    
    // Garante o prefixo 55 (Brasil) se não existir
    if (strlen($whatsapp) > 0 && substr($whatsapp, 0, 2) !== '55') {
        $whatsapp = '55' . $whatsapp;
    }

    $id = $_POST['id'] ?? 0;

    if ($id) {
        $db->query("UPDATE users SET name = ?, email = ?, role = ?, whatsapp = ? WHERE id = ?", [$name, $email, $role, $whatsapp, $id]);
        if (!empty($_POST['password'])) {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password = ? WHERE id = ?", [$hash, $id]);
        }
    } else {
        $hash = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
        $newId = $db->insert("INSERT INTO users (name, email, password, role, whatsapp) VALUES (?, ?, ?, ?, ?)", [$name, $email, $hash, $role, $whatsapp]);
    }
    header('Location: users.php');
    exit;
}

if (isset($_GET['delete'])) {
    $db->query("DELETE FROM users WHERE id = ? AND role != 'admin'", [$_GET['delete']]);
    header('Location: users.php');
    exit;
}

$users = $db->fetchAll("SELECT * FROM users ORDER BY role, name");
$editUser = null;
if (isset($_GET['edit'])) {
    $editUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$_GET['edit']]);
}

$title = 'Gerenciar Usuários';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-people-fill text-primary me-2"></i>Usuários</h4>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><?= $editUser ? 'Editar' : 'Novo' ?> Usuário</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $editUser['id'] ?? '' ?>">
                    <div class="mb-3">
                        <label class="form-label small">Nome</label>
                        <input type="text" name="name" class="form-control form-modern" value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">E-mail</label>
                        <input type="email" name="email" class="form-control form-modern" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Senha <?= $editUser ? '(deixe em branco)' : '' ?></label>
                        <input type="password" name="password" class="form-control form-modern" <?= $editUser ? '' : 'required' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Perfil</label>
                        <select name="role" class="form-select form-modern" required>
                            <?php foreach (ROLES as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ($editUser['role'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="whatsappField">
                        <label class="form-label small">WhatsApp (com DDD)</label>
                        <input type="text" name="whatsapp" class="form-control form-modern" value="<?= htmlspecialchars($editUser['whatsapp'] ?? '') ?>" placeholder="(11) 99999-0000" maxlength="16" oninput="maskWhatsApp(this)">
                    </div>
                    <button type="submit" name="save" class="btn btn-modern btn-primary w-100">Salvar</button>
                    <?php if ($editUser): ?>
                    <a href="users.php" class="btn btn-modern btn-outline w-100 mt-1">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Todos os Usuários</div>
            <div class="card-body p-0">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Perfil</th>
                            <th>WhatsApp</th>
                            <th>Ativo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($u['name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge badge-modern bg-info"><?= ROLES[$u['role']] ?></span></td>
                            <td><?= $u['whatsapp'] ? preg_replace('/(?:55)?(\d{2})(\d{4,5})(\d{4})/', '($1) $2-$3', $u['whatsapp']) : '' ?></td>
                            <td><?= $u['active'] ? '<span class="text-success"><i class="bi bi-check-circle-fill"></i></span>' : '<span class="text-danger"><i class="bi bi-x-circle-fill"></i></span>' ?></td>
                            <td>
                                <a href="?edit=<?= $u['id'] ?>" class="btn btn-modern btn-outline btn-sm"><i class="bi bi-pencil"></i></a>
                                <?php if ($u['role'] !== 'admin'): ?>
                                <a href="?delete=<?= $u['id'] ?>" class="btn btn-modern btn-outline btn-sm text-danger" onclick="return confirm('Excluir este usuário?')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
function maskWhatsApp(el) {
    let v = el.value.replace(/\D/g, '');
    if (v.length > 11) v = v.slice(0, 11);
    if (v.length > 6) {
        v = '(' + v.slice(0, 2) + ') ' + v.slice(2, 7) + '-' + v.slice(7);
    } else if (v.length > 2) {
        v = '(' + v.slice(0, 2) + ') ' + v.slice(2);
    } else if (v.length > 0) {
        v = '(' + v;
    }
    el.value = v;
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
