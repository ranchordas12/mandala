<?php
session_start();
require_once 'api/config.php';
require_once 'api/auth.php';

// Validate session token
$user = validateSession();
if(!$user){
    header('Location: index.html?auth=required');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Mandala Gallery</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,300&family=Josefin+Sans:wght@200;300;400&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --ink:#0e0e0e;--paper:#f8f4ef;--gold:#c9a84c;--rose:#8b3a52;
  --teal:#2a5c5a;--muted:#9e9085;--white:#fff;--success:#2d7a4f;
  --err:#8b3a52;--sidebar:#0e0e0e;--card:#ffffff;
  --shadow:0 2px 20px rgba(14,14,14,.07);
}
body{font-family:'Josefin Sans',sans-serif;background:var(--paper);color:var(--ink);display:grid;grid-template-columns:240px 1fr;min-height:100vh}

/* SIDEBAR */
.sidebar{background:var(--sidebar);color:#fff;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar-brand{padding:2rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.06)}
.sidebar-brand h1{font-family:'Cormorant Garamond',serif;font-size:1.2rem;font-weight:300;letter-spacing:.08em}
.sidebar-brand h1 span{color:var(--gold)}
.sidebar-user{padding:1rem 1.5rem;font-size:.68rem;letter-spacing:.15em;text-transform:uppercase;color:rgba(255,255,255,.3);border-bottom:1px solid rgba(255,255,255,.06)}
.sidebar-user strong{display:block;color:rgba(255,255,255,.7);margin-top:.2rem;text-transform:none;letter-spacing:0;font-weight:300;font-size:.78rem}
nav.sidebar-nav{flex:1;padding:1rem 0}
.nav-item{display:block;padding:.85rem 1.5rem;font-size:.7rem;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.45);cursor:pointer;border:none;background:none;width:100%;text-align:left;transition:all .2s;border-left:2px solid transparent}
.nav-item:hover,.nav-item.active{color:#fff;border-left-color:var(--gold);background:rgba(201,168,76,.06)}
.sidebar-logout{padding:1.5rem;border-top:1px solid rgba(255,255,255,.06)}
.logout-btn{width:100%;padding:.65rem;background:rgba(139,58,82,.15);border:1px solid rgba(139,58,82,.3);color:rgba(255,255,255,.5);font-family:'Josefin Sans',sans-serif;font-size:.65rem;letter-spacing:.18em;text-transform:uppercase;cursor:pointer;transition:all .2s}
.logout-btn:hover{background:var(--rose);color:#fff;border-color:var(--rose)}

/* MAIN */
main{overflow-y:auto;height:100vh}
.topbar{background:var(--white);padding:1.2rem 2rem;border-bottom:1px solid rgba(14,14,14,.06);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10}
.page-title{font-family:'Cormorant Garamond',serif;font-size:1.5rem;font-weight:300}
.content{padding:2rem}

/* TABS */
.tab-panel{display:none}.tab-panel.active{display:block}

/* CARDS / STATS */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem}
.stat-card{background:var(--card);padding:1.5rem;box-shadow:var(--shadow)}
.stat-label{font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);margin-bottom:.4rem}
.stat-val{font-family:'Cormorant Garamond',serif;font-size:2.5rem;font-weight:300}

/* FORMS */
.form-section{background:var(--card);padding:2rem;box-shadow:var(--shadow);margin-bottom:2rem}
.form-section h3{font-family:'Cormorant Garamond',serif;font-size:1.3rem;font-weight:300;margin-bottom:1.5rem;padding-bottom:.8rem;border-bottom:1px solid rgba(14,14,14,.07)}
.field{margin-bottom:1.2rem}
.field label{display:block;font-size:.65rem;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:.4rem}
.field input,.field textarea,.field select{width:100%;padding:.75rem;border:1px solid rgba(14,14,14,.12);background:var(--paper);font-family:'Josefin Sans',sans-serif;font-size:.85rem;outline:none;transition:border-color .2s}
.field input:focus,.field textarea:focus,.field select:focus{border-color:var(--gold)}
.field textarea{resize:vertical;min-height:100px}
.field small{font-size:.65rem;color:var(--muted);margin-top:.3rem;display:block}
.fields-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.drop-zone{border:1.5px dashed rgba(14,14,14,.2);padding:2rem;text-align:center;cursor:pointer;transition:all .2s;background:var(--paper)}
.drop-zone:hover,.drop-zone.drag-over{border-color:var(--gold);background:rgba(201,168,76,.04)}
.drop-zone p{font-size:.75rem;color:var(--muted);letter-spacing:.1em}
.drop-zone input[type=file]{display:none}
.preview-img{max-width:100%;max-height:200px;margin-top:1rem;display:none}

/* BUTTONS */
.btn{padding:.7rem 1.6rem;font-family:'Josefin Sans',sans-serif;font-size:.68rem;letter-spacing:.18em;text-transform:uppercase;border:none;cursor:pointer;transition:all .2s}
.btn-dark{background:var(--ink);color:#fff}.btn-dark:hover{opacity:.8}
.btn-gold{background:var(--gold);color:#fff}.btn-gold:hover{opacity:.85}
.btn-danger{background:rgba(139,58,82,.1);color:var(--rose);border:1px solid rgba(139,58,82,.25)}.btn-danger:hover{background:var(--rose);color:#fff}
.btn:disabled{opacity:.45;cursor:not-allowed}

/* ITEMS LIST */
.items-list{display:grid;gap:1rem}
.item-row{background:var(--card);padding:1.2rem 1.5rem;box-shadow:var(--shadow);display:grid;grid-template-columns:60px 1fr auto;gap:1rem;align-items:center}
.item-thumb{width:60px;height:60px;object-fit:cover;display:block}
.item-thumb-ph{width:60px;height:60px;background:var(--paper);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase}
.item-info h4{font-size:.9rem;font-weight:400;margin-bottom:.25rem}
.item-info p{font-size:.7rem;color:var(--muted);letter-spacing:.1em}
.item-actions{display:flex;gap:.5rem}

/* ALERTS */
.alert{padding:.9rem 1.2rem;font-size:.78rem;letter-spacing:.08em;margin-bottom:1.2rem;border-left:3px solid}
.alert-ok{background:rgba(45,122,79,.08);border-color:var(--success);color:var(--success)}
.alert-err{background:rgba(139,58,82,.08);border-color:var(--rose);color:var(--rose)}
.alert{animation:fadeSlide .3s ease}
@keyframes fadeSlide{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}

/* PROGRESS */
.progress{width:100%;height:4px;background:rgba(14,14,14,.08);margin-top:.8rem;display:none}
.progress-bar{height:100%;background:var(--gold);width:0;transition:width .3s}

@media(max-width:900px){
  body{grid-template-columns:1fr}
  .sidebar{position:fixed;left:-240px;z-index:100;transition:left .3s}
  .sidebar.open{left:0}
  .stats-row{grid-template-columns:1fr}
  .fields-row{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <h1>Mandala<span>.</span></h1>
  </div>
  <div class="sidebar-user">
    Dashboard
    <strong><?= htmlspecialchars($user['name']) ?></strong>
  </div>
  <nav class="sidebar-nav">
    <button class="nav-item active" data-tab="overview">Overview</button>
    <button class="nav-item" data-tab="upload">Upload Mandala</button>
    <button class="nav-item" data-tab="mandalas">Manage Mandalas</button>
    <button class="nav-item" data-tab="blog">Blog / Journal</button>
    <button class="nav-item" data-tab="settings">Settings</button>
  </nav>
  <div class="sidebar-logout">
    <button class="logout-btn" id="logoutBtn">Sign Out</button>
  </div>
</aside>

<!-- MAIN -->
<main>
  <div class="topbar">
    <h2 class="page-title" id="pageTitle">Overview</h2>
    <a href="index.html" style="font-size:.65rem;letter-spacing:.15em;color:var(--muted);text-decoration:none;text-transform:uppercase">← View Site</a>
  </div>

  <div class="content">

    <!-- OVERVIEW -->
    <div class="tab-panel active" id="tab-overview">
      <div class="stats-row">
        <div class="stat-card"><div class="stat-label">Total Mandalas</div><div class="stat-val" id="stat-mandalas">—</div></div>
        <div class="stat-card"><div class="stat-label">Blog Posts</div><div class="stat-val" id="stat-blogs">—</div></div>
        <div class="stat-card"><div class="stat-label">Downloads</div><div class="stat-val" id="stat-dl">—</div></div>
      </div>
      <div id="overview-alert"></div>
      <div class="form-section">
        <h3>Quick Upload</h3>
        <p style="font-size:.78rem;color:var(--muted)">Go to <strong>Upload Mandala</strong> tab to add new artwork to your gallery.</p>
      </div>
    </div>

    <!-- UPLOAD MANDALA -->
    <div class="tab-panel" id="tab-upload">
      <div id="upload-alert"></div>
      <div class="form-section">
        <h3>Upload New Mandala</h3>
        <form id="uploadForm">
          <div class="fields-row">
            <div class="field">
              <label>Title *</label>
              <input type="text" name="title" placeholder="e.g. Lotus Geometry" required maxlength="120">
            </div>
            <div class="field">
              <label>Category *</label>
              <select name="category" required>
                <option value="">— Select —</option>
                <option value="geometric">Geometric</option>
                <option value="floral">Floral</option>
                <option value="spiritual">Spiritual</option>
                <option value="abstract">Abstract</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label>Description</label>
            <textarea name="description" placeholder="About this piece…" rows="3"></textarea>
          </div>
          <div class="field">
            <label>Image File *</label>
            <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
              <input type="file" id="fileInput" accept="image/jpeg,image/png,image/webp" required>
              <p>Click to select — or drag & drop<br>JPG, PNG, WEBP · Max 8 MB</p>
              <img class="preview-img" id="imgPreview" alt="">
            </div>
            <small>Your artist initials will be embedded in the metadata automatically.</small>
          </div>
          <div class="progress" id="uploadProgress"><div class="progress-bar" id="uploadBar"></div></div>
          <button type="submit" class="btn btn-dark" id="uploadBtn" style="margin-top:1rem">Upload Mandala</button>
        </form>
      </div>
    </div>

    <!-- MANAGE MANDALAS -->
    <div class="tab-panel" id="tab-mandalas">
      <div id="mandalas-alert"></div>
      <div class="items-list" id="mandalaList">
        <p style="font-size:.8rem;color:var(--muted)">Loading…</p>
      </div>
    </div>

    <!-- BLOG -->
    <div class="tab-panel" id="tab-blog">
      <div id="blog-alert"></div>
      <div class="form-section">
        <h3>Write Blog Post</h3>
        <form id="blogForm">
          <div class="field">
            <label>Title *</label>
            <input type="text" name="title" placeholder="Post title" required maxlength="200">
          </div>
          <div class="field">
            <label>Excerpt *</label>
            <input type="text" name="excerpt" placeholder="One-line summary (shown on homepage)" required maxlength="250">
          </div>
          <div class="field">
            <label>Content *</label>
            <textarea name="content" rows="10" placeholder="Write your post here…" required></textarea>
          </div>
          <button type="submit" class="btn btn-dark" style="margin-top:.5rem">Publish Post</button>
        </form>
      </div>
      <div class="form-section">
        <h3>Published Posts</h3>
        <div class="items-list" id="blogList">
          <p style="font-size:.8rem;color:var(--muted)">Loading…</p>
        </div>
      </div>
    </div>

    <!-- SETTINGS -->
    <div class="tab-panel" id="tab-settings">
      <div id="settings-alert"></div>
      <div class="form-section">
        <h3>Artist Watermark</h3>
        <p style="font-size:.78rem;color:var(--muted);margin-bottom:1.2rem">These initials are invisibly embedded in every downloaded image's EXIF metadata to prove your ownership.</p>
        <form id="initialsForm">
          <div class="fields-row">
            <div class="field">
              <label>Artist Initials</label>
              <input type="text" name="initials" id="initialsInput" placeholder="e.g. AG" maxlength="10">
            </div>
            <div class="field">
              <label>Artist Full Name</label>
              <input type="text" name="artist_name" id="artistNameInput" placeholder="e.g. Aastha Ghimire" maxlength="80">
            </div>
          </div>
          <button type="submit" class="btn btn-dark">Save Watermark</button>
        </form>
      </div>
      <div class="form-section">
        <h3>Change Password</h3>
        <form id="pwForm">
          <div class="field"><label>Current Password</label><input type="password" name="current" required></div>
          <div class="field"><label>New Password (min 8 chars)</label><input type="password" name="new" required minlength="8"></div>
          <div class="field"><label>Confirm New Password</label><input type="password" name="confirm" required></div>
          <button type="submit" class="btn btn-dark">Update Password</button>
        </form>
      </div>
    </div>

  </div><!-- /content -->
</main>

<script>
const API='api/api.php';
const token=localStorage.getItem('mg_token')||'';
if(!token){window.location.href='index.html';}

/* ── SIDEBAR TABS ── */
const tabs=document.querySelectorAll('.nav-item');
const panels=document.querySelectorAll('.tab-panel');
const pageTitle=document.getElementById('pageTitle');
const titleMap={overview:'Overview',upload:'Upload Mandala',mandalas:'Manage Mandalas',blog:'Journal',settings:'Settings'};

tabs.forEach(t=>{
  t.addEventListener('click',function(){
    tabs.forEach(x=>x.classList.remove('active'));
    panels.forEach(x=>x.classList.remove('active'));
    this.classList.add('active');
    const tab=this.dataset.tab;
    document.getElementById('tab-'+tab).classList.add('active');
    pageTitle.textContent=titleMap[tab]||tab;
    if(tab==='mandalas') loadAdminMandalas();
    if(tab==='blog') loadAdminBlogs();
    if(tab==='overview') loadStats();
    if(tab==='settings') loadSettings();
  });
});

/* ── ALERT HELPER ── */
function showAlert(containerId,msg,type='ok'){
  const el=document.getElementById(containerId);
  el.innerHTML=`<div class="alert alert-${type}">${msg}</div>`;
  setTimeout(()=>{el.innerHTML='';},5000);
}

/* ── AUTH HEADER ── */
function authHeaders(){return{'Content-Type':'application/json','X-Auth-Token':token};}

/* ── LOGOUT ── */
document.getElementById('logoutBtn').addEventListener('click',async()=>{
  if(!confirm('Sign out?')) return;
  await fetch(API,{method:'POST',headers:authHeaders(),body:JSON.stringify({action:'logout'})}).catch(()=>{});
  localStorage.removeItem('mg_token');
  localStorage.removeItem('mg_user');
  window.location.href='index.html';
});

/* ── STATS ── */
async function loadStats(){
  try{
    const r=await fetch(API+'?action=stats',{headers:{'X-Auth-Token':token}});
    const d=await r.json();
    if(d.success){
      document.getElementById('stat-mandalas').textContent=d.data.mandalas;
      document.getElementById('stat-blogs').textContent=d.data.blogs;
      document.getElementById('stat-dl').textContent=d.data.downloads;
    }
  }catch{}
}

/* ── FILE UPLOAD DRAG & DROP ── */
const dropZone=document.getElementById('dropZone');
const fileInput=document.getElementById('fileInput');
const preview=document.getElementById('imgPreview');

dropZone.addEventListener('dragover',e=>{e.preventDefault();dropZone.classList.add('drag-over');});
dropZone.addEventListener('dragleave',()=>dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop',e=>{
  e.preventDefault();dropZone.classList.remove('drag-over');
  if(e.dataTransfer.files[0]){fileInput.files=e.dataTransfer.files;showPreview(e.dataTransfer.files[0]);}
});
fileInput.addEventListener('change',()=>{if(fileInput.files[0])showPreview(fileInput.files[0]);});

function showPreview(file){
  if(file.size>8*1024*1024){showAlert('upload-alert','File too large (max 8 MB)','err');return;}
  const reader=new FileReader();
  reader.onload=e=>{preview.src=e.target.result;preview.style.display='block';};
  reader.readAsDataURL(file);
}

/* ── UPLOAD MANDALA ── */
document.getElementById('uploadForm').addEventListener('submit',async function(e){
  e.preventDefault();
  if(!fileInput.files[0]){showAlert('upload-alert','Please select an image','err');return;}
  const btn=document.getElementById('uploadBtn');
  btn.disabled=true; btn.textContent='Uploading…';
  const progress=document.getElementById('uploadProgress');
  const bar=document.getElementById('uploadBar');
  progress.style.display='block'; bar.style.width='0%';

  try{
    const fd=new FormData(this);
    fd.append('action','upload_mandala');
    fd.append('image',fileInput.files[0]);

    const xhr=new XMLHttpRequest();
    xhr.open('POST',API);
    xhr.setRequestHeader('X-Auth-Token',token);
    xhr.upload.onprogress=ev=>{if(ev.lengthComputable){bar.style.width=Math.round(ev.loaded/ev.total*100)+'%';}};
    xhr.onload=function(){
      progress.style.display='none';
      try{
        const d=JSON.parse(xhr.responseText);
        if(d.success){showAlert('upload-alert','Mandala uploaded successfully!');e.target.reset();preview.style.display='none';}
        else showAlert('upload-alert',d.message||'Upload failed','err');
      }catch{showAlert('upload-alert','Unexpected error','err');}
      btn.disabled=false; btn.textContent='Upload Mandala';
    };
    xhr.onerror=()=>{showAlert('upload-alert','Network error','err');btn.disabled=false;btn.textContent='Upload Mandala';};
    xhr.send(fd);
  }catch(ex){showAlert('upload-alert','Upload failed: '+ex.message,'err');btn.disabled=false;btn.textContent='Upload Mandala';}
});

/* ── ADMIN MANDALAS ── */
async function loadAdminMandalas(){
  const list=document.getElementById('mandalaList');
  list.innerHTML='<p style="font-size:.8rem;color:var(--muted)">Loading…</p>';
  try{
    const r=await fetch(API+'?action=admin_mandalas',{headers:{'X-Auth-Token':token}});
    const d=await r.json();
    if(!d.success||!d.data.length){list.innerHTML='<p style="font-size:.8rem;color:var(--muted)">No mandalas yet.</p>';return;}
    list.innerHTML=d.data.map(m=>`
      <div class="item-row" id="mrow-${m.id}">
        <img class="item-thumb" src="api/thumb.php?id=${m.id}" alt="" onerror="this.style.display='none'">
        <div class="item-info">
          <h4>${esc(m.title)}</h4>
          <p>${m.category} &middot; ${fmtDate(m.created_at)}</p>
        </div>
        <div class="item-actions">
          <button class="btn btn-danger" onclick="deleteMandala(${m.id})">Delete</button>
        </div>
      </div>
    `).join('');
  }catch{list.innerHTML='<p style="font-size:.8rem;color:var(--muted)">Failed to load.</p>';}
}

async function deleteMandala(id){
  if(!confirm('Permanently delete this mandala?')) return;
  try{
    const r=await fetch(API,{method:'POST',headers:authHeaders(),body:JSON.stringify({action:'delete_mandala',id})});
    const d=await r.json();
    if(d.success){document.getElementById('mrow-'+id)?.remove();showAlert('mandalas-alert','Mandala deleted.');}
    else showAlert('mandalas-alert',d.message||'Delete failed','err');
  }catch{showAlert('mandalas-alert','Network error','err');}
}

/* ── BLOG ── */
document.getElementById('blogForm').addEventListener('submit',async function(e){
  e.preventDefault();
  const fd=new FormData(this);
  try{
    const r=await fetch(API,{method:'POST',headers:authHeaders(),body:JSON.stringify({action:'create_blog',title:fd.get('title'),excerpt:fd.get('excerpt'),content:fd.get('content')})});
    const d=await r.json();
    if(d.success){showAlert('blog-alert','Post published!');this.reset();loadAdminBlogs();}
    else showAlert('blog-alert',d.message||'Failed','err');
  }catch{showAlert('blog-alert','Network error','err');}
});

async function loadAdminBlogs(){
  const list=document.getElementById('blogList');
  list.innerHTML='<p style="font-size:.8rem;color:var(--muted)">Loading…</p>';
  try{
    const r=await fetch(API+'?action=admin_blogs',{headers:{'X-Auth-Token':token}});
    const d=await r.json();
    if(!d.success||!d.data.length){list.innerHTML='<p style="font-size:.8rem;color:var(--muted)">No posts yet.</p>';return;}
    list.innerHTML=d.data.map(b=>`
      <div class="item-row" id="brow-${b.id}">
        <div class="item-thumb-ph">Post</div>
        <div class="item-info">
          <h4>${esc(b.title)}</h4>
          <p>${fmtDate(b.created_at)}</p>
        </div>
        <div class="item-actions">
          <button class="btn btn-danger" onclick="deleteBlog(${b.id})">Delete</button>
        </div>
      </div>
    `).join('');
  }catch{list.innerHTML='<p style="font-size:.8rem;color:var(--muted)">Failed to load.</p>';}
}

async function deleteBlog(id){
  if(!confirm('Delete this post?')) return;
  try{
    const r=await fetch(API,{method:'POST',headers:authHeaders(),body:JSON.stringify({action:'delete_blog',id})});
    const d=await r.json();
    if(d.success){document.getElementById('brow-'+id)?.remove();showAlert('blog-alert','Post deleted.');}
    else showAlert('blog-alert',d.message||'Failed','err');
  }catch{showAlert('blog-alert','Network error','err');}
}

/* ── SETTINGS ── */
async function loadSettings(){
  try{
    const r=await fetch(API+'?action=get_settings',{headers:{'X-Auth-Token':token}});
    const d=await r.json();
    if(d.success&&d.data){
      document.getElementById('initialsInput').value=d.data.artist_initials||'';
      document.getElementById('artistNameInput').value=d.data.artist_name||'';
    }
  }catch{}
}

document.getElementById('initialsForm').addEventListener('submit',async function(e){
  e.preventDefault();
  const fd=new FormData(this);
  try{
    const r=await fetch(API,{method:'POST',headers:authHeaders(),body:JSON.stringify({action:'save_settings',artist_initials:fd.get('initials'),artist_name:fd.get('artist_name')})});
    const d=await r.json();
    if(d.success) showAlert('settings-alert','Watermark settings saved!');
    else showAlert('settings-alert',d.message||'Failed','err');
  }catch{showAlert('settings-alert','Network error','err');}
});

document.getElementById('pwForm').addEventListener('submit',async function(e){
  e.preventDefault();
  const fd=new FormData(this);
  if(fd.get('new')!==fd.get('confirm')){showAlert('settings-alert','Passwords do not match','err');return;}
  try{
    const r=await fetch(API,{method:'POST',headers:authHeaders(),body:JSON.stringify({action:'change_password',current:fd.get('current'),new_password:fd.get('new')})});
    const d=await r.json();
    if(d.success){showAlert('settings-alert','Password updated!');this.reset();}
    else showAlert('settings-alert',d.message||'Failed','err');
  }catch{showAlert('settings-alert','Network error','err');}
});

/* ── UTILS ── */
function fmtDate(s){return new Date(s).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});}
function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}

/* ── INIT ── */
loadStats();
</script>
</body>
</html>
