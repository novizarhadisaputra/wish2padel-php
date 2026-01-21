<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>View Club - Wish2Padel</title>
  <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
      <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" sizes="32x32" href="<?= getSiteLogo() ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= getSiteLogo() ?>">
        <link rel="apple-touch-icon" href="<?= getSiteLogo() ?>">
</head>
<body style="background-color: #303030">

<?php view('partials.navbar'); ?>

<section class="container py-5">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-white mb-0">
      <i class="bi bi-building text-warning me-2"></i> 
      View Club: <?= htmlspecialchars($center['name']); ?>
    </h2>
    <?php if($center['logo_url']): ?>
      <img src="<?= asset('uploads/club/' . $center['logo_url']) ?>"
           alt="Logo" class="img-fluid rounded shadow-sm" style="max-height:70px;">
    <?php endif; ?>
  </div>

  <!-- Club Info -->
  <div class="card shadow-sm border-0 mb-5">
    <div class="card-header bg-dark text-white">
      <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Club Info</h5>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><strong>Street:</strong> <?= htmlspecialchars($center['street']); ?></div>
        <div class="col-md-3"><strong>City:</strong> <?= htmlspecialchars($center['city']); ?></div>
        <div class="col-md-3"><strong>Zone:</strong> <?= htmlspecialchars($center['zone']); ?></div>
        <div class="col-md-3"><strong>Postal Code:</strong> <?= htmlspecialchars($center['postal_code']); ?></div>
        <div class="col-md-3"><strong>Phone:</strong> <?= htmlspecialchars($center['phone']); ?></div>
        <div class="col-md-3"><strong>Email:</strong> <?= htmlspecialchars($center['email']); ?></div>
        <div class="col-md-3"><strong>Website:</strong> 
          <a href="<?= htmlspecialchars($center['website']); ?>" target="_blank" class="text-decoration-none text-primary">
            <i class="bi bi-box-arrow-up-right"></i> Visit
          </a>
        </div>
        <div class="col-12">
          <strong>Description:</strong>
          <div class="p-3 mt-2 border rounded bg-light">
            <?= $center['description']; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Pistas -->
  <div class="card shadow-sm border-0 mb-5">
    <div class="card-header bg-dark text-white">
      <h5 class="mb-0"><i class="bi bi-dribbble me-2"></i> Fields / Pistas</h5>
    </div>
    <div class="card-body">
      <div class="row row-cols-1 row-cols-md-3 g-3">
        <?php if($pistas->num_rows > 0): ?>
          <?php while($pista = $pistas->fetch_assoc()): ?>
            <div class="col">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                  <h6 class="fw-bold"><?= htmlspecialchars($pista['name']); ?></h6>
                  <p class="text-muted mb-0">Quantity: <?= htmlspecialchars($pista['quantity']); ?></p>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-muted">No fields registered.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Schedules -->
  <div class="card shadow-sm border-0 mb-5">
    <div class="card-header bg-dark text-white">
      <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i> Schedules</h5>
    </div>
    <div class="card-body">
      <div class="row row-cols-1 row-cols-md-3 g-3">
        <?php if($schedules->num_rows > 0): ?>
          <?php while($schedule = $schedules->fetch_assoc()): ?>
            <div class="col">
              <div class="card border-0 shadow-sm h-100 text-center">
                <div class="card-body">
                  <h6 class="fw-bold"><?= htmlspecialchars($schedule['day']); ?></h6>
                  <span class="badge bg-success"><?= htmlspecialchars($schedule['open_time']); ?></span> - 
                  <span class="badge bg-danger"><?= htmlspecialchars($schedule['close_time']); ?></span>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-muted">No schedules found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Photos -->
  <div class="card shadow-sm border-0">
    <div class="card-header bg-dark text-white">
      <h5 class="mb-0"><i class="bi bi-images me-2"></i> Photos</h5>
    </div>
    <div class="card-body">
      <div class="row row-cols-1 row-cols-md-4 g-3">
        <?php if($photos->num_rows > 0): ?>
          <?php while($photo = $photos->fetch_assoc()): ?>
            <div class="col">
              <div class="card border-0 shadow-sm h-100">
                <img src="<?= asset('uploads/club/' . $photo['url']) ?>"
                     class="card-img-top rounded" style="height:180px; object-fit:cover;">
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-muted">No photos uploaded.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
