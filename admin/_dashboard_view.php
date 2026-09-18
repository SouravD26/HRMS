<?php
// Shared dashboard view for dashboard.php (super admin) and admin_dashboard.php (admin)
// Requires: $conn, $admin_name. Sections are hidden when the admin lacks the matching right ($_can from _navbar.php).

// Ensure leave_applications table exists (for stats below)
$conn->query("CREATE TABLE IF NOT EXISTS leave_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    leave_type VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL, end_date DATE NOT NULL,
    days_count DECIMAL(5,1) NOT NULL DEFAULT 1,
    reason TEXT, status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    admin_notes TEXT, reviewed_by INT, reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

function dm_count($conn, $sql) {
    $r = $conn->query($sql);
    return $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;
}

// KPIs
$stat_total_emp      = dm_count($conn, "SELECT COUNT(*) c FROM users WHERE role='employee' AND status='Working'");
$stat_present_today  = dm_count($conn, "SELECT COUNT(DISTINCT a.user_id) c FROM attendance a JOIN users u ON u.id=a.user_id WHERE a.date=CURDATE() AND a.status IN ('Present','Late') AND u.role='employee'");
$stat_late_today     = dm_count($conn, "SELECT COUNT(DISTINCT a.user_id) c FROM attendance a JOIN users u ON u.id=a.user_id WHERE a.date=CURDATE() AND a.status='Late' AND u.role='employee'");
$stat_on_leave_today = dm_count($conn, "SELECT COUNT(DISTINCT user_id) c FROM leave_applications WHERE status='Approved' AND CURDATE() BETWEEN start_date AND end_date");
$stat_pending_leaves = dm_count($conn, "SELECT COUNT(*) c FROM leave_applications WHERE status='Pending'");
$stat_not_marked     = max(0, $stat_total_emp - $stat_present_today - $stat_on_leave_today);
$attendance_rate     = $stat_total_emp > 0 ? round($stat_present_today / $stat_total_emp * 100) : 0;

// Last 7 days attendance trend
$trend = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trend[$d] = 0;
}
$res = $conn->query("SELECT a.date d, COUNT(DISTINCT a.user_id) c FROM attendance a JOIN users u ON u.id=a.user_id
    WHERE a.date >= CURDATE() - INTERVAL 6 DAY AND a.status IN ('Present','Late') AND u.role='employee' GROUP BY a.date");
if ($res) while ($row = $res->fetch_assoc()) { if (isset($trend[$row['d']])) $trend[$row['d']] = (int)$row['c']; }
$trend_max = max(1, $stat_total_emp, max($trend));

// Recent punches today
$recent = [];
$res = $conn->query("SELECT u.name, u.employee_id, a.punch_in, a.punch_out, a.status FROM attendance a
    JOIN users u ON u.id=a.user_id WHERE a.date=CURDATE() AND u.role='employee'
    ORDER BY COALESCE(a.punch_out, a.punch_in) DESC LIMIT 8");
if ($res) while ($row = $res->fetch_assoc()) $recent[] = $row;

// Pending leave requests
$pending = [];
$res = $conn->query("SELECT u.name, l.leave_type, l.start_date, l.end_date, l.days_count FROM leave_applications l
    JOIN users u ON u.id=l.user_id WHERE l.status='Pending' ORDER BY l.created_at DESC LIMIT 6");
if ($res) while ($row = $res->fetch_assoc()) $pending[] = $row;

$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$status_class = ['Present' => 'ok', 'Late' => 'warn', 'Leave' => 'info', 'Absent' => 'bad'];
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$fmt_time = fn($t) => $t ? date('g:i A', strtotime($t)) : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= ($_SESSION['role'] ?? '') === 'suparadmin' ? 'Super Admin' : 'Admin' ?> Dashboard</title>
<link rel="icon" type="image/png" href="../assets/images/favicon.png">
<style>
:root{
  --bg:var(--la-bg); --panel:var(--la-panel); --border:var(--la-border); --border-soft:var(--la-border-soft);
  --text:var(--la-text); --muted:var(--la-muted); --faint:var(--la-faint); --hover:var(--la-hover); --accent:var(--la-accent); --accent-soft:var(--la-accent-soft);
  --ok:var(--la-ok); --warn:var(--la-warn); --bad:var(--la-bad); --info:var(--la-info);
}
body{font-size:13px}
a{color:inherit;text-decoration:none}
.dot{width:7px;height:7px;border-radius:50%;display:inline-block}
.avatar{width:26px;height:26px;border-radius:50%;display:grid;place-items:center;font-size:11px;font-weight:600;flex-shrink:0}
.content{padding:28px 32px 48px;max-width:1280px;width:100%}
.page-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:22px}
.page-head h1{font-size:20px;font-weight:600;margin:0 0 4px;letter-spacing:-.01em}
.page-head p{margin:0;color:var(--muted)}
.btn{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 12px;border-radius:6px;border:1px solid var(--border);background:var(--panel);font:inherit;font-weight:500;color:var(--text);cursor:pointer;white-space:nowrap}
.btn:hover{background:var(--hover)}
.btn.primary{background:var(--accent);border-color:var(--accent);color:#fff}
.btn.primary:hover{background:#4f5bc4}
.actions{display:flex;gap:8px;flex-wrap:wrap}

/* KPI strip */
.kpis{display:flex;flex-wrap:wrap;overflow:hidden;background:var(--panel);border:1px solid var(--border);border-radius:8px;margin-bottom:16px}
.kpi{flex:1 1 170px;padding:14px 16px;display:block;box-shadow:1px 0 0 var(--border-soft),0 1px 0 var(--border-soft);margin:0 1px 1px 0}
.kpi:hover{background:#fcfcfd}
.kpi-label{color:var(--muted);display:flex;align-items:center;gap:6px}
.kpi-value{font-size:22px;font-weight:600;margin-top:6px;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
.kpi-sub{color:var(--faint);font-size:12px;margin-top:2px}

/* Panels */
.grid{display:grid;grid-template-columns:1.6fr 1fr;gap:16px;margin-bottom:16px}
.panel{background:var(--panel);border:1px solid var(--border);border-radius:8px;min-width:0}
.panel-head{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border-soft)}
.panel-head h2{font-size:13px;font-weight:600;margin:0}
.panel-head a{color:var(--muted);font-size:12px}
.panel-head a:hover{color:var(--accent)}
.panel-body{padding:16px}

/* Chart */
.chart{display:flex;align-items:flex-end;gap:14px;height:160px;padding-top:8px}
.bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;height:100%}
.bar-track{flex:1;width:100%;max-width:44px;display:flex;align-items:flex-end;border-radius:4px;background:repeating-linear-gradient(to top,transparent 0 39px,var(--border-soft) 39px 40px)}
.bar{width:100%;background:#c9cdf2;border-radius:4px 4px 2px 2px;min-height:2px;position:relative}
.bar-col.today .bar{background:var(--accent)}
.bar-val{position:absolute;top:-18px;left:50%;transform:translateX(-50%);font-size:11px;color:var(--muted);font-variant-numeric:tabular-nums}
.bar-day{font-size:11px;color:var(--faint)}
.bar-col.today .bar-day{color:var(--text);font-weight:500}

/* Breakdown */
.stack{display:flex;height:8px;border-radius:4px;overflow:hidden;background:var(--border-soft);margin:6px 0 16px}
.stack span{display:block;height:100%}
.legend-row{display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border-soft)}
.legend-row:last-child{border-bottom:0}
.legend-row .l{display:flex;align-items:center;gap:8px;color:#3c3f45}
.legend-row .v{font-variant-numeric:tabular-nums;font-weight:500}
.big-rate{font-size:30px;font-weight:600;letter-spacing:-.02em}

/* Lists */
.list-row{display:flex;align-items:center;gap:12px;padding:9px 16px;border-bottom:1px solid var(--border-soft)}
.list-row:last-child{border-bottom:0}
.list-row:hover{background:#fcfcfd}
.list-row .avatar{background:#e7e8ee;color:#555a66}
.list-main{flex:1;min-width:0}
.list-main .t{font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.list-main .s{color:var(--faint);font-size:12px}
.mono{font-variant-numeric:tabular-nums;color:var(--muted);white-space:nowrap}
.pill{display:inline-flex;align-items:center;gap:5px;height:20px;padding:0 8px;border:1px solid var(--border);border-radius:10px;font-size:11px;font-weight:500;color:#3c3f45;white-space:nowrap}
.pill .dot{width:6px;height:6px}
.empty{padding:28px 16px;text-align:center;color:var(--faint)}

/* Module list */
.modules{display:grid;grid-template-columns:repeat(4,1fr)}
.module{display:flex;align-items:center;gap:10px;padding:11px 16px;border-right:1px solid var(--border-soft);border-bottom:1px solid var(--border-soft)}
.module:hover{background:var(--hover)}
.module i{width:28px;height:28px;border-radius:6px;background:var(--accent-soft);color:var(--accent);display:grid;place-items:center;font-size:12px;flex-shrink:0}
.module span{font-weight:500}
.module .arrow{margin-left:auto;color:var(--faint);font-size:11px;background:none;width:auto;height:auto}


@media (max-width:1100px){
  .grid{grid-template-columns:1fr}
  .modules{grid-template-columns:repeat(2,1fr)}
}
@media (max-width:820px){
  .content{padding:20px 16px 40px}
  .page-head{flex-direction:column;align-items:flex-start}
  .kpi{flex-basis:140px}
  .modules{grid-template-columns:1fr}
  .chart{gap:6px}
}
</style>
</head>
<body>
<?php include('_navbar.php'); ?>

    <div class="content">
      <div class="page-head">
        <div>
          <h1><?= $h($greeting) ?>, <?= $h(explode(' ', trim($admin_name))[0]) ?></h1>
          <p><?= date('l, j F Y') ?> · Overview of attendance and requests</p>
        </div>
        <div class="actions">
          <?php if ($_can('export_reports')): ?>
<a class="btn" href="export_monthly.php?from=<?= $_from ?>"><i class="fa-solid fa-file-arrow-down"></i>Export</a>
<?php endif; ?>
          <?php if ($_can('manual_attendance')): ?>
<a class="btn" href="manual_attendance.php"><i class="fa-solid fa-keyboard"></i>Manual entry</a>
<?php endif; ?>
          <?php if ($_can('manage_employees')): ?>
<a class="btn primary" href="employees.php?from=<?= $_from ?>"><i class="fa-solid fa-plus"></i>Employee</a>
<?php endif; ?>
        </div>
      </div>

      <?php if ($_can('manage_employees') || $_can('view_attendance') || $_can('leave_management')): ?>
      <section class="kpis">
        <?php if ($_can('manage_employees')): ?>
<a class="kpi" href="employees.php?from=<?= $_from ?>">
          <div class="kpi-label"><span class="dot" style="background:var(--accent)"></span>Active employees</div>
          <div class="kpi-value"><?= $stat_total_emp ?></div>
          <div class="kpi-sub">Status: working</div>
        </a>
<?php endif; ?>
        <?php if ($_can('view_attendance')): ?>
<a class="kpi" href="attendance.php?from=<?= $_from ?>">
          <div class="kpi-label"><span class="dot" style="background:var(--ok)"></span>Present today</div>
          <div class="kpi-value"><?= $stat_present_today ?></div>
          <div class="kpi-sub"><?= $attendance_rate ?>% attendance</div>
        </a>
        <a class="kpi" href="attendance.php?from=<?= $_from ?>">
          <div class="kpi-label"><span class="dot" style="background:var(--warn)"></span>Late today</div>
          <div class="kpi-value"><?= $stat_late_today ?></div>
          <div class="kpi-sub">Included in present</div>
        </a>
        <?php endif; ?>
        <?php if ($_can('leave_management')): ?>
        <a class="kpi" href="leave_management.php">
          <div class="kpi-label"><span class="dot" style="background:var(--info)"></span>On leave</div>
          <div class="kpi-value"><?= $stat_on_leave_today ?></div>
          <div class="kpi-sub">Approved for today</div>
        </a>
        <a class="kpi" href="leave_management.php">
          <div class="kpi-label"><span class="dot" style="background:var(--bad)"></span>Pending requests</div>
          <div class="kpi-value"><?= $stat_pending_leaves ?></div>
          <div class="kpi-sub">Awaiting review</div>
        </a>
      <?php endif; ?>
      </section>
      <?php endif; ?>

      <?php if ($_can('view_attendance')): ?>
      <section class="grid">
        <div class="panel">
          <div class="panel-head"><h2>Attendance · last 7 days</h2><a href="attendance.php?from=<?= $_from ?>">View records →</a></div>
          <div class="panel-body">
            <div class="chart">
              <?php foreach ($trend as $d => $c): $pct = round($c / $trend_max * 100); ?>
              <div class="bar-col <?= $d === date('Y-m-d') ? 'today' : '' ?>" title="<?= date('D, j M', strtotime($d)) ?>: <?= $c ?> present">
                <div class="bar-track"><div class="bar" style="height:<?= $pct ?>%"><span class="bar-val"><?= $c ?></span></div></div>
                <div class="bar-day"><?= date('D', strtotime($d)) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head"><h2>Today's breakdown</h2></div>
          <div class="panel-body">
            <div class="big-rate"><?= $attendance_rate ?>%</div>
            <div style="color:var(--muted)">of active employees checked in</div>
            <?php $t = max(1, $stat_total_emp); ?>
            <div class="stack">
              <span style="width:<?= ($stat_present_today - $stat_late_today) / $t * 100 ?>%;background:var(--ok)"></span>
              <span style="width:<?= $stat_late_today / $t * 100 ?>%;background:var(--warn)"></span>
              <span style="width:<?= $stat_on_leave_today / $t * 100 ?>%;background:var(--info)"></span>
            </div>
            <div class="legend-row"><span class="l"><span class="dot" style="background:var(--ok)"></span>On time</span><span class="v"><?= $stat_present_today - $stat_late_today ?></span></div>
            <div class="legend-row"><span class="l"><span class="dot" style="background:var(--warn)"></span>Late</span><span class="v"><?= $stat_late_today ?></span></div>
            <div class="legend-row"><span class="l"><span class="dot" style="background:var(--info)"></span>On leave</span><span class="v"><?= $stat_on_leave_today ?></span></div>
            <div class="legend-row"><span class="l"><span class="dot" style="background:var(--border)"></span>Not marked</span><span class="v"><?= $stat_not_marked ?></span></div>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <?php if ($_can('view_attendance') || $_can('leave_management')): ?>
      <section class="grid" style="<?= !($_can('view_attendance') && $_can('leave_management')) ? 'grid-template-columns:1fr' : '' ?>">
        <?php if ($_can('view_attendance')): ?>
        <div class="panel">
          <div class="panel-head"><h2>Recent activity</h2><a href="all_punch_records.php">All punches →</a></div>
          <?php if (!$recent): ?>
            <div class="empty">No punches recorded today yet.</div>
          <?php else: foreach ($recent as $r): $sc = $status_class[$r['status']] ?? 'info'; ?>
            <div class="list-row">
              <div class="avatar"><?= $h(strtoupper(substr($r['name'], 0, 1))) ?></div>
              <div class="list-main"><div class="t"><?= $h($r['name']) ?></div><div class="s"><?= $h($r['employee_id'] ?: '—') ?></div></div>
              <span class="mono"><?= $fmt_time($r['punch_in']) ?> → <?= $fmt_time($r['punch_out']) ?></span>
              <span class="pill"><span class="dot" style="background:var(--<?= $sc ?>)"></span><?= $h($r['status']) ?></span>
            </div>
          <?php endforeach; endif; ?>
        </div>

        <?php endif; ?>
        <?php if ($_can('leave_management')): ?>
        <div class="panel">
          <div class="panel-head"><h2>Pending leave</h2><a href="leave_management.php">Review →</a></div>
          <?php if (!$pending): ?>
            <div class="empty">All caught up — no pending requests.</div>
          <?php else: foreach ($pending as $p): ?>
            <a class="list-row" href="leave_management.php">
              <div class="list-main">
                <div class="t"><?= $h($p['name']) ?></div>
                <div class="s"><?= $h($p['leave_type']) ?> · <?= date('j M', strtotime($p['start_date'])) ?><?= $p['end_date'] !== $p['start_date'] ? ' – ' . date('j M', strtotime($p['end_date'])) : '' ?></div>
              </div>
              <span class="mono"><?= rtrim(rtrim($p['days_count'], '0'), '.') ?>d</span>
            </a>
          <?php endforeach; endif; ?>
        </div>
      <?php endif; ?>
      </section>
      <?php endif; ?>

      <section class="panel">
        <div class="panel-head"><h2>Modules</h2></div>
        <div class="modules">
          <?php foreach ($_nav_groups as $links): foreach ($links as [$href, $icon, $label]): if ($label === 'Dashboard') continue; ?>
          <a class="module" href="<?= $h($href) ?>"><i class="fa-solid <?= $icon ?>"></i><span><?= $h($label) ?></span><i class="fa-solid fa-chevron-right arrow"></i></a>
          <?php endforeach; endforeach; ?>
        </div>
      </section>
    </div>

</body>
</html>
