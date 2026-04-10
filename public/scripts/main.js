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
