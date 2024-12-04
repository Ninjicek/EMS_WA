<header>
<nav class="navbar bg-body-tertiary fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">EMS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">EMS </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
        <li class="nav-item">
                <a class="nav-link" href="account.php">Účet</a>
            </li>
          <li class="nav-item">
            <a class="nav-link" href="employees.php">Správa zaměstanců</a>
          </li>
          <?php if ($_SESSION['isAdmin']):?>
          <li class="nav-item">
            <a class="nav-link" href="departments.php">Správa oddělení</a>
          </li>
          <?php endif; ?>
          <?php if ($_SESSION['isAdmin']):?>
            <li class="nav-item">
                <a class="nav-link" href="positions.php">Správa pozicí</a>
            </li>
            <?php endif; ?>
            <li class="nav-item d-flex align-items-end">
                <a class="nav-link mt-auto text-danger" href="logout.php">Odhlásit se</a>
            </li>
      </div>
    </div>
  </div>
</nav>
</header>