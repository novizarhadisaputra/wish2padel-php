<?php if (isset($sticky_target)): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.getElementById('maiavbar');
    const hero = document.getElementById('<?= $sticky_target ?>');

    function toggleNavbarFixed() {
      if (!hero || !navbar) return;

      const scrollPos = window.scrollY;
      const heroHeight = hero.offsetHeight;

      if (scrollPos >= heroHeight) {
        navbar.classList.add('navbar-fixed');
        document.body.style.paddingTop = navbar.offsetHeight + 'px';
      } else {
        navbar.classList.remove('navbar-fixed');
        document.body.style.paddingTop = '0';
      }
    }

    window.addEventListener('scroll', toggleNavbarFixed);
    toggleNavbarFixed(); // jalankan sekali saat load
  });
</script>

<style>
  /* Navbar default */
  nav#maiavbar {
    width: 100%;
    transition: all 0.3s ease;
    z-index: 9999;
  }

  /* Navbar jadi fixed dan muncul dengan animasi */
  nav#maiavbar.navbar-fixed {
    position: fixed;
    top: 0;
    left: 0;
    background: linear-gradient(90deg, #000000, #4f4f4f);
    box-shadow: 0 3px 8px rgba(0,0,0,0.25);
    animation: fadeInDown 0.4s ease forwards;
  }

  @keyframes fadeInDown {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>
<?php endif; ?>
