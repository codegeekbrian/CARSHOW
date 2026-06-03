(function initCarousels() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('[data-carousel]').forEach(carousel => {
        const track = carousel.querySelector('[data-carousel-track]');
        if (!track) return;

        const items = Array.from(track.children);
        if (items.length <= 1) return;

        let index = 0;
        let timer = null;
        const interval = Number(carousel.dataset.carouselInterval || 5000);
        const progress = carousel.querySelector('[data-carousel-progress]');
        const dotsWrap = carousel.querySelector('[data-carousel-dots]');
        const dots = [];
        let startX = 0;
        let pointerDown = false;

        if (dotsWrap) {
            items.forEach((item, i) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.setAttribute('aria-label', `Show featured vehicle ${i + 1}`);
                dot.addEventListener('click', () => {
                    stop();
                    go(i);
                    start();
                });
                dotsWrap.appendChild(dot);
                dots.push(dot);
            });
        }

        function render() {
            carousel.dataset.carouselIndex = String(index);
            track.style.setProperty('--carousel-index', index);
            const offset = items[index].getBoundingClientRect().left - items[0].getBoundingClientRect().left;
            track.style.setProperty('--carousel-offset', `${offset * -1}px`);
            track.style.transform = `translate3d(${offset * -1}px, 0, 0)`;
            items.forEach((item, i) => {
                item.classList.toggle('is-active', i === index);
                item.setAttribute('aria-hidden', i === index ? 'false' : 'true');
            });
            dots.forEach((dot, i) => {
                dot.classList.toggle('is-active', i === index);
                dot.setAttribute('aria-current', i === index ? 'true' : 'false');
            });
            if (progress) {
                progress.style.transform = `scaleX(${(index + 1) / items.length})`;
            }
        }

        function go(nextIndex) {
            index = (nextIndex + items.length) % items.length;
            render();
        }

        function start() {
            if (reduceMotion || timer) return;
            timer = window.setInterval(() => go(index + 1), interval);
        }

        function stop() {
            if (!timer) return;
            window.clearInterval(timer);
            timer = null;
        }

        carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => {
            stop();
            go(index - 1);
            start();
        });
        carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => {
            stop();
            go(index + 1);
            start();
        });
        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop);
        carousel.addEventListener('focusout', start);

        function isInsideCarousel(e) {
            const rect = carousel.getBoundingClientRect();
            return e.clientX >= rect.left && e.clientX <= rect.right && e.clientY >= rect.top && e.clientY <= rect.bottom;
        }

        function beginPointer(e) {
            pointerDown = true;
            startX = e.clientX;
        }

        carousel.addEventListener('pointerdown', beginPointer);
        carousel.addEventListener('mousedown', beginPointer);
        document.addEventListener('pointerdown', (e) => {
            if (!pointerDown && isInsideCarousel(e)) beginPointer(e);
        });
        document.addEventListener('mousedown', (e) => {
            if (!pointerDown && isInsideCarousel(e)) beginPointer(e);
        });
        function finishPointer(e) {
            if (!pointerDown) return;
            pointerDown = false;
            const delta = e.clientX - startX;
            if (Math.abs(delta) < 48) return;
            stop();
            go(delta > 0 ? index - 1 : index + 1);
            start();
        }

        document.addEventListener('pointerup', finishPointer);
        document.addEventListener('mouseup', finishPointer);
        document.addEventListener('pointercancel', () => {
            pointerDown = false;
        });
        window.addEventListener('resize', render);

        render();
        start();
    });
})();

(function initLightbox() {
    const gallery = document.querySelector('.vehicle-gallery');
    const mainImg = document.querySelector('.vehicle-detail-media img');
    if (!gallery && !mainImg) return;

    let images = [];
    const galleryImgs = gallery ? gallery.querySelectorAll('img') : [];
    if (galleryImgs.length) {
        images = Array.from(galleryImgs).map(img => img.src);
    } else if (mainImg) {
        images = [mainImg.src];
    }
    if (!images.length) return;

    const lb = document.createElement('div');
    lb.className = 'lightbox';
    lb.hidden = true;
    lb.setAttribute('role', 'dialog');
    lb.setAttribute('aria-modal', 'true');
    lb.setAttribute('aria-hidden', 'true');
    lb.setAttribute('aria-label', 'Vehicle image gallery');
    lb.innerHTML = `<button type="button" class="lightbox-close" aria-label="Close gallery">&times;</button>
        <div class="lightbox-content">
            <button type="button" class="lightbox-prev" aria-label="Previous image">&lsaquo;</button>
            <button type="button" class="lightbox-next" aria-label="Next image">&rsaquo;</button>
        </div>`;
    document.body.appendChild(lb);

    const lightboxContent = lb.querySelector('.lightbox-content');
    let lbImg = null;
    const closeBtn = lb.querySelector('.lightbox-close');
    const prevBtn = lb.querySelector('.lightbox-prev');
    const nextBtn = lb.querySelector('.lightbox-next');
    let currentIdx = 0;
    let lastTrigger = null;

    function getLightboxImage() {
        if (!lbImg) {
            lbImg = document.createElement('img');
            lbImg.className = 'lightbox-image';
            lbImg.alt = 'Gallery image';
            lightboxContent.insertBefore(lbImg, nextBtn);
        }
        return lbImg;
    }

    function open(idx) {
        lastTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        currentIdx = idx;
        getLightboxImage().src = images[currentIdx];
        lb.hidden = false;
        lb.classList.add('is-open');
        lb.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        updateNav();
        closeBtn.focus({ preventScroll: true });
    }

    function close() {
        if (lastTrigger?.isConnected) {
            if (!lastTrigger.matches('a, button, input, select, textarea, [tabindex]')) {
                lastTrigger.setAttribute('tabindex', '-1');
            }
            lastTrigger.focus({ preventScroll: true });
        } else if (document.activeElement instanceof HTMLElement) {
            document.activeElement.blur();
        }
        lb.classList.remove('is-open');
        lb.setAttribute('aria-hidden', 'true');
        lb.hidden = true;
        document.body.style.overflow = '';
    }

    function prev() {
        if (images.length > 1) {
            currentIdx = (currentIdx - 1 + images.length) % images.length;
            getLightboxImage().src = images[currentIdx];
            updateNav();
        }
    }

    function next() {
        if (images.length > 1) {
            currentIdx = (currentIdx + 1) % images.length;
            getLightboxImage().src = images[currentIdx];
            updateNav();
        }
    }

    function updateNav() {
        const hasMultipleImages = images.length > 1;
        prevBtn.hidden = !hasMultipleImages;
        nextBtn.hidden = !hasMultipleImages;
    }

    if (galleryImgs.length) {
        galleryImgs.forEach((img, i) => {
            img.style.cursor = 'zoom-in';
            img.addEventListener('click', () => open(i));
        });
    } else if (mainImg) {
        mainImg.style.cursor = 'zoom-in';
        mainImg.addEventListener('click', () => open(0));
    }

    closeBtn.addEventListener('click', close);
    lb.addEventListener('click', (e) => {
        if (e.target === lb) close();
    });
    prevBtn.addEventListener('click', (e) => { e.stopPropagation(); prev(); });
    nextBtn.addEventListener('click', (e) => { e.stopPropagation(); next(); });

    document.addEventListener('keydown', (e) => {
        if (!lb.classList.contains('is-open')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') prev();
        if (e.key === 'ArrowRight') next();
    });

    let touchStartX = 0;
    lb.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });
    lb.addEventListener('touchend', (e) => {
        const diff = e.changedTouches[0].screenX - touchStartX;
        if (Math.abs(diff) > 50) {
            diff > 0 ? prev() : next();
        }
    }, { passive: true });
})();
