<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }
$userId = (int)$_SESSION["user_id"];


$stmt = $pdo->prepare("SELECT Reward_points FROM visitor WHERE ID=?");
$stmt->execute([$userId]);
$points = (int)$stmt->fetchColumn();

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Poké Flip Match</title>
  <link rel="stylesheet" href="style.css?v=999">

  <style>
    .wrap{max-width:980px;margin:24px auto;padding:0 16px;}
    .panel{padding:18px;border-radius:18px;background:rgba(255,255,255,.06);backdrop-filter:blur(10px);}
    .topline{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between}
    .badge{padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.10);font-weight:800}
    .sub{opacity:.85;margin-top:6px}

    .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:14px;}
    @media (max-width:640px){ .grid{grid-template-columns:repeat(3,minmax(0,1fr));} }

    .card{
      position:relative; width:100%; aspect-ratio: 1/1.18; perspective: 900px;
      border-radius:16px;
    }
    .inner{
      position:absolute; inset:0;
      transform-style: preserve-3d;
      transition: transform .45s cubic-bezier(.2,.8,.2,1);
      border-radius:16px;
    }
    .card.flipped .inner{ transform: rotateY(180deg); }
    .face{
      position:absolute; inset:0;
      border-radius:16px;
      backface-visibility:hidden;
      display:flex;align-items:center;justify-content:center;
      box-shadow:0 14px 35px rgba(0,0,0,.28);
      overflow:hidden;
      user-select:none;
    }

    
    .back{
      background: radial-gradient(circle at 30% 20%, #ffd54a, #ff77b7 45%, #6aa8ff 100%);
      border: 2px solid rgba(255,255,255,.25);
    }
    .back::before{
      content:"POKÉ";
      font-weight:1000;
      letter-spacing:2px;
      font-size:28px;
      color:rgba(0,0,0,.75);
      text-shadow:0 2px 0 rgba(255,255,255,.35);
      transform: rotate(-8deg);
    }
    .back::after{
      content:"";
      position:absolute; width:90px;height:90px;border-radius:50%;
      border:8px solid rgba(255,255,255,.35);
      filter:blur(.2px);
      opacity:.75;
    }

    
    .front{
      background: rgba(255,255,255,.10);
      transform: rotateY(180deg);
      border: 2px solid rgba(255,255,255,.18);
    }
    .front img{
      width:80%; height:80%; object-fit:contain;
      filter: drop-shadow(0 10px 18px rgba(0,0,0,.45));
      transform: translateY(2px);
    }

    .card.matched{ opacity:.55; pointer-events:none; }
    .stats{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .msg{margin-top:10px;min-height:26px}

    .btnrow{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .btnx{
      padding:12px 14px;border-radius:14px;border:none;cursor:pointer;font-weight:900;
      background:linear-gradient(135deg,#ff77b7,#ff4fa0);color:#111;
      box-shadow:0 12px 30px rgba(0,0,0,.25);
    }
    .ghost{
      padding:12px 14px;border-radius:14px;border:1px solid rgba(255,255,255,.22);
      background:rgba(255,255,255,.06); color:#fff; text-decoration:none; font-weight:800;
    }
  </style>
</head>
<body class="app-body">
  <div class="wrap">
    <div class="panel">
      <div class="topline">
        <div>
          <div class="app-title">Poké Flip  Match 🃏⚡</div>
          <div class="sub">Match all Pokémon pairs to win reward points!</div>
        </div>
        <div class="badge">Your Points: <span id="pointsNow"><?= (int)$points ?></span></div>
      </div>

      <div class="stats">
        <div class="badge">Moves: <span id="moves">0</span></div>
        <div class="badge">Matches: <span id="matches">0</span>/8</div>
      </div>

      <div id="grid" class="grid"></div>

      <div id="msg" class="msg"></div>

      <div class="btnrow">
        <button class="btnx" id="restart">Restart</button>
        <a class="ghost" href="games.php">Back to Games</a>
        <a class="ghost" href="dashboard.php">Dashboard</a>
      </div>
    </div>
  </div>

<script>

const pokemonImages = [
  "assets/pokemon/pikachu.png",
  "assets/pokemon/bulbasaur.png",
  "assets/pokemon/charmander.png",
  "assets/pokemon/squirtle.png",
  "assets/pokemon/eeve.png",
  "assets/pokemon/pokemon-10549.png",
  "assets/pokemon/jigglypuff.png",
  "assets/pokemon/pokemon-10531.png"];

const grid = document.getElementById("grid");
const movesEl = document.getElementById("moves");
const matchesEl = document.getElementById("matches");
const msg = document.getElementById("msg");
const restartBtn = document.getElementById("restart");
const pointsNow = document.getElementById("pointsNow");

let deck = [];
let first = null;
let lock = false;
let moves = 0;
let matches = 0;
let finished = false;

function shuffle(arr){
  for (let i=arr.length-1;i>0;i--){
    const j = Math.floor(Math.random()*(i+1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  return arr;
}

function buildDeck(){
 
  deck = [];
  pokemonImages.forEach((src, idx) => {
    deck.push({id: idx+"a", pair: idx, src});
    deck.push({id: idx+"b", pair: idx, src});
  });
  shuffle(deck);
}

function cardEl(card){
  const c = document.createElement("div");
  c.className = "card";
  c.dataset.pair = card.pair;

  c.innerHTML = `
    <div class="inner">
      <div class="face back"></div>
      <div class="face front"><img src="${card.src}" alt="pokemon"></div>
    </div>
  `;

  c.addEventListener("click", () => onPick(c), {passive:true});
  return c;
}

function render(){
  grid.innerHTML = "";
  deck.forEach(card => grid.appendChild(cardEl(card)));
  moves = 0; matches = 0; finished=false; first=null; lock=false;
  movesEl.textContent = "0";
  matchesEl.textContent = "0";
  msg.textContent = "";
}

function setMsg(t){ msg.textContent = t; }

async function awardPoints(){

  let reward = 25;
  if (moves <= 14) reward = 40;
  else if (moves <= 18) reward = 30;

  const res = await fetch("game_reward.php", {
    method: "POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify({game: "POKE_FLIP", points: reward})
  });
  const data = await res.json();
  if (!data.ok) throw new Error(data.msg || "Reward failed");
  pointsNow.textContent = data.new_points;
  return {reward, newPoints: data.new_points};
}

function onPick(card){
  if (lock || finished) return;
  if (card.classList.contains("flipped") || card.classList.contains("matched")) return;

  card.classList.add("flipped");

  if (!first){
    first = card;
    return;
  }


  moves++;
  movesEl.textContent = String(moves);

  const a = first;
  const b = card;

  lock = true;

  if (a.dataset.pair === b.dataset.pair){

    setTimeout(() => {
      a.classList.add("matched");
      b.classList.add("matched");
      matches++;
      matchesEl.textContent = String(matches);
      first = null;
      lock = false;

      if (matches === 6){
        finished = true;
        setMsg("✅ You matched all Pokémon! Claiming rewards...");

        awardPoints().then(({reward, newPoints})=>{
          setMsg(`🎉 You earned +${reward} points! New total: ${newPoints}`);
        }).catch(e=>{
          setMsg("❌ " + e.message);
        });
      }
    }, 260);
  } else {
   
    setTimeout(() => {
      a.classList.remove("flipped");
      b.classList.remove("flipped");
      first = null;
      lock = false;
    }, 650);
  }
}

restartBtn.addEventListener("click", () => {
  buildDeck();
  render();
});

buildDeck();
render();
</script>
</body>
</html>
