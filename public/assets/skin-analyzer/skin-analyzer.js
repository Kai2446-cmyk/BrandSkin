(function(){
  document.querySelectorAll('[data-action="back"]').forEach((button) => {
    button.addEventListener('click', () => {
      if (window.history.length > 1) window.history.back();
      else window.location.href = 'index.html';
    });
  });

  const track = document.querySelector('[data-carousel-track]');

  if (track) {
    let carouselTimer = null;

    const startCarousel = () => {
      carouselTimer = window.setInterval(() => {
        const item = track.querySelector('.skin-item');
        const step = item ? item.offsetWidth + 16 : 104;
        const maxScroll = track.scrollWidth - track.clientWidth - 4;

        if (track.scrollLeft >= maxScroll) {
          track.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
          track.scrollBy({ left: step, behavior: 'smooth' });
        }
      }, 2600);
    };

    const stopCarousel = () => {
      if (carouselTimer) window.clearInterval(carouselTimer);
    };

    track.addEventListener('mouseenter', stopCarousel);
    track.addEventListener('mouseleave', startCarousel);
    track.addEventListener('touchstart', stopCarousel, { passive: true });
    track.addEventListener('touchend', startCarousel, { passive: true });

    startCarousel();
  }
})();