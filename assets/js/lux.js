// Tropical Fresh · interactielaag v2
// Eén easing-systeem, korte functionele beweging. Alles faalt stil terug naar
// bruikbare HTML zonder JavaScript (.no-js blijft dan op <html> staan).

(function () {
  document.documentElement.classList.remove("no-js");

  var calm = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var fine = window.matchMedia("(pointer: fine)").matches;

  /* reveal-on-scroll */
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) { e.target.classList.add("in"); io.unobserve(e.target); }
    });
  }, { threshold: 0.12 });
  document.querySelectorAll(".reveal").forEach(function (el) { io.observe(el); });

  /* 3D-tilt op podia (alleen muis, alleen zonder reduced motion) */
  if (fine && !calm) {
    document.querySelectorAll(".stage[data-tilt]").forEach(function (stage) {
      var tilt = stage.querySelector(".tilt");
      if (!tilt) return;
      var max = parseFloat(stage.dataset.tilt || "8");
      stage.addEventListener("pointermove", function (ev) {
        var r = stage.getBoundingClientRect();
        var x = (ev.clientX - r.left) / r.width - 0.5;
        var y = (ev.clientY - r.top) / r.height - 0.5;
        tilt.style.transition = "transform 0.12s ease-out";
        tilt.style.transform = "rotateY(" + (x * max * 2).toFixed(2) + "deg) rotateX(" + (-y * max * 1.3).toFixed(2) + "deg)";
      });
      stage.addEventListener("pointerleave", function () {
        tilt.style.transition = "transform 0.7s cubic-bezier(0.22,1,0.36,1)";
        tilt.style.transform = "rotateY(0deg) rotateX(0deg)";
      });
    });
  }

  /* aantal-steppers */
  document.querySelectorAll(".qty").forEach(function (q) {
    var input = q.querySelector("input");
    q.querySelectorAll("button").forEach(function (b) {
      b.addEventListener("click", function () {
        var v = parseInt(input.value || "1", 10) + (b.dataset.d === "-" ? -1 : 1);
        input.value = Math.max(1, v);
      });
    });
  });

  /* winkelwagen-demo: korte fysieke reactie + tellerbump
     (in de WooCommerce-bouwfase vervangen door echte add-to-cart) */
  var count = 0;
  var counters = document.querySelectorAll(".cart-btn .count");
  document.querySelectorAll("[data-add]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var qty = 1;
      var row = btn.closest(".buy-row");
      if (row) { var inp = row.querySelector(".qty input"); if (inp) qty = parseInt(inp.value, 10) || 1; }
      count += qty;
      counters.forEach(function (c) {
        c.textContent = count;
        c.classList.remove("bump");
        void c.offsetWidth;
        c.classList.add("bump");
      });
      var card = btn.closest(".card, .pdp-info, .stickybuy");
      if (card && !calm) {
        card.style.transition = "transform 0.14s cubic-bezier(0.22,1,0.36,1)";
        card.style.transform = "scale(0.985)";
        setTimeout(function () { card.style.transform = ""; }, 150);
      }
    });
  });

  /* scent explorer */
  var explorer = document.querySelector(".explorer");
  if (explorer) {
    var tabs = explorer.querySelectorAll(".tab");
    var img = explorer.querySelector(".stage img.bottle");
    var copyEl = explorer.querySelector(".scent-copy p");
    var metaTemp = explorer.querySelector(".scent-meta [data-slot='karakter'] b");
    var metaStock = explorer.querySelector(".scent-meta [data-slot='status'] b");
    var cta = explorer.querySelector("[data-slot='cta']");
    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        tabs.forEach(function (t) { t.setAttribute("aria-selected", t === tab ? "true" : "false"); });
        explorer.style.setProperty("--world", tab.dataset.world);
        if (copyEl) copyEl.textContent = tab.dataset.copy;
        if (metaTemp) metaTemp.textContent = tab.dataset.karakter;
        if (metaStock) metaStock.textContent = tab.dataset.status;
        if (cta) {
          cta.href = tab.dataset.url;
          cta.textContent = tab.dataset.ctalabel;
        }
        if (img && img.getAttribute("src") !== tab.dataset.img) {
          img.classList.add("swap");
          setTimeout(function () {
            img.src = tab.dataset.img;
            img.alt = tab.dataset.alt;
            img.onload = function () { img.classList.remove("swap"); };
          }, calm ? 0 : 240);
        }
      });
    });
  }

  /* mobiel menu */
  var menuBtn = document.querySelector(".menu-btn");
  var mobnav = document.getElementById("mobnav");
  if (menuBtn && mobnav) {
    var zetMenu = function (open) {
      mobnav.classList.toggle("open", open);
      menuBtn.setAttribute("aria-expanded", open ? "true" : "false");
      mobnav.setAttribute("aria-hidden", open ? "false" : "true");
      menuBtn.textContent = open ? "Sluit" : "Menu";
    };
    menuBtn.addEventListener("click", function () {
      zetMenu(!mobnav.classList.contains("open"));
    });
    mobnav.addEventListener("click", function (e) {
      if (e.target.closest("a")) zetMenu(false);
    });
    document.addEventListener("keydown", function (e) {
      if ("Escape" === e.key && mobnav.classList.contains("open")) zetMenu(false);
    });
  }

  /* sticky koopbalk op mobiel: tonen zodra de primaire koopknop uit beeld is */
  var buyBtn = document.querySelector(".pdp .buy-row, .pdp .buy-block");
  var sticky = document.querySelector(".stickybuy");
  if (buyBtn && sticky) {
    var bio = new IntersectionObserver(function (entries) {
      sticky.classList.toggle("show", !entries[0].isIntersecting);
    }, { threshold: 0 });
    bio.observe(buyBtn);
  }

  /* formulieren in prototype: nog niet gekoppeld */
  document.querySelectorAll("form[data-proto]").forEach(function (f) {
    f.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var note = f.querySelector(".form-note");
      if (note) note.textContent = "Bedankt. Dit formulier wordt in de shopversie gekoppeld.";
    });
  });
})();
