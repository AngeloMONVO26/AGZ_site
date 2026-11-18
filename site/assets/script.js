
const toggle=document.querySelector('.mobile-toggle'),nav=document.querySelector('.nav-links');
if(toggle)toggle.addEventListener('click',()=>nav.classList.toggle('open'));
document.querySelectorAll('.nav-links a').forEach(a=>a.addEventListener('click',()=>nav.classList.remove('open')));
const reveal=document.querySelectorAll('.reveal');
const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.style.opacity=1;e.target.style.transform='none';io.unobserve(e.target)}}),{threshold:.12});
reveal.forEach(e=>{e.style.opacity=0;e.style.transform='translateY(20px)';e.style.transition='opacity .7s ease,transform .7s ease';io.observe(e)});
document.querySelectorAll('[data-year]').forEach(e=>e.textContent=new Date().getFullYear());
