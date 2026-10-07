var App = (function () {
    function e() {
        function e() {
            window.innerWidth <= 991
                ? document.querySelector("body").classList.contains("alt-menu")
                    ? (o.class.navbar.classList.remove("expand-header"),
                      o.class.overlay.classList.remove("show"),
                      o.id.container.classList.remove("sbar-open"),
                      o.main.classList.remove("sidebar-noneoverflow"))
                    : (o.id.container.classList.add("sidebar-closed"),
                      o.class.overlay.classList.remove("show"))
                : 991 < window.innerWidth &&
                  (document.querySelector("body").classList.contains("alt-menu")
                      ? (o.main.classList.add("sidebar-noneoverflow"),
                        o.id.container.classList.add("sidebar-closed"),
                        o.class.navbar.classList.add("expand-header"),
                        o.class.overlay.classList.add("show"),
                        o.id.container.classList.add("sbar-open"),
                        document
                            .querySelector(
                                '.sidebar-wrapper [aria-expanded="true"]'
                            )
                            .parentNode.querySelector(".collapse")
                            .classList.remove("show"))
                      : (o.id.container.classList.remove("sidebar-closed"),
                        o.class.navbar.classList.remove("expand-header"),
                        o.class.overlay.classList.remove("show"),
                        o.id.container.classList.remove("sbar-open"),
                        o.main.classList.remove("sidebar-noneoverflow")));
        }
        e(),
            window.addEventListener("resize", function (t) {
                window.innerWidth <= 991
                    ? document
                          .querySelector(".main-container")
                          .classList.contains("sbar-open") || e()
                    : 991 < window.innerWidth && e();
            });
    }
    var t = 991,
        o = {
            main: document.querySelector("html, body"),
            id: { container: document.querySelector("#container") },
            class: {
                navbar: document.querySelector(".navbar"),
                overlay: document.querySelector(".overlay"),
                search: document.querySelector(".toggle-search"),
                searchOverlay: document.querySelector(".search-overlay"),
                searchForm: document.querySelector(".search-form-control"),
                mainContainer: document.querySelector(".main-container"),
                mainHeader: document.querySelector(".header.navbar"),
            },
        },
        s = function () {
            var e,
                t = document.querySelectorAll(".sidebar-wrapper li.active")[0];
            t &&
                ((e = t.offsetTop - 50),
                setTimeout(() => {
                    document.querySelector(".menu-categories").scrollTop = e;
                }, 50));
        },
        r = function (e) {
            document.querySelectorAll(".sidebarCollapse").forEach((t) => {
                t.addEventListener("click", function (t) {
                    t.preventDefault(),
                        (getSidebar =
                            document.querySelector(".sidebar-wrapper")),
                        !0 === e &&
                            (document
                                .querySelector(".collapse.submenu")
                                .classList.contains("show")
                                ? (document
                                      .querySelector(".submenu.show")
                                      .classList.add("mini-recent-submenu"),
                                  getSidebar
                                      .querySelector(".collapse.submenu")
                                      .classList.remove("show"),
                                  getSidebar
                                      .querySelector(".collapse.submenu")
                                      .classList.remove("show"),
                                  document
                                      .querySelector(".collapse.submenu")
                                      .parentNode.querySelector(
                                          ".dropdown-toggle"
                                      )
                                      .setAttribute("aria-expanded", "false"))
                                : o.class.mainContainer.classList.contains(
                                      "sidebar-closed"
                                  ) &&
                                  (document
                                      .querySelector(".collapse.submenu")
                                      .classList.contains("recent-submenu") ||
                                      document
                                          .querySelector("li.active .submenu")
                                          .classList.add("recent-submenu"),
                                  getSidebar
                                      .querySelector(
                                          ".collapse.submenu.recent-submenu"
                                      )
                                      .classList.add("show"),
                                  document
                                      .querySelector(
                                          ".collapse.submenu.recent-submenu"
                                      )
                                      .parentNode.querySelector(
                                          ".dropdown-toggle"
                                      )
                                      .setAttribute("aria-expanded", "true"),
                                  document
                                      .querySelector(".submenu")
                                      .classList.remove(
                                          "mini-recent-submenu"
                                      ))),
                        o.class.mainContainer.classList.toggle(
                            "sidebar-closed"
                        ),
                        o.class.mainHeader.classList.toggle("expand-header"),
                        o.class.mainContainer.classList.toggle("sbar-open"),
                        o.class.overlay.classList.toggle("show"),
                        o.main.classList.toggle("sidebar-noneoverflow");
                });
            });
        },
        n = function () {
            ["mouseenter", "mouseleave"].forEach(function (e) {
                document
                    .querySelector(".sidebar-wrapper")
                    .addEventListener(e, function () {
                        document
                            .querySelector("body")
                            .classList.contains("alt-menu")
                            ? document
                                  .querySelector(".main-container")
                                  .classList.contains("sidebar-closed") &&
                              ("mouseenter" === e
                                  ? (document.querySelector(
                                        "li.menu .submenu"
                                    ) &&
                                        document
                                            .querySelector("li.menu .submenu")
                                            .classList.remove("show"),
                                    document
                                        .querySelector(
                                            "li.menu.active .submenu"
                                        )
                                        .classList.add("recent-submenu"),
                                    document
                                        .querySelector("li.menu.active")
                                        .querySelector(
                                            ".collapse.submenu.recent-submenu"
                                        )
                                        .classList.add("show"),
                                    document
                                        .querySelector(
                                            ".collapse.submenu.recent-submenu"
                                        )
                                        .parentNode.querySelector(
                                            ".dropdown-toggle"
                                        )
                                        .setAttribute("aria-expanded", "true"))
                                  : "mouseleave" === e &&
                                    (getMenuList =
                                        document.querySelectorAll(
                                            "li.menu"
                                        )).forEach((e) => {
                                        var t = e.querySelector(
                                            ".collapse.submenu.show"
                                        );
                                        t && t.classList.remove("show"),
                                            (t = e.querySelector(
                                                '.dropdown-toggle[aria-expanded="true"]'
                                            ));
                                        t &&
                                            t.setAttribute(
                                                "aria-expanded",
                                                "false"
                                            );
                                    }))
                            : document
                                  .querySelector(".main-container")
                                  .classList.contains("sidebar-closed") &&
                              ("mouseenter" === e
                                  ? (document
                                        .querySelector("li.menu .submenu")
                                        .classList.remove("show"),
                                    document.querySelector(
                                        "li.menu.active .submenu"
                                    ) &&
                                        (document
                                            .querySelector(
                                                "li.menu.active .submenu"
                                            )
                                            .classList.add("recent-submenu"),
                                        document
                                            .querySelector("li.menu.active")
                                            .querySelector(
                                                ".collapse.submenu.recent-submenu"
                                            )
                                            .classList.add("show"),
                                        document
                                            .querySelector(
                                                ".collapse.submenu.recent-submenu"
                                            )
                                            .parentNode.querySelector(
                                                ".dropdown-toggle"
                                            )
                                            .setAttribute(
                                                "aria-expanded",
                                                "true"
                                            )))
                                  : "mouseleave" === e &&
                                    (getMenuList =
                                        document.querySelectorAll(
                                            "li.menu"
                                        )).forEach((e) => {
                                        var t = e.querySelector(
                                            ".collapse.submenu.show"
                                        );
                                        t && t.classList.remove("show"),
                                            (t = e.querySelector(
                                                '.dropdown-toggle[aria-expanded="true"]'
                                            ));
                                        t &&
                                            t.setAttribute(
                                                "aria-expanded",
                                                "false"
                                            );
                                    }));
                    });
            });
        },
        a = function () {},
        c = function () {
            document
                .querySelector("#dismiss, .overlay")
                .addEventListener("click", function () {
                    o.class.mainContainer.classList.add("sidebar-closed"),
                        o.class.mainContainer.classList.remove("sbar-open"),
                        o.class.overlay.classList.remove("show"),
                        o.main.classList.remove("sidebar-noneoverflow");
                });
        },
        l = function () {
            o.class.search &&
                (o.class.search.addEventListener("click", function (e) {
                    this.classList.add("show-search"),
                        o.class.searchOverlay.classList.add("show"),
                        document
                            .querySelector("body")
                            .classList.add("search-active");
                }),
                o.class.searchOverlay.addEventListener("click", function (e) {
                    this.classList.remove("show"),
                        o.class.search.classList.remove("show-search"),
                        document
                            .querySelector("body")
                            .classList.remove("search-active");
                }),
                document
                    .querySelector(".search-close")
                    .addEventListener("click", function (e) {
                        e.stopPropagation(),
                            o.class.searchOverlay.classList.remove("show"),
                            o.class.search.classList.remove("show-search"),
                            document
                                .querySelector("body")
                                .classList.remove("search-active"),
                            (document.querySelector(
                                ".search-form-control"
                            ).value = "");
                    }));
        },
        i = function (e) {
            var t = document.querySelector(".theme-toggle");
            document.body,
                t &&
                    t.addEventListener("click", function () {
                        var e,
                            t,
                            o,
                            s,
                            r = localStorage.getItem("theme");
                        r = JSON.parse(r);
                        r.settings.layout.darkMode
                            ? ((e = { ...r.settings.layout, darkMode: !1 }),
                              (t = { ...r, settings: { layout: e } }),
                              localStorage.setItem("theme", JSON.stringify(t)),
                              (o = localStorage.getItem("theme")),
                              (s = JSON.parse(o)).settings.layout.darkMode ||
                                  (document.body.classList.remove("dark"),
                                  (ifStarterKit =
                                      "starter-pack" ===
                                      document.body.getAttribute("page"))
                                      ? document
                                            .querySelector(".navbar-logo")
                                            .setAttribute(
                                                "src",
                                                "../../src/assets/img/logo2.svg"
                                            )
                                      : document
                                            .querySelector(".navbar-logo")
                                            .setAttribute(
                                                "src",
                                                s.settings.layout.logo.lightLogo
                                            )))
                            : ((e = { ...r.settings.layout, darkMode: !0 }),
                              (t = { ...r, settings: { layout: e } }),
                              localStorage.setItem("theme", JSON.stringify(t)),
                              (o = localStorage.getItem("theme")),
                              (s = JSON.parse(o)).settings.layout.darkMode &&
                                  (document.body.classList.add("dark"),
                                  (ifStarterKit =
                                      "starter-pack" ===
                                      document.body.getAttribute("page"))
                                      ? document
                                            .querySelector(".navbar-logo")
                                            .setAttribute(
                                                "src",
                                                "../../src/assets/img/logo.svg"
                                            )
                                      : document
                                            .querySelector(".navbar-logo")
                                            .setAttribute(
                                                "src",
                                                s.settings.layout.logo.darkLogo
                                            )));
                    });
        },
        u = function () {
            document.querySelector(".menu-categories") &&
                new PerfectScrollbar(".menu-categories", {
                    wheelSpeed: 0.5,
                    swipeEasing: !0,
                    minScrollbarLength: 40,
                    maxScrollbarLength: 300,
                });
        },
        d = function () {
            document.querySelector(".notification-scroll") &&
                new PerfectScrollbar(".notification-scroll", {
                    wheelSpeed: 0.5,
                    swipeEasing: !0,
                    minScrollbarLength: 40,
                    maxScrollbarLength: 300,
                });
        },
        m = function () {
            function e(e) {
                (e = e || window.event).preventDefault && e.preventDefault(),
                    (e.returnValue = !1),
                    (t.scrollTop -= e.wheelDeltaY);
            }
            var t = document.querySelectorAll(
                "#sidebar, .user-profile-dropdown .dropdown-menu, .notification-dropdown .dropdown-menu,  .language-dropdown .dropdown-menu"
            );
            t.forEach((t) => {
                t.addEventListener("mousewheel", e),
                    t.addEventListener("DOMMouseScroll", e);
            });
        },
        b = function () {
            o.class.search &&
                Mousetrap.bind("ctrl+/", function () {
                    return (
                        document.body.classList.add("search-active"),
                        o.class.search.classList.add("show-search"),
                        o.class.searchOverlay.classList.add("show"),
                        o.class.searchForm.focus(),
                        !1
                    );
                });
        },
        y = function () {
            var e = document.querySelectorAll(".bs-tooltip");
            for (let t = 0; t < e.length; t++) new bootstrap.Tooltip(e[t]);
        },
        v = function () {
            var e = document.querySelectorAll(".bs-popover");
            for (let t = 0; t < e.length; t++) new bootstrap.Popover(e[t]);
        },
        p = function () {
            document.body.classList.contains("alt-menu") &&
                document
                    .querySelector(
                        '.sidebar-wrapper li.menu.active [aria-expanded="true"]'
                    )
                    .setAttribute("aria-expanded", "false");
        },
        S = function () {
            (getAllBtn =
                document.querySelectorAll("button.btn, a.btn")).forEach((e) => {
                e.classList.contains("_no--effects") ||
                    e.classList.add("_effect--ripple");
            }),
                document.querySelector("._effect--ripple") &&
                    (Waves.attach("._effect--ripple", "waves-light"),
                    Waves.init());
        },
        h = function () {
            for (
                var e = document.querySelectorAll(
                        ".more-dropdown .dropdown-item"
                    ),
                    t = 0;
                t < e.length;
                t++
            )
                e[t].addEventListener("click", function () {
                    document.querySelectorAll(
                        ".more-dropdown .dropdown-toggle > span"
                    )[0].innerText = this.getAttribute("data-value");
                });
        },
        w = function () {
            window.bootstrap.Dropdown.prototype._detectNavbar = function () {
                return !1;
            };
        },
        g = function () {
            window.innerWidth <= t && (s(), r());
        },
        L = function () {
            window.addEventListener("resize", function (e) {
                e.preventDefault(), window.innerWidth <= t && a();
            });
        },
        f = function () {
            var e = window.innerWidth;
            t < e && (s(), r(), n());
        },
        q = function () {
            window.addEventListener("resize", function (e) {
                e.preventDefault(), (e = window.innerWidth), t < e && n();
            });
        };
    return {
        init: function (t) {
            c(),
                l(),
                i(t),
                f(),
                q(),
                g(),
                L(),
                e(),
                u(),
                d(),
                m(),
                b(),
                y(),
                v(),
                p(),
                S(),
                h(),
                w();
        },
    };
})();
window.addEventListener("load", function () {
    App.init("layout");
});
