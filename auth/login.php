<?php
session_start();

$messages = [
    'invalid_phone'    => 'Enter a valid mobile number (at least 10 digits).',
    'invalid_password' => 'Invalid mobile number or password.',
    'user_not_found'   => 'No account found for this mobile number.',
    'database_error'   => 'Something went wrong on our side. Please try again.',
    'invalid_request'  => 'Invalid request. Please sign in again.',
];
$error_msg = isset($_GET['error']) ? ($messages[$_GET['error']] ?? 'Login failed. Please try again.') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#08090a">
<title>Sign in - Attendance System</title>
<link rel="icon" type="image/png" href="../assets/images/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{
  --bg:#08090a; --panel:#0f1011; --panel-2:#141516; --line:rgba(255,255,255,.08); --line-2:rgba(255,255,255,.14);
  --text:#f7f8f8; --muted:#8a8f98; --faint:#62666d;
  --accent:#5e6ad2; --accent-2:#8b5cf6; --accent-3:#22d3ee; --ok:#4cb782; --warn:#f2994a; --bad:#eb5757;
}
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%}
body{
  min-height:100vh;background:var(--bg);color:var(--text);
  font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;font-size:14px;
  -webkit-font-smoothing:antialiased;overflow-x:hidden;
}
a{color:inherit;text-decoration:none}

/* ── Background ── */
.bg{position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:0}
.bg .grid{position:absolute;inset:-1px;
  background-image:linear-gradient(var(--line) 1px,transparent 1px),linear-gradient(90deg,var(--line) 1px,transparent 1px);
  background-size:64px 64px;
  mask-image:radial-gradient(ellipse 70% 60% at 50% 30%,#000 30%,transparent 75%);
  -webkit-mask-image:radial-gradient(ellipse 70% 60% at 50% 30%,#000 30%,transparent 75%);
  opacity:.55}
.bg .orb{position:absolute;border-radius:50%;filter:blur(90px);opacity:.55;animation:float 16s ease-in-out infinite}
.bg .o1{width:560px;height:560px;background:radial-gradient(circle,var(--accent) 0,transparent 65%);top:-180px;left:8%}
.bg .o2{width:480px;height:480px;background:radial-gradient(circle,var(--accent-2) 0,transparent 65%);bottom:-200px;right:4%;animation-delay:-6s}
.bg .o3{width:340px;height:340px;background:radial-gradient(circle,var(--accent-3) 0,transparent 65%);top:40%;left:40%;opacity:.18;animation-delay:-11s}
@keyframes float{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(40px,30px) scale(1.08)}}

/* ── Layout ── */
.shell{position:relative;z-index:1;min-height:100vh;display:grid;grid-template-columns:1.15fr 1fr}
.hero{padding:40px 56px;display:flex;flex-direction:column;border-right:1px solid var(--line)}
.side{display:flex;flex-direction:column;padding:40px 56px}

.top{display:flex;align-items:center;justify-content:space-between}
.logo{display:flex;align-items:center;gap:10px;font-weight:600}
.logo img{height:30px;max-width:150px;object-fit:contain;filter:brightness(0) invert(1)}
.status{display:inline-flex;align-items:center;gap:8px;height:28px;padding:0 12px;border:1px solid var(--line-2);border-radius:999px;color:var(--muted);font-size:12px;background:rgba(255,255,255,.02)}
.status .pulse{width:7px;height:7px;border-radius:50%;background:var(--ok);box-shadow:0 0 0 0 rgba(76,183,130,.6);animation:pulse 2s infinite}
@keyframes pulse{70%{box-shadow:0 0 0 8px rgba(76,183,130,0)}100%{box-shadow:0 0 0 0 rgba(76,183,130,0)}}

.hero-body{flex:1;display:flex;flex-direction:column;justify-content:center;max-width:620px;padding:48px 0}
.eyebrow{display:inline-flex;align-items:center;gap:8px;align-self:flex-start;height:28px;padding:0 12px 0 4px;border:1px solid var(--line-2);border-radius:999px;font-size:12px;color:var(--muted);background:rgba(255,255,255,.03);margin-bottom:24px}
.eyebrow b{height:20px;padding:0 8px;border-radius:999px;background:linear-gradient(90deg,var(--accent),var(--accent-2));color:#fff;font-weight:500;display:inline-flex;align-items:center;font-size:11px}
.hero h1{font-size:clamp(34px,4.2vw,56px);line-height:1.05;letter-spacing:-.035em;font-weight:600;margin-bottom:18px}
.grad{background:linear-gradient(92deg,#fff 10%,#b4bcff 45%,#c4b5fd 70%,#67e8f9 100%);-webkit-background-clip:text;background-clip:text;color:transparent}
.lead{font-size:16px;line-height:1.6;color:var(--muted);max-width:480px}

/* Product preview */
.preview{margin-top:40px;position:relative;border:1px solid var(--line-2);border-radius:14px;background:linear-gradient(180deg,rgba(255,255,255,.05),rgba(255,255,255,.015));box-shadow:0 30px 80px rgba(0,0,0,.55),inset 0 1px 0 rgba(255,255,255,.06);overflow:hidden;transform:perspective(1400px) rotateX(8deg);transform-origin:top center}
.preview::before{content:"";position:absolute;inset:0;background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.06) 50%,transparent 70%);transform:translateX(-100%);animation:shine 6s ease-in-out infinite}
@keyframes shine{60%,100%{transform:translateX(100%)}}
.pv-bar{display:flex;align-items:center;gap:6px;height:34px;padding:0 12px;border-bottom:1px solid var(--line)}
.pv-bar i{width:9px;height:9px;border-radius:50%;background:#2a2b2e}
.pv-bar span{margin-left:10px;color:var(--faint);font-size:11px}
.pv-body{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--line)}
.pv-kpi{background:var(--panel);padding:14px 16px}
.pv-kpi small{display:flex;align-items:center;gap:6px;color:var(--muted);font-size:11px}
.pv-kpi small i{width:6px;height:6px;border-radius:50%}
.pv-kpi strong{display:block;font-size:22px;font-weight:600;letter-spacing:-.02em;margin-top:6px;font-variant-numeric:tabular-nums}
.pv-kpi em{font-style:normal;font-size:11px;color:var(--ok)}
.pv-chart{grid-column:1/-1;background:var(--panel);padding:14px 16px 12px;display:flex;align-items:flex-end;gap:8px;height:110px}
.pv-chart span{flex:1;border-radius:3px 3px 1px 1px;background:linear-gradient(180deg,rgba(94,106,210,.9),rgba(94,106,210,.25));transform-origin:bottom;animation:grow 1.2s cubic-bezier(.2,.8,.2,1) both}
.pv-chart span:last-child{background:linear-gradient(180deg,#a78bfa,#5e6ad2)}
@keyframes grow{from{transform:scaleY(0)}}

.hero-foot{display:flex;gap:28px;color:var(--faint);font-size:12px}
.hero-foot div{display:flex;align-items:center;gap:8px}
.hero-foot i{color:var(--muted)}

/* ── Form ── */
.form-area{flex:1;display:flex;align-items:center;justify-content:center}
.card{width:100%;max-width:380px}
.card h2{font-size:24px;font-weight:600;letter-spacing:-.02em;margin-bottom:6px}
.card .sub{color:var(--muted);margin-bottom:28px}

.alert{display:flex;gap:10px;align-items:flex-start;padding:11px 12px;border:1px solid rgba(235,87,87,.35);background:rgba(235,87,87,.08);color:#ffb4b4;border-radius:10px;font-size:13px;margin-bottom:20px;animation:shake .4s}
.alert i{margin-top:2px;color:var(--bad)}
@keyframes shake{25%{transform:translateX(-4px)}75%{transform:translateX(4px)}}

.field{margin-bottom:16px}
.label-row{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px}
label{font-size:13px;font-weight:500;color:#d0d6e0}
.label-row a{font-size:12px;color:var(--muted);transition:color .15s}
.label-row a:hover{color:#b4bcff}
.input{position:relative;display:flex;align-items:center;gap:10px;height:44px;padding:0 14px;border:1px solid var(--line-2);border-radius:10px;background:rgba(255,255,255,.03);transition:border-color .15s,background .15s,box-shadow .15s}
.input:hover{border-color:rgba(255,255,255,.22)}
.input:focus-within{border-color:var(--accent);background:rgba(94,106,210,.06);box-shadow:0 0 0 4px rgba(94,106,210,.18)}
.input > i{color:var(--faint);font-size:13px;width:14px;text-align:center;transition:color .15s}
.input:focus-within > i{color:#b4bcff}
.input input{flex:1;min-width:0;border:0;outline:0;background:none;font:inherit;font-size:14px;color:var(--text)}
.input input::placeholder{color:var(--faint)}
.input input:-webkit-autofill{-webkit-text-fill-color:var(--text);-webkit-box-shadow:0 0 0 40px #111218 inset;caret-color:var(--text)}
.eye{border:0;background:none;color:var(--faint);cursor:pointer;width:28px;height:28px;border-radius:6px;display:grid;place-items:center}
.eye:hover{background:rgba(255,255,255,.06);color:var(--text)}

.btn{position:relative;width:100%;height:44px;margin-top:10px;border:0;border-radius:10px;cursor:pointer;font:inherit;font-weight:500;color:#fff;
  background:linear-gradient(180deg,#6e79e0,#5e6ad2);box-shadow:0 0 0 1px rgba(255,255,255,.12) inset,0 8px 24px rgba(94,106,210,.35);
  display:flex;align-items:center;justify-content:center;gap:8px;overflow:hidden;transition:transform .15s,box-shadow .15s,filter .15s}
.btn:hover{filter:brightness(1.08);box-shadow:0 0 0 1px rgba(255,255,255,.18) inset,0 12px 32px rgba(94,106,210,.5)}
.btn:active{transform:translateY(1px)}
.btn .arrow{transition:transform .15s}
.btn:hover .arrow{transform:translateX(3px)}
.btn .spin{display:none;width:15px;height:15px;border:2px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
.btn.loading .spin{display:block}
.btn.loading .arrow{display:none}
.btn:disabled{cursor:default}
@keyframes spin{to{transform:rotate(360deg)}}

.divider{display:flex;align-items:center;gap:12px;color:var(--faint);font-size:12px;margin:26px 0 18px}
.divider::before,.divider::after{content:"";flex:1;height:1px;background:var(--line)}
.roles{display:flex;flex-wrap:wrap;gap:8px;justify-content:center}
.roles span{display:inline-flex;align-items:center;gap:6px;height:26px;padding:0 10px;border:1px solid var(--line);border-radius:999px;color:var(--muted);font-size:12px;background:rgba(255,255,255,.02)}
.roles i{font-size:11px;color:var(--faint)}

.foot{display:flex;justify-content:space-between;color:var(--faint);font-size:12px}
.foot b{color:var(--muted);font-weight:500}
kbd{font-family:inherit;font-size:11px;padding:1px 5px;border:1px solid var(--line-2);border-bottom-width:2px;border-radius:4px;color:var(--muted)}

/* Entrance */
.rise{opacity:0;transform:translateY(12px);animation:rise .7s cubic-bezier(.2,.8,.2,1) forwards}
.d1{animation-delay:.05s}.d2{animation-delay:.15s}.d3{animation-delay:.25s}.d4{animation-delay:.35s}
@keyframes rise{to{opacity:1;transform:none}}

@media (max-width:1024px){
  .shell{grid-template-columns:1fr}
  .hero{display:none}
  .side{padding:28px 20px}
  .side .top-mobile{display:flex}
}
.top-mobile{display:none;align-items:center;justify-content:space-between}
@media (prefers-reduced-motion:reduce){*{animation:none !important}.rise{opacity:1;transform:none}}
</style>
</head>
<body>

<div class="bg"><div class="grid"></div><div class="orb o1"></div><div class="orb o2"></div><div class="orb o3"></div></div>

<div class="shell">
  <!-- Left: brand / product -->
  <section class="hero">
    <div class="top rise d1">
      <div class="logo"><img src="../assets/images/logo.png" alt="Logo" onerror="this.outerHTML='HRMS'"></div>
      <div class="status"><span class="pulse"></span>All systems operational</div>
    </div>

    <div class="hero-body">
      <div class="eyebrow rise d1"><b>New</b> Attendance, leave &amp; payroll in one place</div>
      <h1 class="rise d2">Run your workforce<br><span class="grad">with clarity.</span></h1>
      <p class="lead rise d3">GPS &amp; selfie punches, shifts, leave approvals and salary slips — tracked in real time, built for teams in the field.</p>

      <div class="preview rise d4" aria-hidden="true">
        <div class="pv-bar"><i></i><i></i><i></i><span>Dashboard · Today</span></div>
        <div class="pv-body">
          <div class="pv-kpi"><small><i style="background:#5e6ad2"></i>Employees</small><strong data-count="248">0</strong><em>Active</em></div>
          <div class="pv-kpi"><small><i style="background:#4cb782"></i>Present</small><strong data-count="231">0</strong><em>↑ 93%</em></div>
          <div class="pv-kpi"><small><i style="background:#f2994a"></i>On leave</small><strong data-count="9">0</strong><em style="color:#8a8f98">3 pending</em></div>
          <div class="pv-chart">
            <span style="height:62%;animation-delay:.5s"></span><span style="height:78%;animation-delay:.56s"></span>
            <span style="height:70%;animation-delay:.62s"></span><span style="height:88%;animation-delay:.68s"></span>
            <span style="height:74%;animation-delay:.74s"></span><span style="height:40%;animation-delay:.8s"></span>
            <span style="height:30%;animation-delay:.86s"></span><span style="height:82%;animation-delay:.92s"></span>
            <span style="height:90%;animation-delay:.98s"></span><span style="height:76%;animation-delay:1.04s"></span>
            <span style="height:86%;animation-delay:1.1s"></span><span style="height:94%;animation-delay:1.16s"></span>
          </div>
        </div>
      </div>
    </div>

    <div class="hero-foot rise d4">
      <div><i class="fa-solid fa-location-crosshairs"></i>Geo-fenced punches</div>
      <div><i class="fa-solid fa-shield-halved"></i>Role-based access</div>
      <div><i class="fa-solid fa-file-invoice"></i>Payroll ready</div>
    </div>
  </section>

  <!-- Right: form -->
  <section class="side">
    <div class="top-mobile rise d1">
      <div class="logo"><img src="../assets/images/logo.png" alt="Logo" onerror="this.outerHTML='HRMS'"></div>
      <div class="status"><span class="pulse"></span>Online</div>
    </div>

    <div class="form-area">
      <div class="card">
        <h2 class="rise d1">Welcome back</h2>
        <p class="sub rise d2">Sign in with your mobile number to continue.</p>

        <?php if ($error_msg): ?>
        <div class="alert" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($error_msg) ?></span></div>
        <?php endif; ?>

        <form method="POST" action="login_process.php" id="loginForm" class="rise d3">
          <div class="field">
            <div class="label-row"><label for="phone">Mobile number</label></div>
            <div class="input">
              <i class="fa-solid fa-mobile-screen"></i>
              <input type="tel" id="phone" name="phone" placeholder="10-digit mobile number"
                     required inputmode="numeric" pattern="[0-9]{10,}" autocomplete="username" autofocus
                     title="Phone number must contain only digits (minimum 10 digits)"
                     oninput="this.value=this.value.replace(/[^0-9]/g,'');">
            </div>
          </div>

          <div class="field">
            <div class="label-row">
              <label for="password">Password</label>
              <a href="../admin/reset_password.php">Forgot password?</a>
            </div>
            <div class="input">
              <i class="fa-solid fa-lock"></i>
              <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
              <button type="button" class="eye" id="togglePassword" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
            </div>
          </div>

          <button type="submit" class="btn" id="loginBtn">
            <span class="spin"></span><span>Sign in</span><i class="fa-solid fa-arrow-right arrow"></i>
          </button>
        </form>

        <!-- <div class="divider rise d4">One login for every role</div>
          <div class="roles rise d4">
            <span><i class="fa-solid fa-user-shield"></i>Admin</span>
            <span><i class="fa-solid fa-user-tie"></i>Supervisor</span>
            <span><i class="fa-solid fa-user"></i>Employee</span>
          </div> -->
      </div>
    </div>

    <div class="foot rise d4">
      <span>Made by <b>BlueCoree</b></span>
      <span>Press <kbd>Enter</kbd> to sign in</span>
    </div>
  </section>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
  const p = document.getElementById('password');
  const show = p.type === 'password';
  p.type = show ? 'text' : 'password';
  this.innerHTML = show ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
  this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});
document.getElementById('loginForm').addEventListener('submit', function () {
  const b = document.getElementById('loginBtn');
  b.classList.add('loading'); b.disabled = true;
});

// Count-up numbers in the preview card
document.querySelectorAll('[data-count]').forEach(el => {
  const end = +el.dataset.count, t0 = performance.now() + 500, dur = 1200;
  (function tick(now) {
    const k = Math.min(1, Math.max(0, (now - t0) / dur));
    el.textContent = Math.round(end * (1 - Math.pow(1 - k, 3)));
    if (k < 1) requestAnimationFrame(tick);
  })(performance.now());
});
</script>
</body>
</html>
