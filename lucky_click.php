<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/config.php";
if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Lucky Click</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="style.css?v=999">
  <style>
    .wrap{max-width:980px;margin:24px auto;padding:0 16px;}
    .panel{padding:18px;border-radius:18px;background:rgba(255,255,255,.06);backdrop-filter:blur(10px);}

    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-top:14px;}

    .cardBtn{
      border:none; cursor:pointer; padding:0;
      border-radius:18px; overflow:hidden;
      background:rgba(255,255,255,.07);
      box-shadow:0 12px 30px rgba(0,0,0,.22);
      transition:.15s transform, .15s background;
      position:relative;
      aspect-ratio: 16/10;
    }
    .cardBtn:hover{transform:translateY(-3px); background:rgba(255,255,255,.10)}
    .cardBtn:disabled{opacity:.7;cursor:not-allowed;transform:none;}


    .cover{
      position:absolute; inset:0;
      width:100%; height:100%;
      object-fit:cover;
      display:block;
      filter:saturate(1.05) contrast(1.05);
    }

    .shade{
      position:absolute; inset:0;
      background:linear-gradient(to top, rgba(0,0,0,.65), rgba(0,0,0,.10));
    }

    .label{
      position:absolute; left:14px; bottom:12px;
      font-weight:900; font-size:18px; color:#fff;
      text-shadow:0 8px 18px rgba(0,0,0,.9);
    }

    /* reveal */
    .revealBox{
      position:absolute; inset:0;
      display:flex; align-items:center; justify-content:center;
      font-weight:1000; font-size:34px;
      color:#fff;
      background:rgba(0,0,0,.65);
      backdrop-filter: blur(3px);
      opacity:0;
      transform:scale(.98);
      transition: .18s ease;
    }
    .cardBtn.revealed .revealBox{
      opacity:1;
      transform:scale(1);
    }

    .msg{margin-top:12px;min-height:26px}
    .hint{opacity:.85;margin-top:6px}
  </style>
</head>
<body class="app-body">
  <div class="wrap">
    <div class="panel">
      <div class="app-title">Lucky Click 🍀</div>
      <div class="app-sub">Pick 1 card per day. Cover = movie frame. Inside = points.</div>
      <div class="hint"><b>Options:</b> 0, 5, 10, 15, 20, 30</div>

      <div class="grid" id="cards"></div>
      <div id="msg" class="msg"></div>

      <p><a class="btn btn-ghost" href="games.php">Back to Games</a></p>
    </div>
  </div>

<script>
const cardsEl = document.getElementById("cards");
const msg = document.getElementById("msg");


const rewards = [0,5,10,15,20,30];


const covers = [
  "assets/frame1.jpeg",
  "assets/frame2.jpeg",
  "assets/frame3.jpeg",
  "assets/frame4.jpeg",
  "assets/frame5.jpeg",
  "assets/frame6.jpg"
];


function shuffle(arr){
  const a = arr.slice();
  for (let i=a.length-1;i>0;i--){
    const j = Math.floor(Math.random()*(i+1));
    [a[i],a[j]] = [a[j],a[i]];
  }
  return a;
}
const hiddenPoints = shuffle(rewards);

function makeCard(i){
  const btn = document.createElement("button");
  btn.className = "cardBtn";
  btn.type = "button";

  const img = document.createElement("img");
  img.className = "cover";
  img.src = covers[i] || "assets/placeholder.jpg";
  img.alt = "Movie frame";

  const shade = document.createElement("div");
  shade.className = "shade";

  const label = document.createElement("div");
  label.className = "label";
  label.textContent = "Tap to reveal";

  const reveal = document.createElement("div");
  reveal.className = "revealBox";
  reveal.textContent = "";

  btn.appendChild(img);
  btn.appendChild(shade);
  btn.appendChild(label);
  btn.appendChild(reveal);

  btn.addEventListener("click", () => pick(i, btn, reveal), {once:true});
  return btn;
}

for(let i=0;i<6;i++) cardsEl.appendChild(makeCard(i));

async function award(points){
  const res = await fetch("game_reward.php", {
    method:"POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify({game:"LUCKY_CLICK", points})
  });

  const text = await res.text();
  let data;
  try { data = JSON.parse(text); }
  catch { throw new Error("Server did not return JSON. Check game_reward.php"); }

  if (!data.ok) throw new Error(data.msg || "Failed");
  return data;
}

async function pick(i, btn, revealEl){

  [...cardsEl.querySelectorAll("button")].forEach(x => x.disabled = true);

  const points = hiddenPoints[i];


  btn.classList.add("revealed");
  revealEl.textContent = `+${points}`;

  try{
    const result = await award(points);
    msg.innerHTML = `You got <b>${points}</b> points ✅ (New total: <b>${result.new_points}</b>)`;
  }catch(e){
    msg.innerHTML = `<span style="color:#ffb4b4">❌ ${e.message}</span>`;
    
    [...cardsEl.querySelectorAll("button")].forEach(x => x.disabled = false);
    btn.classList.remove("revealed");
    revealEl.textContent = "";
  }
}
</script>
</body>
</html>



