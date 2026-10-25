/* Shipxpeed marketing site — progressive enhancement. All content works without JS. */
(function () {
  "use strict";
  var SX = window.SX_CONFIG || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var inr = function (n) { return "₹" + Math.round(n).toLocaleString("en-IN"); };

  /* ---------- header ---------- */
  var header = $(".site-header");
  if (header) {
    var onScroll = function () { header.classList.toggle("is-scrolled", window.scrollY > 8); };
    window.addEventListener("scroll", onScroll, { passive: true }); onScroll();
  }

  /* ---------- mega menus ---------- */
  var items = $$(".nav-item");
  var closeAll = function (except) {
    items.forEach(function (it) {
      if (it === except) return;
      var b = $(".nav-trigger", it), m = $(".mega", it);
      if (b) b.setAttribute("aria-expanded", "false");
      if (m) m.classList.remove("is-open");
    });
  };
  var openItem = function (it, focusFirst) {
    closeAll(it);
    var b = $(".nav-trigger", it), m = $(".mega", it);
    if (!b || !m) return;
    b.setAttribute("aria-expanded", "true"); m.classList.add("is-open");
    // keep the panel inside the viewport
    m.style.left = ""; var r = m.getBoundingClientRect();
    if (r.right > window.innerWidth - 16) m.style.left = (-12 - (r.right - window.innerWidth + 16)) + "px";
    if (focusFirst) { var f = $("a", m); if (f) f.focus(); }
  };
  items.forEach(function (it) {
    var b = $(".nav-trigger", it), m = $(".mega", it), t;
    if (!b || !m) return;
    var canHover = window.matchMedia("(hover: hover)").matches;
    if (canHover) {
      it.addEventListener("mouseenter", function () { clearTimeout(t); t = setTimeout(function () { openItem(it); }, 60); });
      it.addEventListener("mouseleave", function () { clearTimeout(t); t = setTimeout(function () { b.setAttribute("aria-expanded", "false"); m.classList.remove("is-open"); }, 140); });
    }
    b.addEventListener("click", function () { b.getAttribute("aria-expanded") === "true" ? closeAll() : openItem(it); });
    b.addEventListener("keydown", function (e) { if (e.key === "ArrowDown") { e.preventDefault(); openItem(it, true); } });
    m.addEventListener("keydown", function (e) {
      var links = $$("a", m), i = links.indexOf(document.activeElement);
      if (e.key === "ArrowDown") { e.preventDefault(); (links[i + 1] || links[0]).focus(); }
      if (e.key === "ArrowUp") { e.preventDefault(); (links[i - 1] || links[links.length - 1]).focus(); }
    });
    it.addEventListener("focusout", function (e) { if (!it.contains(e.relatedTarget)) { b.setAttribute("aria-expanded", "false"); m.classList.remove("is-open"); } });
  });
  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    var open = items.filter(function (it) { return $(".mega.is-open", it); })[0];
    closeAll(); if (open) $(".nav-trigger", open).focus();
    closeDrawer();
  });
  document.addEventListener("click", function (e) { if (!e.target.closest(".nav-item")) closeAll(); });

  /* ---------- mobile drawer ---------- */
  var drawer = $("#drawer"), burger = $(".burger"), lastFocus;
  function openDrawer() {
    if (!drawer) return; lastFocus = document.activeElement;
    drawer.classList.add("is-open"); drawer.removeAttribute("hidden"); document.body.classList.add("no-scroll");
    burger && burger.setAttribute("aria-expanded", "true");
    var c = $(".drawer-close", drawer); c && c.focus();
  }
  function closeDrawer() {
    if (!drawer || !drawer.classList.contains("is-open")) return;
    drawer.classList.remove("is-open"); drawer.setAttribute("hidden", ""); document.body.classList.remove("no-scroll");
    burger && burger.setAttribute("aria-expanded", "false"); lastFocus && lastFocus.focus();
  }
  burger && burger.addEventListener("click", openDrawer);
  if (drawer) {
    $$(".drawer-close, .drawer-scrim", drawer).forEach(function (el) { el.addEventListener("click", closeDrawer); });
    $$(".drawer-sec > button", drawer).forEach(function (b) {
      b.addEventListener("click", function () {
        var open = b.getAttribute("aria-expanded") === "true";
        b.setAttribute("aria-expanded", String(!open));
        document.getElementById(b.getAttribute("aria-controls")).hidden = open;
      });
    });
    $$("a", drawer).forEach(function (a) { a.addEventListener("click", closeDrawer); });
    drawer.addEventListener("keydown", function (e) { // focus trap
      if (e.key !== "Tab") return;
      var f = $$("a, button", drawer).filter(function (x) { return x.offsetParent !== null; });
      if (!f.length) return;
      if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
      else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
    });
  }

  /* ---------- tabs (product showcase) ---------- */
  $$("[data-tabs]").forEach(function (root) {
    var tabs = $$('[role="tab"]', root);
    var select = function (tab, focus) {
      tabs.forEach(function (t) {
        var on = t === tab; t.setAttribute("aria-selected", String(on)); t.tabIndex = on ? 0 : -1;
        document.getElementById(t.getAttribute("aria-controls")).hidden = !on;
      });
      if (focus) tab.focus();
    };
    tabs.forEach(function (t, i) {
      t.addEventListener("click", function () { select(t); });
      t.addEventListener("keydown", function (e) {
        var k = e.key, n = null;
        if (k === "ArrowDown" || k === "ArrowRight") n = tabs[(i + 1) % tabs.length];
        if (k === "ArrowUp" || k === "ArrowLeft") n = tabs[(i - 1 + tabs.length) % tabs.length];
        if (k === "Home") n = tabs[0]; if (k === "End") n = tabs[tabs.length - 1];
        if (n) { e.preventDefault(); select(n, true); }
      });
    });
    // deep link: #platform-ndr etc.
    var h = (location.hash || "").replace("#", "");
    var match = tabs.filter(function (t) { return t.dataset.key && h === "platform-" + t.dataset.key; })[0];
    if (match) { select(match); setTimeout(function () { var a = document.getElementById("platform-" + match.dataset.key); (a || root).scrollIntoView(); }, 50); }
    window.addEventListener("hashchange", function () {
      var h2 = location.hash.replace("#", ""); var m2 = tabs.filter(function (t) { return h2 === "platform-" + t.dataset.key; })[0];
      if (m2) { select(m2); root.scrollIntoView({ behavior: reduce ? "auto" : "smooth" }); }
    });
  });

  /* ---------- FAQ accordion ---------- */
  $$(".faq-q").forEach(function (q) {
    q.addEventListener("click", function () {
      var open = q.getAttribute("aria-expanded") === "true";
      q.setAttribute("aria-expanded", String(!open));
      var a = document.getElementById(q.getAttribute("aria-controls")); a.hidden = open;
      q.closest(".faq-item").classList.toggle("is-open", !open);
    });
  });

  /* ---------- courier filter ---------- */
  $$("[data-filter-group]").forEach(function (g) {
    var chips = $$(".chip", g), target = document.getElementById(g.dataset.filterGroup);
    chips.forEach(function (c) {
      c.addEventListener("click", function () {
        chips.forEach(function (x) { x.setAttribute("aria-pressed", String(x === c)); });
        var cat = c.dataset.cat;
        $$("[data-cat]", target).forEach(function (card) { card.hidden = !(cat === "all" || card.dataset.cat === cat); });
      });
    });
  });

  /* ---------- tracking box ---------- */
  $$("form[data-track]").forEach(function (f) {
    f.addEventListener("submit", function (e) {
      var inp = $("input", f), v = (inp.value || "").trim();
      if (!/^[A-Za-z0-9-]{6,30}$/.test(v)) { e.preventDefault(); inp.setAttribute("aria-invalid", "true"); inp.focus(); var m = $(".err-msg", f.parentNode); if (m) m.textContent = "Enter a valid AWB or tracking number (6–30 letters or digits)."; return; }
      inp.removeAttribute("aria-invalid");
    });
  });

  /* ---------- rate calculator (demo unless a real endpoint is configured) ---------- */
  var calc = $("#rate-calc");
  if (calc) {
    var out = $("#rate-out"), srcBadge = $("#rate-src"), wnote = $("#wt-note");
    var METRO = ["11", "40", "56", "60", "70", "50", "41", "38"]; // Delhi, Mumbai, Bengaluru, Chennai, Kolkata, Hyderabad, Pune, Ahmedabad
    var SPECIAL = function (p) { var a = p.slice(0, 2), b = p.slice(0, 3); return a === "78" || a === "79" || a === "18" || a === "19" || b === "744" || b === "194"; }; // NE states, J&K, Andaman, Ladakh
    var zoneOf = function (a, b) {
      if (SPECIAL(a) || SPECIAL(b)) return { z: "E", name: "Special zone (NE, J&K, islands)" };
      if (a.slice(0, 3) === b.slice(0, 3)) return { z: "A", name: "Within city" };
      if (a.slice(0, 2) === b.slice(0, 2)) return { z: "B", name: "Within region" };
      if (METRO.indexOf(a.slice(0, 2)) > -1 && METRO.indexOf(b.slice(0, 2)) > -1) return { z: "C", name: "Metro to metro" };
      return { z: "D", name: "Rest of India" };
    };
    // SAMPLE rate card: price per 500 g slab by zone. Not real Shipxpeed or courier pricing.
    var SERVICES = [
      { id: "s1", name: "Sample Courier A", mode: "Surface", base: { A: 32, B: 38, C: 46, D: 52, E: 68 }, add: { A: 28, B: 33, C: 40, D: 46, E: 60 }, eta: { A: "1–2", B: "2–3", C: "3–4", D: "4–6", E: "6–9" }, max: 50, min: 0 },
      { id: "s2", name: "Sample Courier B", mode: "Air express", base: { A: 45, B: 55, C: 62, D: 74, E: 96 }, add: { A: 40, B: 48, C: 56, D: 66, E: 88 }, eta: { A: "1", B: "1–2", C: "2–3", D: "2–4", E: "4–6" }, max: 10, min: 0 },
      { id: "s3", name: "Sample Courier C", mode: "Surface economy", base: { A: 29, B: 35, C: 43, D: 49, E: 64 }, add: { A: 26, B: 31, C: 38, D: 44, E: 58 }, eta: { A: "2–3", B: "3–4", C: "4–5", D: "5–7", E: "7–10" }, max: 30, min: 0 },
      { id: "s4", name: "Sample Courier D", mode: "Heavy surface", perKg: { A: 18, B: 22, C: 26, D: 30, E: 40 }, minCharge: 220, eta: { A: "2–3", B: "3–4", C: "4–6", D: "5–7", E: "7–10" }, max: 500, min: 5 }
    ];
    var COD = { fixed: 35, pct: 0.02 };

    var num = function (id) { var v = parseFloat($(id, calc).value); return isNaN(v) ? 0 : v; };
    var setErr = function (id, msg) { var el = $(id, calc); el.setAttribute("aria-invalid", msg ? "true" : "false"); var m = document.getElementById(el.id + "-err"); if (m) m.textContent = msg || ""; return !msg; };

    var render = function (ev) {
      if (ev) ev.preventDefault();
      var from = $("#rc-from", calc).value.trim(), to = $("#rc-to", calc).value.trim();
      var w = num("#rc-wt"), L = num("#rc-l"), B = num("#rc-b"), H = num("#rc-h");
      var pay = ($('input[name="rc-pay"]:checked', calc) || {}).value || "prepaid";
      var cv = num("#rc-cod");
      var pinRe = /^[1-9][0-9]{5}$/;
      var ok = true;
      ok = setErr("#rc-from", pinRe.test(from) ? "" : "Enter a 6-digit pickup pincode.") && ok;
      ok = setErr("#rc-to", pinRe.test(to) ? "" : "Enter a 6-digit delivery pincode.") && ok;
      ok = setErr("#rc-wt", w > 0 && w <= 500 ? "" : "Weight must be between 0.1 and 500 kg.") && ok;
      $("#rc-cod-wrap", calc).hidden = pay !== "cod";
      if (pay === "cod") ok = setErr("#rc-cod", cv > 0 ? "" : "Enter the order value to estimate the COD fee.") && ok;
      if (!ok) { out.innerHTML = '<div class="calc-err" role="alert">Check the highlighted fields to see sample rates.</div>'; return; }

      var vol = (L && B && H) ? (L * B * H) / 5000 : 0;
      var chg = Math.max(w, vol);
      var slabs = Math.ceil(chg / 0.5);
      var zone = zoneOf(from, to);
      wnote.innerHTML = "Actual <b>" + w.toFixed(2) + " kg</b> · Volumetric <b>" + vol.toFixed(2) + " kg</b> (L×B×H ÷ 5000) · Chargeable <b>" + (slabs * 0.5).toFixed(1) + " kg</b> · Zone <b>" + zone.z + "</b> " + zone.name;

      if (SX.rateApi && SX.rateApi.endpoint) { return liveRates({ from: from, to: to, weight: w, l: L, b: B, h: H, payment: pay, codValue: cv }); }

      var rows = SERVICES.map(function (s) {
        var serviceable = chg <= s.max && chg >= s.min;
        var price = 0;
        if (s.perKg) price = Math.max(s.minCharge, Math.ceil(chg) * s.perKg[zone.z]);
        else price = s.base[zone.z] + Math.max(0, slabs - 1) * s.add[zone.z];
        var cod = pay === "cod" ? Math.max(COD.fixed, cv * COD.pct) : 0;
        return { s: s, ok: serviceable, price: price * 1.18, cod: cod * 1.18, rto: price * 1.18, eta: s.eta[zone.z],
          why: !serviceable ? (chg > s.max ? "Above " + s.max + " kg limit" : "Minimum " + s.min + " kg") : "" };
      });
      var live = rows.filter(function (r) { return r.ok; }).sort(function (a, b) { return (a.price + a.cod) - (b.price + b.cod); });
      var best = live[0];
      var html = '<div class="tscroll"><table class="rate-table"><caption class="sr-only">Sample courier rates for the entered shipment</caption><thead><tr><th scope="col">Courier (sample)</th><th scope="col">Est. delivery</th><th scope="col">Freight</th><th scope="col">COD fee</th><th scope="col">RTO charge</th><th scope="col">Serviceability</th></tr></thead><tbody>';
      rows.sort(function (a, b) { return (b.ok - a.ok) || ((a.price + a.cod) - (b.price + b.cod)); }).forEach(function (r) {
        html += '<tr class="' + (r === best ? "best" : "") + '"><td><b>' + r.s.name + '</b><small>' + r.s.mode + (r === best ? " · Lowest sample price" : "") + '</small></td>' +
          '<td>' + (r.ok ? r.eta + " days" : '<span class="muted">—</span>') + '</td>' +
          '<td class="price">' + (r.ok ? inr(r.price) : '<span class="muted">—</span>') + '</td>' +
          '<td>' + (pay === "cod" && r.ok ? inr(r.cod) : '<span class="muted">' + (pay === "cod" ? "—" : "Not applicable") + '</span>') + '</td>' +
          '<td>' + (r.ok ? inr(r.rto) : '<span class="muted">—</span>') + '</td>' +
          '<td>' + (r.ok ? '<span class="badge badge--live">Serviceable (sample)</span>' : '<span class="badge badge--planned">' + r.why + '</span>') + '</td></tr>';
      });
      html += "</tbody></table></div>";
      out.innerHTML = html;
      srcBadge.textContent = "Demo data"; srcBadge.className = "badge badge--demo";
    };

    var liveRates = function (payload) {
      out.innerHTML = '<div class="calc-empty" aria-busy="true">Fetching rates…</div>';
      fetch(SX.rateApi.endpoint, { method: "POST", headers: { "Content-Type": "application/json", "Accept": "application/json" }, body: JSON.stringify(payload) })
        .then(function (r) { if (!r.ok) throw new Error("HTTP " + r.status); return r.json(); })
        .then(function (d) {
          var list = (d && d.rates) || [];
          if (!list.length) { out.innerHTML = '<div class="calc-empty">No courier services returned for this route.</div>'; return; }
          var html = '<div class="tscroll"><table class="rate-table"><thead><tr><th>Courier</th><th>Est. delivery</th><th>Freight</th><th>COD fee</th><th>RTO charge</th></tr></thead><tbody>';
          list.forEach(function (r) { html += "<tr><td><b>" + String(r.courier).replace(/</g, "&lt;") + "</b></td><td>" + (r.eta || "—") + "</td><td class=\"price\">" + inr(r.freight) + "</td><td>" + (r.cod != null ? inr(r.cod) : "—") + "</td><td>" + (r.rto != null ? inr(r.rto) : "—") + "</td></tr>"; });
          out.innerHTML = html + "</tbody></table></div>";
          srcBadge.textContent = SX.rateApi.sandbox ? "Sandbox rates" : "Live rates"; srcBadge.className = "badge " + (SX.rateApi.sandbox ? "badge--dev" : "badge--live");
        })
        .catch(function () { out.innerHTML = '<div class="calc-err" role="alert">We could not fetch rates right now. Log in to the seller panel to see your current rates.</div>'; });
    };

    calc.addEventListener("submit", render);
    $$("input", calc).forEach(function (i) { i.addEventListener("change", function () { render(); }); });
    render();
  }

  /* ---------- analytics preview (illustrative) ---------- */
  var an = $("#analytics");
  if (an) {
    var seed = function (s) { return function () { s = (s * 9301 + 49297) % 233280; return s / 233280; }; };
    var svg = $("#an-line", an), tip = $(".an-tooltip", an);
    var draw = function (days) {
      var rnd = seed(days * 7 + 3), n = days <= 7 ? 7 : days <= 30 ? 15 : 13;
      var del = [], rto = [], base = days <= 7 ? 140 : days <= 30 ? 155 : 170;
      for (var i = 0; i < n; i++) { var v = base + i * (days <= 7 ? 4 : 3) + rnd() * 40 - 10; del.push(Math.round(v)); rto.push(Math.round(v * (0.07 + rnd() * 0.04))); }
      var W = 560, H = 210, pl = 34, pr = 10, pt = 10, pb = 26, mv = Math.max.apply(null, del), step = mv > 300 ? 100 : 50, max = Math.ceil(mv / step) * step;
      var x = function (i) { return pl + i * (W - pl - pr) / (n - 1); }, y = function (v) { return pt + (H - pt - pb) * (1 - v / max); };
      var path = function (a) { return a.map(function (v, i) { return (i ? "L" : "M") + x(i).toFixed(1) + " " + y(v).toFixed(1); }).join(" "); };
      var g = "";
      for (var gv = 0; gv <= max; gv += step) { g += '<line class="grid-l" x1="' + pl + '" x2="' + (W - pr) + '" y1="' + y(gv) + '" y2="' + y(gv) + '"/><text class="ax" x="' + (pl - 6) + '" y="' + (y(gv) + 3) + '" text-anchor="end">' + Math.round(gv) + "</text>"; }
      var lab = days <= 7 ? ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"] : null;
      for (var j = 0; j < n; j += (n > 8 ? 2 : 1)) { g += '<text class="ax" x="' + x(j) + '" y="' + (H - 8) + '" text-anchor="middle">' + (lab ? lab[j] : (days <= 30 ? "D" + (j * 2 + 1) : "W" + (j + 1))) + "</text>"; }
      var area = path(del) + " L" + x(n - 1) + " " + y(0) + " L" + x(0) + " " + y(0) + " Z";
      g += '<defs><linearGradient id="ga" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#22C7D6" stop-opacity=".28"/><stop offset="1" stop-color="#22C7D6" stop-opacity="0"/></linearGradient></defs>';
      g += '<path d="' + area + '" fill="url(#ga)"/>';
      g += '<path d="' + path(del) + '" fill="none" stroke="#22C7D6" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round"/>';
      g += '<path d="' + path(rto) + '" fill="none" stroke="#8FB0FF" stroke-width="2" stroke-dasharray="4 4" stroke-linecap="round"/>';
      g += '<circle cx="' + x(n - 1) + '" cy="' + y(del[n - 1]) + '" r="4.5" fill="#0E1729" stroke="#22C7D6" stroke-width="2.4"/>';
      for (var k = 0; k < n; k++) g += '<rect x="' + (x(k) - (W - pl - pr) / (n - 1) / 2) + '" y="' + pt + '" width="' + ((W - pl - pr) / (n - 1)) + '" height="' + (H - pt - pb) + '" fill="transparent" data-i="' + k + '"/>';
      svg.innerHTML = g;
      $$("rect[data-i]", svg).forEach(function (r) {
        r.addEventListener("mouseenter", function () {
          var i = +r.dataset.i, bb = svg.getBoundingClientRect(), ab = an.getBoundingClientRect();
          tip.textContent = "Delivered " + del[i] + " · RTO " + rto[i];
          tip.style.left = (bb.left - ab.left + x(i) / W * bb.width) + "px"; tip.style.top = (bb.top - ab.top + y(del[i]) / H * bb.height) + "px"; tip.style.opacity = 1;
        });
        r.addEventListener("mouseleave", function () { tip.style.opacity = 0; });
      });
      var tot = del.reduce(function (a, b) { return a + b; }, 0) * (days / n), rt = rto.reduce(function (a, b) { return a + b; }, 0) * (days / n);
      var set = function (id, v) { var el = document.getElementById(id); if (el) el.textContent = v; };
      set("k-orders", Math.round(tot * 1.12).toLocaleString("en-IN"));
      set("k-del", (92 + rnd() * 3).toFixed(1) + "%");
      set("k-rto", (rt / tot * 100).toFixed(1) + "%");
      set("k-ndr", (4 + rnd() * 2).toFixed(1) + "%");
      set("k-spend", inr(tot * 1.12 * 61));
      set("k-cod", inr(tot * 0.55 * 640));
      $$(".bar-row", an).forEach(function (row, idx) { var v = Math.round(86 + rnd() * 11 - idx); $("i", row).style.width = v + "%"; $("b", row).textContent = v + "%"; });
    };
    $$(".an-filters button", an).forEach(function (b) {
      b.addEventListener("click", function () { $$(".an-filters button", an).forEach(function (x) { x.setAttribute("aria-pressed", String(x === b)); }); draw(+b.dataset.days); });
    });
    draw(30);
  }

  /* ---------- lead form ---------- */
  $$("form[data-lead]").forEach(function (f) {
    var status = $(".form-status", f), btn = $('button[type="submit"]', f);
    var started = Date.now();
    var ts = $('input[name="_ts"]', f); if (ts) ts.value = String(started);
    var params = new URLSearchParams(location.search);
    var svc = $("#lf-service", f); if (svc && params.get("topic")) { var o = $('option[value="' + params.get("topic") + '"]', svc); if (o) svc.value = o.value; }
    var rules = {
      name: function (v) { return v.trim().length >= 2 ? "" : "Enter your full name."; },
      email: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()) ? "" : "Enter a valid business email address."; },
      phone: function (v) { return /^(\+?91[\s-]?)?[6-9]\d{9}$/.test(v.replace(/[\s-]/g, "")) ? "" : "Enter a 10-digit Indian mobile number."; },
      company: function (v) { return v.trim().length >= 2 ? "" : "Enter your business name."; },
      volume: function (v) { return v ? "" : "Choose your monthly shipment volume."; },
      service: function (v) { return v ? "" : "Choose the service you are interested in."; },
      message: function (v) { return v.length <= 2000 ? "" : "Keep the message under 2000 characters."; }
    };
    var check = function (el) {
      var r = rules[el.name]; if (!r) return true;
      var msg = r(el.value || ""); el.setAttribute("aria-invalid", msg ? "true" : "false");
      var m = document.getElementById(el.id + "-err"); if (m) m.textContent = msg; return !msg;
    };
    $$("input, select, textarea", f).forEach(function (el) { el.addEventListener("blur", function () { if (el.value) check(el); }); });
    var show = function (kind, html) { status.hidden = false; status.className = "form-status alert alert--" + kind; status.innerHTML = html; status.focus(); };
    f.addEventListener("submit", function (e) {
      e.preventDefault();
      var els = $$("input, select, textarea", f).filter(function (x) { return rules[x.name]; });
      var bad = els.filter(function (x) { return !check(x); });
      if (bad.length) { bad[0].focus(); show("err", "Please fix the " + bad.length + " highlighted field" + (bad.length > 1 ? "s" : "") + "."); return; }
      var contactLine = SX.contact ? (" You can also write to <b>" + SX.contact.email + "</b> or call <b>" + SX.contact.phone + "</b>.") : "";
      if (!SX.lead || !SX.lead.endpoint) { show("info", "This form is not connected to a delivery address yet, so nothing was sent." + contactLine); return; }
      var data = {}; new FormData(f).forEach(function (v, k) { data[k] = v; });
      btn.setAttribute("aria-busy", "true"); btn.disabled = true; var label = btn.textContent; btn.textContent = "Sending…";
      fetch(SX.lead.endpoint, { method: "POST", headers: { "Content-Type": "application/json", "Accept": "application/json" }, body: JSON.stringify(data) })
        .then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, status: r.status, d: d }; }); })
        .then(function (res) {
          if (res.ok && res.d && res.d.ok) { f.reset(); show("ok", "<b>Thanks, " + String(data.name).split(" ")[0].replace(/</g, "&lt;") + ".</b> Your request reached our team. We usually reply within one business day."); return; }
          if (res.d && res.d.error === "not_configured") { show("info", "This form is not connected to a delivery address yet, so nothing was sent." + contactLine); return; }
          if (res.status === 429) { show("err", "Too many requests from this connection. Please wait a few minutes and try again."); return; }
          if (res.status === 422 && res.d && res.d.errors) { Object.keys(res.d.errors).forEach(function (k) { var el = f.elements[k]; if (el) { el.setAttribute("aria-invalid", "true"); var m = document.getElementById(el.id + "-err"); if (m) m.textContent = res.d.errors[k]; } }); show("err", "Please fix the highlighted fields."); return; }
          show("err", "We could not send your request right now." + contactLine);
        })
        .catch(function () { show("err", "We could not reach the form service." + contactLine); })
        .then(function () { btn.removeAttribute("aria-busy"); btn.disabled = false; btn.textContent = label; });
    });
  });

  /* ---------- subtle reveal for below-the-fold blocks ---------- */
  if (!reduce && "IntersectionObserver" in window) {
    var vh = window.innerHeight;
    var els = $$(".reveal").filter(function (el) { return el.getBoundingClientRect().top > vh; });
    var io = new IntersectionObserver(function (ents) { ents.forEach(function (en) { if (en.isIntersecting) { en.target.classList.remove("pre"); io.unobserve(en.target); } }); }, { rootMargin: "0px 0px -8% 0px" });
    els.forEach(function (el) { el.classList.add("pre"); io.observe(el); });
    setTimeout(function () { els.forEach(function (el) { el.classList.remove("pre"); }); }, 4000);
  }

  /* year */
  $$("[data-year]").forEach(function (el) { el.textContent = new Date().getFullYear(); });
})();
