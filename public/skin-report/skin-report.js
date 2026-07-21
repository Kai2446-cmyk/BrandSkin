
(function(){
  const baseAsset = '/skin-report/assets/';
  const diaryUrl = document.body.dataset.skinDiaryUrl || '/profile/skin-diary';
  const reportUrl = document.body.dataset.skinReportUrl || '/skin-analyzer/report';
  const recommendationUrl = document.body.dataset.skinRecommendationUrl || '/skin-analyzer/recommendations';
  const reports = [
    { id: 1, date: "03 Jul 2026 14:00", image: baseAsset + "face.png" },
    { id: 2, date: "30 Jun 2026 10:50", image: baseAsset + "face.png" }
  ];

  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

  $$("[data-home]").forEach(el => el.addEventListener("click", () => location.href = diaryUrl));
  $$("[data-back]").forEach(el => el.addEventListener("click", () => history.length > 1 ? history.back() : location.href = diaryUrl));
  $$("[data-history-list]").forEach(el => el.addEventListener("click", () => location.href = diaryUrl + "#all-history"));
  $$("[data-detail]").forEach(el => el.addEventListener("click", () => location.href = reportUrl));
  $$("[data-recommendation]").forEach(el => el.addEventListener("click", () => location.href = recommendationUrl));
  $$("[data-report]").forEach(el => el.addEventListener("click", () => location.href = reportUrl));

  const search = $("#historySearch");
  const list = $("#historyList");
  const total = $("#historyTotal");
  const historyPreviewCount = $("#historyPreviewCount");
  if(historyPreviewCount){
    historyPreviewCount.textContent = `View All (${reports.length})`;
  }

  function renderHistory(items){
    if(!list) return;
    list.innerHTML = "";
    items.forEach(item => {
      const row = document.createElement("div");
      row.className = "history-item";
      row.innerHTML = `
        <svg><use href="${baseAsset}icons.svg#report"></use></svg>
        <button class="history-item" style="display:block;padding:0;grid-template-columns:1fr;background:transparent;border:0" data-open="${item.id}">
          <span class="date">${item.date}</span>
        </button>
        <button class="item-trash" data-delete="${item.id}" aria-label="Delete report"><svg><use href="${baseAsset}icons.svg#trash"></use></svg></button>
      `;
      list.appendChild(row);
    });
    if(total) total.textContent = `Total ${items.length} item`;
  }

  if(list){
    let activeReports = [...reports];
    renderHistory(activeReports);

    search?.addEventListener("input", () => {
      const q = search.value.trim().toLowerCase();
      renderHistory(activeReports.filter(r => r.date.toLowerCase().includes(q)));
    });

    list.addEventListener("click", (e) => {
      const openBtn = e.target.closest("[data-open]");
      const delBtn = e.target.closest("[data-delete]");
      if(openBtn) location.href = reportUrl;
      if(delBtn) showConfirm("Apakah anda yakin untuk menghapus skin report ini?", () => {
        const id = Number(delBtn.dataset.delete);
        activeReports = activeReports.filter(r => r.id !== id);
        renderHistory(activeReports);
      });
    });

    $("#deleteAll")?.addEventListener("click", () => {
      showConfirm("Apakah anda yakin untuk menghapus semua skin report history?", () => {
        activeReports = [];
        renderHistory(activeReports);
      });
    });
  }

  function showConfirm(message, onConfirm){
    const modal = $("#confirmModal");
    if(!modal) return;
    $("#confirmMessage").textContent = message;
    modal.classList.add("show");
    const confirm = $("#confirmYes");
    const cancel = $("#confirmNo");
    const close = () => modal.classList.remove("show");
    const newConfirm = confirm.cloneNode(true);
    confirm.parentNode.replaceChild(newConfirm, confirm);
    newConfirm.addEventListener("click", () => { onConfirm(); close(); }, { once:true });
    cancel.onclick = close;
    modal.addEventListener("click", e => { if(e.target === modal) close(); }, { once:true });
  }



  const skinDetailsContent = {
    health: {
      causesTitle: "What affects skin health?",
      causes: [
        "Skin health is affected by hydration, sleep, nutrition, cleansing habits, and sun exposure.",
        "Pollution, stress, and inconsistent skincare can make the skin barrier feel less balanced.",
        "A consistent routine helps support stronger-looking, healthier skin over time.",
        "Protecting your skin daily is one of the easiest ways to maintain a healthy complexion."
      ],
      tipsTitle: "How to maintain healthy skin?",
      tips: [
        "Cleanse gently: Use a mild cleanser to keep your skin fresh without stripping moisture.",
        "Hydrate daily: Keep your skin moisturized and drink enough water.",
        "Sunscreen always: Protect your skin from UV rays every morning.",
        "Sleep well: Give your skin time to recover overnight.",
        "Stay consistent: A simple routine done regularly gives better long-term results."
      ]
    },
    acne: {
      causesTitle: "What causes acne?",
      causes: [
        "Acne happens when hair follicles get clogged with dead skin cells and oil.",
        "Hormonal changes and genetics can lead to acne.",
        "Keep your face clean with a gentle cleanser, avoid touching, and use non-comedogenic products.",
        "Don't worry, with the right care, we can see the improvements!"
      ],
      tipsTitle: "How to reduce acne?",
      tips: [
        "Cleanse gently: Wash your face twice daily with a mild cleanser.",
        "Hands off: Resist picking or touching your face to avoid spreading bacteria.",
        "Spot treat: Use acne-fighting products with benzoyl peroxide or salicylic acid on individual breakouts.",
        "Moisturize: Choose an oil-free, non-comedogenic moisturizer to keep your skin hydrated.",
        "Healthy habits: Eat a balanced diet, drink plenty of water, and manage stress to support overall skin health."
      ]
    },
    texture: {
      causesTitle: "What affects skin texture?",
      causes: [
        "Rough skin texture happens when dead skin cells build up aren't shed properly.",
        "Sun exposure, pollution, as well as not moisturizing enough can worsen roughness!",
        "Try gentle exfoliation using a scrub or a soft brush, don't forget to moisturize daily, do regular cleansing and protect from the sun.",
        "Do it all and it will help you achieve a smoother complexion."
      ],
      tipsTitle: "How to improve skin texture?",
      tips: [
        "Regular cleansing: Cleanse your face twice daily with a gentle face wash.",
        "Exfoliate weekly: Use a scrub or chemical exfoliant to smoothen your skin.",
        "Hydrate well: Moisturize daily with a non-greasy, oil-free lotion to keep a supple skin.",
        "Sun protection: Wear sunscreen daily to shield skin from harmful UV ray.",
        "Healthy lifestyle: Eat nutritious foods, get enough sleep, and manage stress for a better skin texture."
      ]
    },
    pore: {
      causesTitle: "What causes large pores?",
      causes: [
        "Large pore size refers to visible and enlarged openings on the skin's surface.",
        "Genetic and hormonal changes can play a role in your pores size!",
        "Sun exposure also worsens it by damaging the skin's collagen and elastin.",
        "Cleanse your face regularly, use non-comedogenic products, and protect from the sun will help minimize the appearance of large pores."
      ],
      tipsTitle: "How to minimize pore size?",
      tips: [
        "Gentle cleansing: Wash your face twice daily with a mild cleanser.",
        "Exfoliate wisely: Use a gentle exfoliator once a week.",
        "Non-comedogenic products: Choose oil-free, pore-friendly skincare and makeup products.",
        "Sunscreen protection: Wear sunscreen daily to shield skin from UV rays and prevent further damage to pores.",
        "Patience and consistency: Stick to your routine and don't get discouraged!"
      ]
    },
    spots: {
      causesTitle: "What causes dark spots?",
      causes: [
        "Dark spots (hyperpigmentation) is caused by excess production of melanin.",
        "Sun, acne, skin injuries, hormonal changes, picking pimples, all of it may also cause and leave dark marks.",
        "Avoiding the sun, using sunscreen, and not picking at your skin can help prevent dark spots!",
        "Treatments like topical creams with ingredients like retinoids or vitamin C may fade them over time."
      ],
      tipsTitle: "How to get rid of dark spots?",
      tips: [
        "Sunscreen always: Protect your skin from the damaging sun.",
        "Fade creams: Use products like vitamin C or niacinamide to lighten dark spots.",
        "Patience is key: Dark spots take time to fade, so give your skincare routine some time.",
        "Avoid picking: Refrain from picking pimples to prevent dark marks.",
        "Consult a pro: If dark spots persist, seek advice from a dermatologist."
      ]
    },
    brightness: {
      causesTitle: "What causes dull skin?",
      causes: [
        "Dull skin happens when dead skin cells build up on the surface, blocking light reflection.",
        "Not exfoliating regularly, dehydration, pollution, or harsh weather can contribute to dullness.",
        "Maintaining a balanced diet, staying hydrated, and getting enough sleep can help improve your skin's radiance.",
        "Keep your skin looking fresh by using a gentle exfoliator 1–2 times a week and moisturizing daily."
      ],
      tipsTitle: "How to get glowing skin?",
      tips: [
        "Cleanse & exfoliate: Regularly cleanse your face and exfoliate for a brighter complexion!",
        "Hydration matters: Moisturize daily to keep your skin plump and glow naturally.",
        "Sunscreen protection: Use sunscreen to avoid the dangers of UV rays.",
        "Healthy diet: Eat fruits, veggies, and antioxidants to nourish your skin and promote radiance.",
        "Beauty Sleep: Get enough sleep every night so your skin can regenerate properly."
      ]
    }
  };

  function renderNumberedList(containerId, items){
    const container = document.getElementById(containerId);
    if(!container) return;
    container.innerHTML = items.map((text, index) => `
      <div class="reason-item">
        <span class="number-badge">${index + 1}</span>
        <p>${text}</p>
      </div>
    `).join("");
  }

  const analysisCopy = {
    health: {
      title: "Skin Health",
      score: "6/10",
      statusClass: "medium",
      tip: "Moderate. No worries, read on for more tips!",
      desc: "You're rocking it! Keep in mind, dedication pays off on your journey to healthier skin. Embrace your uniqueness and keep pushing forward with style!"
    },
    acne: {
      title: "Acne",
      score: "6/10",
      statusClass: "medium",
      tip: "Moderate. No worries, read on for more tips!",
      desc: "No worries, everything's a work in progress, and acne doesn't define you. Stick to your skincare routine, and remember, you're not alone on this journey!"
    },
    texture: {
      title: "Texture",
      score: "6/10",
      statusClass: "medium",
      tip: "Moderate. No worries, read on for more tips!",
      desc: "Stay positive and keep your spirits high. Your worth goes beyond your skin texture. Keep pampering yourself and embrace your unique beauty."
    },
    pore: {
      title: "Pore Size",
      score: "4/10",
      statusClass: "bad",
      tip: "Can be improved. No fret, read on to learn!",
      desc: "Visible pores are completely natural, and you're nailing it with your skincare routine. Stay confident and keep pampering your skin."
    },
    spots: {
      title: "Dark Spots",
      score: "6/10",
      statusClass: "medium",
      tip: "Moderate. No worries, read on for more tips!",
      desc: "You're amazing! Don't let dark spots define you. Continue to care for yourself and celebrate your uniqueness."
    },
    brightness: {
      title: "Brightness",
      score: "7/10",
      statusClass: "good",
      tip: "Good progress. Keep it up!",
      desc: "Your radiance is coming through. Keep cleansing, moisturizing, and protecting your skin consistently for a brighter glow."
    }
  };

  function setAnalysis(key){
    const data = analysisCopy[key];
    if(!data) return;

    const title = document.getElementById("analysisTitle");
    const score = document.getElementById("analysisScore");
    const tip = document.getElementById("analysisTip");
    const desc = document.getElementById("analysisDescription");

    if(title) title.textContent = data.title;
    if(score) {
      score.textContent = data.score;
      score.className = data.statusClass;
    }
    if(tip) {
      tip.textContent = data.tip;
      tip.className = "tip " + data.statusClass;
    }
    if(desc) desc.textContent = data.desc;

    const detail = skinDetailsContent[key] || skinDetailsContent.health;
    const causesTitle = document.getElementById("causesTitle");
    const tipsTitle = document.getElementById("tipsTitle");

    if(causesTitle) causesTitle.textContent = detail.causesTitle;
    if(tipsTitle) tipsTitle.textContent = detail.tipsTitle;

    renderNumberedList("causesList", detail.causes);
    renderNumberedList("tipsList", detail.tips);

    const causesCard = document.getElementById("causesCard");
    const tipsCard = document.getElementById("tipsCard");
    const summarySection = document.getElementById("skinSummarySection");
    const maintainSection = document.getElementById("maintainAspectSection");

    if(key === "health"){
      if(causesCard) causesCard.style.display = "none";
      if(tipsCard) tipsCard.style.display = "none";
      if(summarySection) summarySection.style.display = "";
      if(maintainSection) maintainSection.style.display = "";
    }else{
      if(causesCard) causesCard.style.display = "";
      if(tipsCard) tipsCard.style.display = "";
      if(summarySection) summarySection.style.display = "none";
      if(maintainSection) maintainSection.style.display = "none";
    }

    document.querySelectorAll("[data-score-key]").forEach(btn => {
      btn.classList.toggle("active", btn.dataset.scoreKey === key);
    });
  }

  document.querySelectorAll("[data-score-key]").forEach(btn => {
    btn.addEventListener("click", () => setAnalysis(btn.dataset.scoreKey));
  });

  if(document.getElementById("analysisTitle")){
    const activeScore = document.querySelector("[data-score-key].active");
    setAnalysis(activeScore ? activeScore.dataset.scoreKey : "health");
  }

  const scoreTrack = $("#scoreCarousel");
  if(scoreTrack){
    let scoreTimer = setInterval(() => {
      const item = scoreTrack.querySelector(".score-card");
      const step = item ? item.offsetWidth + 14 : 76;
      const max = scoreTrack.scrollWidth - scoreTrack.clientWidth - 3;
      if(scoreTrack.scrollLeft >= max) scoreTrack.scrollTo({left:0,behavior:"smooth"});
      else scoreTrack.scrollBy({left:step,behavior:"smooth"});
    }, 2300);
    scoreTrack.addEventListener("touchstart", () => clearInterval(scoreTimer), {passive:true});
  }

  const routines = {
    morning: [
      ["Step 1", "cleanser"], ["Step 2", "toner"], ["Step 3", "serum"], ["Step 4", "moist"], ["Step 5", "sunscreen"]
    ],
    night: [
      ["Step 1", "cleanser"], ["Step 2", "toner"], ["Step 3", "serum"], ["Step 4", "night cream"]
    ],
    other: [
      ["Product 1", "mask"], ["Product 2", "spot care"], ["Product 3", "exfoliating gel"]
    ]
  };

  function renderProducts(mode){
    const grid = $("#productGrid");
    if(!grid) return;
    grid.innerHTML = "";
    routines[mode].forEach(([step, type]) => {
      const wrap = document.createElement("div");
      wrap.innerHTML = `
        <div class="product-step">${step}<span>${type}</span></div>
        <div class="product-card">
          <img src="assets/product.png" alt="GlowSkin product">
          <h3>GlowSkin Green Balance Care</h3>
          <p>Rp 16.000,-</p>
          <button class="bag-btn"><svg><use href="assets/icons.svg#bag"></use></svg> Add to Bag</button>
        </div>
      `;
      grid.appendChild(wrap);
    });
  }

  if($("#productGrid")){
    renderProducts("morning");
    $$(".mode-tab").forEach(tab => {
      tab.addEventListener("click", () => {
        $$(".mode-tab").forEach(t => t.classList.remove("active"));
        tab.classList.add("active");
        renderProducts(tab.dataset.mode);
      });
    });
  }
})();
