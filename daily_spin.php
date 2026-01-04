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
  <title>Daily Spin</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="style.css?v=999">
  <style>
    .wrap{max-width:980px;margin:24px auto;padding:0 16px;}
    .panel{padding:18px;border-radius:18px;background:rgba(255,255,255,.06);backdrop-filter:blur(10px);}
    .row{display:flex;gap:18px;flex-wrap:wrap;align-items:center;justify-content:center;margin-top:16px;}

    .wheelWrap{position:relative;width:280px;height:280px;}
    .wheel{
      position:relative;
      width:280px;height:280px;border-radius:50%;
      background:conic-gradient(
        #ff77b7 0 45deg,
        #7dd3fc 45deg 90deg,
        #facc15 90deg 135deg,
        #a7f3d0 135deg 180deg,
        #c4b5fd 180deg 225deg,
        #fb7185 225deg 270deg,
        #fda4af 270deg 315deg,
        #34d399 315deg 360deg
      );
      box-shadow:0 18px 50px rgba(0,0,0,.35);
      transition:transform 3.2s cubic-bezier(.14,.86,.21,1);
      overflow:hidden;
    }

    /* labels on top of wheel */
    .wheel .lbl{
      position:absolute;
      left:50%;
      top:50%;
      font-weight:900;
      font-size:18px;
      color:#fff;
      text-shadow:0 6px 16px rgba(0,0,0,.8);
      user-select:none;
      pointer-events:none;
      transform-origin:0 0;
    }

    /* small center cap */
    .wheel::after{
      content:"";
      position:absolute;
      left:50%;
      top:50%;
      width:54px;
      height:54px;
      border-radius:999px;
      transform:translate(-50%,-50%);
      background:rgba(0,0,0,.35);
      border:1px solid rgba(255,255,255,.14);
      box-shadow:0 10px 25px rgba(0,0,0,.35);
    }

    .pointer{
      position:absolute;top:-10px;left:50%;transform:translateX(-50%);
      width:0;height:0;border-left:14px solid transparent;border-right:14px solid transparent;border-bottom:26px solid #fff;
      filter:drop-shadow(0 6px 10px rgba(0,0,0,.45));
      z-index:5;
    }

    .btnBig{
      padding:14px 18px;border-radius:14px;border:none;cursor:pointer;font-weight:900;
      background:linear-gradient(135deg,#ff77b7,#ff4fa0);color:#111;box-shadow:0 12px 30px rgba(0,0,0,.25);
    }
    .btnBig:disabled{opacity:.6;cursor:not-allowed;}
    .msg{margin-top:10px;min-height:24px}

    .spark{position:fixed;inset:0;pointer-events:none;overflow:hidden}
    .conf{position:absolute;top:-10px;font-size:20px;animation:fall 1.6s linear forwards}
    @keyframes fall{to{transform:translateY(110vh) rotate(360deg);opacity:0}}
  </style>
</head>

<body class="app-body">
  <div class="wrap">
    <div class="panel">
      <div class="app-title">Daily Spin 🎡</div>
      <div class="app-sub">Spin once per day and win points!</div>

      <div class="row">
        <div class="wheelWrap">
          <div class="pointer"></div>
          <div id="wheel" class="wheel"></div>
        </div>

        <div style="max-width:420px">
          <p><b>Possible rewards:</b> 0, 5, 10, 15, 20, 25, 30, 40 points</p>
          <button id="spinBtn" class="btnBig">SPIN</button>
          <div id="msg" class="msg"></div>
          <p><a class="btn btn-ghost" href="games.php">Back to Games</a></p>
        </div>
      </div>
    </div>
  </div>

  <div id="spark" class="spark"></div>

<script>
const wheel = document.getElementById("wheel");
const msg = document.getElementById("msg");
const spinBtn = document.getElementById("spinBtn");


const rewards = [0,5,10,15,20,25,30,40];

let spinning = false;
let rotation = 0;


function buildLabels(){
  wheel.innerHTML = ""; 
  const n = rewards.length;
  const sliceDeg = 360 / n;
  const radius = 96; 
  for (let i=0;i<n;i++){
    const lbl = document.createElement("div");
    lbl.className = "lbl";
    lbl.textContent = rewards[i];

    const angle = i * sliceDeg + sliceDeg/2;
    lbl.style.transform = `rotate(${angle}deg) translate(${radius}px, -10px) rotate(${-angle}deg)`;

    wheel.appendChild(lbl);
  }
}
buildLabels();

function confetti() {
  const box = document.getElementById("spark");
  const emojis = ["✨","🎉","💖","⭐","🍬","🎟️"];
  for (let i=0;i<24;i++){
    const s = document.createElement("div");
    s.className="conf";
    s.textContent = emojis[Math.floor(Math.random()*emojis.length)];
    s.style.left = Math.random()*100+"vw";
    s.style.animationDuration = (1.2+Math.random()*1.2)+"s";
    box.appendChild(s);
    setTimeout(()=>s.remove(), 2000);
  }
}

async function award(points){
  const res = await fetch("game_reward.php", {
    method:"POST",
    headers: {"Content-Type":"application/json"},
    
    body: JSON.stringify({game:"DAILY_SPIN", points})
  });
  const data = await res.json();
  if (!data.ok) throw new Error(data.msg || "Failed");
  return data;
}

spinBtn.addEventListener("click", async () => {
  if (spinning) return;
  spinning = true;
  spinBtn.disabled = true;
  msg.textContent = "";

  
  const idx = Math.floor(Math.random()*rewards.length);
  const points = rewards[idx];

  
  const sliceDeg = 360 / rewards.length;
  const targetDeg = 360 - (idx * sliceDeg); 
  const extraSpins = 5 * 360; 
  rotation = rotation + extraSpins + targetDeg;

  wheel.style.transform = `rotate(${rotation}deg)`;

  setTimeout(async () => {
    try{
      const result = await award(points);
      if (points > 0) confetti();
      msg.innerHTML = `You won <b>${points}</b> points! ✅<br>New total: <b>${result.new_points}</b>`;
    }catch(e){
      msg.innerHTML = `<span style="color:#ffb4b4">❌ ${e.message}</span>`;
    }finally{
      spinning = false;
      spinBtn.disabled = false;
    }
  }, 3200);
});
</script>
</body>
</html>


