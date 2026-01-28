<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Wish2Padel</title>
    <link rel="stylesheet" href="<?= asset('assets/css/style1.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: #303030;">
    <?php view('partials.navbar'); ?>

    <div class="container py-5">
        <h2 class="text-white mb-4"><i class="bi bi-gear-fill text-primary"></i> Admin Settings</h2>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- General Settings (Logo) -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">General Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= asset('admin/settings') ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_general">

                            <div class="mb-3">
                                <label class="form-label">Current Logo</label>
                                <div class="bg-light p-3 rounded text-center mb-2">
                                    <img src="<?= getSiteLogo() ?>" alt="Site Logo" style="max-height: 100px;">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="site_logo" class="form-label">Upload New Logo</label>
                                <input type="file" class="form-control" id="site_logo" name="site_logo" accept="image/*">
                                <small class="text-muted">Recommended size: 200x200px (PNG/JPG)</small>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Update Logo</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings (Password) -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Security Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= asset('admin/settings') ?>" method="POST">
                            <input type="hidden" name="action" value="change_password">

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger w-100">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
    </script>
</body>
</html>
