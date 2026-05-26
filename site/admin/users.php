<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
Auth::requireRole('admin');

$db = Database::getInstance();

// Criar/Editar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $id = $_POST['id'] ?? 0;

    if ($id) {
        $db->query("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?", [$name, $email, $role, $id]);
        if (!empty($_POST['password'])) {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $db->query("UPDATE users SET password = ? WHERE id = ?", [$hash, $id]);
        }
    } else {
        $hash = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
        $db->insert("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)", [$name, $email, $hash, $role]);
    }
    header('Location: users.php');
    exit;
}

// Excluir
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

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><?= $editUser ? 'Editar' : 'Novo' ?> Usuário</h6></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $editUser['id'] ?? '' ?>">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha <?= $editUser ? '(deixe em branco para manter)' : '' ?></label>
                        <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Perfil</label>
                        <select name="role" class="form-select" required>
                            <?php foreach (ROLES as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ($editUser['role'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="save" class="btn btn-primary w-100">Salvar</button>
                    <?php if ($editUser): ?>
                    <a href="users.php" class="btn btn-secondary w-100 mt-1">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Usuários</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Perfil</th>
                            <th>Ativo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge bg-info"><?= ROLES[$u['role']] ?></span></td>
                            <td><?= $u['active'] ? '✅' : '❌' ?></td>
                            <td>
                                <a href="?edit=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                <?php if ($u['role'] !== 'admin'): ?>
                                <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir?')">Excluir</a>
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
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
