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

  /* sticky koopbalk op mobiel: we kijken naar de koopknoppen zelf, niet naar het
     hele koopblok. Is geen enkele knop voor minstens de helft in beeld (onder de
     vaste kop), dan verschijnt de balk. Zo staat er altijd een koopknop in beeld,
     en dekt de balk de kop niet af zolang de echte knop al zichtbaar is. */
  var sticky = document.querySelector(".stickybuy");
  var koopKnoppen = document.querySelectorAll(".pdp .single_add_to_cart_button, .pdp .set-add, .pdp .duo-add");
  if (sticky && koopKnoppen.length) {
    if ("IntersectionObserver" in window) {
      var zicht = [];
      var bio = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          var i = Array.prototype.indexOf.call(koopKnoppen, e.target);
          zicht[i] = e.isIntersecting && e.intersectionRatio >= 0.5;
        });
        sticky.classList.toggle("show", zicht.indexOf(true) === -1);
      }, { threshold: [0, 0.5, 1], rootMargin: "-64px 0px 0px 0px" });
      koopKnoppen.forEach(function (k) { bio.observe(k); });
    } else {
      sticky.classList.add("show");
    }
  }

  /* losse knop in de stickybalk: via het echte koopformulier (POST) toevoegen, zodat
     herladen niet ongemerkt nog een flesje toevoegt. Zonder JS blijft de link werken. */
  var stickyLos = document.querySelector(".stickybuy [data-sim-koop]");
  var hoofdKnop = document.querySelector(".pdp form.cart .single_add_to_cart_button");
  if (stickyLos && hoofdKnop) {
    stickyLos.addEventListener("click", function (ev) {
      ev.preventDefault();
      var aantal = hoofdKnop.form && hoofdKnop.form.querySelector("input.qty");
      if (aantal && stickyLos.getAttribute("data-sim-koop")) aantal.value = stickyLos.getAttribute("data-sim-koop");
      hoofdKnop.click();
    });
  }

  /* reviews: bij 0 reviews staat het blok verborgen (geen lege 'Reviews (0)'). Komt
     iemand via een reviewlink of na het plaatsen van een review (#reviews,
     #review_form, #comment-12), dan tonen en openen we het alsnog. */
  var rvBody = document.querySelector(".pdp-accs .sim-reviews");
  var rvAcc = rvBody && rvBody.closest("details");
  if (rvAcc && rvAcc.hidden) {
    var toonReviews = function () {
      var h = window.location.hash;
      if (!/^#(reviews|sim-reviews|review_form|review_form_wrapper|respond|comments|comment-\d+)$/.test(h)) return;
      rvAcc.hidden = false;
      rvAcc.open = true;
      (document.querySelector(h) || rvAcc).scrollIntoView({ block: "start" });
    };
    // pas na het parsen: de Turnstile-lader in de footer luistert dan al naar 'toggle'
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", toonReviews);
    } else {
      toonReviews();
    }
    window.addEventListener("hashchange", toonReviews);
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
