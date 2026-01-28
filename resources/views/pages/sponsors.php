<!DOCTYPE html>
<html lang="en">
    <?php view('partials.head', ['title' => 'Sponsors - Wish2Padel']); ?>
<body style="background-color:#303030;">

<?php view('partials.navbar'); ?>

<section class="sponsors-page py-5">
  <div class="container">

    <h3 class="category-title text-gradient-gold mb-4">Sponsors</h3>

    <?php if (!empty($premiumSponsors)): ?>
      <div class="sponsor-category mb-5">
        <?php foreach ($premiumSponsors as $row): ?>
          <div class="pp-partner-card">
            <div class="pp-partner-row">

              <div class="pp-logo">
                <img src="uploads/sponsor/<?= htmlspecialchars($row['sponsor_logo']) ?>"
                     alt="<?= htmlspecialchars($row['sponsor_name']) ?>">
              </div>

              <div class="pp-content">
                  <span class="badge bg-warning text-dark mb-2"><?= strtoupper($row['type'] ?? 'SPONSOR') ?></span>
                <h4><?= htmlspecialchars($row['sponsor_name']) ?></h4>
                <p><?= htmlspecialchars($row['description']) ?></p>
              </div>

              <?php if (!empty($row['website'])): ?>
                <div class="pp-action">
                  <a href="<?= htmlspecialchars($row['website']) ?>" target="_blank">
                    VISIT
                  </a>
                </div>
              <?php endif; ?>

            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($standardSponsors)): ?>
      <h3 class="category-title text-gradient-gold mb-4">Official Sponsors</h3>

      <div class="sponsor-category mb-5">
        <div class="sponsor-grid merged-sponsors">
          <?php foreach ($standardSponsors as $row): ?>
            <div class="grid-logo gold-logo">
              <img src="uploads/sponsor/<?= htmlspecialchars($row['sponsor_logo']) ?>" alt="<?= htmlspecialchars($row['sponsor_name']) ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($resultCollaborators && $resultCollaborators->num_rows > 0): ?>
      <h3 class="category-title text-gradient-silver mb-4">Collaborate</h3>

      <div class="sponsor-category mb-5">
        <div class="sponsor-grid merged-sponsors">
          <?php while($row = $resultCollaborators->fetch_assoc()): ?>
            <div class="grid-logo collaborator-logo">
              <img src="uploads/sponsor/<?= htmlspecialchars($row['sponsor_logo']) ?>" alt="<?= htmlspecialchars($row['sponsor_name']) ?>">
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</section>

<style>
/* ================= BASE ================= */
.sponsors-page {
  background:#0c0c0c;
  color:#fff;
  font-family:Poppins,sans-serif;
}

.tour-title {
  font-weight:800;
  font-size:80px;
}

.category-title {
  font-size:1.3rem;
  font-weight:600;
}

/* ================= PARTNER LONG CARDS ================= */
.pp-partner-card {
  background: linear-gradient(135deg,#000 70%,#141414);
  border:1px solid rgba(212,175,55,.45);
  border-radius:18px;
  padding:16px 40px;
  margin-top:24px;
  margin-bottom:24px;
  transition:.35s;
}

.pp-partner-card:hover {
  transform:translateY(-4px);
  box-shadow:0 18px 45px rgba(212,175,55,.25);
}

.pp-partner-row {
  display:grid;
  grid-template-columns:260px 1fr 120px;
  align-items:center;
  gap:32px;
  padding-top:0.1vh;
  padding-bottom:0.1vh;
  transition:.35s;
}

.pp-logo img {
  max-width:220px;
  max-height:120px;
  filter:drop-shadow(0 0 10px rgba(212,175,55,.3));
}

.pp-content h4 {
  font-weight:700;
}

.pp-content p {
  color:#bdbdbd;
}

.pp-action a {
  padding:8px 24px;
  border-radius:30px;
  border:1px solid rgba(212,175,55,.6);
  color:#fff;
  text-decoration:none;
  transition:.3s;
}

.pp-action a:hover {
  background:linear-gradient(135deg,#d4af37,#b8962e);
  color:#000;
}

/* ================= MERGED LOGOS ================= */
.merged-sponsors {
  display:flex;
  gap:32px;
  justify-content:center;
  flex-wrap: wrap; /* Added flex-wrap for responsiveness */
}

.grid-logo {
    display: flex;
    align-items: center;
    justify-content: center;
}

.grid-logo img {
  max-height:120px;
  max-width: 100%; /* Safety */
  opacity:.9;
}

/* ================= GRADIENTS ================= */
.text-gradient-gold {
  background: linear-gradient(90deg, #FFD700, #D4AF37);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
.text-gradient-silver {
  background: linear-gradient(90deg, #EDEDED, #AFAFAF);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}


/* ================= RESPONSIVE ================= */
@media (max-width:768px) {
  .pp-partner-row {
    grid-template-columns:1fr;
    text-align:center;
  }
}
</style>

<?php view('partials.footer'); ?>
<?php view('partials.navbar_sticky_script', ['sticky_target' => 'maiavbar']); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
