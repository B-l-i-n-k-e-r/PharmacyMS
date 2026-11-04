<?php
// Fallback title if the page doesn't set one
$page_title = isset($page_title) ? $page_title : 'Pharmacy Management System';
$user = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($page_title); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
:root {
  --pine: #123b36; --pine-soft: #1c524b; --pine-line: #2a5f57; --amber: #f2b544;
  --mist: #eef4f1; --paper: #ffffff; --ink: #17211f; --ink-soft: #5b6b67;
  --line: #d5e0dc; --danger: #b3261e; --danger-bg: #fdecea; --ok: #1e6b3a; --ok-bg: #e6f4ea;
  --radius: 10px; --sidebar: 260px;
  --font: "Figtree", system-ui, -apple-system, sans-serif;
}
*, *::before, *::after { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }
body { margin: 0; font-family: var(--font); font-size: 16px; line-height: 1.5; color: var(--ink); background: var(--mist); }
a { color: inherit; text-decoration: none; }
:focus-visible { outline: 3px solid var(--amber); outline-offset: 2px; }

/* Sidebar */
.sidebar { position: fixed; inset: 0 auto 0 0; width: var(--sidebar); display: flex; flex-direction: column; background: var(--pine); color: #fff; padding: 24px 16px; z-index: 30; overflow-y: auto; }
.brand { display: flex; align-items: center; gap: 12px; padding: 0 8px 24px; margin-bottom: 8px; border-bottom: 1px solid var(--pine-line); }
.brand-mark { flex: none; width: 40px; height: 18px; border-radius: 999px; background: linear-gradient(90deg, var(--amber) 0 50%, #f7f3e8 50% 100%); transform: rotate(-24deg); }
.brand-name { font-weight: 700; line-height: 1.2; letter-spacing: -0.01em; }
.nav { list-style: none; margin: 16px 0 0; padding: 0; flex: 1; }
.nav li + li { margin-top: 2px; }
.nav a { display: flex; align-items: center; gap: 14px; padding: 11px 12px; border-radius: var(--radius); color: #c4d8d2; font-weight: 500; transition: background-color .15s, color .15s; }
.nav a i { width: 20px; text-align: center; font-size: 1rem; }
.nav a:hover { background: var(--pine-soft); color: #fff; }
.nav a.active { background: #fff; color: var(--pine); }
.sidebar-foot { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--pine-line); }
.sidebar-foot a { display: flex; align-items: center; gap: 14px; padding: 11px 12px; border-radius: var(--radius); color: #c4d8d2; font-weight: 500; }
.sidebar-foot a i { width: 20px; text-align: center; }
.sidebar-foot a:hover { background: var(--pine-soft); color: #fff; }

/* Topbar */
.topbar { position: sticky; top: 0; z-index: 20; margin-left: var(--sidebar); display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px 40px; background: var(--paper); border-bottom: 1px solid var(--line); }
.topbar-title { font-weight: 600; }
.menu-btn { display: none; align-items: center; justify-content: center; width: 42px; height: 42px; font-size: 1.1rem; color: var(--pine); background: var(--mist); border: 0; border-radius: var(--radius); cursor: pointer; }
.user-chip { display: flex; align-items: center; gap: 10px; font-weight: 500; }
.avatar { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 50%; background: var(--pine); color: #fff; font-weight: 700; }

/* Main Area */
.main { margin-left: var(--sidebar); padding: 40px; max-width: 1200px; }
.main h1 { margin: 0 0 6px; font-size: clamp(1.6rem, 2.6vw, 2.2rem); line-height: 1.15; letter-spacing: -0.02em; }
.main .lead { margin: 0 0 28px; color: var(--ink-soft); }

/* Dashboard Tiles */
.group h2 { margin: 0 0 16px; font-size: 1.15rem; font-weight: 700; }
.tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.tile { display: flex; align-items: flex-start; gap: 16px; padding: 20px; background: var(--paper); border: 1px solid var(--line); border-radius: 14px; transition: all .15s ease; }
.tile:hover { border-color: var(--pine); box-shadow: 0 4px 12px rgba(18, 59, 54, 0.05); transform: translateY(-2px); }
.tile-icon { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 10px; background: var(--mist); color: var(--pine); font-size: 1.15rem; flex-shrink: 0; }
.tile h3 { margin: 0; font-size: 1rem; font-weight: 600; }
.tile p { margin: 4px 0 0; color: var(--ink-soft); font-size: 0.85rem; line-height: 1.4; }

/* Dashboard Metrics */
.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
.metric-card { background: var(--paper); border: 1px solid var(--line); border-radius: 14px; padding: 24px; display: flex; align-items: center; gap: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.metric-icon { width: 54px; height: 54px; border-radius: 12px; display: grid; place-items: center; font-size: 1.5rem; flex-shrink: 0; }
.metric-icon--pine { background: var(--pine); color: #fff; }
.metric-icon--amber { background: #fbebc8; color: #7a5200; }
.metric-icon--danger { background: var(--danger-bg); color: var(--danger); }
.metric-info h3 { margin: 0; font-size: 0.85rem; color: var(--ink-soft); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.metric-info p { margin: 4px 0 0; font-size: 1.8rem; font-weight: 700; color: var(--ink); line-height: 1; }

/* Cards & Tables */
.card { background: var(--paper); border: 1px solid var(--line); border-radius: 14px; overflow: hidden; }
.card-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 16px 20px; border-bottom: 1px solid var(--line); }
.card-head h2 { margin: 0; font-size: 1.05rem; }
.count { color: var(--ink-soft); font-weight: 500; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
th, td { padding: 13px 20px; text-align: left; white-space: nowrap; }
th { font-size: 0.875rem; font-weight: 600; color: var(--ink-soft); background: #f7faf9; border-bottom: 1px solid var(--line); }
td { border-bottom: 1px solid #e6eeeb; }
tbody tr:last-child td { border-bottom: 0; }
tbody tr:hover td { background: #f7faf9; }
.icon-btn { display: inline-grid; place-items: center; width: 36px; height: 36px; border-radius: 8px; color: var(--ink-soft); transition: background-color .15s, color .15s; }
.icon-btn--edit:hover { background: var(--mist); color: var(--pine); }
.icon-btn--delete:hover { background: var(--danger-bg); color: var(--danger); }

/* Alerts & Forms */
.alert { margin: 0 0 20px; padding: 12px 14px; border-radius: var(--radius); font-size: 0.95rem; font-weight: 500; }
.alert--error { border: 1px solid #f0b8b3; background: var(--danger-bg); color: var(--danger); }
.alert--ok { border: 1px solid #b9dcc5; background: var(--ok-bg); color: var(--ok); }
.tabs { display: inline-flex; gap: 4px; padding: 4px; margin-bottom: 20px; background: #dfe9e5; border-radius: 12px; }
.tab { padding: 9px 18px; font: inherit; font-weight: 600; color: var(--ink-soft); background: transparent; border: 0; border-radius: 9px; cursor: pointer; transition: background-color .15s, color .15s; }
.tab:hover { color: var(--ink); }
.tab[aria-selected="true"] { background: var(--paper); color: var(--pine); }
.panel[hidden] { display: none; }
.form-card { max-width: 680px; padding: 28px; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 16px; }
.span-2 { grid-column: 1 / -1; }
.field label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.95rem; }
.field input, .field select { width: 100%; padding: 12px 14px; font: inherit; color: var(--ink); background: var(--paper); border: 1.5px solid var(--line); border-radius: var(--radius); transition: border-color .15s, box-shadow .15s; }
.field input:focus, .field select:focus { outline: none; border-color: var(--pine); box-shadow: 0 0 0 3px rgba(18, 59, 54, .15); }
.field input:read-only { background-color: var(--mist); color: var(--ink-soft); cursor: not-allowed; border-color: var(--mist); }
.form-actions { display: flex; gap: 12px; margin-top: 24px; }
.btn { display: inline-block; padding: 12px 22px; font: inherit; font-weight: 600; color: #fff; background: var(--pine); border: 0; border-radius: var(--radius); cursor: pointer; transition: background-color .15s; }
.btn:hover { background: var(--pine-soft); }
.btn--ghost { color: var(--ink); background: var(--mist); }
.btn--ghost:hover { background: #dfe9e5; }

/* Mobile */
.scrim { display: none; }
@media (max-width: 900px) {
  .sidebar { transform: translateX(-100%); transition: transform .2s ease; }
  body.nav-open .sidebar { transform: none; }
  body.nav-open .scrim { display: block; position: fixed; inset: 0; z-index: 25; background: rgba(10, 25, 22, .5); }
  .topbar { margin-left: 0; padding: 12px 20px; }
  .menu-btn { display: inline-flex; }
  .main { margin-left: 0; padding: 28px 20px 48px; }
  .grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>