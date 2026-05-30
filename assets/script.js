/* ── CURSOR ── */
const cur = document.getElementById('cur');
const curR = document.getElementById('cur-r');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{ mx=e.clientX; my=e.clientY; cur.style.left=mx+'px'; cur.style.top=my+'px'; });
(function animR(){ rx+=(mx-rx)*.11; ry+=(my-ry)*.11; curR.style.left=rx+'px'; curR.style.top=ry+'px'; requestAnimationFrame(animR); })();
document.querySelectorAll('button,a,.mi,.card').forEach(el=>{
  el.addEventListener('mouseenter',()=>{ cur.style.width='18px'; cur.style.height='18px'; curR.style.width='54px'; curR.style.height='54px'; curR.style.borderColor='rgba(255,184,0,.65)'; });
  el.addEventListener('mouseleave',()=>{ cur.style.width='10px'; cur.style.height='10px'; curR.style.width='36px'; curR.style.height='36px'; curR.style.borderColor='rgba(255,184,0,.45)'; });
});

/* ── PROGRESS BAR + NAV ── */
const bar = document.getElementById('bar');
window.addEventListener('scroll',()=>{
  bar.style.width=(window.scrollY/(document.body.scrollHeight-innerHeight)*100)+'%';
  document.getElementById('nav').classList.toggle('scrolled',window.scrollY>60);
});

/* ── PARALLAX HERO BG ── */
const heroBg = document.querySelector('.hero-bg');
window.addEventListener('scroll',()=>{
  const y = window.scrollY;
  if(y < window.innerHeight) {
    heroBg.style.transform = `scale(1) translateY(${y * 0.25}px)`;
  }
},{passive:true});

/* ── SCROLL STORY ── */
const canvas = document.getElementById('c');
const ctx = canvas.getContext('2d');

const sequences = [
  { folder:'frames/hero/',   count:240 },
  { folder:'frames/drop/',   count:240 },
  { folder:'frames/fry/',    count:121 },
  { folder:'frames/served/', count:240 },
];
const sceneBreaks = [0,0.25,0.5,0.75,1.0];
const sceneTextIds = ['s1','s2','s3','s4'];
const dots = document.querySelectorAll('.dot');

function preloadSequence(seq){
  seq.frames=[];
  for(let i=1;i<=seq.count;i++){
    const img=new Image();
    img.src=seq.folder+'frame'+String(i).padStart(3,'0')+'.jpg';
    seq.frames.push(img);
  }
}
preloadSequence(sequences[0]);
setTimeout(()=>preloadSequence(sequences[1]),500);
setTimeout(()=>preloadSequence(sequences[2]),1500);
setTimeout(()=>preloadSequence(sequences[3]),2500);

function resize(){canvas.width=window.innerWidth;canvas.height=window.innerHeight;}
resize();
window.addEventListener('resize',resize);

function drawFrame(img){
  if(!img||!img.complete||!img.naturalWidth) return;
  const cw=canvas.width,ch=canvas.height,iw=img.naturalWidth,ih=img.naturalHeight;
  const scale=Math.max(cw/iw,ch/ih);
  const dw=iw*scale,dh=ih*scale;
  ctx.clearRect(0,0,cw,ch);
  ctx.drawImage(img,(cw-dw)/2,(ch-dh)/2,dw,dh);
}

let currentScene=-1;
function updateScene(idx){
  if(idx===currentScene) return;
  currentScene=idx;
  dots.forEach((d,i)=>d.classList.toggle('active',i===idx));
  document.getElementById('scroll-hint').style.opacity=idx===0?'1':'0';
}

function onScroll(){
  const story=document.getElementById('story');
  const rect=story.getBoundingClientRect();
  const totalScroll=story.offsetHeight-window.innerHeight;
  const scrolled=Math.max(0,-rect.top);
  const progress=Math.min(1,scrolled/totalScroll);
  let sceneIdx=0;
  for(let i=sceneBreaks.length-2;i>=0;i--){ if(progress>=sceneBreaks[i]){sceneIdx=i;break;} }
  sceneIdx=Math.min(sceneIdx,sequences.length-1);
  updateScene(sceneIdx);
  const sceneStart=sceneBreaks[sceneIdx];
  const sceneEnd=sceneBreaks[sceneIdx+1];
  const clampedP=Math.max(0,Math.min(1,(progress-sceneStart)/(sceneEnd-sceneStart)));
  const seq=sequences[sceneIdx];
  if(!seq.frames) return;
  const frameIdx=Math.min(seq.count-1,Math.floor(clampedP*seq.count));
  const img=seq.frames[frameIdx];
  if(img&&img.complete&&img.naturalWidth) drawFrame(img);
  else if(img) img.onload=()=>drawFrame(img);
}
window.addEventListener('scroll',onScroll,{passive:true});

sequences[0].frames[0].onload=()=>drawFrame(sequences[0].frames[0]);
setTimeout(()=>{ if(sequences[0].frames[0].complete) drawFrame(sequences[0].frames[0]); },100);

/* ── DOTS SHOW ONLY DURING STORY ── */
const dotsEl = document.getElementById('dots');
const storyEl = document.getElementById('story');
window.addEventListener('scroll',()=>{
  const r=storyEl.getBoundingClientRect();
  const inView = r.top < window.innerHeight && r.bottom > 0;
  dotsEl.style.opacity = inView ? '1' : '0';
  dotsEl.style.transition = 'opacity .4s';
},{passive:true});

/* ── REVEAL ON SCROLL ── */
const revealEls=document.querySelectorAll('.reveal');
const ro=new IntersectionObserver(entries=>{
  entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('vis'); });
},{threshold:.12});
revealEls.forEach(el=>ro.observe(el));

/* ── GSAP SCROLL STORY ANIMATIONS ── */
gsap.registerPlugin(ScrollTrigger);

const storyTl = gsap.timeline({
  scrollTrigger: {
    trigger: '#story',
    start: 'top top',
    end: 'bottom bottom',
    scrub: 1
  }
});

const sceneTimings = [
  { id: '#s1', start: 0, end: 0.25 },
  { id: '#s2', start: 0.25, end: 0.5 },
  { id: '#s3', start: 0.5, end: 0.75 },
  { id: '#s4', start: 0.75, end: 1.0 }
];

sceneTimings.forEach((scene, index) => {
  const sStart = scene.start;
  const sLen = scene.end - scene.start;
  
  // Entry timings within the scene (0 to 1) mapped to total timeline (0 to 1)
  const tagIn = sStart + sLen * 0.15;
  const tagOut = sStart + sLen * 0.75;
  const hIn = sStart + sLen * 0.22;
  const hOut = sStart + sLen * 0.72;
  const subIn = sStart + sLen * 0.30;
  const subOut = sStart + sLen * 0.68;
  const quoteIn = sStart + sLen * 0.35;
  const quoteOut = sStart + sLen * 0.62;
  const pillIn = sStart + sLen * 0.38;
  const pillOut = sStart + sLen * 0.62;

  // Alternate direction based on scene index
  const isEven = index % 2 === 0;
  const startX = isEven ? '-40px' : '40px';
  
  // Tag
  storyTl.fromTo(`${scene.id} .st-tag`, { opacity: 0, x: startX }, { opacity: 1, x: 0, duration: sLen * 0.05 }, tagIn);
  storyTl.to(`${scene.id} .st-tag`, { opacity: 0, y: -15, duration: sLen * 0.08 }, tagOut);
  
  // Headline words
  const words = document.querySelectorAll(`${scene.id} .word-inner`);
  if (words.length > 0) {
    storyTl.fromTo(words, 
      { x: startX, opacity: 0 }, 
      { x: '0px', opacity: 1, stagger: (sLen * 0.05) / words.length, duration: sLen * 0.08 }, 
      hIn
    );
    storyTl.to(words, { opacity: 0, y: '-20px', duration: sLen * 0.08 }, hOut);
  }
  
  // Subline
  storyTl.fromTo(`${scene.id} .st-sub`, { opacity: 0, x: startX, filter: 'blur(6px)' }, { opacity: 1, x: '0px', filter: 'blur(0px)', duration: sLen * 0.08 }, subIn);
  storyTl.to(`${scene.id} .st-sub`, { opacity: 0, y: -15, duration: sLen * 0.08 }, subOut);
  
  // Quote
  const quote = document.querySelector(`${scene.id} .st-quote`);
  if (quote) {
    const quoteStartX = isEven ? '40px' : '-40px'; // Quote comes from opposite side
    storyTl.fromTo(quote, { opacity: 0, x: quoteStartX }, { opacity: 1, x: 0, duration: sLen * 0.08 }, quoteIn);
    storyTl.to(quote, { opacity: 0, y: -15, duration: sLen * 0.08 }, quoteOut);
  }
  
  // Pillars
  const pillars = document.querySelectorAll(`${scene.id} .pillar`);
  if (pillars.length > 0) {
    storyTl.fromTo(pillars, 
      { opacity: 0, x: startX }, 
      { opacity: 1, x: 0, stagger: (sLen * 0.04) / pillars.length, duration: sLen * 0.08 }, 
      pillIn
    );
    storyTl.to(pillars, { opacity: 0, y: -10, duration: sLen * 0.08 }, pillOut);
  }
});

storyTl.to({}, {duration: 0.01}, 1);