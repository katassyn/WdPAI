// ===== GLOBAL UTILITIES: Modal + Toast =====
(function () {
    function getRoot(id) {
        var root = document.getElementById(id);
        if (!root) {
            root = document.createElement('div');
            root.id = id;
            document.body.appendChild(root);
        }
        return root;
    }

    window.openModal = function (innerHtml, options) {
        options = options || {};
        var root = getRoot('modal-root');
        var size = options.large ? ' modal-lg' : '';
        var overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML = '<div class="modal' + size + '">' + innerHtml + '</div>';
        root.appendChild(overlay);

        // Close on backdrop click
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) window.closeModal();
        });

        // Close on X button
        var closeBtn = overlay.querySelector('.modal-close');
        if (closeBtn) closeBtn.addEventListener('click', window.closeModal);

        return overlay;
    };

    window.closeModal = function () {
        var root = document.getElementById('modal-root');
        if (root) root.innerHTML = '';
    };

    window.confirmModal = function (title, message, onConfirm, options) {
        options = options || {};
        var confirmText = options.confirmText || 'Confirm';
        var confirmClass = options.danger ? 'btn-danger' : 'btn-primary';

        var html =
            '<div class="modal-header">' +
            '<h2>' + title + '</h2>' +
            '<button class="modal-close" type="button"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>' +
            '<div class="modal-body"><p>' + message + '</p></div>' +
            '<div class="modal-footer">' +
            '<button class="btn btn-outline" data-cancel>Cancel</button>' +
            '<button class="btn ' + confirmClass + '" data-ok>' + confirmText + '</button>' +
            '</div>';

        var overlay = window.openModal(html);
        overlay.querySelector('[data-cancel]').addEventListener('click', window.closeModal);
        overlay.querySelector('[data-ok]').addEventListener('click', function () {
            window.closeModal();
            if (typeof onConfirm === 'function') onConfirm();
        });
    };

    var toastIcons = {
        success: 'fa-check',
        error: 'fa-xmark',
        info: 'fa-info',
        warning: 'fa-exclamation'
    };

    window.showToast = function (message, type) {
        type = type || 'success';
        var root = getRoot('toast-root');
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML =
            '<div class="toast-icon"><i class="fa-solid ' + (toastIcons[type] || 'fa-check') + '"></i></div>' +
            '<div class="toast-message">' + message + '</div>';
        root.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('removing');
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 250);
        }, 3000);
    };

    // Generic dropdown setup with click-outside-to-close
    window.setupDropdown = function (trigger, dropdown) {
        if (!trigger || !dropdown) return;

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    };
})();

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

// ===== Recipe filters (filter pills + category from URL) =====
(function () {
    var pills = document.querySelectorAll('.filter-pill[data-filter]');
    var cards = document.querySelectorAll('.recipe-card[data-tags]');
    if (!cards.length) return;

    var emptyState = document.getElementById('recipes-empty');
    var grid = document.querySelector('.recipes-library-grid');
    var loadMore = document.querySelector('.load-more');
    var clearBtn = document.getElementById('clear-filters');
    var headerH1 = document.querySelector('.recipes-header h1');

    var currentTag = 'all';
    var currentCategory = null;

    function applyFilters() {
        var visible = 0;
        cards.forEach(function (card) {
            var tags = (card.getAttribute('data-tags') || '').split(',');
            var cat = card.getAttribute('data-category');

            var tagMatch = currentTag === 'all' || tags.indexOf(currentTag) !== -1;
            var catMatch = !currentCategory || cat === currentCategory;

            if (tagMatch && catMatch) {
                card.style.display = '';
                visible++;
            } else {
                card.style.display = 'none';
            }
        });

        if (emptyState && grid) {
            if (visible === 0) {
                emptyState.style.display = '';
                grid.style.display = 'none';
                if (loadMore) loadMore.style.display = 'none';
            } else {
                emptyState.style.display = 'none';
                grid.style.display = '';
                if (loadMore) loadMore.style.display = '';
            }
        }
    }

    // Filter pills click
    pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            currentTag = pill.getAttribute('data-filter');
            pills.forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');
            applyFilters();
        });
    });

    // Read ?category= from URL
    var params = new URLSearchParams(window.location.search);
    var urlCat = params.get('category');
    if (urlCat) {
        currentCategory = urlCat;

        // Add category badge to header
        if (headerH1) {
            var badge = document.createElement('span');
            badge.className = 'recipes-category-badge';
            badge.innerHTML = '<i class="fa-solid fa-tag"></i> ' + urlCat;
            headerH1.appendChild(badge);
        }

        // Mark sidebar category active
        var sidebarCat = document.querySelector('.sidebar-category-item[data-category="' + urlCat + '"]');
        if (sidebarCat) sidebarCat.classList.add('active');
    }

    // Clear filters button
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            currentTag = 'all';
            currentCategory = null;
            pills.forEach(function (p) { p.classList.remove('active'); });
            var allPill = document.querySelector('.filter-pill[data-filter="all"]');
            if (allPill) allPill.classList.add('active');

            // Remove badge
            var existingBadge = document.querySelector('.recipes-category-badge');
            if (existingBadge) existingBadge.remove();

            // Remove sidebar active
            document.querySelectorAll('.sidebar-category-item.active').forEach(function (el) {
                el.classList.remove('active');
            });

            // Clean URL
            if (window.history && history.replaceState) {
                history.replaceState(null, '', window.location.pathname);
            }

            applyFilters();
        });
    }

    applyFilters();
})();

// ===== Recipe detail: hero favorite + ingredient checkboxes =====
(function () {
    var heroFav = document.getElementById('recipe-fav');
    if (heroFav) {
        heroFav.addEventListener('click', function () {
            heroFav.classList.toggle('active');
            var icon = heroFav.querySelector('i');
            if (heroFav.classList.contains('active')) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
                if (window.showToast) window.showToast('Added to favorites', 'success');
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
                if (window.showToast) window.showToast('Removed from favorites', 'info');
            }
        });
    }
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

// ===== Forgot password form =====
(function () {
    var form = document.getElementById('forgot-form');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (window.showToast) window.showToast('Reset link sent to your email', 'success');
        setTimeout(function () { window.location.href = '/login'; }, 1800);
    });
})();

// ===== Sidebar user menu dropdown =====
(function () {
    var trigger = document.getElementById('sidebar-user-menu-trigger');
    var dropdown = document.getElementById('sidebar-user-menu-dropdown');
    if (!trigger || !dropdown) return;

    trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== trigger && !trigger.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });

    var logoutBtn = dropdown.querySelector('[data-action="logout"]');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            e.preventDefault();
            dropdown.classList.remove('open');
            if (window.showToast) window.showToast('Signed out successfully', 'info');
            setTimeout(function () { window.location.href = '/'; }, 800);
        });
    }
})();

// ===== Topbar search suggestions =====
(function () {
    var input = document.getElementById('topbar-search-input');
    var suggestionsBox = document.getElementById('topbar-search-suggestions');
    if (!input || !suggestionsBox) return;

    var data = [
        { name: 'Grilled Salmon with Asparagus', type: 'recipe', icon: 'fa-utensils' },
        { name: 'Quinoa Superfood Salad', type: 'recipe', icon: 'fa-utensils' },
        { name: 'Avocado Toast with Egg', type: 'recipe', icon: 'fa-utensils' },
        { name: 'Spicy Chicken Stir Fry', type: 'recipe', icon: 'fa-utensils' },
        { name: 'Berry Bliss Smoothie Bowl', type: 'recipe', icon: 'fa-utensils' },
        { name: 'Greek Yogurt Parfait', type: 'recipe', icon: 'fa-utensils' },
        { name: 'Hearty Lentil Soup', type: 'recipe', icon: 'fa-utensils' },
        { name: 'Chicken breast', type: 'ingredient', icon: 'fa-drumstick-bite' },
        { name: 'Avocado', type: 'ingredient', icon: 'fa-leaf' },
        { name: 'Quinoa', type: 'ingredient', icon: 'fa-seedling' },
        { name: 'Salmon fillet', type: 'ingredient', icon: 'fa-fish' },
        { name: 'High Protein', type: 'tag', icon: 'fa-tag' },
        { name: 'Vegetarian', type: 'tag', icon: 'fa-tag' },
        { name: 'Low Carb', type: 'tag', icon: 'fa-tag' },
        { name: 'Under 30 mins', type: 'tag', icon: 'fa-tag' }
    ];

    function render(items, isRecent) {
        if (!items.length) {
            suggestionsBox.innerHTML = '<div class="search-suggestion-empty">No results found</div>';
            return;
        }
        var html = '';
        if (isRecent) {
            html += '<div class="search-suggestion-header">Recent searches</div>';
        }
        items.forEach(function (item) {
            html += '<div class="search-suggestion-item" data-name="' + item.name + '">' +
                '<div class="search-suggestion-icon"><i class="fa-solid ' + item.icon + '"></i></div>' +
                '<div class="search-suggestion-name">' + item.name + '</div>' +
                '<div class="search-suggestion-type">' + item.type + '</div>' +
                '</div>';
        });
        suggestionsBox.innerHTML = html;

        suggestionsBox.querySelectorAll('.search-suggestion-item').forEach(function (el) {
            el.addEventListener('mousedown', function (e) {
                e.preventDefault();
                input.value = el.getAttribute('data-name');
                suggestionsBox.classList.remove('open');
                input.blur();
                if (window.showToast) window.showToast('Searching for "' + el.getAttribute('data-name') + '"', 'info');
            });
        });
    }

    input.addEventListener('focus', function () {
        render(data.slice(0, 5), true);
        suggestionsBox.classList.add('open');
    });

    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        if (!q) {
            render(data.slice(0, 5), true);
        } else {
            var filtered = data.filter(function (it) {
                return it.name.toLowerCase().includes(q);
            });
            render(filtered, false);
        }
        suggestionsBox.classList.add('open');
    });

    input.addEventListener('blur', function () {
        setTimeout(function () { suggestionsBox.classList.remove('open'); }, 180);
    });
})();

// ===== Notification bell + dropdown =====
(function () {
    var bell = document.getElementById('topbar-notification-trigger');
    var dropdown = document.getElementById('topbar-notification-dropdown');
    if (!bell || !dropdown) return;

    bell.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== bell) {
            dropdown.classList.remove('open');
        }
    });

    var markRead = document.getElementById('notification-mark-read');
    if (markRead) {
        markRead.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.querySelectorAll('.notification-item.unread').forEach(function (item) {
                item.classList.remove('unread');
                var dot = item.querySelector('.notification-unread-dot');
                if (dot) dot.remove();
            });
            var badge = bell.querySelector('.topbar-notification-badge');
            if (badge) badge.style.display = 'none';
            if (window.showToast) window.showToast('All notifications marked as read', 'success');
        });
    }

    dropdown.querySelectorAll('.notification-item').forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            dropdown.classList.remove('open');
            if (window.showToast) window.showToast('Notification opened', 'info');
        });
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

// ===== Moderation: preview modal + action handlers =====
(function () {
    var previewBtns = document.querySelectorAll('[data-preview]');
    if (!previewBtns.length) return;

    function buildPreviewHtml(item) {
        var img = item.querySelector('.moderation-item-image').style.backgroundImage;
        var title = item.querySelector('h3').textContent;
        var meta = item.querySelectorAll('.moderation-item-meta span');
        var desc = item.querySelector('.moderation-item-desc').textContent;
        var author = meta[0] ? meta[0].textContent.trim() : '';
        var time = meta[1] ? meta[1].textContent.trim() : '';
        var category = meta[2] ? meta[2].textContent.trim() : '';

        return '' +
            '<div class="modal-header">' +
            '<h2>Recipe Preview</h2>' +
            '<button class="modal-close" type="button"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>' +
            '<div class="modal-body">' +
            '<div class="mod-preview-image" style="background-image:' + img + '"></div>' +
            '<h3 class="mod-preview-title">' + title + '</h3>' +
            '<div class="mod-preview-meta">' +
            '<span><i class="fa-solid fa-user"></i> ' + author + '</span>' +
            '<span><i class="fa-solid fa-clock"></i> ' + time + '</span>' +
            '<span><i class="fa-solid fa-tag"></i> ' + category + '</span>' +
            '</div>' +
            '<p>' + desc + '</p>' +
            '<h4 class="mod-preview-subtitle">Ingredients</h4>' +
            '<ul class="mod-preview-list">' +
            '<li>200g main ingredient</li>' +
            '<li>2 tbsp olive oil</li>' +
            '<li>Salt and pepper to taste</li>' +
            '<li>Fresh herbs for garnish</li>' +
            '</ul>' +
            '<h4 class="mod-preview-subtitle">Cooking Steps</h4>' +
            '<ol class="mod-preview-list">' +
            '<li>Prepare all ingredients</li>' +
            '<li>Heat the pan and add oil</li>' +
            '<li>Cook main ingredient until done</li>' +
            '<li>Season and serve</li>' +
            '</ol>' +
            '<div class="mod-preview-nutrition">' +
            '<div><strong>450</strong><span>kcal</span></div>' +
            '<div><strong>32g</strong><span>protein</span></div>' +
            '<div><strong>28g</strong><span>carbs</span></div>' +
            '<div><strong>18g</strong><span>fats</span></div>' +
            '</div>' +
            '</div>' +
            '<div class="modal-footer">' +
            '<button class="btn btn-outline" data-cancel>Close</button>' +
            '<button class="btn btn-danger" data-modal-action="reject"><i class="fa-solid fa-xmark"></i> Reject</button>' +
            '<button class="btn btn-primary" data-modal-action="approve"><i class="fa-solid fa-check"></i> Approve</button>' +
            '</div>';
    }

    previewBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = btn.closest('.moderation-item');
            if (!item) return;
            var overlay = window.openModal(buildPreviewHtml(item));

            overlay.querySelector('[data-cancel]').addEventListener('click', window.closeModal);

            overlay.querySelectorAll('[data-modal-action]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var act = b.getAttribute('data-modal-action');
                    window.closeModal();
                    removeItem(item, act);
                });
            });
        });
    });

    function removeItem(item, action) {
        var messages = {
            approve: { text: 'Recipe approved successfully', type: 'success' },
            reject: { text: 'Recipe rejected', type: 'error' },
            revoke: { text: 'Approval revoked', type: 'info' },
            restore: { text: 'Recipe restored to pending', type: 'success' },
            'clear-flag': { text: 'Flag cleared', type: 'success' },
            remove: { text: 'Recipe removed permanently', type: 'error' }
        };
        var msg = messages[action] || { text: 'Action completed', type: 'info' };

        item.style.transition = 'opacity 0.25s, transform 0.25s';
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        setTimeout(function () {
            if (item.parentNode) item.parentNode.removeChild(item);
        }, 250);

        if (window.showToast) window.showToast(msg.text, msg.type);
    }

    // Direct action button handlers (Approve/Reject/Revoke/Restore/Clear/Remove)
    document.querySelectorAll('[data-mod-action]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = btn.closest('.moderation-item');
            if (!item) return;
            var action = btn.getAttribute('data-mod-action');

            if (action === 'reject' || action === 'remove') {
                window.confirmModal(
                    action === 'remove' ? 'Remove recipe?' : 'Reject recipe?',
                    'This action cannot be undone.',
                    function () { removeItem(item, action); },
                    { danger: true, confirmText: action === 'remove' ? 'Remove' : 'Reject' }
                );
            } else {
                removeItem(item, action);
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

// ===== Settings page: tabs + actions =====
(function () {
    var tabs = document.querySelectorAll('[data-settings-tab]');
    var panels = document.querySelectorAll('[data-settings-content]');
    if (!tabs.length) return;

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-settings-tab');
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            panels.forEach(function (p) {
                if (p.getAttribute('data-settings-content') === target) {
                    p.style.display = '';
                    p.classList.add('active');
                } else {
                    p.style.display = 'none';
                    p.classList.remove('active');
                }
            });
        });
    });

    // Save buttons
    document.querySelectorAll('[data-settings-save]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (window.showToast) window.showToast('Settings saved successfully', 'success');
        });
    });

    // Export
    var exportBtn = document.querySelector('[data-settings-export]');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            if (window.showToast) window.showToast('Data export started', 'info');
        });
    }

    // Delete account with confirm
    var deleteBtn = document.querySelector('[data-settings-delete]');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            if (window.confirmModal) {
                window.confirmModal(
                    'Delete account?',
                    'This will permanently delete your account and all associated data. This action cannot be undone.',
                    function () {
                        if (window.showToast) window.showToast('Account deletion requested', 'error');
                    },
                    { danger: true, confirmText: 'Delete account' }
                );
            }
        });
    }
})();
