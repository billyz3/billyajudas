document.addEventListener('DOMContentLoaded', () => {
  document.documentElement.classList.add('js');
  const toggle = document.querySelector('.menu-toggle'), menu = document.querySelector('#main-menu');
  if (toggle && menu) {
    const setMenuOpen = open => {
      menu.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', String(open));
    };

    toggle.addEventListener('click', () => setMenuOpen(!menu.classList.contains('open')));
    menu.addEventListener('click', event => {
      if (event.target.closest('a')) setMenuOpen(false);
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && menu.classList.contains('open')) {
        setMenuOpen(false);
        toggle.focus();
      }
    });

    const desktopQuery = window.matchMedia('(min-width: 641px)');
    const resetMenuOnDesktop = event => {
      if (event.matches) setMenuOpen(false);
    };
    if (desktopQuery.addEventListener) desktopQuery.addEventListener('change', resetMenuOnDesktop);
    else desktopQuery.addListener(resetMenuOnDesktop);
  }
  document.querySelectorAll('[data-carousel]').forEach(carousel => {
    const track = carousel.querySelector('.carousel-track');
    if (!track) return;
    carousel.querySelectorAll('[data-direction]').forEach(button => button.addEventListener('click', () => {
      const amount = track.clientWidth * .82 * (button.dataset.direction === 'next' ? 1 : -1);
      track.scrollBy({left: amount, behavior: 'smooth'});
    }));
  });
});
