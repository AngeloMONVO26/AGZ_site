/* ============================================================
   AGZ SOLUTIONS — composants partagés (sidebar, topbar, icônes)
   ============================================================ */

const AGZ = (() => {


const ICONS = {
    grid: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1.5"/>
            <rect x="14" y="3" width="7" height="7" rx="1.5"/>
            <rect x="3" y="14" width="7" height="7" rx="1.5"/>
            <rect x="14" y="14" width="7" height="7" rx="1.5"/>
        </svg>
    `,

    home: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 11.5 12 4l9 7.5"/>
            <path d="M5.5 10v9a1 1 0 0 0 1 1H10v-6h4v6h3.5a1 1 0 0 0 1-1v-9"/>
        </svg>
    `,

    tool: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.7 6.3a4 4 0 0 0-5.4 4.6L3 17.2V21h3.8l6.3-6.3a4 4 0 0 0 4.6-5.4l-2.6 2.6-2-2 2.6-2.6Z"/>
        </svg>
    `,

    info: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 11v5.5M12 7.7v.1"/>
        </svg>
    `,

    image: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4.5" width="18" height="15" rx="2"/>
            <circle cx="8.5" cy="10" r="1.6"/>
            <path d="m5 17 4.8-4.8a2 2 0 0 1 2.8 0L19 18.5"/>
        </svg>
    `,

    article: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 3.5h9L19 7.5V20a.5.5 0 0 1-.5.5h-12a.5.5 0 0 1-.5-.5v-16a.5.5 0 0 1 .5-.5Z"/>
            <path d="M9 12h6M9 15.5h6M9 8.5h3"/>
        </svg>
    `,

    mail: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="5" width="18" height="14" rx="2"/>
            <path d="m4 6.5 8 6 8-6"/>
        </svg>
    `,

    tag: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11.5 4h6A2.5 2.5 0 0 1 20 6.5v6a2 2 0 0 1-.6 1.4l-7.6 7.6a2 2 0 0 1-2.8 0l-5.5-5.5a2 2 0 0 1 0-2.8l7.6-7.6A2 2 0 0 1 11.5 4Z"/>
            <circle cx="15" cy="9" r="1.4"/>
        </svg>
    `,

    stack: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 3 9 4.7-9 4.7-9-4.7L12 3Z"/>
            <path d="m3 12.3 9 4.7 9-4.7M3 16.6l9 4.7 9-4.7"/>
        </svg>
    `,

    star: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 3.5 2.6 5.4 5.9.8-4.3 4.2 1 6-5.2-2.8-5.2 2.8 1-6-4.3-4.2 5.9-.8L12 3.5Z"/>
        </svg>
    `,

    settings: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 13.5a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.9 2.9l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.9-2.9l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1h-.2a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.9-2.9l.1.1a1.7 1.7 0 0 0 1.9.3H10a1.7 1.7 0 0 0 1-1.6v-.2a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.9 2.9l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.6 1h.2a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.6 1Z"/>
        </svg>
    `,

    logout: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5.5a1.5 1.5 0 0 1-1.5-1.5v-15A1.5 1.5 0 0 1 5.5 3H9"/>
            <path d="M16 17l5-5-5-5M21 12H9"/>
        </svg>
    `,

    search: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="7"/>
            <path d="m21 21-4.3-4.3"/>
        </svg>
    `,

    bell: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 9a6 6 0 0 1 12 0c0 4.2 1.2 5.8 2 6.5H4c.8-.7 2-2.3 2-6.5Z"/>
            <path d="M10 19a2 2 0 0 0 4 0"/>
        </svg>
    `,

    plus: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 5v14M5 12h14"/>
        </svg>
    `,

    edit: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 20h4l10.5-10.5a2 2 0 0 0 0-2.8L17.3 5.5a2 2 0 0 0-2.8 0L4 16v4Z"/>
            <path d="m14 6.5 3.5 3.5"/>
        </svg>
    `,

    eye: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    `,

    eyeOff: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 3l18 18"/>
            <path d="M10.6 5.6A10.6 10.6 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a13.6 13.6 0 0 1-3.1 3.8M6.6 6.6C4 8.3 2.5 12 2.5 12S6 18.5 12 18.5a9.8 9.8 0 0 0 4-.8"/>
            <path d="M9.9 10a3 3 0 0 0 4.2 4.2"/>
        </svg>
    `,

    trash: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 7h16"/>
            <path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m2 0-.6 12.2A2 2 0 0 1 14.4 21H9.6a2 2 0 0 1-2-1.8L7 7"/>
            <path d="M10 11v6M14 11v6"/>
        </svg>
    `,

    chevronLeft: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m14.5 5-7 7 7 7"/>
        </svg>
    `,

    chevronRight: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9.5 5 7 7-7 7"/>
        </svg>
    `,

    users: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="8" r="3.2"/>
            <path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/>
            <circle cx="17.5" cy="8.5" r="2.4"/>
            <path d="M16 14.6c2.5.4 4 2.2 4 5.4"/>
        </svg>
    `,

    trend: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 16 6-6 4 4 8-9"/>
            <path d="M15 5h6v6"/>
        </svg>
    `,

    calendar: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3.5" y="5" width="17" height="16" rx="2"/>
            <path d="M8 3v4M16 3v4M3.5 10h17"/>
        </svg>
    `,

    menu: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    `,

    upload: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 15V4M8 8l4-4 4 4"/>
            <path d="M4 15v3.5A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5V15"/>
        </svg>
    `,

    check: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m4 12.5 5.5 5.5L20 6.5"/>
        </svg>
    `,

    globe: `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <path d="M3 12h18M12 3c2.5 2.6 3.8 5.7 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.7-3.8-9S9.5 5.6 12 3Z"/>
        </svg>
    `,
};


  const NAV = [
    { top:true, key:'dashboard', label:'Tableau de bord', icon:'grid', href:'index.php' },
    { top:true, key:'messages', label:'Messages', icon:'mail', href:'ges_messages.php', badge:'messages' },
    /*{ top:true, key:'messages', label:'Messages', icon:'mail', href:'ges_messages.php', badge:3 },*/
    /* { top:true, key:'messages', label:'Messages', icon:'mail', href:'messages.html', badge:3 },*/

  /* Gestion du site*/
    { group:'gestion du site', items:[
      { key:'services', label:'Services', icon:'tool', href:'ges_services.html' },
      { key:'realisations', label:'Réalisations', icon:'image', href:'ges_realisations.html' },
      { key:'articles', label:'Articles', icon:'article', href:'ges_articles.php' },
      { key:'categories', label:'Catégories', icon:'tag', href:'ges_blog-categories.php' },
      { key:'commentaires', label:'Commentaires', icon:'message-circle', href:'ges_commentaires.php' },
    ]},

/* Site Web*/
{ group:'Site web', items:[
  { key:'accueil', label:'Accueil', icon:'home', href:'site/index.html' },
  { key:'services', label:'Services', icon:'tool', href:'site/services.html' },
  { key:'about', label:'À propos', icon:'info', href:'site/a-propos.html' },
  { key:'blog', label:'Blog', icon:'article', href:'site/blog.html' },
  { key:'contact', label:'Contact', icon:'mail', href:'site/contact.html' },
]},

/* Autres*/
    { group:'Autres', items:[
      { key:'medias', label:'Médias', icon:'image', href:'#' },
      { key:'temoignages', label:'Témoignages', icon:'star', href:'#' },
    ]},
  ];

  function icon(name){ return ICONS[name] || ''; }

  function sidebarHTML(active, opts){
    let groups = '';
    NAV.forEach(entry => {
      if (entry.top){
        groups += navLink(entry, active, opts);
      } else if (entry.group){
        groups += `<div class="nav-group"><div class="nav-group-label">${entry.group.toUpperCase()}</div>`;
        entry.items.forEach(it => groups += navLink(it, active, opts));
        groups += `</div>`;
      }
    });

    return `
    <div class="sidebar-brand">
      <div class="mark">A</div>
      <div class="word">AGZ<span>SOLUTIONS</span></div>
    </div>
    <div class="nav-group" style="margin-top:0">${groups}</div>
    <div class="sidebar-footer">
      <a class="nav-link${active==='settings' ? ' active' : ''}" href="settings.html">${icon('settings')}<span>Paramètres</span></a>
      <a class="nav-link" href="logout.php">${icon('logout')}<span>Déconnexion</span></a>
    </div>`;
  }

  function navLink(it, active, opts){
    const isActive = it.key === active;

    const badgeValue = it.badge === 'messages'
      ? (opts?.messagesCount ?? 0)
      : it.badge;

    const badge = badgeValue > 0
      ? `<span class="badge badge-green" style="margin-left:auto;padding:2px 8px">${badgeValue}</span>`
      : '';

    return `<a class="nav-link${isActive?' active':''}" href="${it.href}">${icon(it.icon)}<span>${it.label}</span>${badge}</a>`;
}

  function topbarHTML(opts){
    opts = opts || {};
    const adminUsername = (opts.adminUsername || 'Administrateur').trim();
    const initials = adminUsername.slice(0, 2).toUpperCase();
    return `
    <button class="icon-btn" id="agzMobileToggle" style="display:none">${icon('menu')}</button>
    <div class="topbar-search">
      ${icon('search')}
      <input type="text" placeholder="${opts.searchPlaceholder || 'Rechercher…'}">
    </div>
    <div class="topbar-right">
      <button class="icon-btn" aria-label="Notifications">${icon('bell')}<span class="dot"></span></button>
      <div class="user-chip">
       <div class="avatar">${initials}</div>
        <div class="who">
          <div class="name">${adminUsername}</div>
          <div class="role">Super admin</div>
        </div>
      </div>
    </div>`;
  }

  function initLayout(active, opts){
    document.addEventListener('DOMContentLoaded', () => {
      const sMount = document.getElementById('agz-sidebar');
      const tMount = document.getElementById('agz-topbar');
      if (sMount) sMount.innerHTML = sidebarHTML(active, opts);
      if (tMount) tMount.innerHTML = topbarHTML(opts);

      const toggle = document.getElementById('agzMobileToggle');
      if (toggle && sMount){
        toggle.style.display = 'flex';
        toggle.addEventListener('click', () => sMount.classList.toggle('open'));
      }
    });
  }

  function initTabs(root){
    document.addEventListener('DOMContentLoaded', () => {
      const scope = document.querySelector(root);
      if (!scope) return;
      const tabs = scope.querySelectorAll('[data-tab]');
      const panels = scope.querySelectorAll('[data-panel]');
      tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          tabs.forEach(t => t.classList.remove('active'));
          panels.forEach(p => p.style.display = 'none');
          tab.classList.add('active');
          const target = scope.querySelector(`[data-panel="${tab.dataset.tab}"]`);
          if (target) target.style.display = 'block';
        });
      });
    });
  }

  function initPasswordToggle(inputSel, btnSel){
    document.addEventListener('DOMContentLoaded', () => {
      const input = document.querySelector(inputSel);
      const btn = document.querySelector(btnSel);
      if (!input || !btn) return;
      btn.innerHTML = icon('eye');
      btn.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.innerHTML = show ? icon('eyeOff') : icon('eye');
      });
    });
  }

  return { icon, initLayout, initTabs, initPasswordToggle };
})();

/* Page accueil du site*/


const toggle=document.querySelector('.mobile-toggle'),nav=document.querySelector('.nav-links');
if(toggle)toggle.addEventListener('click',()=>nav.classList.toggle('open'));
document.querySelectorAll('.nav-links a').forEach(a=>a.addEventListener('click',()=>nav.classList.remove('open')));
const reveal=document.querySelectorAll('.reveal');
const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.style.opacity=1;e.target.style.transform='none';io.unobserve(e.target)}}),{threshold:.12});
reveal.forEach(e=>{e.style.opacity=0;e.style.transform='translateY(20px)';e.style.transition='opacity .7s ease,transform .7s ease';io.observe(e)});
document.querySelectorAll('[data-year]').forEach(e=>e.textContent=new Date().getFullYear());
