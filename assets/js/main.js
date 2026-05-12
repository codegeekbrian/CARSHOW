// main.js — OWN IT & DRIVE IT STORE

// ── Dark / Light theme ─────────────────────────────────────
(function initTheme(){
  const prefers = matchMedia('(prefers-color-scheme:light)');
  const saved  = localStorage.getItem('site-theme');
  const mode   = saved || (prefers.matches ? 'light' : 'dark');
  document.documentElement.setAttribute('data-theme', mode);
})();

document.addEventListener('DOMContentLoaded', ()=>{
  const btn = document.getElementById('theme-toggle');
  if(!btn) return;
  const current = document.documentElement.getAttribute('data-theme');
  btn.setAttribute('aria-label', current==='light'?'Switch to dark mode':'Switch to light mode');
  btn.querySelector('.theme-label') && (btn.querySelector('.theme-label').textContent = current==='light'?'Dark':'Light');
  btn.addEventListener('click', ()=>{
    const next = document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('site-theme', next);
    btn.setAttribute('aria-label', next==='light'?'Switch to dark mode':'Switch to light mode');
    btn.querySelector('.theme-label') && (btn.querySelector('.theme-label').textContent = next==='light'?'Dark':'Light');
  });
});

function toggleNav() {
  const links = document.getElementById('navLinks');
  const toggle = document.getElementById('navToggle');
  if (links) {
    links.classList.toggle('open');
    if (toggle) {
      toggle.classList.toggle('open');
    }
  }
}

// Mobile dropdown toggle
document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
  const trigger = dropdown.querySelector('.dropdown-trigger');
  if (trigger) {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const menu = dropdown.querySelector('.dropdown-menu');
      if (menu) {
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
      }
    });
  }
});

// Close nav when a link is clicked (mobile)
document.querySelectorAll('.navbar-links a').forEach(a => {
  a.addEventListener('click', () => {
    const links = document.getElementById('navLinks');
    if (links) links.classList.remove('open');
  });
});

// ── Back to top ────────────────────────────────────────────
const backTop = document.getElementById('back-top');
if (backTop) {
  window.addEventListener('scroll', () => {
    backTop.classList.toggle('visible', window.scrollY > 400);
  }, { passive: true });
}

// ── Contact form (ajax submit to PHP handler) ──────────────
(function initContact(){
  const form = document.querySelector('form[onsubmit*="submitForm"], .contact-wrap form');
  if (!form) return;
  // Remove inline handler
  form.removeAttribute('onsubmit');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const name    = form.querySelector('#name')?.value.trim() || '';
    const email   = form.querySelector('#email')?.value.trim() || '';
    const message = form.querySelector('#message')?.value.trim() || '';
    const resp    = document.getElementById('response');
    const btn     = form.querySelector('.btn-submit');
    if (!resp) return;
    if (!name || !email || !message) {
      resp.textContent = 'Please fill in all fields.';
      resp.className = 'visible';
      return;
    }
    btn.disabled = true;
    btn.textContent = 'Sending...';
    try {
      const fd = new FormData(form);
      const r  = await fetch(form.getAttribute('action') || '../include/contact_handler.php', {
        method: 'POST', body: fd
      });
      const data = await r.json();
      resp.textContent = data.message || (data.success ? 'Message sent successfully!' : 'Something went wrong.');
      resp.className = 'visible' + (data.success ? '' : ' error-response');
      if (data.success) form.reset();
    } catch (err) {
      resp.textContent = 'Network error. Please try again later.';
      resp.className = 'visible error-response';
    }
    btn.disabled = false;
    btn.textContent = 'Send Message';
  });
})();

function esc(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Heritage page tab switching ────────────────────────────
function showTab(tabId, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => {
    p.style.display = 'none';
    p.style.opacity = '0';
  });
  document.querySelectorAll('.page-tabs a').forEach(a => a.classList.remove('active'));

  const panel = document.getElementById(tabId);
  if (panel) {
    panel.style.display = 'block';
    // Tiny timeout lets browser paint display:block before opacity transition
    requestAnimationFrame(() => {
      panel.style.transition = 'opacity 0.3s ease';
      panel.style.opacity = '1';
    });
  }
  if (btn) btn.classList.add('active');
}

// ── Inventory Brand Filtering ────────────────────────────────
(function initInventoryFilter() {
  const filterNav = document.getElementById('brandFilter');
  if (!filterNav) return;

  const filterLinks = filterNav.querySelectorAll('a');
  const carCards = document.querySelectorAll('.car-card');

  filterLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const targetBrand = link.getAttribute('data-brand');

      // Update active state of links
      filterLinks.forEach(l => l.classList.remove('active'));
      link.classList.add('active');

      // Filter cards
      carCards.forEach(card => {
        const cardBrand = card.getAttribute('data-brand');
        if (targetBrand === 'all' || cardBrand === targetBrand) {
          card.style.display = 'block';
          // Trigger a small fade-in via opacity
          card.style.opacity = '0';
          requestAnimationFrame(() => {
            card.style.transition = 'opacity 0.3s ease';
            card.style.opacity = '1';
          });
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
})();

document.addEventListener('DOMContentLoaded', () => {
  const firstTab = document.querySelector('.page-tabs a');
  if (firstTab) firstTab.click();

  // Scroll-reveal for gen-blocks and overview-cards
  const revealEls = document.querySelectorAll('.gen-block, .overview-card, .car-card, .heritage-tile, .stat-item');
  if (!revealEls.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.08 });

  revealEls.forEach((el, i) => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity 0.5s ease ' + (i * 0.04) + 's, transform 0.5s ease ' + (i * 0.04) + 's';
    observer.observe(el);
  });
});
