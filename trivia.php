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
  <title>Trivia Quiz</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="style.css?v=999">
  <style>
    .wrap{max-width:980px;margin:24px auto;padding:0 16px;}
    .panel{padding:18px;border-radius:18px;background:rgba(255,255,255,.06);backdrop-filter:blur(10px);}
    .q{font-size:20px;font-weight:900;margin-top:8px}
    .opts{display:grid;gap:10px;margin-top:12px}
    .opt{border:none;border-radius:14px;padding:14px;cursor:pointer;font-weight:800;
      background:rgba(255,255,255,.08); color:#fff; text-align:left;
      transition:.15s transform, .15s background;
    }
    .opt:hover{transform:translateY(-2px);background:rgba(255,255,255,.12)}
    .ok{background:rgba(52,211,153,.18)!important}
    .bad{background:rgba(251,113,133,.18)!important}
    .bar{height:10px;border-radius:999px;background:rgba(255,255,255,.10);overflow:hidden;margin-top:12px}
    .bar > div{height:100%;width:0%;background:linear-gradient(90deg,#ff77b7,#ff4fa0);transition:width .35s ease}
    .msg{margin-top:12px;min-height:26px}
  </style>
</head>
<body class="app-body">
  <div class="wrap">
    <div class="panel">
      <div class="app-title">Trivia Quiz 🧠</div>
      <div class="app-sub">Answer 5 questions. Each correct = +5 points.</div>

      <div class="bar"><div id="bar"></div></div>
      <div class="q" id="q"></div>
      <div class="opts" id="opts"></div>
      <div class="msg" id="msg"></div>

      <p>
        <button class="btn btn-ghost" id="restart">New Quiz</button>
        <a class="btn btn-ghost" href="games.php">Back</a>
      </p>
    </div>
  </div>

<script>
const quiz = [
  {q:"Which one is NOT a movie genre?", a:["Comedy","Horror","Romance","Keyboard"], c:3},
  {q:"What does 'Sequel' mean?", a:["First film","Second film","Trailer","Ticket"], c:1},
  {q:"Cinema snack classic?", a:["Popcorn","Rice","Pasta","Soup"], c:0},
  {q:"What is a 'Premiere'?", a:["First showing","Last showing","A seat","A subtitle"], c:0},
  {q:"Gift cards usually store…", a:["Remaining value","Movie duration","Friend list","Poll votes"], c:0},
];

const qEl=document.getElementById("q");
const oEl=document.getElementById("opts");
const mEl=document.getElementById("msg");
const bar=document.getElementById("bar");
const restart=document.getElementById("restart");

let i=0, score=0, locked=false;

function render(){
  locked=false;
  mEl.textContent="";
  const item=quiz[i];
  qEl.textContent = `Q${i+1}: ${item.q}`;
  oEl.innerHTML="";
  bar.style.width = `${(i/quiz.length)*100}%`;

  item.a.forEach((txt, idx)=>{
    const b=document.createElement("button");
    b.className="opt";
    b.textContent = txt;
    b.onclick = ()=>answer(idx,b);
    oEl.appendChild(b);
  });
}

async function award(points){
  const res = await fetch("game_reward.php", {
    method:"POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify({game:"TRIVIA", points})
  });
  const data = await res.json();
  if (!data.ok) throw new Error(data.msg || "Failed");
  return data;
}

async function answer(idx, btn){
  if (locked) return;
  locked=true;

  const item=quiz[i];
  const buttons=[...oEl.querySelectorAll("button")];
  buttons.forEach(b=>b.disabled=true);

  if (idx===item.c){
    btn.classList.add("ok");
    score++;
    try{
      const points = 5;
      const result = await award(points);
      mEl.innerHTML = `Correct! +${points} points (Total points: <b>${result.new_points}</b>)`;
    }catch(e){
      mEl.innerHTML = `❌ ${e.message}`;
    }
  }else{
    btn.classList.add("bad");
    buttons[item.c].classList.add("ok");
    mEl.textContent = "❌ Wrong!";
  }

  setTimeout(()=>{
    i++;
    if (i>=quiz.length){
      bar.style.width="100%";
      qEl.textContent = `Finished! Score: ${score}/${quiz.length}`;
      oEl.innerHTML="";
    }else{
      render();
    }
  }, 850);
}

restart.onclick=()=>{ i=0; score=0; render(); };
render();
</script>
</body>
</html>

