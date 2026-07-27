<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $book->title }} - Flipbook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/page-flip@2.0.7/dist/js/page-flip.browser.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; font-family: 'Inter', sans-serif; background: #1c1c1e; color: #fff; }

        /* Loading */
        #loading-overlay {
            position: fixed; inset: 0; z-index: 300; background: #1c1c1e;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            transition: opacity 0.6s ease;
        }
        #loading-overlay.hidden { opacity: 0; pointer-events: none; }
        .progress-ring { transform: rotate(-90deg); }
        .progress-ring-circle { transition: stroke-dashoffset 0.3s ease; transform-origin: 50% 50%; }

        /* Desktop */
        #desktop-viewer {
            display: none; position: fixed; inset: 0;
            flex-direction: column; background: #1c1c1e;
        }
        #top-bar {
            position: absolute; top: 0; left: 0; right: 0; height: 48px; z-index: 50;
            display: flex; align-items: center; justify-content: space-between; padding: 0 18px;
            background: linear-gradient(180deg, rgba(0,0,0,.6) 0%, transparent 100%);
        }
        #book-stage {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        #zoom-wrapper { transition: transform .25s cubic-bezier(.4,0,.2,1); transform-origin: center; }
        #flipbook { display: none; position: relative; z-index: 10; }
        .page-container { position: relative; overflow: hidden; background: #fff; }
        .page-container canvas { display: block; image-rendering: auto; }

        /* Side nav */
        .nav-side {
            position: absolute; top: 50%; transform: translateY(-50%); z-index: 40;
            display: flex; flex-direction: column; align-items: center; gap: 8px;
        }
        #nav-left  { left: 20px; }
        #nav-right { right: 20px; }
        .nav-btn {
            width: 46px; height: 46px; border-radius: 50%; border: none; cursor: pointer;
            background: rgba(255,255,255,.13); backdrop-filter: blur(8px);
            color: rgba(255,255,255,.85); display: flex; align-items: center; justify-content: center;
            transition: background .2s, transform .15s, color .2s; -webkit-tap-highlight-color: transparent;
        }
        .nav-btn:hover { background: rgba(255,255,255,.28); color: #fff; transform: scale(1.08); }
        .nav-btn:active { transform: scale(0.94); }
        .nav-btn.sm { width: 36px; height: 36px; background: rgba(255,255,255,.08); }
        .nav-btn.sm:hover { background: rgba(255,255,255,.2); }

        /* Bottom toolbar */
        #bottom-bar {
            position: absolute; bottom: 0; left: 0; right: 0; height: 54px; z-index: 50;
            display: flex; align-items: center; justify-content: center; gap: 2px;
            background: linear-gradient(0deg, rgba(0,0,0,.72) 0%, transparent 100%);
        }
        .tool-btn {
            width: 40px; height: 40px; border-radius: 8px; border: none; cursor: pointer;
            background: transparent; color: rgba(255,255,255,.6);
            display: flex; align-items: center; justify-content: center;
            transition: background .15s, color .15s; position: relative;
        }
        .tool-btn:hover { background: rgba(255,255,255,.12); color: #fff; }
        .tool-btn.active { color: #818cf8; }
        .tool-sep { width: 1px; height: 22px; background: rgba(255,255,255,.12); margin: 0 4px; flex-shrink: 0; }
        #zoom-label { font-size: 11px; font-weight: 600; color: rgba(255,255,255,.6); min-width: 36px; text-align: center; }
        .tool-btn::after {
            content: attr(data-tip); position: absolute; bottom: 44px; left: 50%; transform: translateX(-50%);
            background: rgba(0,0,0,.88); color: #fff; font-size: 10px; font-weight: 500;
            padding: 3px 8px; border-radius: 5px; white-space: nowrap;
            opacity: 0; pointer-events: none; transition: opacity .15s;
        }
        .tool-btn:hover::after { opacity: 1; }

        /* Skeleton */
        .skeleton-loader {
            position: absolute; inset: 0; background: #f5f5f5;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .skeleton-loader::after {
            content: ""; position: absolute; inset: 0; transform: translateX(-100%);
            background: linear-gradient(90deg,transparent,rgba(0,0,0,.06),transparent);
            animation: shimmer 1.4s infinite;
        }
        @keyframes shimmer { 100%{ transform: translateX(100%); } }

        /* Thumbnail strip */
        #thumb-panel {
            position: absolute; bottom: 54px; left: 0; right: 0; height: 130px; z-index: 45;
            background: rgba(10,10,12,.88); backdrop-filter: blur(14px);
            border-top: 1px solid rgba(255,255,255,.07);
            display: none; align-items: center; gap: 10px; padding: 0 20px;
            overflow-x: auto; overflow-y: hidden;
        }
        #thumb-panel.open { display: flex; }
        #thumb-panel::-webkit-scrollbar { height: 3px; }
        #thumb-panel::-webkit-scrollbar-thumb { background: rgba(255,255,255,.18); border-radius: 2px; }
        .thumb-item {
            flex: 0 0 auto; cursor: pointer; position: relative;
            border: 2px solid transparent; border-radius: 4px;
            transition: border-color .2s; overflow: hidden;
        }
        .thumb-item:hover { border-color: rgba(129,140,248,.6); }
        .thumb-item.active { border-color: #818cf8; }
        .thumb-item canvas { display: block; border-radius: 2px; }
        .thumb-num {
            position: absolute; bottom: 0; left: 0; right: 0; text-align: center;
            font-size: 9px; font-weight: 600; background: rgba(0,0,0,.5);
            color: rgba(255,255,255,.8); padding: 2px 0;
        }

        /* Mobile */
        #mobile-viewer { display: none; position: fixed; inset: 0; flex-direction: column; background: #030712; }
        #m-header {
            height: 52px; flex-shrink: 0; background: rgba(3,7,18,.9); backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,.06);
            display: flex; align-items: center; justify-content: space-between; padding: 0 14px;
        }
        #mobile-stage { flex: 1; overflow: hidden; touch-action: pan-y; position: relative; display: flex; align-items: center; justify-content: center; }
        #mobile-strip { position: relative; z-index: 10; display: none; }
        .mobile-page {
            position: relative; overflow: hidden; background: #030712;
        }
        .mobile-page canvas { display: block; image-rendering: auto; }
        .mobile-page .skeleton-loader { position: absolute; inset: 0; background: #0b0f19; }
        #m-footer {
            height: 58px; flex-shrink: 0; background: rgba(3,7,18,.9); backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255,255,255,.06);
            display: flex; align-items: center; justify-content: space-between; padding: 0 14px;
        }
        .m-nav-btn {
            padding: 8px 18px; background: rgba(255,255,255,.08); border: none; border-radius: 10px;
            color: rgba(255,255,255,.75); font-size: 13px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 6px; transition: background .2s;
        }
        .m-nav-btn:hover { background: rgba(255,255,255,.15); }
        #swipe-hint {
            position: fixed; bottom: 66px; left: 50%; transform: translateX(-50%);
            background: rgba(99,102,241,.15); border: 1px solid rgba(99,102,241,.3);
            color: #a5b4fc; font-size: 11px; padding: 5px 16px; border-radius: 99px;
            pointer-events: none; z-index: 50; transition: opacity .5s;
        }
        @keyframes pulse2 { 0%,100%{opacity:1} 50%{opacity:.35} }

        /* Responsive */
        @media (min-width: 768px) {
            #desktop-viewer { display: flex !important; }
            #mobile-viewer  { display: none !important; }
            #swipe-hint     { display: none !important; }
        }
        @media (max-width: 767px) {
            #desktop-viewer { display: none !important; }
            #mobile-viewer  { display: flex !important; }
        }
        :fullscreen #top-bar,:fullscreen #bottom-bar,
        :-webkit-full-screen #top-bar,:-webkit-full-screen #bottom-bar { opacity:0;transition:opacity .3s; }
        :fullscreen:hover #top-bar,:fullscreen:hover #bottom-bar,
        :-webkit-full-screen:hover #top-bar,:-webkit-full-screen:hover #bottom-bar { opacity:1; }
    </style>
</head>
<body>

<!-- Loading -->
<div id="loading-overlay">
    <div style="position:relative;width:96px;height:96px;margin-bottom:24px">
        <svg class="progress-ring" width="96" height="96" viewBox="0 0 100 100">
            <circle stroke="rgba(255,255,255,.08)" stroke-width="5" fill="transparent" r="42" cx="50" cy="50"/>
            <circle id="progress-circle" class="progress-ring-circle" stroke="#6366f1" stroke-width="5"
                stroke-linecap="round" fill="transparent" r="42" cx="50" cy="50"
                stroke-dasharray="263.89" stroke-dashoffset="263.89"/>
        </svg>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center">
            <span id="loading-percent" style="font-size:19px;font-weight:700;font-family:monospace;color:#e2e8f0">0%</span>
        </div>
    </div>
    <p id="loading-text" style="color:rgba(255,255,255,.45);font-size:13px;font-weight:500;text-align:center;max-width:240px;line-height:1.5">Connecting to stream...</p>
    <button id="retry-btn" style="display:none;margin-top:20px;padding:10px 24px;background:#6366f1;border:none;border-radius:10px;color:#fff;font-size:13px;font-weight:600;cursor:pointer">Retry</button>
</div>

<!-- Desktop -->
<div id="desktop-viewer">
    <div id="top-bar">
        <div style="display:flex;align-items:center;gap:12px">
            <a href="{{ route('books.index') }}" style="width:32px;height:32px;border-radius:7px;background:rgba(255,255,255,.1);color:rgba(255,255,255,.75);display:flex;align-items:center;justify-content:center;text-decoration:none;transition:background .2s" onmouseover="this.style.background='rgba(255,255,255,.2)'" onmouseout="this.style.background='rgba(255,255,255,.1)'">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <span id="page-indicator" style="font-size:13px;font-weight:500;color:rgba(255,255,255,.7);font-variant-numeric:tabular-nums">– / –</span>
        </div>
        <span style="font-size:12px;font-weight:500;color:rgba(255,255,255,.4);max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $book->title }}</span>
    </div>

    <div id="book-stage">
        <div id="zoom-wrapper"><div id="flipbook"></div></div>
    </div>

    <!-- Left nav -->
    <div class="nav-side" id="nav-left">
        <button class="nav-btn" id="btn-prev">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button class="nav-btn sm" id="btn-first" title="First page">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7M18 19l-7-7 7-7"/></svg>
        </button>
    </div>

    <!-- Right nav -->
    <div class="nav-side" id="nav-right">
        <button class="nav-btn" id="btn-next">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        <button class="nav-btn sm" id="btn-last" title="Last page">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M6 5l7 7-7 7"/></svg>
        </button>
    </div>

    <!-- Thumbnail strip -->
    <div id="thumb-panel"></div>

    <!-- Bottom toolbar -->
    <div id="bottom-bar">
        <button class="tool-btn" id="tb-zoom-out" data-tip="Zoom out">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
        </button>
        <span id="zoom-label">100%</span>
        <button class="tool-btn" id="tb-zoom-in" data-tip="Zoom in">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        </button>
        <div class="tool-sep"></div>
        <button class="tool-btn" id="tb-thumb" data-tip="Thumbnails">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
        </button>
        <div class="tool-sep"></div>
        <button class="tool-btn" id="tb-autoplay" data-tip="Autoplay">
            <svg id="ap-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.97l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/>
            </svg>
        </button>
        <div class="tool-sep"></div>
        <button class="tool-btn" id="tb-fit" data-tip="Fit to screen">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"/>
            </svg>
        </button>
        <button class="tool-btn" id="tb-fs" data-tip="Fullscreen">
            <svg id="fs-expand" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"/></svg>
            <svg id="fs-shrink" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9H4m5 0V4M15 9h5m-5 0V4M9 15H4m5 0v5M15 15h5m-5 0v5"/></svg>
        </button>
    </div>
</div>

<!-- Mobile -->
<div id="mobile-viewer">
    <div id="m-header">
        <div style="display:flex;align-items:center;gap:10px;min-width:0">
            <a href="{{ route('books.index') }}" style="flex-shrink:0;padding:7px;background:rgba(255,255,255,.08);border-radius:10px;color:rgba(255,255,255,.7);text-decoration:none;display:flex">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <span style="font-size:13px;font-weight:600;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $book->title }}</span>
        </div>
        <span id="m-indicator" style="font-size:12px;font-weight:600;color:rgba(255,255,255,.4);font-family:monospace;flex-shrink:0">– / –</span>
    </div>
    <div id="mobile-stage"><div id="mobile-strip"></div></div>
    <div id="m-footer">
        <button id="m-prev" class="m-nav-btn">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>Prev
        </button>
        <div id="m-pill" style="display:flex;align-items:center;gap:5px;padding:5px 12px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);border-radius:99px;font-size:11px;font-weight:600;color:rgb(251,191,36)">
            <span id="m-dot" style="width:6px;height:6px;border-radius:50%;background:currentColor;animation:pulse2 1.5s infinite;flex-shrink:0"></span>
            <span id="m-count" style="font-family:monospace">0/--</span>
        </div>
        <button id="m-next" class="m-nav-btn">
            Next<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
</div>

<div id="swipe-hint">← Swipe to turn pages →</div>

<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

// Build the stream URL dynamically from the current host.
// This ensures it works on localhost AND via ngrok without changing .env
const bookId    = {{ $book->id }};
const streamUrl = window.location.origin + '/book/' + bookId + '/stream';
const isMobile  = window.innerWidth < 768;

const bookHasImages = {{ !empty($book->folder_path) ? 'true' : 'false' }};
const bookPageCount = {{ $book->page_count ?? 0 }};
const bookFolderPath = '{{ !empty($book->folder_path) ? asset($book->folder_path) : '' }}';

let pdfDoc=null, pageFlip=null, totalPages=0;
let currentZoom=1, baseW=0, baseH=0, mIdx=0;
let autoTimer=null, autoOn=false, thumbsDone=false;
const done=new Set(), fly=new Map();

const flipEl   = document.getElementById('flipbook');
const mStrip   = document.getElementById('mobile-strip');
const zWrapper = document.getElementById('zoom-wrapper');
const loadOv   = document.getElementById('loading-overlay');
const loadTxt  = document.getElementById('loading-text');
const progCirc = document.getElementById('progress-circle');
const retryBtn = document.getElementById('retry-btn');
const thumbPan = document.getElementById('thumb-panel');
const hint     = document.getElementById('swipe-hint');

const C = 2 * Math.PI * 42;
progCirc.style.strokeDasharray = C + ' ' + C;
progCirc.style.strokeDashoffset = C;

function setProgress(p) {
    progCirc.style.strokeDashoffset = C - (p/100)*C;
    document.getElementById('loading-percent').textContent = Math.round(p)+'%';
}

function setPgIndicator(n) {
    var s = Math.min(n+1,totalPages);
    var lbl = totalPages===1 ? n+' / '+totalPages : n+'\u2013'+s+' / '+totalPages;
    var el=document.getElementById('page-indicator'); if(el) el.textContent=lbl;
    var mel=document.getElementById('m-indicator'); if(mel) mel.textContent=n+' / '+totalPages;
}

function updateMPill() {
    var d=done.size, t=totalPages;
    var c=document.getElementById('m-count'); if(c) c.textContent=d+'/'+(t||'--');
    if(t>0&&d>=t){
        var pill=document.getElementById('m-pill'),dot=document.getElementById('m-dot');
        if(pill){pill.style.background='rgba(16,185,129,.1)';pill.style.borderColor='rgba(16,185,129,.25)';pill.style.color='rgb(52,211,153)';}
        if(dot){dot.style.animation='none';}
    }
}

/* Page flip sound */
var AudioCtx=window.AudioContext||window.webkitAudioContext, audioCtx=null;
function playFlip() {
    try {
        if(!audioCtx) audioCtx=new AudioCtx();
        var buf=audioCtx.createBuffer(1,audioCtx.sampleRate*0.18,audioCtx.sampleRate);
        var d=buf.getChannelData(0);
        for(var i=0;i<d.length;i++){var t=i/audioCtx.sampleRate;d[i]=(Math.random()*2-1)*Math.exp(-t*22)*0.28;}
        var src=audioCtx.createBufferSource(),g=audioCtx.createGain(),f=audioCtx.createBiquadFilter();
        f.type='bandpass';f.frequency.value=1900;f.Q.value=0.7;
        src.buffer=buf;src.connect(f);f.connect(g);g.connect(audioCtx.destination);
        g.gain.setValueAtTime(1,audioCtx.currentTime);
        g.gain.linearRampToValueAtTime(0,audioCtx.currentTime+0.15);
        src.start();
    } catch(e){}
}

/* Page size */
async function calcSize() {
    if(bookHasImages) {
        return new Promise(resolve => {
            var img = new Image();
            img.onload = function() {
                var vp = {width: img.width, height: img.height};
                if(isMobile) {
                    var ah=window.innerHeight-110,aw=window.innerWidth-8;
                    var s=Math.min(ah/vp.height,aw/vp.width);
                    baseW=Math.floor(vp.width*s); baseH=Math.floor(vp.height*s);
                } else {
                    var ah2=window.innerHeight-100,aw2=window.innerWidth-160;
                    var s2=Math.min(ah2/vp.height,(aw2/2)/vp.width);
                    baseW=Math.floor(vp.width*s2); baseH=Math.floor(vp.height*s2);
                }
                resolve({width:baseW,height:baseH});
            };
            img.src = bookFolderPath + '/page_1.jpg';
        });
    }

    var page=await pdfDoc.getPage(1), vp=page.getViewport({scale:1});
    if(isMobile) {
        var ah=window.innerHeight-110,aw=window.innerWidth-8;
        var s=Math.min(ah/vp.height,aw/vp.width);
        baseW=Math.floor(vp.width*s); baseH=Math.floor(vp.height*s);
    } else {
        var ah2=window.innerHeight-100,aw2=window.innerWidth-160;
        var s2=Math.min(ah2/vp.height,(aw2/2)/vp.width);
        baseW=Math.floor(vp.width*s2); baseH=Math.floor(vp.height*s2);
    }
    return {width:baseW,height:baseH};
}

/* Render page */
function renderPage(n) {
    if(done.has(n)) return Promise.resolve();
    if(fly.has(n))  return fly.get(n);
    var sel = isMobile ? '.mobile-page[data-page-index="'+n+'"]' : '.page-container[data-page-index="'+n+'"]';
    var cont = document.querySelector(sel);
    if(!cont) return Promise.resolve();

    if (bookHasImages) {
        var p = new Promise(resolve => {
            var img = new Image();
            img.style.width=baseW+'px'; img.style.height=baseH+'px';
            img.style.display='block';
            img.onload = function() {
                cont.innerHTML=''; cont.appendChild(img);
                done.add(n); updateMPill();
                resolve();
            };
            img.onerror = function() {
                cont.innerHTML='<div class="skeleton-loader"><span style="color:#f87171;font-size:11px">Page '+n+' failed</span></div>';
                resolve();
            };
            img.src = bookFolderPath + '/page_' + n + '.jpg';
        });
        fly.set(n,p); return p;
    }

    var p=(async function(){
        try {
            var page=await pdfDoc.getPage(n);
            var native=page.getViewport({scale:1});
            var fit=Math.min(baseW/native.width,baseH/native.height);
            var sc=fit*(isMobile?2.5:3.0);
            var vp=page.getViewport({scale:sc});
            var canvas=document.createElement('canvas');
            canvas.dataset.page=n;
            canvas.width=Math.round(vp.width); canvas.height=Math.round(vp.height);
            canvas.style.width=baseW+'px'; canvas.style.height=baseH+'px';
            var ctx=canvas.getContext('2d',{alpha:false});
            ctx.imageSmoothingEnabled=true; ctx.imageSmoothingQuality='high';
            ctx.fillStyle='#fff'; ctx.fillRect(0,0,canvas.width,canvas.height);
            await page.render({canvasContext:ctx,viewport:vp,intent:'display'}).promise;
            cont.innerHTML=''; cont.appendChild(canvas);
            done.add(n); updateMPill();
        } catch(e) {
            console.warn('Page '+n,e); fly.delete(n);
            if(cont) cont.innerHTML='<div class="skeleton-loader"><span style="color:#f87171;font-size:11px">Page '+n+' failed</span></div>';
        }
    })();
    fly.set(n,p); return p;
}

/* Thumbnails */
async function renderThumb(n) {
    if (bookHasImages) {
        return new Promise(resolve => {
            var img = new Image();
            img.style.width='100%'; img.style.height='100%';
            img.style.objectFit='cover'; img.style.borderRadius='2px';
            img.onload = () => resolve(img);
            img.onerror = () => resolve(document.createElement('canvas')); // fallback
            img.src = bookFolderPath + '/page_' + n + '.jpg';
        });
    }

    var page=await pdfDoc.getPage(n), vp=page.getViewport({scale:1});
    var sc=80/vp.height, vp2=page.getViewport({scale:sc});
    var c=document.createElement('canvas');
    c.width=Math.round(vp2.width); c.height=Math.round(vp2.height);
    var ctx=c.getContext('2d',{alpha:false});
    ctx.fillStyle='#fff'; ctx.fillRect(0,0,c.width,c.height);
    await page.render({canvasContext:ctx,viewport:vp2,intent:'display'}).promise;
    return c;
}
async function buildThumbs() {
    if(thumbsDone) return; thumbsDone=true;
    thumbPan.innerHTML='';
    for(var i=1;i<=totalPages;i++){
        var item=document.createElement('div'); item.className='thumb-item'; item.dataset.page=i;
        item.innerHTML='<div style="width:56px;height:80px;background:#1a1a1a;border-radius:3px"></div><div class="thumb-num">'+i+'</div>';
        (function(pg,el){
            el.addEventListener('click',function(){pageFlip&&pageFlip.flip(pg-1);closeThumbs();});
            renderThumb(pg).then(function(c){
                el.innerHTML=''; c.style.borderRadius='2px'; el.appendChild(c);
                var num=document.createElement('div'); num.className='thumb-num'; num.textContent=pg; el.appendChild(num);
            }).catch(function(){});
        })(i,item);
        thumbPan.appendChild(item);
    }
}
function openThumbs()  { thumbPan.classList.add('open'); buildThumbs(); document.getElementById('tb-thumb').classList.add('active'); }
function closeThumbs() { thumbPan.classList.remove('open'); document.getElementById('tb-thumb').classList.remove('active'); }
function toggleThumbs(){ thumbPan.classList.contains('open')?closeThumbs():openThumbs(); }
function syncThumb(n) {
    document.querySelectorAll('.thumb-item').forEach(function(el){el.classList.toggle('active',parseInt(el.dataset.page)===n);});
}

/* Autoplay */
function toggleAutoplay() {
    autoOn=!autoOn;
    var btn=document.getElementById('tb-autoplay'),icon=document.getElementById('ap-icon');
    btn.classList.toggle('active',autoOn);
    if(autoOn){
        icon.innerHTML='<rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>';
        autoTimer=setInterval(function(){pageFlip&&pageFlip.flipNext();},3000);
    } else {
        icon.innerHTML='<path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.97l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/>';
        clearInterval(autoTimer);
    }
}

/* Zoom */
function setZoom(r) {
    currentZoom=Math.max(0.4,Math.min(3,r));
    zWrapper.style.transform='scale('+currentZoom+')';
    document.getElementById('zoom-label').textContent=Math.round(currentZoom*100)+'%';
    document.getElementById('book-stage').style.overflow=currentZoom>1?'auto':'hidden';
}

/* Fullscreen */
function toggleFS() {
    if(!document.fullscreenElement) document.getElementById('desktop-viewer').requestFullscreen&&document.getElementById('desktop-viewer').requestFullscreen();
    else document.exitFullscreen&&document.exitFullscreen();
}
document.addEventListener('fullscreenchange',function(){
    var f=!!document.fullscreenElement;
    document.getElementById('fs-expand').style.display=f?'none':'';
    document.getElementById('fs-shrink').style.display=f?'':'none';
});

/* ══ MOBILE ══ */
async function initMobile(sz) {
    mStrip.innerHTML='';
    for(var i=1;i<=totalPages;i++){
        var sl=document.createElement('div'); sl.className='mobile-page';
        sl.dataset.pageIndex = i;
        sl.style.width=sz.width+'px'; sl.style.height=sz.height+'px';
        sl.innerHTML='<div class="skeleton-loader"><span style="color:#475569;font-size:11px">Page '+i+'</span></div>';
        mStrip.appendChild(sl);
    }
    loadTxt.textContent='Rendering cover...';
    var initPages = Math.min(1, totalPages);
    for(var i=1;i<=initPages;i++){
        await renderPage(i); setProgress(42+(i/initPages)*53);
    }
    setProgress(98); loadTxt.textContent='Opening flipbook...';
    mStrip.style.display='block';

    pageFlip=new St.PageFlip(mStrip,{
        width:sz.width, height:sz.height, size:'fixed',
        drawShadow:true, showCover:true, maxShadowOpacity:0.45,
        usePortrait:true, autoSize:false, flippingTime:520, useMouseEvents:true
    });
    pageFlip.loadFromHTML(document.querySelectorAll('.mobile-page'));
    setProgress(100);
    setTimeout(function(){loadOv.classList.add('hidden');},300);
    setPgIndicator(1);
    
    pageFlip.on('flip',function(e){
        playFlip();setPgIndicator(e.data+1);mIdx=e.data;updateMPill();
        var p = e.data + 1;
        renderPage(p);
        if(p+1 <= totalPages) renderPage(p+1);
        if(p+2 <= totalPages) renderPage(p+2);
        if(p-1 >= 1) renderPage(p-1);
    });

    setTimeout(async function() {
        for(var i=1;i<=totalPages;i++){
            if(!done.has(i)) {
                await renderPage(i);
                await new Promise(r => setTimeout(r, 50));
            }
        }
    }, 1000);

    document.getElementById('m-prev').addEventListener('click',function(){pageFlip.flipPrev();});
    document.getElementById('m-next').addEventListener('click',function(){pageFlip.flipNext();});
    setTimeout(function(){if(hint)hint.style.opacity='0';},3000);
}

/* ══ DESKTOP ══ */
async function initDesktop(sz) {
    flipEl.innerHTML='';
    for(var i=1;i<=totalPages;i++){
        var d=document.createElement('div'); d.className='page-container';
        d.dataset.pageIndex = i;
        d.style.width=sz.width+'px'; d.style.height=sz.height+'px';
        d.innerHTML='<div class="skeleton-loader"><span style="color:#94a3b8;font-size:11px">Page '+i+'</span></div>';
        flipEl.appendChild(d);
    }
    loadTxt.textContent='Rendering cover...';
    var initPages = Math.min(2, totalPages); // 2 pages for desktop to show front cover and inside cover if they open it instantly
    for(var i=1;i<=initPages;i++){
        await renderPage(i); setProgress(42+(i/initPages)*53);
    }
    setProgress(98); loadTxt.textContent='Opening flipbook...';

    pageFlip=new St.PageFlip(flipEl,{
        width:sz.width, height:sz.height, size:'fixed',
        drawShadow:true, showCover:true, maxShadowOpacity:0.45,
        usePortrait:false, autoSize:false, flippingTime:520, useMouseEvents:true
    });
    pageFlip.loadFromHTML(document.querySelectorAll('.page-container'));
    flipEl.style.display='block';
    setProgress(100);
    setTimeout(function(){loadOv.classList.add('hidden');},400);
    setPgIndicator(1);

    pageFlip.on('flip',function(e){
        playFlip();setPgIndicator(e.data+1);syncThumb(e.data+1);
        var p = e.data + 1;
        renderPage(p);
        if(p+1 <= totalPages) renderPage(p+1);
        if(p+2 <= totalPages) renderPage(p+2);
        if(p+3 <= totalPages) renderPage(p+3);
        if(p-1 >= 1) renderPage(p-1);
        if(p-2 >= 1) renderPage(p-2);
    });

    setTimeout(async function() {
        for(var i=1;i<=totalPages;i++){
            if(!done.has(i)) {
                await renderPage(i);
                await new Promise(r => setTimeout(r, 50));
            }
        }
    }, 1000);

    document.getElementById('btn-prev').addEventListener('click',function(){pageFlip.flipPrev();});
    document.getElementById('btn-next').addEventListener('click',function(){pageFlip.flipNext();});
    document.getElementById('btn-first').addEventListener('click',function(){pageFlip.flip(0);});
    document.getElementById('btn-last').addEventListener('click',function(){pageFlip.flip(totalPages-1);});
    document.getElementById('tb-zoom-out').addEventListener('click',function(){setZoom(currentZoom-0.2);});
    document.getElementById('tb-zoom-in').addEventListener('click',function(){setZoom(currentZoom+0.2);});
    document.getElementById('tb-thumb').addEventListener('click',toggleThumbs);
    document.getElementById('tb-autoplay').addEventListener('click',toggleAutoplay);
    document.getElementById('tb-fit').addEventListener('click',function(){setZoom(1);});
    document.getElementById('tb-fs').addEventListener('click',toggleFS);

    document.addEventListener('keydown',function(e){
        if(e.key==='ArrowLeft'){pageFlip.flipPrev();}
        if(e.key==='ArrowRight'){pageFlip.flipNext();}
        if(e.key==='Escape'){closeThumbs();}
        if(e.key==='+'||e.key==='=') setZoom(currentZoom+0.2);
        if(e.key==='-') setZoom(currentZoom-0.2);
    });
}

/* ══ MAIN ══ */
async function init() {
    try {
        loadTxt.textContent='Connecting to stream...';
        retryBtn.style.display='none';

        if (bookHasImages) {
            totalPages = bookPageCount;
            setProgress(42);
            loadTxt.textContent='Calculating layout...';
            var sz=await calcSize();
            if(isMobile) await initMobile(sz);
            else await initDesktop(sz);
            return;
        }

        // Verify the stream URL is reachable before giving it to pdf.js
        loadTxt.textContent='Loading PDF from: ' + streamUrl.replace(window.location.origin,'');

        var task=pdfjsLib.getDocument({
            url:             streamUrl,
            disableRange:    false,
            disableStream:   false,
            rangeChunkSize:  65536,
            withCredentials: false,
            // Skip ngrok browser-warning interstitial page
            httpHeaders: { 'ngrok-skip-browser-warning': '1' }
        });
        task.onProgress=function(p){if(p.total>0)setProgress((p.loaded/p.total)*40);};
        pdfDoc=await task.promise;
        totalPages=pdfDoc.numPages;
        setProgress(42);
        loadTxt.textContent='Calculating layout...';
        var sz=await calcSize();
        if(isMobile) await initMobile(sz);
        else await initDesktop(sz);
    } catch(err) {
        console.error('PDF load error:', err);
        // Show a specific error message to help diagnose the problem
        var msg = 'Stream error';
        if(err && err.message) {
            if(err.message.indexOf('Missing PDF')>-1 || err.message.indexOf('404')>-1) {
                msg = 'PDF not found (404). Check if the file exists.';
            } else if(err.message.indexOf('NetworkError')>-1 || err.message.indexOf('Failed to fetch')>-1) {
                msg = 'Network error. Is the server running?';
            } else if(err.message.indexOf('CORS')>-1 || err.message.indexOf('cross-origin')>-1) {
                msg = 'CORS error. Check server headers.';
            } else {
                msg = 'Error: ' + err.message.substring(0,80);
            }
        } else if(err && err.name) {
            msg = err.name + ' — ' + streamUrl;
        }
        loadTxt.textContent = msg;
        retryBtn.style.display='block';
    }
}
retryBtn.addEventListener('click',function(){window.location.reload();});
init();
</script>
</body>
</html>
