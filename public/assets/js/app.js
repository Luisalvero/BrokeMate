// Enhanced BrokeMate JavaScript
(() => {
  'use strict';

  // Theme Management with smooth transitions
  const initTheme = () => {
    const themeBtn = document.getElementById('theme-toggle');
    if (!themeBtn) return;

    const getPreferredTheme = () => {
      const saved = localStorage.getItem('bm_theme');
      if (saved) return saved;
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    };

    const applyTheme = (theme) => {
      document.documentElement.setAttribute('data-theme', theme);
      themeBtn.textContent = theme === 'dark' ? '☀️' : '🌙';
      themeBtn.setAttribute('aria-label', `Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`);
    };

    // Initialize
    applyTheme(getPreferredTheme());

    // Toggle handler
    themeBtn.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-theme') || 'light';
      const next = current === 'light' ? 'dark' : 'light';
      applyTheme(next);
      localStorage.setItem('bm_theme', next);
    });

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (!localStorage.getItem('bm_theme')) {
        applyTheme(e.matches ? 'dark' : 'light');
      }
    });
  };

  // CSRF-protected fetch utility
  window.csrfFetch = async (url, opts = {}) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
      console.error('CSRF token not found');
      return { status: 'error', message: 'Security token missing' };
    }

    const headers = {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/x-www-form-urlencoded',
      ...(opts.headers || {})
    };

    const body = opts.body 
      ? `${opts.body}&_csrf=${encodeURIComponent(token)}`
      : `_csrf=${encodeURIComponent(token)}`;

    try {
      const response = await fetch(url, {
        method: opts.method || 'POST',
        headers,
        body
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      return await response.json().catch(() => ({ status: 'ok' }));
    } catch (error) {
      console.error('Fetch error:', error);
      return { status: 'error', message: error.message };
    }
  };

  // Notification Bell
  const initNotifications = () => {
    const bell = document.getElementById('notify-bell');
    if (!bell) return;

    bell.addEventListener('click', async (e) => {
      e.preventDefault();
      const result = await csrfFetch('/notifications/mark-read', { body: '' });
      
      if (result.status === 'ok') {
        const dot = bell.querySelector('.dot');
        if (dot) {
          dot.style.animation = 'fadeOut 0.3s ease';
          setTimeout(() => dot.remove(), 300);
        }
      }
      
      // Navigate to notifications page
      window.location.href = '/notifications';
    });
  };

  // Enhanced Chart System
  const bmCharts = {
    // Pie chart with modern styling
    pie: (canvasId, slices) => {
      const canvas = document.getElementById(canvasId);
      if (!canvas) return;

      const ctx = canvas.getContext('2d');
      const width = canvas.width;
      const height = canvas.height;
      const centerX = width / 2;
      const centerY = height / 2;
      const radius = Math.min(centerX, centerY) - 10;

      // Clear canvas
      ctx.clearRect(0, 0, width, height);

      if (!slices || slices.length === 0) {
        ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--muted').trim();
        ctx.font = '14px system-ui';
        ctx.textAlign = 'center';
        ctx.fillText('No data', centerX, centerY);
        return;
      }

      const total = slices.reduce((sum, s) => sum + (s.value || 0), 0);
      if (total === 0) return;

      const colors = [
        '#3b82f6', // blue
        '#10b981', // green
        '#f59e0b', // amber
        '#ef4444', // red
        '#8b5cf6', // violet
        '#ec4899', // pink
        '#06b6d4', // cyan
      ];

      let currentAngle = -Math.PI / 2;

      slices.forEach((slice, index) => {
        const sliceAngle = (2 * Math.PI * slice.value) / total;
        
        // Draw slice
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
        ctx.closePath();
        ctx.fillStyle = colors[index % colors.length];
        ctx.fill();

        // Add subtle stroke
        ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--card-bg').trim();
        ctx.lineWidth = 2;
        ctx.stroke();

        currentAngle += sliceAngle;
      });

      // Draw center circle for donut effect
      ctx.beginPath();
      ctx.arc(centerX, centerY, radius * 0.6, 0, 2 * Math.PI);
      ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--card-bg').trim();
      ctx.fill();
    },

    // Line chart with gradient fill
    line: (canvasId, points) => {
      const canvas = document.getElementById(canvasId);
      if (!canvas) return;

      const ctx = canvas.getContext('2d');
      const width = canvas.width;
      const height = canvas.height;
      const padding = 20;

      ctx.clearRect(0, 0, width, height);

      if (!points || points.length === 0) {
        ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--muted').trim();
        ctx.font = '14px system-ui';
        ctx.textAlign = 'center';
        ctx.fillText('No data', width / 2, height / 2);
        return;
      }

      const xValues = points.map(p => p.x);
      const yValues = points.map(p => p.y);
      const minX = Math.min(...xValues);
      const maxX = Math.max(...xValues);
      const minY = Math.min(...yValues, 0);
      const maxY = Math.max(...yValues);

      const scaleX = (x) => padding + ((x - minX) / (maxX - minX || 1)) * (width - 2 * padding);
      const scaleY = (y) => height - padding - ((y - minY) / (maxY - minY || 1)) * (height - 2 * padding);

      // Draw grid lines
      ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--border').trim();
      ctx.lineWidth = 1;
      for (let i = 0; i <= 4; i++) {
        const y = padding + (i * (height - 2 * padding) / 4);
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(width - padding, y);
        ctx.stroke();
      }

      // Create gradient
      const gradient = ctx.createLinearGradient(0, 0, 0, height);
      const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim();
      gradient.addColorStop(0, primaryColor + '40');
      gradient.addColorStop(1, primaryColor + '00');

      // Draw filled area
      ctx.beginPath();
      ctx.moveTo(scaleX(points[0].x), height - padding);
      points.forEach((point, i) => {
        if (i === 0) {
          ctx.lineTo(scaleX(point.x), scaleY(point.y));
        } else {
          ctx.lineTo(scaleX(point.x), scaleY(point.y));
        }
      });
      ctx.lineTo(scaleX(points[points.length - 1].x), height - padding);
      ctx.closePath();
      ctx.fillStyle = gradient;
      ctx.fill();

      // Draw line
      ctx.beginPath();
      points.forEach((point, i) => {
        const x = scaleX(point.x);
        const y = scaleY(point.y);
        if (i === 0) {
          ctx.moveTo(x, y);
        } else {
          ctx.lineTo(x, y);
        }
      });
      ctx.strokeStyle = primaryColor;
      ctx.lineWidth = 3;
      ctx.stroke();

      // Draw points
      points.forEach((point) => {
        const x = scaleX(point.x);
        const y = scaleY(point.y);
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, 2 * Math.PI);
        ctx.fillStyle = primaryColor;
        ctx.fill();
        ctx.strokeStyle = getComputedStyle(document.documentElement).getPropertyValue('--card-bg').trim();
        ctx.lineWidth = 2;
        ctx.stroke();
      });
    }
  };

  window.bmCharts = bmCharts;

  // Copy to clipboard with feedback
  const initCopyButtons = () => {
    document.querySelectorAll('[data-copy]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const text = btn.getAttribute('data-copy') || '';
        const originalText = btn.textContent;
        
        try {
          await navigator.clipboard.writeText(text);
          btn.textContent = '✓ Copied!';
          btn.style.background = 'var(--success)';
          
          setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '';
          }, 2000);
        } catch (err) {
          console.error('Copy failed:', err);
          btn.textContent = '✗ Failed';
          setTimeout(() => {
            btn.textContent = originalText;
          }, 2000);
        }
      });
    });
  };

  // Button ripple effect
  const initRippleEffect = () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return;
    }

    document.addEventListener('click', (e) => {
      const target = e.target.closest('.btn');
      if (!target) return;

      const rect = target.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const size = Math.max(rect.width, rect.height) * 2;

      const ripple = document.createElement('span');
      ripple.className = 'rfx';
      ripple.style.cssText = `
        left: ${x}px;
        top: ${y}px;
        width: ${size}px;
        height: ${size}px;
      `;

      target.appendChild(ripple);
      ripple.addEventListener('animationend', () => ripple.remove());
    }, { passive: true });
  };

  // Form validation feedback
  const initFormValidation = () => {
    document.querySelectorAll('form').forEach(form => {
      form.addEventListener('submit', (e) => {
        const invalidInputs = form.querySelectorAll('input:invalid, select:invalid, textarea:invalid');
        invalidInputs.forEach(input => {
          input.style.borderColor = 'var(--danger)';
          const reset = () => {
            input.style.borderColor = '';
            input.removeEventListener('input', reset);
          };
          input.addEventListener('input', reset);
        });

        if (invalidInputs.length > 0) {
          e.preventDefault();
          const first = invalidInputs[0];
          try { first.focus(); } catch (err) { /* ignore */ }
        }
      });
    });
  };

  // Initialize all features on DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initNotifications();
    initCopyButtons();
    initRippleEffect();
    initFormValidation();
  });
})();
