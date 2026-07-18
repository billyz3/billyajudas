document.addEventListener('DOMContentLoaded', () => {
  document.documentElement.classList.add('js');
  const toggle = document.querySelector('.menu-toggle'), menu = document.querySelector('#main-menu');
  if (toggle && menu) toggle.addEventListener('click', () => { const open = menu.classList.toggle('open'); toggle.setAttribute('aria-expanded', String(open)); });
  document.querySelectorAll('[data-carousel]').forEach(carousel => {
    const track = carousel.querySelector('.carousel-track');
    carousel.querySelectorAll('[data-direction]').forEach(button => button.addEventListener('click', () => {
      const amount = track.clientWidth * .82 * (button.dataset.direction === 'next' ? 1 : -1);
      track.scrollBy({left: amount, behavior: 'smooth'});
    }));
  });
});
