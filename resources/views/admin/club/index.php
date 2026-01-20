<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Club - Wish2Padel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/stylee.css?v=12') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('assets/image/w2p logo.jpeg') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('assets/image/w2p logo.jpeg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('assets/image/w2p logo.jpeg') ?>">
  </head>
  <body style="background-color: #303030">
    <?php view('partials.navbar'); ?>

    <section class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-white mb-0">
      <i class="bi bi-building me-2 text-warning"></i> Manage Club
    </h2>
    <a href="<?= asset('admin/club/create') ?>" class="btn-gold" style="text-decoration:none">
      <i class="bi bi-plus-circle me-2"></i> Add Club
    </a>
  </div>

  <div class="card shadow border-0 rounded-3">
    <div class="card-header py-3" style="background:#212529;">
      <h5 class="mb-0 text-white"><i class="bi bi-list-ul me-2"></i> Club List</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead style="background:#343a40; color:#fff;">
            <tr>
              <th>#</th>
              <th>Club Name</th>
              <th>City</th>
              <th>Postal Code</th>
              <th>Phone</th>
              <th>Website</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $offset = ($page - 1) * 10;
            if ($result->num_rows > 0): ?>
            <?php $no = $offset + 1; while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $no++; ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($row['name']); ?></td>
              <td><?= htmlspecialchars($row['city']); ?></td>
              <td><?= htmlspecialchars($row['postal_code']); ?></td>
              <td><?= htmlspecialchars($row['phone']); ?></td>
              <td>
                <a href="<?= htmlspecialchars($row['website']); ?>" target="_blank" class="text-decoration-none text-warning fw-semibold">
                  <i class="bi bi-box-arrow-up-right me-1"></i> Visit
                </a>
              </td>
              <td class="text-center">
                <a href="<?= asset('admin/club/show') ?>?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="View">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="<?= asset('admin/club/edit') ?>?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-warning me-1" title="Update">
                  <i class="bi bi-pencil-square"></i>
                </a>
                <a href="<?= asset('admin/club/delete') ?>?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')" title="Delete">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No clubs found.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Pagination -->
  <nav aria-label="Page navigation">
    <ul class="pagination justify-content-center mt-4">
      <?php if($page > 1): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?= $page-1 ?>">Previous</a>
      </li>
      <?php endif; ?>

      <?php for($i=1; $i<=$totalPages; $i++): ?>
      <li class="page-item <?= $i==$page?'active':'' ?>">
        <a class="page-link" style="<?= $i==$page?'background:#d4af37; border-color:#d4af37; color:#fff;':'' ?>" href="?page=<?= $i ?>">
          <?= $i ?>
        </a>
      </li>
      <?php endfor; ?>

      <?php if($page < $totalPages): ?>
      <li class="page-item">
        <a class="page-link" href="?page=<?= $page+1 ?>">Next</a>
      </li>
      <?php endif; ?>
    </ul>
  </nav>
</section>


    <!-- Scroll to Top Button -->
    <button id="scrollTopBtn" title="Go to top">↑</button>

    <script>
      const scrollBtn = document.getElementById("scrollTopBtn");

      window.onscroll = function () {
        if (
          document.body.scrollTop > 200 ||
          document.documentElement.scrollTop > 200
        ) {
          scrollBtn.style.display = "block";
        } else {
          scrollBtn.style.display = "none";
        }
      };

      scrollBtn.addEventListener("click", function () {
        window.scrollTo({
          top: 0,
          behavior: "smooth",
        });
      });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
