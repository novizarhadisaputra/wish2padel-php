<?php
// Ambil semua data sponsor dari database
if (!isset($conn)) $conn = getDBConnection();
$result = $conn ? $conn->query("SELECT * FROM sponsors ORDER BY sponsor_id DESC") : null;

$premiumSponsors = [];
$standardSponsors = [];
$collaborates = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Sponsor (bukan collaborate)
        if ($row['status'] === 'sponsor') {
            // Bedakan berdasarkan type
            if (isset($row['type']) && $row['type'] === 'premium') {
                $premiumSponsors[] = $row;
            } else {
                $standardSponsors[] = $row;
            }
        // Collaborate
        } elseif ($row['status'] === 'collaborate') {
            $collaborates[] = $row;
        }
    }
}

?>

<footer style="background: linear-gradient(90deg, #000000, #4f4f4f); color: white; padding: 60px 20px; font-family: Arial, sans-serif;">

  <!-- Footer Heading -->
  <div style="max-width: 1200px; margin: 0 auto 40px auto;">
    <p style="max-width: 500px; text-transform: uppercase; font-weight: 700; letter-spacing: 3px; font-size: 2rem; color: #f3e686;">
      Elevate Your Game with Wish2Padel Team League
    </p>

  </div>

  <!-- Main Footer Content -->
  <div style="max-width: 1200px; margin: 0 auto 60px auto; display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 40px 60px;">

    <!-- Hello -->
    <div>
      <!--<h6 style="text-transform: uppercase; color: #FFC107; letter-spacing: 2px; font-weight: 700; margin-bottom: 15px;">Hello</h6>-->
      <p style="font-size: 0.95rem; line-height: 1.5; color: white;">
        Welcome to Wish2Padel Team League, your all-in-one platform where <b>players, groups, and companies</b> come together for leagues, tournaments, training, and gear. Join our <b>vibrant Wish2Padel community</b> and let your <b>top performance be recognized</b>.
      </p>

    </div>

    <!-- Office -->
    <div>
      <h6 style="text-transform: uppercase; color: #f3e686; letter-spacing: 2px; font-weight: 700; margin-bottom: 15px;">Office</h6>
      <p style="font-size: 0.95rem; line-height: 1.6; margin-bottom: 10px;">
        Riyadh (Saudi Arabia)
      </p>
      <p style="font-size: 0.95rem; line-height: 1.6; margin-bottom: 10px;">
        <a href="mailto:info@wish2padel.com" style="color: #f3e686; text-decoration: underline;">info@wish2padel.com</a>
      </p>
      <p style="font-size: 0.95rem; line-height: 1.6;">Phone: +966 55 322 4559</p>
    </div>

    <!-- Socials -->
    <div>
      <h6 style="text-transform: uppercase; color: #f3e686; letter-spacing: 2px; font-weight: 700; margin-bottom: 15px;">Socials</h6>
      <div style="display: flex; flex-direction: column; gap: 20px; font-size: 1.2rem;">
        <a href="https://www.instagram.com/wish2padel?igsh=MWx1ejA5MjIzYTFsZg==" aria-label="Instagram" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px;">
          <i class="bi bi-instagram" style="font-size: 1.2rem;"></i>
          Instagram
        </a>
        <a href="#" aria-label="TikTok" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px;">
          <i class="bi bi-tiktok" style="font-size: 1.2rem;"></i>
          TikTok
        </a>
        <a href="#" aria-label="Facebook" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px;">
          <i class="bi bi-facebook" style="font-size: 1.2rem;"></i>
          Facebook
        </a>
        <a href="#" aria-label="YouTube" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px;">
          <i class="bi bi-youtube" style="font-size: 1.2rem;"></i>
          YouTube
        </a>
        <a href="#" aria-label="Twitter" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 10px;">
          <i class="bi bi-twitter" style="font-size: 1.2rem;"></i>
          Twitter
        </a>
      </div>
    </div>

  </div>

  <!-- Sponsors & Collaborates (Flexible, bottom before copyright) -->
<div style="max-width: 1200px; margin: 60px auto; display: flex; flex-direction: column; gap: 65px;">

  <!-- 🌟 PREMIUM SPONSORS -->
  <?php if(!empty($premiumSponsors)): ?>
  <div style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 50px;">
    <?php foreach($premiumSponsors as $row): ?>
      <a href="<?= htmlspecialchars($row['website']) ?>" target="_blank" style="display:block; transition: .3s;">
        <?php if($row['sponsor_logo']): ?>
          <img 
            src="/uploads/sponsor/<?= $row['sponsor_logo'] ?>" 
            alt="<?= htmlspecialchars($row['sponsor_name']) ?>"
            style="
              height: 120px;
              object-fit: contain;
              margin: 12px 20px;
              transition: all .3s;
              filter: drop-shadow(0 6px 22px rgba(255,255,255,0.25));
            "
            onmouseover="this.style.transform='scale(1.08)';"
            onmouseout="this.style.transform='scale(1)';"
          >
        <?php else: ?>
          <span style="color:#fff; font-size:22px; margin: 10px 20px; font-weight:600;">
            <?= htmlspecialchars($row['sponsor_name']) ?>
          </span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>


  <!-- ✅ STANDARD SPONSORS -->
  <?php if(!empty($standardSponsors)): ?>
  <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 28px;">
    <?php foreach($standardSponsors as $row): ?>
      <a href="<?= htmlspecialchars($row['website']) ?>" target="_blank" style="display:block;">
        <?php if($row['sponsor_logo']): ?>
          <img 
            src="/uploads/sponsor/<?= $row['sponsor_logo'] ?>" 
            alt="<?= htmlspecialchars($row['sponsor_name']) ?>"
            style="
              height: 65px;
              object-fit: contain;
              margin: 6px 10px;
              opacity: 0.95;
              transition: all .3s;
            "
            onmouseover="this.style.opacity='1'; this.style.transform='scale(1.05)';"
            onmouseout="this.style.opacity='0.95'; this.style.transform='scale(1)';"
          >
        <?php else: ?>
          <span style="color:#ddd; font-size:16px; margin: 8px 10px;">
            <?= htmlspecialchars($row['sponsor_name']) ?>
          </span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>


  <!-- 🤝 COLLABORATES -->
  <?php if(!empty($collaborates)): ?>
  <div style="margin-top: 40px; display: flex; flex-wrap: wrap; justify-content: center; gap: 22px;">
    <?php foreach($collaborates as $row): ?>
      <a href="<?= htmlspecialchars($row['website']) ?>" target="_blank" style="display:block;">
        <?php if($row['sponsor_logo']): ?>
          <img 
            src="/uploads/sponsor/<?= $row['sponsor_logo'] ?>" 
            alt="<?= htmlspecialchars($row['sponsor_name']) ?>" 
            style="
              height: 65px;
              object-fit: contain;
              margin: 6px 10px;
              transition: all .3s;
            "
            onmouseover="this.style.opacity='1'; this.style.transform='scale(1.05)';"
            onmouseout="this.style.opacity='0.8'; this.style.transform='scale(1)';"
          >
        <?php else: ?>
          <span style="color:#ccc; font-size:15px;"><?= htmlspecialchars($row['sponsor_name']) ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

  <hr style="border-color: #a5d6a7; margin: 40px 0 20px 0;">

  <!-- Copyright -->
  <div style="max-width: 1200px; margin: 0 auto; text-align: center; font-size: 0.9rem; color: #7c9a89;">
    &copy; 2025 Wish2Padel. All rights reserved.
  </div>
  
  <script>
(async function autoTranslatePage() {
  const lang = localStorage.getItem("lang") || "en";
  if (lang !== "ar") return;

  // Set RTL layout
  document.documentElement.setAttribute("dir", "rtl");
  document.body.style.textAlign = "right";
  // ✅ Lock logo area agar tidak kena RTL & Translasi
const brand = document.querySelector(".navbar-brand");
if (brand) {
  brand.setAttribute("data-no-translate", "true");
  brand.style.direction = "ltr";
  brand.style.textAlign = "left";
}


  // Fixed translation for specific words (agar tidak ngawur)
  const customMap = {
    "League": "دوري",
    "LEAGUE": "دوري",
    "league": "دوري",
    "Leagues": "الدوريات",
    "Regist Team": "تسجيل الفريق",
    "Sponsors": "الرعاة",
    "Media": "وسائل الإعلام",
    "News": "الأخبار",
    "Club": "النادي"
  };

  // Ambil semua text node secara agresif tapi tetap aman
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
  const nodes = [];

  while (walker.nextNode()) {
    const node = walker.currentNode;
    const text = node.nodeValue.trim();

    if (!text) continue; // Skip kosong
    if (/^[\d\s\W]+$/.test(text)) continue; // Skip angka/simbol

    const parentTag = node.parentNode?.nodeName.toLowerCase();

    // ❌ Jangan translate teks dalam logo/icon
    if (["img", "svg", "script", "style"].includes(parentTag)) continue;

    nodes.push(node);
  }

  for (const node of nodes) {
    let original = node.nodeValue.trim();

    // Skip jika sudah ada huruf Arab (tidak re-translate)
    if (/[\u0600-\u06FF]/.test(original)) continue;

    if (customMap[original]) {
      node.nodeValue = customMap[original];
      continue;
    }

    try {
      const res = await fetch("<?= asset('proxy.php') ?>", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "text=" + encodeURIComponent(original)
      });

      const data = await res.json();
      if (data?.translatedText) {
        node.nodeValue = data.translatedText;
      }
    } catch (e) {
      console.warn("Translate failed for:", original);
    }
  }
})();
</script>



</footer>

<style>
.footer-sponsor-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center; /* selalu center untuk 1 atau beberapa sponsor */
    gap: 25px;
    align-items: center;
}

.footer-sponsor-logo {
    height: 120px;
    object-fit: contain;
    transition: transform 0.3s ease, filter 0.3s ease, opacity 0.6s ease;
    opacity: 0;
    animation: fadeIn 0.8s forwards;
}
.footer-sponsor-logo:hover {
    transform: scale(1.1);
    filter: brightness(1.2);
}

.footer-sponsor-text {
    font-size: 1rem;
    color: #B2DFDB;
    padding: 10px 15px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    transition: transform 0.3s ease, background 0.3s ease, opacity 0.6s ease;
    opacity: 0;
    animation: fadeIn 0.8s forwards;
}
.footer-sponsor-text:hover {
    transform: scale(1.05);
    background: rgba(255, 255, 255, 0.1);
}

.footer-sponsor-link {
    display: inline-block;
    text-decoration: none;
}

@keyframes fadeIn {
    to { opacity: 1; }
}
</style>
