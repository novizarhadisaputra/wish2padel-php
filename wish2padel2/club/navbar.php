<nav id="maiavbar" class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a class="navbar-brand" href="index">
      <img src="../assets/image/w2p.png" alt="Logo" class="d-inline-block align-text-top">
    </a>
    <button
      class="navbar-toggler"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#mainNavbar"
      aria-controls="mainNavbar"
      aria-expanded="false"
      aria-label="Toggle navigation"
    >
    <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

 
      <?php if (!empty($_SESSION['center_id'])): ?>
        <li class="nav-item">
          <a class="nav-link" href="dashboard">Dashboard</a>
        </li>
      <?php endif ?>

        <?php if (!empty($_SESSION['center_id'])): ?>
            <li class="nav-item">
          <a class="nav-link" href="team">Manage Team</a>
        </li>
<?php endif; ?>

        <li class="nav-item">
            <a href="../regis" class="btn btn-gold">Regist Team</a>
        </li>


        <li class="nav-item">
          <a class="nav-link" href="../club.php">Club</a>
        </li>

<li class="nav-item">
          <a class="nav-link" href="../news">News</a>
        </li>

        <?php if (empty($_SESSION['center_id'])): ?>
<li class="nav-item">
          <a class="nav-link" href="../media/gallery">Media</a>
        </li>
        <?php endif ?>

<li class="nav-item">
          <a class="nav-link" href="../sponsor">Sponsors</a>
        </li>

      </ul>

      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if ($username): ?>
          <li class="nav-item dropdown">
            <a
              class="nav-link dropdown-toggle text-light"
              href="#"
              id="profileDropdown"
              role="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <?= htmlspecialchars($username) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
              <li><hr class="dropdown-divider" /></li>
              <li><a class="dropdown-item text-danger" href="../logout">Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
        <li class="nav-item">
            <a href="login/login.php" class="nav-link text-light" title="Login">
            <i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>
            </a>
        </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
<style>
    nav .navbar-brand {
      margin-left:50px;
      margin-right:80px;
    }
    .navbar-brand img {
        height: 120px;
        width:auto;
    }
    nav .nav-item {
      margin-left:40px;
    }
    
</style>
