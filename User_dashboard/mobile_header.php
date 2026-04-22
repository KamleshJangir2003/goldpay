<link rel="stylesheet" href="../responsive.css">
<header id="mobileHeader">
  <div class="logo-container">
    <img src="../image/logo.png" alt="Dollario" style="height:auto;width:150px;">
  </div>
  <div class="menu-container">
    <button class="menu-btn" id="menuToggle">☰</button>
  </div>
</header>
<style>
  #mobileHeader {
    display: none;
    background: #0e1a2b;
    color: white;
    padding: 10px 20px;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 998;
  }
  #mobileHeader .menu-btn {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    line-height: 1;
  }
  @media (max-width: 768px) {
    #mobileHeader { display: flex; }
  }
</style>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('menuToggle');
    if (btn) {
      btn.addEventListener('click', function () {
        if (window.toggleUserSidebar) window.toggleUserSidebar();
      });
    }
  });
</script>
