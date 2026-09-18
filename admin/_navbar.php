<?php
// Shared sidebar + top bar for all admin pages
// Usage: include('_navbar.php');  (right after <body>)
// Requires: $conn, $_SESSION to be set already

$_admin_name = 'Admin';
$_admin_rights = null; // null = full access
$_is_super = ($_SESSION['role'] ?? '') === 'suparadmin';
if (isset($_SESSION['user_id'])) {
    $__uid = (int)$_SESSION['user_id'];
    $__s = $conn->prepare("SELECT * FROM users WHERE id=?");
    $__s->bind_param("i", $__uid); $__s->execute();
    $__r = $__s->get_result()->fetch_assoc(); $__s->close();
    $_admin_name = $__r['name'] ?? 'Admin';
    if (!$_is_super && !empty($__r['rights'])) {
        $__d = json_decode($__r['rights'], true);
        if (is_array($__d) && $__d) $_admin_rights = $__d;
    }
}
$_home = $_is_super ? 'dashboard.php' : 'admin_dashboard.php';
$_from = $_is_super ? 'suparadmin' : 'admin';
$_current_page = basename($_SERVER['PHP_SELF']);

// [href, icon, label, right key (null = always shown)]
$_nav_groups = [
    'Workspace' => [
        [$_home, 'fa-house', 'Dashboard', null],
        ["employees.php?from=$_from", 'fa-users', 'Employees', 'manage_employees'],
        ["admin.php?from=$_from", 'fa-user-shield', 'Admins', 'manage_admins'],
        ['supervisors.php', 'fa-user-tie', 'Supervisors', 'manage_supervisors'],
        ["manage_passwords.php?from=$_from", 'fa-key', 'Passwords', 'manage_passwords'],
    ],
    'Attendance' => [
        ["attendance.php?from=$_from", 'fa-chart-simple', 'Records', 'view_attendance'],
        ['manual_attendance.php', 'fa-keyboard', 'Manual entry', 'manual_attendance'],
        ['attendance_policy.php', 'fa-scale-balanced', 'Policy', 'attendance_policy'],
        ["geo_restriction.php?from=$_from", 'fa-location-crosshairs', 'GPS restriction', 'gps_restriction'],
        ["export_monthly.php?from=$_from", 'fa-file-arrow-down', 'Export reports', 'export_reports'],
    ],
    'Time off & pay' => [
        ['leave_management.php', 'fa-calendar-minus', 'Leave', 'leave_management'],
        ['comp_off_management.php', 'fa-calendar-plus', 'Comp off', 'comp_off'],
        ["od_management.php?from=$_from", 'fa-plane-departure', 'On duty (OD)', 'od_management'],
        ['project_holidays.php', 'fa-umbrella-beach', 'Holidays', 'project_holidays'],
        ['salary_slip.php', 'fa-wallet', 'Salary slips', 'salary_slip'],
    ],
    'Organization' => [
        ["department.php?from=$_from", 'fa-diagram-project', 'Projects', 'manage_departments'],
        ["companies.php?from=$_from", 'fa-building', 'Companies', 'manage_companies'],
        ["locations.php?from=$_from", 'fa-location-dot', 'Locations', 'manage_locations'],
        ["shifts.php?from=$_from", 'fa-clock', 'Shifts', 'manage_shifts'],
    ],
];

// Hide links this admin has no rights to
$_can = fn($key) => $key === null || $_admin_rights === null || in_array($key, $_admin_rights, true);
foreach ($_nav_groups as $__g => $__links) {
    $_nav_groups[$__g] = array_values(array_filter($__links, fn($l) => $_can($l[3])));
    if (!$_nav_groups[$__g]) unset($_nav_groups[$__g]);
}

// Work out current section + page title for the breadcrumb
$_crumb_group = 'Workspace';
$_crumb_title = ucwords(str_replace(['_', '-'], ' ', pathinfo($_current_page, PATHINFO_FILENAME)));
foreach ($_nav_groups as $__g => $__links) {
    foreach ($__links as $__l) {
        if (parse_url($__l[0], PHP_URL_PATH) === $_current_page) {
            $_crumb_group = $__g;
            $_crumb_title = $__l[2];
        }
    }
}
$_e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../assets/css/linear-admin.css">
<script>document.body.classList.add('la-body');</script>

<aside class="la-side" id="laSide">
    <div class="la-side-head">
        <a href="<?= $_e($_home) ?>"><img src="../assets/images/logo.png" alt="Logo" onerror="this.outerHTML='<span class=&quot;la-brand&quot;>HRMS</span>'"></a>
    </div>
    <nav class="la-side-nav">
        <?php foreach ($_nav_groups as $__g => $__links): ?>
        <div class="la-group">
            <div class="la-group-title"><?= $_e($__g) ?></div>
            <?php foreach ($__links as [$__href, $__icon, $__label]):
                $__active = parse_url($__href, PHP_URL_PATH) === $_current_page; ?>
            <a class="la-link <?= $__active ? 'active' : '' ?>" href="<?= $_e($__href) ?>" data-label="<?= $_e(strtolower($__label)) ?>">
                <i class="fa-solid <?= $__icon ?>"></i><?= $_e($__label) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </nav>
    <div class="la-side-foot">
        <div class="la-avatar"><?= $_e(strtoupper(substr($_admin_name, 0, 1))) ?></div>
        <div class="la-who"><div><?= $_e($_admin_name) ?></div><div class="la-role"><?= $_is_super ? 'Super admin' : 'Admin' ?></div></div>
        <a class="la-icon-btn" href="../auth/logout.php" title="Log out"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
    </div>
</aside>
<div class="la-overlay" id="laOverlay"></div>

<header class="la-topbar">
    <button class="la-icon-btn la-menu-btn" id="laMenuBtn" type="button" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    <div class="la-crumb"><?= $_e($_crumb_group) ?> / <b><?= $_e($_crumb_title) ?></b></div>
    <div class="la-spacer"></div>
    <label class="la-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input id="laSearch" placeholder="Jump to…" autocomplete="off">
        <kbd>/</kbd>
        <div class="la-results" id="laResults"></div>
    </label>
</header>

<script>
(function(){
    const side = document.getElementById('laSide'), overlay = document.getElementById('laOverlay');
    const setOpen = open => { side.classList.toggle('open', open); overlay.classList.toggle('show', open); };
    document.getElementById('laMenuBtn').addEventListener('click', () => setOpen(!side.classList.contains('open')));
    overlay.addEventListener('click', () => setOpen(false));

    // "Jump to" quick navigation
    const input = document.getElementById('laSearch'), box = document.getElementById('laResults');
    const links = [...side.querySelectorAll('.la-link')];
    let sel = 0, matches = [];
    function render() {
        const q = input.value.trim().toLowerCase();
        matches = q ? links.filter(l => l.dataset.label.includes(q)) : [];
        sel = 0;
        box.innerHTML = '';
        matches.forEach((l, i) => {
            const a = document.createElement('a');
            a.href = l.href; a.innerHTML = l.innerHTML;
            if (i === sel) a.className = 'sel';
            box.appendChild(a);
        });
        box.classList.toggle('show', matches.length > 0);
    }
    function highlight() { [...box.children].forEach((a, i) => a.classList.toggle('sel', i === sel)); }
    input.addEventListener('input', render);
    input.addEventListener('blur', () => setTimeout(() => box.classList.remove('show'), 150));
    input.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown' && matches.length) { e.preventDefault(); sel = (sel + 1) % matches.length; highlight(); }
        if (e.key === 'ArrowUp' && matches.length) { e.preventDefault(); sel = (sel - 1 + matches.length) % matches.length; highlight(); }
        if (e.key === 'Enter' && matches[sel]) { e.preventDefault(); location.href = matches[sel].href; }
        if (e.key === 'Escape') { input.value = ''; render(); input.blur(); }
    });
    document.addEventListener('keydown', e => {
        const t = e.target.tagName;
        if (e.key === '/' && t !== 'INPUT' && t !== 'TEXTAREA' && t !== 'SELECT' && !e.target.isContentEditable) {
            e.preventDefault(); input.focus();
        }
    });
})();
</script>
