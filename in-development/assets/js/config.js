"use strict";

/* -------------------------------------------------------------------------- */
/*                              Config                                        */
/* -------------------------------------------------------------------------- */
var CONFIG = {
  isNavbarVerticalCollapsed: false,
  theme: 'light',
  isRTL: false,
  isFluid: true,
  navbarStyle: 'transparent',
  navbarPosition: 'vertical'
};

// Always overwrite localStorage values
Object.keys(CONFIG).forEach(function (key) {
  localStorage.setItem(key, CONFIG[key]);
});

// Apply DOM changes based on config
if (JSON.parse(localStorage.getItem('isNavbarVerticalCollapsed'))) {
  document.documentElement.classList.add('navbar-vertical-collapsed');
}

if (localStorage.getItem('theme') === 'dark') {
  document.documentElement.setAttribute('data-bs-theme', 'dark');
} else if (localStorage.getItem('theme') === 'auto') {
  document.documentElement.setAttribute(
    'data-bs-theme',
    window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  );
} else {
  document.documentElement.setAttribute('data-bs-theme', 'light');
}
