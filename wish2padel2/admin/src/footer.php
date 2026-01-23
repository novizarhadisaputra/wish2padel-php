<footer style="background: linear-gradient(90deg, #000000, #4f4f4f); color: #E0F2F1; padding: 40px 20px; font-family: Arial, sans-serif;">
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
      const res = await fetch("/proxy.php", {
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
