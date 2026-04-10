// Active sidebar nav item based on current page
var currentPath = window.location.pathname.replace(/^\/|\/$/g, '');
var navItems = document.querySelectorAll('.sidebar-nav-item[data-page]');

navItems.forEach(function (item) {
    item.classList.remove('active');
    var page = item.getAttribute('data-page');
    if (page === currentPath || (currentPath === '' && page === 'dashboard')) {
        item.classList.add('active');
    }
});

// ===== Recipe filters (filter pills) =====
(function () {
    var pills = document.querySelectorAll('.filter-pill[data-filter]');
    var cards = document.querySelectorAll('.recipe-card[data-tags]');
    if (!pills.length || !cards.length) return;

    pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            var filter = pill.getAttribute('data-filter');

            pills.forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');

            cards.forEach(function (card) {
                if (filter === 'all') {
                    card.style.display = '';
                    return;
                }
                var tags = card.getAttribute('data-tags').split(',');
                if (tags.indexOf(filter) !== -1) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
})();

// ===== Heart / favorite toggle =====
(function () {
    var hearts = document.querySelectorAll('.recipe-card-favorite');
    if (!hearts.length) return;

    hearts.forEach(function (heart) {
        heart.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            heart.classList.toggle('active');
            var icon = heart.querySelector('i');
            if (heart.classList.contains('active')) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
            }
        });
    });
})();

// ===== Sort dropdown (visual sort by title) =====
(function () {
    var sortSelect = document.querySelector('.recipes-sort select');
    var grid = document.querySelector('.recipes-library-grid');
    if (!sortSelect || !grid) return;

    sortSelect.addEventListener('change', function () {
        var cards = Array.prototype.slice.call(grid.querySelectorAll('.recipe-card'));
        var mode = sortSelect.value.toLowerCase();

        cards.sort(function (a, b) {
            if (mode.indexOf('calories') !== -1) {
                var ca = parseInt(a.querySelector('.recipe-card-calories').textContent) || 0;
                var cb = parseInt(b.querySelector('.recipe-card-calories').textContent) || 0;
                return ca - cb;
            }
            if (mode.indexOf('prep') !== -1 || mode.indexOf('time') !== -1) {
                var ta = parseInt(a.querySelector('.recipe-card-time').textContent) || 0;
                var tb = parseInt(b.querySelector('.recipe-card-time').textContent) || 0;
                return ta - tb;
            }
            // Newest / Popularity -> alphabetical fallback
            var na = a.querySelector('.recipe-card-title').textContent;
            var nb = b.querySelector('.recipe-card-title').textContent;
            return na.localeCompare(nb);
        });

        cards.forEach(function (card) {
            grid.appendChild(card);
        });
    });
})();

// ===== Load More (toast: no more recipes) =====
(function () {
    var btn = document.querySelector('.load-more-btn');
    if (!btn) return;

    btn.addEventListener('click', function () {
        btn.textContent = 'No more recipes to load';
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'not-allowed';
    });
})();

// ===== Notification bell (toggle dropdown placeholder) =====
(function () {
    var bell = document.querySelector('.topbar-notification');
    if (!bell) return;

    bell.addEventListener('click', function () {
        bell.classList.toggle('active');
        var badge = bell.querySelector('.topbar-notification-badge');
        if (badge) badge.style.display = bell.classList.contains('active') ? 'none' : '';
    });
})();

// ===== Login tabs (signin / signup) =====
(function () {
    var tabs = document.querySelectorAll('.login-tab[data-login-tab]');
    if (!tabs.length) return;

    var forms = document.querySelectorAll('[data-login-form]');
    var switchLinks = document.querySelectorAll('[data-switch-to]');

    function show(target) {
        tabs.forEach(function (t) {
            t.classList.toggle('active', t.getAttribute('data-login-tab') === target);
        });
        forms.forEach(function (f) {
            f.style.display = f.getAttribute('data-login-form') === target ? '' : 'none';
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            show(tab.getAttribute('data-login-tab'));
        });
    });

    switchLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            show(link.getAttribute('data-switch-to'));
        });
    });
})();

// ===== Password visibility toggle =====
(function () {
    var toggles = document.querySelectorAll('[data-toggle-password]');
    if (!toggles.length) return;

    toggles.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input');
            var icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
})();

// ===== Mobile sidebar toggle =====
(function () {
    var toggle = document.querySelector('.topbar-menu-toggle');
    var sidebar = document.querySelector('.sidebar');
    var backdrop = document.querySelector('.sidebar-backdrop');
    if (!toggle || !sidebar || !backdrop) return;

    function open() {
        sidebar.classList.add('open');
        backdrop.classList.add('active');
    }

    function close() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('active');
    }

    toggle.addEventListener('click', function () {
        if (sidebar.classList.contains('open')) {
            close();
        } else {
            open();
        }
    });

    backdrop.addEventListener('click', close);

    // Close when clicking a nav link
    document.querySelectorAll('.sidebar-nav-item').forEach(function (link) {
        link.addEventListener('click', close);
    });
})();

// ===== Creator step navigation =====
(function () {
    var prevBtn = document.getElementById('creator-prev');
    var nextBtn = document.getElementById('creator-next');
    if (!prevBtn || !nextBtn) return;

    var panels = document.querySelectorAll('.creator-step-panel');
    var stepIndicators = document.querySelectorAll('.creator-step[data-step]');
    var currentStep = 1;
    var totalSteps = panels.length;

    function showStep(step) {
        // Hide all panels
        panels.forEach(function (panel) {
            panel.style.display = 'none';
        });

        // Show active panel
        var activePanel = document.querySelector('.creator-step-panel[data-creator-step="' + step + '"]');
        if (activePanel) {
            activePanel.style.display = 'block';
        }

        // Update step indicators
        stepIndicators.forEach(function (indicator) {
            var indicatorStep = parseInt(indicator.getAttribute('data-step'));
            indicator.classList.remove('active', 'completed');
            if (indicatorStep === step) {
                indicator.classList.add('active');
            } else if (indicatorStep < step) {
                indicator.classList.add('completed');
            }
        });

        // Update buttons
        if (step === 1) {
            prevBtn.style.display = 'none';
        } else {
            prevBtn.style.display = '';
        }

        if (step === totalSteps) {
            nextBtn.innerHTML = '<i class="fa-solid fa-check"></i> Save Recipe';
        } else {
            nextBtn.innerHTML = 'Next <i class="fa-solid fa-chevron-right"></i>';
        }

        currentStep = step;
    }

    nextBtn.addEventListener('click', function () {
        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    });

    prevBtn.addEventListener('click', function () {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    // Also allow clicking on step indicators
    stepIndicators.forEach(function (indicator) {
        indicator.style.cursor = 'pointer';
        indicator.addEventListener('click', function () {
            var step = parseInt(indicator.getAttribute('data-step'));
            showStep(step);
        });
    });
})();

// ===== Admin tabs switching =====
(function () {
    var tabs = document.querySelectorAll('.admin-tab[data-tab]');
    if (!tabs.length) return;

    var contents = document.querySelectorAll('[data-tab-content]');
    var infoEl = document.querySelector('.admin-pagination-info');

    var counts = {
        active: { shown: '1-6', total: 124 },
        pending: { shown: '1-3', total: 8 },
        suspended: { shown: '1-3', total: 3 }
    };

    var labels = {
        active: 'users',
        pending: 'invites',
        suspended: 'users'
    };

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-tab');

            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');

            contents.forEach(function (c) {
                if (c.getAttribute('data-tab-content') === target) {
                    c.style.display = '';
                } else {
                    c.style.display = 'none';
                }
            });

            if (infoEl && counts[target]) {
                infoEl.textContent = 'Showing ' + counts[target].shown + ' of ' + counts[target].total + ' ' + labels[target];
            }
        });
    });
})();

// ===== Moderation tabs switching =====
(function () {
    var tabs = document.querySelectorAll('.admin-tab[data-mod-tab]');
    if (!tabs.length) return;

    var contents = document.querySelectorAll('[data-mod-content]');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-mod-tab');

            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');

            contents.forEach(function (c) {
                if (c.getAttribute('data-mod-content') === target) {
                    c.style.display = '';
                } else {
                    c.style.display = 'none';
                }
            });
        });
    });
})();

// ===== Cooking mode step navigation =====
(function () {
    var prevBtn = document.getElementById('cooking-prev');
    var nextBtn = document.getElementById('cooking-next');
    if (!prevBtn || !nextBtn) return;

    var panels = document.querySelectorAll('.cooking-step-panel');
    var dots = document.querySelectorAll('.cooking-step-dot[data-dot]');
    var currentEl = document.getElementById('cooking-current');
    var percentEl = document.getElementById('cooking-percent');
    var progressEl = document.getElementById('cooking-progress');
    var ingredientsStepEl = document.getElementById('cooking-ingredients-step');
    var currentStep = 1;
    var totalSteps = panels.length;

    function showStep(step) {
        panels.forEach(function (panel) {
            panel.style.display = 'none';
        });

        var activePanel = document.querySelector('.cooking-step-panel[data-cooking-step="' + step + '"]');
        if (activePanel) {
            activePanel.style.display = 'block';
        }

        // Update dots
        dots.forEach(function (dot) {
            var dotStep = parseInt(dot.getAttribute('data-dot'));
            dot.classList.remove('active', 'completed');
            if (dotStep === step) {
                dot.classList.add('active');
            } else if (dotStep < step) {
                dot.classList.add('completed');
            }
        });

        // Update counter
        var percent = Math.round((step / totalSteps) * 100);
        currentEl.textContent = step;
        percentEl.textContent = percent;
        progressEl.style.width = percent + '%';
        ingredientsStepEl.textContent = step;

        // Update buttons
        if (step === 1) {
            prevBtn.style.visibility = 'hidden';
        } else {
            prevBtn.style.visibility = 'visible';
        }

        if (step === totalSteps) {
            nextBtn.innerHTML = '<i class="fa-solid fa-check"></i> Finish';
        } else {
            nextBtn.innerHTML = 'Next Step <i class="fa-solid fa-chevron-right"></i>';
        }

        currentStep = step;
    }

    nextBtn.addEventListener('click', function () {
        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        } else {
            window.location.href = '/dashboard';
        }
    });

    prevBtn.addEventListener('click', function () {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    dots.forEach(function (dot) {
        dot.style.cursor = 'pointer';
        dot.addEventListener('click', function () {
            var step = parseInt(dot.getAttribute('data-dot'));
            showStep(step);
        });
    });
})();
