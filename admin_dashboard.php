<?php
/**
 * Campus2Community Jharkhand - Administrative Scrutiny & University Allocation Console
 * Native PHP + MySQL Database Integration
 */
require_once __DIR__ . '/db.php';

// Authentication Check: Only Admin can access
$user = $_SESSION['c2c_user'] ?? null;
if (!$user || $user['role'] !== 'admin') {
    header('Location: login.php?role=admin&alert=unauthorized');
    exit;
}

$pdo = getDbConnection();
if (!$pdo) {
    die("Database connection failed. Please ensure MySQL is running on 127.0.0.1:3306");
}

// Fetch all complaints from MySQL
$stmt = $pdo->query("SELECT * FROM `complaints` ORDER BY `id` DESC");
$complaints = $stmt->fetchAll();

// Fetch university proposals for each complaint
$totalBids = 0;
$totalAssigned = 0;
$totalGrants = 0;
$pendingScrutiny = 0;

foreach ($complaints as &$c) {
    $pStmt = $pdo->prepare("SELECT * FROM `university_proposals` WHERE `complaint_id` = ? ORDER BY `match_score` DESC");
    $pStmt->execute([$c['id']]);
    $c['proposals'] = $pStmt->fetchAll();

    $totalBids += count($c['proposals']);
    if ($c['status'] === 'University Assigned' || !empty($c['assigned_university_name'])) {
        $totalAssigned++;
        $totalGrants += floatval($c['sanctioned_grant']);
    } else {
        $pendingScrutiny++;
    }
}
unset($c);
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Admin Scrutiny & University Allocation Console | Campus2Community Jharkhand</title>
  <!-- Material Symbols & Typography -->
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Noto+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#002238",
            "primary-container": "#0f3854",
            "on-primary": "#ffffff",
            "on-primary-container": "#7ea2c2",
            "secondary": "#006d3b",
            "secondary-container": "#97f3b3",
            "on-secondary": "#ffffff",
            "on-secondary-container": "#04723e",
            "tertiary": "#e65100",
            "surface": "#f7f9ff",
            "surface-container-low": "#ecf4ff",
            "surface-container": "#e4effc",
            "surface-container-high": "#dee9f6",
            "surface-container-lowest": "#ffffff",
            "on-surface": "#121d26",
            "on-surface-variant": "#42474d",
            "outline": "#72777e",
            "outline-variant": "#c2c7ce",
            "error": "#ba1a1a",
            "error-container": "#ffdad6"
          },
          fontFamily: {
            "headline": ["Noto Sans", "sans-serif"],
            "body": ["Inter", "sans-serif"]
          }
        }
      }
    }
  </script>
  <style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      display: inline-block;
      vertical-align: middle;
      line-height: 1;
    }
    .civic-tricolor-bar {
      height: 4px;
      background: linear-gradient(90deg, #E65100 0%, #FFFFFF 50%, #006d3b 100%);
    }
    *:focus-visible {
      outline: 3px solid #006d3b !important;
      outline-offset: 2px !important;
    }
    .badge-critical {
      background-color: #ffdad6;
      color: #93000a;
      border: 1px solid #ba1a1a;
    }
    .badge-high {
      background-color: #ffeedb;
      color: #8a3b00;
      border: 1px solid #e65100;
    }
    .badge-medium {
      background-color: #e4effc;
      color: #0f3854;
      border: 1px solid #7ea2c2;
    }
    .badge-assigned {
      background-color: #e7f5ed;
      color: #006d3b;
      border: 1px solid #006d3b;
    }
  </style>
</head>
<body class="bg-surface text-on-surface font-body antialiased min-h-screen flex flex-col justify-between selection:bg-secondary-container selection:text-on-secondary-container">

  <!-- 1. Sovereign Tricolor Stripe -->
  <div class="civic-tricolor-bar w-full sticky top-0 z-50"></div>

  <!-- 2. Government Top Authority Bar -->
  <div class="bg-primary text-on-primary border-b border-primary-container text-xs py-1.5 px-4">
    <div class="max-w-[1340px] mx-auto flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <span class="w-2.5 h-2.5 rounded-full bg-secondary-container inline-block"></span>
        <span class="font-bold tracking-wide uppercase text-[11px] text-surface-container-high">
          Government of Jharkhand | उच्च एवं तकनीकी शिक्षा विभाग • State Innovation &amp; Scrutiny Council
        </span>
      </div>
      <div class="flex items-center gap-4 text-xs">
        <span class="text-surface-container-high flex items-center gap-1 font-mono">
          <span class="w-2 h-2 rounded-full bg-secondary-container animate-pulse"></span>
          ADMIN CONSOLE • MYSQL LIVE SYNC
        </span>
        <span class="text-outline-variant">|</span>
        <a href="index.php" class="text-secondary-container hover:underline font-bold flex items-center gap-1">
          <span class="material-symbols-outlined text-[16px]">public</span> Public Citizen Portal
        </a>
      </div>
    </div>
  </div>

  <!-- 3. Admin Header Navigation -->
  <header class="bg-surface-container-lowest border-b border-outline-variant shadow-sm sticky top-1 z-40">
    <div class="max-w-[1340px] mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-4">
      
      <!-- Brand & Admin Title -->
      <div class="flex items-center gap-3.5">
        <a href="index.php" class="flex items-center gap-2 group">
          <img alt="Campus2Community Logo" class="h-10 w-auto object-contain" src="https://lh3.googleusercontent.com/aida/AEtjO1U_Yy-0US0ZQ0BNmC5bQQ-Z-DI2WsjWqswuBtuGCYeqmWQ_TD1DKf8DsiS7ER9E_dlr119lcW3HjKnQYC14EOXjdwxr72asn-WG5LVsc72yBMWcnvsKYFmgxYoh6kOqWOYavm6zKxLiZtHb9QPkT8Ctssx68ffFMocJMLJW8xig6iRGunZb69LeFw3EWqUgRhkCHiej3oTv7tbRZkyYvCes-peoc9gX-0HvslxpqIshwEkbbtpRySCVk-uV"/>
        </a>
        <div class="border-l-2 border-outline-variant pl-3.5">
          <div class="flex items-center gap-2">
            <span class="font-headline text-lg font-bold text-primary tracking-tight">Admin Scrutiny &amp; University Allocation Desk</span>
            <span class="px-2 py-0.5 rounded bg-tertiary/10 text-tertiary border border-tertiary/30 text-[10px] font-extrabold uppercase">
              Authoritative Portal
            </span>
          </div>
          <span class="block text-xs text-on-surface-variant">Review Grievances, Scrutinize University Bids &amp; Sanction State Innovation Grants</span>
        </div>
      </div>

      <!-- Admin Identity & Actions -->
      <div class="flex items-center gap-3">
        <!-- Officer Profile Pill -->
        <div class="hidden md:flex items-center gap-2.5 bg-surface-container-low border border-outline-variant px-3 py-1.5 rounded-xl text-xs">
          <div class="w-8 h-8 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold">
            <span class="material-symbols-outlined text-[18px]">shield_person</span>
          </div>
          <div>
            <span id="admin-display-name" class="font-bold text-primary block leading-tight"><?= htmlspecialchars($user['name']) ?></span>
            <span id="admin-display-role" class="text-[11px] text-secondary font-medium">Badge: <?= htmlspecialchars($user['adminId'] ?? 'ADM-OFFICER') ?></span>
          </div>
        </div>

        <!-- Quick Switch to Citizen Portal -->
        <a href="index.php" class="flex items-center gap-1.5 bg-surface-container-lowest hover:bg-surface-container text-primary font-bold text-xs px-3.5 py-2 rounded-lg border border-outline-variant transition">
          <span class="material-symbols-outlined text-[18px]">home</span>
          <span>Citizen Portal</span>
        </a>

        <!-- Sign Out Button -->
        <a href="logout.php" class="flex items-center gap-1.5 bg-error/10 hover:bg-error/20 text-error font-bold text-xs px-3 py-2 rounded-lg border border-error/30 transition">
          <span class="material-symbols-outlined text-[18px]">logout</span>
          <span>Sign Out</span>
        </a>
      </div>

    </div>
  </header>

  <!-- 4. Main Console Content -->
  <main class="flex-grow py-6 px-4 max-w-[1340px] mx-auto w-full">

    <!-- KPI Metric Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
      
      <div class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant shadow-sm">
        <span class="text-[11px] text-on-surface-variant font-bold block uppercase tracking-wider">Total Grievances</span>
        <div class="flex items-center justify-between mt-1">
          <span class="text-2xl font-black text-primary"><?= count($complaints) ?></span>
          <span class="material-symbols-outlined text-primary text-[22px]">inbox</span>
        </div>
        <span class="text-[10px] text-secondary font-semibold">Live in MySQL</span>
      </div>

      <div class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant shadow-sm">
        <span class="text-[11px] text-on-surface-variant font-bold block uppercase tracking-wider">Needs Scrutiny</span>
        <div class="flex items-center justify-between mt-1">
          <span class="text-2xl font-black text-tertiary"><?= $pendingScrutiny ?></span>
          <span class="material-symbols-outlined text-tertiary text-[22px]">pending_actions</span>
        </div>
        <span class="text-[10px] text-tertiary font-semibold">Awaiting Allocation</span>
      </div>

      <div class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant shadow-sm">
        <span class="text-[11px] text-on-surface-variant font-bold block uppercase tracking-wider">University Bids</span>
        <div class="flex items-center justify-between mt-1">
          <span class="text-2xl font-black text-primary"><?= $totalBids ?></span>
          <span class="material-symbols-outlined text-primary text-[22px]">school</span>
        </div>
        <span class="text-[10px] text-outline font-semibold">Active Competing Teams</span>
      </div>

      <div class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant shadow-sm">
        <span class="text-[11px] text-on-surface-variant font-bold block uppercase tracking-wider">Assigned to Uni</span>
        <div class="flex items-center justify-between mt-1">
          <span class="text-2xl font-black text-secondary"><?= $totalAssigned ?></span>
          <span class="material-symbols-outlined text-secondary text-[22px]">how_to_reg</span>
        </div>
        <span class="text-[10px] text-secondary font-semibold">MoA Finalized</span>
      </div>

      <div class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant shadow-sm">
        <span class="text-[11px] text-on-surface-variant font-bold block uppercase tracking-wider">Grants Sanctioned</span>
        <div class="flex items-center justify-between mt-1">
          <span class="text-xl font-black text-secondary">₹<?= number_format($totalGrants) ?></span>
          <span class="material-symbols-outlined text-secondary text-[22px]">payments</span>
        </div>
        <span class="text-[10px] text-outline font-semibold">Direct University Transfer</span>
      </div>

      <div class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant shadow-sm">
        <span class="text-[11px] text-on-surface-variant font-bold block uppercase tracking-wider">Districts</span>
        <div class="flex items-center justify-between mt-1">
          <span class="text-2xl font-black text-primary">24 / 24</span>
          <span class="material-symbols-outlined text-primary text-[22px]">map</span>
        </div>
        <span class="text-[10px] text-secondary font-semibold">100% State Coverage</span>
      </div>

    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-surface-container-lowest border border-outline-variant p-4 rounded-xl shadow-sm mb-6 flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3 flex-grow max-w-lg">
        <div class="relative w-full">
          <span class="material-symbols-outlined absolute left-3 top-2.5 text-outline text-[18px]">search</span>
          <input type="text" id="admin-search-input" oninput="filterComplaintsTable()" placeholder="Search problem, ticket ID, village, or citizen name..." class="w-full bg-surface-container-low border border-outline-variant rounded-lg pl-9 pr-3 py-2 text-xs text-on-surface focus:border-primary focus:ring-1 focus:ring-primary"/>
        </div>
      </div>

      <div class="flex items-center gap-2 flex-wrap text-xs">
        <select id="filter-category" onchange="filterComplaintsTable()" class="bg-surface-container-low border border-outline-variant rounded-lg px-2.5 py-2 text-xs text-primary font-medium focus:ring-1 focus:ring-primary">
          <option value="ALL">All Categories</option>
          <option value="Water">Water (पेयजल)</option>
          <option value="Roads">Roads (सड़क)</option>
          <option value="Environment">Environment (पर्यावरण)</option>
          <option value="Waste">Waste Management (कचरा प्रबंधन)</option>
        </select>

        <select id="filter-status" onchange="filterComplaintsTable()" class="bg-surface-container-low border border-outline-variant rounded-lg px-2.5 py-2 text-xs text-primary font-medium focus:ring-1 focus:ring-primary">
          <option value="ALL">All Statuses</option>
          <option value="Needs Scrutiny">Needs Scrutiny</option>
          <option value="University Assigned">University Assigned</option>
        </select>

        <button onclick="window.location.reload()" type="button" class="flex items-center gap-1 px-3 py-2 rounded-lg bg-surface-container border border-outline-variant text-primary font-bold hover:bg-surface-container-high transition">
          <span class="material-symbols-outlined text-[16px]">refresh</span>
          <span>Refresh Database</span>
        </button>
      </div>
    </div>

    <!-- Grievances & Bidding Master List -->
    <div class="space-y-6" id="complaints-list-container">

      <?php foreach ($complaints as $c): ?>
      <?php 
        $isAssigned = ($c['status'] === 'University Assigned' || !empty($c['assigned_university_name']));
        $badgeClass = $isAssigned ? 'badge-assigned' : ($c['urgency'] === 'Critical' ? 'badge-critical' : 'badge-high');
        $statusText = $isAssigned ? 'University Assigned' : 'Needs Scrutiny';
      ?>
      <div class="complaint-card bg-surface-container-lowest border-2 <?= $isAssigned ? 'border-secondary/40' : 'border-outline-variant' ?> rounded-2xl p-5 md:p-6 shadow-sm hover:shadow-md transition"
           data-ticket="<?= htmlspecialchars($c['ticket_id']) ?>"
           data-title="<?= htmlspecialchars(strtolower($c['problem_title'])) ?>"
           data-category="<?= htmlspecialchars($c['category']) ?>"
           data-status="<?= $statusText ?>"
           data-locality="<?= htmlspecialchars(strtolower($c['locality'] . ' ' . $c['district'])) ?>">
        
        <!-- Ticket Header Bar -->
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-outline-variant/60 pb-4 mb-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="font-mono font-black text-sm bg-primary text-on-primary px-2.5 py-0.5 rounded tracking-wide">
                <?= htmlspecialchars($c['ticket_id']) ?>
              </span>
              <span class="px-2.5 py-0.5 rounded text-[11px] font-bold uppercase <?= $badgeClass ?>">
                <?= $statusText ?>
              </span>
              <span class="text-xs bg-surface-container px-2 py-0.5 rounded font-bold text-primary border border-outline-variant/50">
                <?= htmlspecialchars($c['category']) ?>
              </span>
            </div>
            <h3 class="font-headline text-lg md:text-xl font-bold text-primary mt-1">
              <?= htmlspecialchars($c['problem_title']) ?>
            </h3>
            <div class="flex flex-wrap items-center gap-3 text-xs text-on-surface-variant font-medium">
              <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px] text-secondary">pin_drop</span>
                <?= htmlspecialchars($c['locality']) ?>, <?= htmlspecialchars($c['district']) ?>
              </span>
              <span>•</span>
              <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-[15px]">person</span>
                <?= htmlspecialchars($c['citizen_name']) ?> (<?= htmlspecialchars($c['citizen_mobile']) ?>)
              </span>
              <span>•</span>
              <span class="text-outline">Reported: <?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></span>
            </div>
          </div>

          <!-- Action Button: Open Scrutinize Modal -->
          <div>
            <?php if (!$isAssigned): ?>
            <button onclick="openScrutinyModal(<?= htmlspecialchars(json_encode($c)) ?>)" type="button" class="flex items-center gap-2 bg-secondary hover:bg-secondary/90 text-on-secondary font-bold text-xs px-4 py-2.5 rounded-xl shadow transition active:scale-[0.99]">
              <span class="material-symbols-outlined text-[18px]">verified</span>
              <span>Scrutinize &amp; Assign University</span>
            </button>
            <?php else: ?>
            <div class="text-right">
              <span class="inline-flex items-center gap-1 bg-secondary/10 text-secondary border border-secondary/30 px-3 py-1.5 rounded-lg text-xs font-bold">
                <span class="material-symbols-outlined text-[16px]">check_circle</span>
                Assigned: <?= htmlspecialchars($c['assigned_university_name']) ?>
              </span>
              <span class="block text-[11px] text-outline mt-0.5 font-semibold">
                Grant: ₹<?= number_format(floatval($c['sanctioned_grant'])) ?> • <?= htmlspecialchars($c['milestone_timeline']) ?>
              </span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Problem Detail & Geotag Grid -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-5">
          <!-- Description & Photo (8 cols) -->
          <div class="md:col-span-8 space-y-3">
            <p class="text-xs md:text-sm text-on-surface leading-relaxed bg-surface-container-low p-3.5 rounded-xl border border-outline-variant/60">
              <?= nl2br(htmlspecialchars($c['description'])) ?>
            </p>
            
            <?php if ($isAssigned && !empty($c['scrutiny_notes'])): ?>
            <div class="p-3 bg-secondary/5 border border-secondary/30 rounded-lg text-xs text-on-surface">
              <span class="font-bold text-secondary flex items-center gap-1 mb-0.5">
                <span class="material-symbols-outlined text-[15px]">fact_check</span> Official Scrutiny Notes:
              </span>
              <?= htmlspecialchars($c['scrutiny_notes']) ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- GPS & Geotag Card (4 cols) -->
          <div class="md:col-span-4 bg-surface-container-low border border-outline-variant/80 rounded-xl p-3.5 text-xs space-y-2">
            <div class="flex items-center justify-between border-b border-outline-variant/50 pb-2">
              <span class="font-bold text-primary flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px] text-secondary">my_location</span>
                GPS Geotag Fix
              </span>
              <span class="px-1.5 py-0.2 bg-secondary-container text-on-secondary-container font-mono text-[10px] rounded font-bold">
                ±<?= htmlspecialchars($c['accuracy_meters'] ?? '4.5') ?>m
              </span>
            </div>
            <div class="font-mono text-[11px] text-on-surface-variant space-y-0.5">
              <div>Lat: <span class="font-bold text-primary"><?= htmlspecialchars($c['latitude']) ?>° N</span></div>
              <div>Lng: <span class="font-bold text-primary"><?= htmlspecialchars($c['longitude']) ?>° E</span></div>
            </div>
            <div class="text-[11px] text-on-surface-variant leading-tight border-t border-outline-variant/40 pt-1.5">
              <span class="font-semibold text-primary">Geotag Address:</span><br/>
              <?= htmlspecialchars($c['geotag_address']) ?>
            </div>
            <?php if (!empty($c['photo_url'])): ?>
            <div class="pt-1">
              <a href="<?= htmlspecialchars($c['photo_url']) ?>" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-secondary hover:underline">
                <span class="material-symbols-outlined text-[14px]">image</span> View Uploaded Evidence Photo →
              </a>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Enrolled Universities Section -->
        <div class="border-t border-outline-variant/60 pt-4">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-primary text-[20px]">school</span>
              <h4 class="font-bold text-xs uppercase tracking-wider text-primary">
                Universities Enrolled for Solution (विश्वविद्यालय प्रस्ताव): <?= count($c['proposals']) ?> Institute(s) Competing
              </h4>
            </div>
            <span class="text-[11px] text-outline">Sorted by Institutional Match Score</span>
          </div>

          <?php if (empty($c['proposals'])): ?>
          <div class="p-3 bg-surface-container rounded-lg text-xs text-outline text-center">
            No university proposals enrolled yet. Technical cells will review within 24 hours.
          </div>
          <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <?php foreach ($c['proposals'] as $prop): ?>
            <?php 
              $isWinning = ($isAssigned && $c['assigned_university_name'] === $prop['university_name']);
            ?>
            <div class="p-3.5 rounded-xl border-2 <?= $isWinning ? 'border-secondary bg-secondary/5 shadow' : 'border-outline-variant/70 bg-surface-container-lowest' ?> flex flex-col justify-between text-xs space-y-2">
              <div>
                <div class="flex items-start justify-between gap-1 mb-1">
                  <span class="font-bold text-primary leading-tight"><?= htmlspecialchars($prop['university_name']) ?></span>
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold <?= $prop['match_score'] >= 90 ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-primary' ?>">
                    <?= $prop['match_score'] ?>% Match
                  </span>
                </div>
                <p class="text-[11px] text-on-surface-variant font-medium leading-snug">
                  <?= htmlspecialchars($prop['proposal_title']) ?>
                </p>
              </div>

              <div class="border-t border-outline-variant/40 pt-2 text-[11px] space-y-0.5 text-on-surface-variant">
                <div class="flex justify-between">
                  <span>Timeline:</span>
                  <span class="font-bold text-primary"><?= htmlspecialchars($prop['timeline']) ?></span>
                </div>
                <div class="flex justify-between">
                  <span>Estimated Budget:</span>
                  <span class="font-bold text-primary">₹<?= number_format(floatval($prop['estimated_cost'])) ?></span>
                </div>
                <div class="flex justify-between">
                  <span>Faculty Lead:</span>
                  <span class="truncate max-w-[140px] text-outline"><?= htmlspecialchars($prop['faculty_lead']) ?></span>
                </div>
              </div>

              <?php if (!$isAssigned): ?>
              <button onclick="preselectUniAndOpen(<?= htmlspecialchars(json_encode($c)) ?>, '<?= addslashes($prop['university_name']) ?>', <?= floatval($prop['estimated_cost']) ?>, '<?= addslashes($prop['timeline']) ?>')" type="button" class="w-full mt-2 py-1.5 px-2 bg-surface-container hover:bg-primary hover:text-on-primary rounded text-[11px] font-bold text-primary transition text-center flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[14px]">assignment_turned_in</span>
                <span>Select &amp; Sanction</span>
              </button>
              <?php elseif ($isWinning): ?>
              <div class="w-full mt-2 py-1 px-2 bg-secondary text-on-secondary rounded text-[10px] font-extrabold uppercase text-center tracking-wider flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-[13px]">verified</span> Officially Selected
              </div>
              <?php endif; ?>

            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

        </div>

      </div>
      <?php endforeach; ?>

    </div>

  </main>

  <!-- 5. Institutional Civic Footer -->
  <footer class="bg-primary text-on-primary py-6 px-4 border-t-4 border-secondary text-xs mt-12">
    <div class="max-w-[1340px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-secondary-container text-[24px]">account_balance</span>
        <div>
          <span class="font-bold block">Jharkhand State Civic &amp; University Scrutiny Council</span>
          <span class="text-surface-container-high text-[11px]">Department of Higher &amp; Technical Education • Single Window Redressal Desk</span>
        </div>
      </div>
      <div class="flex items-center gap-4 text-surface-container-high text-xs">
        <a href="index.php" class="hover:underline">Citizen Portal</a>
        <a href="login.php" class="hover:underline">Switch Terminal</a>
        <span class="text-secondary-container font-mono">v3.0-SCRUTINY-PHP</span>
      </div>
    </div>
  </footer>

  <!-- ============================================== -->
  <!-- MODAL: SCRUTINIZE & ASSIGN UNIVERSITY          -->
  <!-- ============================================== -->
  <div id="scrutiny-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-primary/70 backdrop-blur-sm">
    <div class="bg-surface-container-lowest border-2 border-primary rounded-2xl max-w-xl w-full shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
      
      <!-- Modal Header -->
      <div class="bg-gradient-to-r from-primary to-primary-container text-on-primary p-5 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-lg bg-secondary text-on-secondary flex items-center justify-center font-bold">
            <span class="material-symbols-outlined text-[20px]">assignment_turned_in</span>
          </div>
          <div>
            <h3 class="font-headline font-bold text-base">Scrutinize &amp; Assign University</h3>
            <span id="modal-ticket-id" class="text-xs text-secondary-container font-mono font-bold block">JC2C-2026-XXXXX</span>
          </div>
        </div>
        <button onclick="closeScrutinyModal()" type="button" class="text-on-primary/80 hover:text-on-primary p-1 rounded-lg">
          <span class="material-symbols-outlined text-[20px]">close</span>
        </button>
      </div>

      <!-- Modal Body -->
      <form id="scrutiny-assign-form" onsubmit="handleScrutinySubmit(event)" class="p-6 space-y-4 text-xs">
        <input type="hidden" id="modal-complaint-id" value=""/>

        <div class="bg-surface-container-low p-3 rounded-xl border border-outline-variant">
          <span class="text-[11px] text-outline font-bold uppercase block">Problem Title:</span>
          <span id="modal-problem-title" class="font-bold text-primary block text-sm mt-0.5">...</span>
          <span id="modal-locality-text" class="text-on-surface-variant block mt-0.5">...</span>
        </div>

        <div>
          <label class="block font-bold text-on-surface-variant mb-1" for="modal-uni-select">
            Select Winning University Node (विश्वविद्यालय चयन) <span class="text-error">*</span>
          </label>
          <select id="modal-uni-select" required class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg px-3 py-2.5 text-xs text-primary font-bold focus:ring-1 focus:ring-primary">
            <!-- Populated dynamically -->
          </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-on-surface-variant mb-1" for="modal-grant-amount">
              Sanctioned State Grant (₹ स्वीकृत राशि) <span class="text-error">*</span>
            </label>
            <div class="relative flex items-center">
              <span class="absolute left-3 text-xs font-bold text-primary">₹</span>
              <input type="number" id="modal-grant-amount" required step="1000" min="5000" value="85000" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg pl-7 pr-3 py-2 text-xs text-primary font-bold focus:ring-1 focus:ring-primary"/>
            </div>
            <span class="text-[10px] text-outline mt-0.5 block">State Innovation Corpus allocation</span>
          </div>

          <div>
            <label class="block font-bold text-on-surface-variant mb-1" for="modal-timeline">
              Implementation Timeline <span class="text-error">*</span>
            </label>
            <input type="text" id="modal-timeline" required value="14 Days (Rapid Implementation)" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg px-3 py-2 text-xs text-on-surface font-semibold focus:ring-1 focus:ring-primary"/>
          </div>
        </div>

        <div>
          <label class="block font-bold text-on-surface-variant mb-1" for="modal-notes">
            Administrative Scrutiny Notes &amp; MoA Terms (निरीक्षण टिप्पणी)
          </label>
          <textarea id="modal-notes" rows="3" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg p-2.5 text-xs text-on-surface focus:ring-1 focus:ring-primary" placeholder="State scrutiny remarks, mandatory weekly lab test submission, citizen sign-off clause...">Approved under Jharkhand Civic Innovation Scheme 2026. Weekly progress reports required from faculty coordinator. 50% mobilization advance sanctioned.</textarea>
        </div>

        <!-- Submit Button -->
        <div class="pt-2 flex items-center justify-end gap-2 border-t border-outline-variant/60">
          <button type="button" onclick="closeScrutinyModal()" class="px-4 py-2 bg-surface-container hover:bg-surface-container-high text-primary font-bold rounded-lg transition">
            Cancel
          </button>
          <button type="submit" id="modal-submit-btn" class="px-5 py-2 bg-secondary hover:bg-secondary/90 text-on-secondary font-bold rounded-lg shadow transition flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">verified</span>
            <span>Officially Sanction &amp; Assign University</span>
          </button>
        </div>
      </form>

    </div>
  </div>

  <!-- ============================================== -->
  <!-- JAVASCRIPT CONNECTING TO PHP ASSIGNMENT API    -->
  <!-- ============================================== -->
  <script>
    let activeComplaint = null;

    function openScrutinyModal(complaint) {
      activeComplaint = complaint;
      document.getElementById('modal-complaint-id').value = complaint.id;
      document.getElementById('modal-ticket-id').textContent = complaint.ticket_id;
      document.getElementById('modal-problem-title').textContent = complaint.problem_title;
      document.getElementById('modal-locality-text').textContent = `${complaint.locality}, ${complaint.district} • GPS: ${complaint.latitude}° N, ${complaint.longitude}° E`;

      // Populate university dropdown
      const select = document.getElementById('modal-uni-select');
      select.innerHTML = '';

      if (complaint.proposals && complaint.proposals.length > 0) {
        complaint.proposals.forEach((p, idx) => {
          const opt = document.createElement('option');
          opt.value = p.university_name;
          opt.textContent = `${p.university_name} (${p.match_score}% Match - ₹${Number(p.estimated_cost).toLocaleString()})`;
          opt.dataset.cost = p.estimated_cost;
          opt.dataset.timeline = p.timeline;
          select.appendChild(opt);
        });
        document.getElementById('modal-grant-amount').value = complaint.proposals[0].estimated_cost;
        document.getElementById('modal-timeline').value = complaint.proposals[0].timeline;
      } else {
        const defaultUnis = [
          'Birla Institute of Technology (BIT), Mesra',
          'National Institute of Technology (NIT), Jamshedpur',
          'IIT (ISM) Dhanbad',
          'Government Polytechnic, Ranchi'
        ];
        defaultUnis.forEach(u => {
          const opt = document.createElement('option');
          opt.value = u;
          opt.textContent = u;
          select.appendChild(opt);
        });
      }

      select.onchange = function() {
        const selectedOpt = select.options[select.selectedIndex];
        if (selectedOpt && selectedOpt.dataset.cost) {
          document.getElementById('modal-grant-amount').value = selectedOpt.dataset.cost;
          document.getElementById('modal-timeline').value = selectedOpt.dataset.timeline;
        }
      };

      document.getElementById('scrutiny-modal').classList.remove('hidden');
    }

    function preselectUniAndOpen(complaint, uniName, cost, timeline) {
      openScrutinyModal(complaint);
      const select = document.getElementById('modal-uni-select');
      select.value = uniName;
      document.getElementById('modal-grant-amount').value = cost;
      document.getElementById('modal-timeline').value = timeline;
    }

    function closeScrutinyModal() {
      document.getElementById('scrutiny-modal').classList.add('hidden');
      activeComplaint = null;
    }

    async function handleScrutinySubmit(e) {
      e.preventDefault();
      const submitBtn = document.getElementById('modal-submit-btn');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = `<span class="material-symbols-outlined text-[16px] animate-spin">refresh</span> Saving to Database...`;

      const complaintId = document.getElementById('modal-complaint-id').value;
      const universityName = document.getElementById('modal-uni-select').value;
      const grantAmount = document.getElementById('modal-grant-amount').value;
      const timeline = document.getElementById('modal-timeline').value;
      const notes = document.getElementById('modal-notes').value;

      try {
        const formData = new FormData();
        formData.append('action', 'assign_university');
        formData.append('complaint_id', complaintId);
        formData.append('university_name', universityName);
        formData.append('grant_amount', grantAmount);
        formData.append('milestone_timeline', timeline);
        formData.append('scrutiny_notes', notes);

        const response = await fetch('api/complaints.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();

        if (result.success) {
          alert(`✓ Official Allocation Confirmed!\n\n${result.message}\nTicket state updated in MySQL.`);
          closeScrutinyModal();
          window.location.reload();
        } else {
          alert('Error: ' + (result.message || 'Could not assign university.'));
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      } catch (err) {
        console.error(err);
        alert('Server communication failed. Please check MySQL server.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    }

    function filterComplaintsTable() {
      const q = document.getElementById('admin-search-input').value.toLowerCase().trim();
      const cat = document.getElementById('filter-category').value;
      const stat = document.getElementById('filter-status').value;

      const cards = document.querySelectorAll('.complaint-card');
      cards.forEach(c => {
        const ticket = c.dataset.ticket.toLowerCase();
        const title = c.dataset.title.toLowerCase();
        const locality = c.dataset.locality.toLowerCase();
        const cCat = c.dataset.category;
        const cStat = c.dataset.status;

        const matchesQuery = !q || ticket.includes(q) || title.includes(q) || locality.includes(q);
        const matchesCategory = cat === 'ALL' || cCat === cat;
        const matchesStatus = stat === 'ALL' || cStat === stat;

        if (matchesQuery && matchesCategory && matchesStatus) {
          c.classList.remove('hidden');
        } else {
          c.classList.add('hidden');
        }
      });
    }
  </script>
</body>
</html>
