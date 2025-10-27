(() => {
  const themeBtn = document.getElementById('theme-toggle');
  if (themeBtn) {
    const apply = t => document.documentElement.setAttribute('data-theme', t);
    const saved = localStorage.getItem('bm_theme') || 'light';
    apply(saved);
    themeBtn.addEventListener('click', () => {
      const cur = document.documentElement.getAttribute('data-theme') || 'light';
      const next = cur === 'light' ? 'dark' : 'light';
      apply(next); localStorage.setItem('bm_theme', next);
    });
  }

  window.csrfFetch = async (url, opts={}) => {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const headers = Object.assign({'X-Requested-With':'fetch'}, opts.headers||{}, {'Content-Type':'application/x-www-form-urlencoded'});
    const body = (opts.body && typeof opts.body === 'string') ? (opts.body + `&_csrf=${encodeURIComponent(token)}`) : `_csrf=${encodeURIComponent(token)}`;
    const resp = await fetch(url, {method:'POST', headers, body});
    return resp.json().catch(()=>({status:'ok'}));
  };

  const bell = document.getElementById('notify-bell');
  if (bell) {
    bell.addEventListener('click', async () => {
      await csrfFetch('/notifications/mark-read', {body:''});
      const dot = bell.querySelector('.dot'); if (dot) dot.remove();
    });
  }

  // Tiny charts
  function pie(canvasId, slices) {
    const c = document.getElementById(canvasId); if (!c) return;
    const ctx = c.getContext('2d'); const cx = c.width/2, cy = c.height/2, r = Math.min(cx,cy)-4;
    const total = slices.reduce((a,s)=>a+s.value,0) || 1;
    let ang = -Math.PI/2; const colors=['#60a5fa','#34d399','#f472b6','#f59e0b','#a78bfa','#f87171','#10b981'];
    slices.forEach((s,i)=>{ const theta = 2*Math.PI*(s.value/total); ctx.beginPath(); ctx.moveTo(cx,cy); ctx.arc(cx,cy,r,ang,ang+theta); ctx.fillStyle=colors[i%colors.length]; ctx.fill(); ang += theta;});
  }
  function line(canvasId, points) {
    const c = document.getElementById(canvasId); if (!c) return; const ctx = c.getContext('2d');
    const w=c.width, h=c.height; ctx.clearRect(0,0,w,h);
    if (!points.length) return; const xs = points.map(p=>p.x), ys=points.map(p=>p.y);
    const minX=Math.min(...xs), maxX=Math.max(...xs), minY=Math.min(...ys), maxY=Math.max(...ys);
    const sx=x=> (x-minX)/(maxX-minX||1)*(w-20)+10; const sy=y=> h-10-(y-minY)/(maxY-minY||1)*(h-20);
    ctx.beginPath(); ctx.strokeStyle=getComputedStyle(document.documentElement).getPropertyValue('--primary'); ctx.lineWidth=2;
    points.forEach((p,i)=>{ const X=sx(p.x), Y=sy(p.y); if(i===0) ctx.moveTo(X,Y); else ctx.lineTo(X,Y); }); ctx.stroke();
  }
  window.bmCharts = { pie, line };

  // Copy to clipboard
  document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const text = btn.getAttribute('data-copy') || '';
      try { await navigator.clipboard.writeText(text); btn.textContent='Copied!'; setTimeout(()=>btn.textContent='Copy',1000); } catch {}
    });
  });

  // Button ripple effect (skips if reduced motion)
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!prefersReduced) {
    document.addEventListener('click', (e) => {
      const target = e.target.closest('.btn');
      if (!target) return;
      const rect = target.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const maxDim = Math.max(rect.width, rect.height);
      const ripple = document.createElement('span');
      ripple.className = 'rfx';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      ripple.style.width = ripple.style.height = (maxDim * 1.6) + 'px';
      target.appendChild(ripple);
      ripple.addEventListener('animationend', () => ripple.remove());
    }, { passive: true });
  }
})();
