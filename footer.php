<footer class="footer mt-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="footer-brand mb-2"><span style="color:var(--kitchen-gold)">●</span> Thekitchen</div>
        <p style="font-size:14px;max-width:300px">A secure C2C marketplace built for South Africa. For the people, vir die mense.</p>
      </div>
      <div class="col-lg-2 col-6">
        <h5>Browse</h5>
        <ul class="list-unstyled" style="font-size:14px">
          <li class="mb-1"><a href="listings.php">All listings</a></li>
          <li class="mb-1"><a href="listings.php?cat=Electronics">Electronics</a></li>
          <li class="mb-1"><a href="listings.php?cat=Clothing">Clothing</a></li>
          <li class="mb-1"><a href="listings.php?cat=Furniture">Furniture</a></li>
        </ul>
      </div>
      <div class="col-lg-2 col-6">
        <h5>Platform</h5>
        <ul class="list-unstyled" style="font-size:14px">
          <li class="mb-1"><a href="sell.php">Sell an item</a></li>
          <li class="mb-1"><a href="register.php">Create account</a></li>
          <li class="mb-1"><a href="dashboard.php">My dashboard</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <h5>Trust &amp; Safety</h5>
        <p style="font-size:13px">All sellers are ID verified. Payments processed securely via PayFast.</p>
        <div class="d-flex gap-2 mt-2">
          <span class="badge-green"><i class="bi bi-shield-check"></i> Verified sellers</span>
          <span class="badge-gold"><i class="bi bi-lock"></i> Secure payments</span>
        </div>
      </div>
    </div>
    <hr class="footer-divider">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <small>&copy; <?= date('Y') ?> Thekitchen. Built for South Africa.</small>
      <small>ITECA-B12 Project – Eduvos</small>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>