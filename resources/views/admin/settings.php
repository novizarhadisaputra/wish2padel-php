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
<body class="admin-page">
    <?php view('partials.navbar'); ?>

    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <script>
      function setupDropzone(containerId, inputId, previewId, removeId) {
        const container = document.getElementById(containerId);
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        if(!container || !input || !preview) return;

        const previewImg = preview.querySelector('img');
        const removeBtn = document.getElementById(removeId);

        container.addEventListener('click', () => input.click());

        container.addEventListener('dragover', (e) => {
          e.preventDefault();
          container.classList.add('dragover');
        });

        container.addEventListener('dragleave', () => {
          container.classList.remove('dragover');
        });

        container.addEventListener('drop', (e) => {
          e.preventDefault();
          container.classList.remove('dragover');
          if (e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            updatePreview(e.dataTransfer.files[0]);
          }
        });

        input.addEventListener('change', () => {
          if (input.files.length) {
            updatePreview(input.files[0]);
          }
        });

        if(removeBtn) {
          removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            input.value = '';
            preview.style.display = 'none';
            container.querySelector('i').style.display = 'block';
            container.querySelector('p').style.display = 'block';
          });
        }

        function updatePreview(file) {
          const reader = new FileReader();
          reader.onload = (e) => {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
            container.querySelector('i').style.display = 'none';
            container.querySelector('p').style.display = 'none';
          };
          reader.readAsDataURL(file);
        }
      }
    </script>

    <div class="container py-5 mt-5">
        <h2 class="text-gold mb-4">Admin Settings</h2>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success border-0 shadow-sm"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- General Settings (Logo) -->
            <div class="col-md-6 mb-4">
                <div class="card admin-card h-100 shadow-lg">
                    <div class="card-header border-0">
                        <h5 class="mb-0">General Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= asset('admin/settings') ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_general">

                            <div class="mb-3">
                                <label class="form-label">Site Logo</label>
                                <div id="dropzone-logo" class="dropzone-container">
                                    <i class="bi bi-cloud-arrow-up" style="display: none;"></i>
                                    <p style="display: none;">Drag & drop logo here or click to upload</p>
                                    <input type="file" name="site_logo" id="file-logo" hidden accept="image/*">
                                    <div id="preview-logo" class="dropzone-preview" style="display: block;">
                                        <img src="<?= getSiteLogo() ?>" alt="Site Logo">
                                        <button type="button" class="dropzone-remove" id="remove-logo">&times;</button>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">Recommended size: 200x200px (PNG/JPG)</small>
                            </div>

                            <button type="submit" class="btn btn-admin-gold w-100">Update Logo</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings (Password) -->
            <div class="col-md-6 mb-4">
                <div class="card admin-card h-100 shadow-lg">
                    <div class="card-header border-0">
                        <h5 class="mb-0">Security Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= asset('admin/settings') ?>" method="POST">
                            <input type="hidden" name="action" value="change_password">

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <button class="btn btn-outline-gold toggle-password" type="button" data-target="current_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                    <button class="btn btn-outline-gold toggle-password" type="button" data-target="new_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-gold toggle-password" type="button" data-target="confirm_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-admin-gold w-100">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Setup for Logo Dropzone
        setupDropzone('dropzone-logo', 'file-logo', 'preview-logo', 'remove-logo');

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
