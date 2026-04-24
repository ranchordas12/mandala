<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Journal — Mandala Gallery</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Josefin+Sans:wght@200;300;400&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--ink:#0e0e0e;--paper:#f8f4ef;--gold:#c9a84c;--muted:#9e9085;--white:#fff}
body{font-family:'Josefin Sans',sans-serif;background:var(--paper);color:var(--ink)}
nav{padding:1.4rem 3rem;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(201,168,76,.15)}
.logo{font-family:'Cormorant Garamond',serif;font-size:1.3rem;font-weight:600;text-decoration:none;color:var(--ink);letter-spacing:.06em}
.logo span{color:var(--gold)}
.back{font-size:.7rem;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);text-decoration:none;transition:color .2s}
.back:hover{color:var(--ink)}
.post-wrap{max-width:720px;margin:5rem auto;padding:0 2rem}
.post-date{font-size:.68rem;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:1rem}
.post-title{font-family:'Cormorant Garamond',serif;font-size:clamp(2rem,5vw,3.2rem);font-weight:300;line-height:1.2;margin-bottom:2rem}
.post-body{font-family:'Cormorant Garamond',serif;font-size:1.2rem;line-height:1.85;color:#333;white-space:pre-wrap}
.loading{color:var(--muted);font-size:.85rem;letter-spacing:.1em}
.error{color:#8b3a52;font-size:.85rem}
</style>
</head>
<body>
<nav>
  <a class="logo" href="index.html">Mandala<span>.</span></a>
  <a class="back" href="index.html#blog">← Back to Journal</a>
</nav>
<div class="post-wrap">
  <p class="loading" id="status">Loading…</p>
  <div id="postContent" style="display:none">
    <div class="post-date" id="postDate"></div>
    <h1 class="post-title" id="postTitle"></h1>
    <div class="post-body" id="postBody"></div>
  </div>
</div>
<script>
const id=new URLSearchParams(location.search).get('id');
if(!id){document.getElementById('status').textContent='Post not found.';} else {
  fetch(`api/api.php?action=blog_post&id=${id}`)
    .then(r=>r.json())
    .then(d=>{
      document.getElementById('status').style.display='none';
      if(!d.success){document.getElementById('status').textContent='Post not found.';document.getElementById('status').className='error';return;}
      const p=d.data;
      document.title=p.title+' — Mandala Gallery';
      document.getElementById('postDate').textContent=new Date(p.created_at).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'});
      document.getElementById('postTitle').textContent=p.title;
      document.getElementById('postBody').textContent=p.content;
      document.getElementById('postContent').style.display='block';
    })
    .catch(()=>{document.getElementById('status').textContent='Failed to load.';document.getElementById('status').className='error';});
}
</script>
</body>
</html>
