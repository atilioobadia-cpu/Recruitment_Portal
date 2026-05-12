const root = document.documentElement;
const themeToggleButtons = document.querySelectorAll('[data-theme-toggle]');
const navToggleButton = document.querySelector('[data-nav-toggle]');
const navMenu = document.querySelector('[data-nav-menu]');
const navCloseButton = document.querySelector('[data-nav-close]');
const sidebarToggleButton = document.querySelector('[data-sidebar-toggle]');
const sidebar = document.querySelector('[data-sidebar]');
const flashMessages = document.querySelectorAll('[data-flash]');
const roleSelect = document.querySelector('[data-role-select]');
const employerFields = document.querySelector('[data-employer-fields]');
const responsiveTables = document.querySelectorAll('.table-wrap table');
const themeStorageKey = 'bb-theme-v2';

const savedTheme = localStorage.getItem(themeStorageKey);
const initialTheme = savedTheme || 'light';

root.setAttribute('data-theme', initialTheme);
document.body.classList.add('is-ready');

const setNavState = (isOpen) => {
    if (!navToggleButton || !navMenu) {
        return;
    }

    navMenu.classList.toggle('is-open', isOpen);
    navToggleButton.setAttribute('aria-expanded', String(isOpen));
    document.body.classList.toggle('nav-open', isOpen);
    if (isOpen) {
        navMenu.scrollTop = 0;
    }
};

const setSidebarState = (isOpen) => {
    if (!sidebarToggleButton || !sidebar) {
        return;
    }

    sidebar.classList.toggle('is-open', isOpen);
    sidebarToggleButton.setAttribute('aria-expanded', String(isOpen));
    document.body.classList.toggle('sidebar-open', isOpen);
};

const syncThemeButtons = () => {
    const nextLabel = root.getAttribute('data-theme') === 'dark' ? 'Light' : 'Dark';
    themeToggleButtons.forEach((button) => {
        button.textContent = nextLabel;
        button.setAttribute('aria-pressed', String(root.getAttribute('data-theme') === 'dark'));
    });
};

const toggleTheme = () => {
    const nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', nextTheme);
    localStorage.setItem(themeStorageKey, nextTheme);
    syncThemeButtons();
};

syncThemeButtons();

themeToggleButtons.forEach((button) => {
    button.addEventListener('click', toggleTheme);
});

if (navToggleButton && navMenu) {
    navToggleButton.setAttribute('aria-expanded', 'false');
    navToggleButton.addEventListener('click', () => {
        setNavState(!navMenu.classList.contains('is-open'));
    });

    if (navCloseButton) {
        navCloseButton.addEventListener('click', () => {
            setNavState(false);
        });
    }

    navMenu.addEventListener('click', (event) => {
        if (event.target === navMenu) {
            setNavState(false);
        }
    });

    navMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            setNavState(false);
        });
    });
}

if (sidebarToggleButton && sidebar) {
    sidebarToggleButton.setAttribute('aria-expanded', 'false');
    sidebarToggleButton.addEventListener('click', () => {
        setSidebarState(!sidebar.classList.contains('is-open'));
    });

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            setSidebarState(false);
        });
    });
}

document.addEventListener('click', (event) => {
    if (navToggleButton && navMenu) {
        const target = event.target;
        if (target instanceof Node && !navMenu.contains(target) && !navToggleButton.contains(target)) {
            setNavState(false);
        }
    }

    if (sidebarToggleButton && sidebar) {
        const target = event.target;
        if (target instanceof Node && !sidebar.contains(target) && !sidebarToggleButton.contains(target)) {
            setSidebarState(false);
        }
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    if (navToggleButton && navMenu) {
        setNavState(false);
    }

    if (sidebarToggleButton && sidebar) {
        setSidebarState(false);
    }
});

flashMessages.forEach((message) => {
    window.setTimeout(() => {
        message.style.opacity = '0';
        window.setTimeout(() => message.remove(), 250);
    }, 5000);
});

const syncRoleFields = () => {
    if (!roleSelect || !employerFields) {
        return;
    }

    const isEmployer = roleSelect.value === 'employer';
    employerFields.classList.toggle('hidden', !isEmployer);
    employerFields.querySelectorAll('input, textarea').forEach((element) => {
        element.toggleAttribute('required', isEmployer);
    });
};

if (roleSelect) {
    roleSelect.addEventListener('change', syncRoleFields);
    syncRoleFields();
}

responsiveTables.forEach((table) => {
    const headers = Array.from(table.querySelectorAll('thead th')).map((heading) => heading.textContent?.trim() || '');
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach((row) => {
        const cells = row.querySelectorAll('td');
        cells.forEach((cell, index) => {
            if (cell.hasAttribute('colspan')) {
                cell.removeAttribute('data-label');
                return;
            }

            const label = headers[index] || 'Details';
            cell.setAttribute('data-label', label);
        });
    });
});
