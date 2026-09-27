<?php
/**
 * Campus2Community Secure Access & Registration Portal
 * PHP + MySQL Database Session Backend
 */
require_once __DIR__ . '/db.php';

// If already logged in, redirect to appropriate destination
if (isset($_SESSION['c2c_user'])) {
    if ($_SESSION['c2c_user']['role'] === 'admin') {
        header('Location: admin_dashboard.php');
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}

$initialRole = $_GET['role'] ?? 'user';
if (!in_array($initialRole, ['user', 'university', 'admin'])) {
    $initialRole = 'user';
}
$initialMode = $_GET['mode'] ?? 'login';
?>
<!DOCTYPE html>
<html class="scroll-smooth" lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Campus2Community | Secure Access & Registration Portal</title>
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
    .role-card-active {
      border-color: #002238 !important;
      background-color: #ecf4ff !important;
      box-shadow: 0 4px 14px -2px rgba(0, 34, 56, 0.12);
    }
    .tab-btn-active {
      border-bottom: 3px solid #006d3b;
      color: #002238;
      font-weight: 700;
    }
  </style>
</head>
<body class="bg-surface text-on-surface font-body antialiased min-h-screen flex flex-col justify-between selection:bg-secondary-container selection:text-on-secondary-container">

  <!-- 1. Sovereign Tricolor Stripe -->
  <div class="civic-tricolor-bar w-full sticky top-0 z-50"></div>

  <!-- 2. Top Government Utility Bar -->
  <div class="bg-primary text-on-primary border-b border-primary-container text-xs py-1.5 px-4">
    <div class="max-w-[1200px] mx-auto flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <span class="w-2.5 h-2.5 rounded-full bg-secondary-container inline-block"></span>
        <span class="font-semibold tracking-wide uppercase text-[11px] text-surface-container-high">
          Government of Jharkhand | उच्च एवं तकनीकी शिक्षा विभाग
        </span>
      </div>
      <div class="flex items-center gap-4 text-xs">
        <span class="text-surface-container-high flex items-center gap-1">
          <span class="material-symbols-outlined text-[15px] text-secondary-container">verified_user</span>
          Official Civic Authentication Gateway (PHP + MySQL Active)
        </span>
        <a href="tel:18003456570" class="hover:underline font-bold text-secondary-container flex items-center gap-1">
          <span class="material-symbols-outlined text-[15px]">call</span> 1800-345-6570
        </a>
      </div>
    </div>
  </div>

  <!-- 3. Header Navigation with Return to Main Portal -->
  <header class="bg-surface-container-lowest border-b border-outline-variant shadow-sm sticky top-1 z-40">
    <div class="max-w-[1200px] mx-auto px-4 py-3 flex items-center justify-between">
      <a href="index.php" class="flex items-center gap-3 group">
        <img alt="Campus2Community Jharkhand Brand Logo" class="h-10 w-auto object-contain" src="https://lh3.googleusercontent.com/aida/AEtjO1U_Yy-0US0ZQ0BNmC5bQQ-Z-DI2WsjWqswuBtuGCYeqmWQ_TD1DKf8DsiS7ER9E_dlr119lcW3HjKnQYC14EOXjdwxr72asn-WG5LVsc72yBMWcnvsKYFmgxYoh6kOqWOYavm6zKxLiZtHb9QPkT8Ctssx68ffFMocJMLJW8xig6iRGunZb69LeFw3EWqUgRhkCHiej3oTv7tbRZkyYvCes-peoc9gX-0HvslxpqIshwEkbbtpRySCVk-uV"/>
        <div class="border-l-2 border-outline-variant pl-3">
          <span class="block font-headline text-lg font-bold text-primary tracking-tight leading-tight">Campus2Community</span>
          <span class="block text-[11px] font-medium text-secondary -mt-0.5">झारखंड जन-समस्या समाधान व विश्वविद्यालय मंच</span>
        </div>
      </a>
      <div class="flex items-center gap-3">
        <a href="index.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:text-secondary px-3 py-2 rounded-lg border border-outline-variant hover:border-secondary transition bg-surface-container-lowest">
          <span class="material-symbols-outlined text-[18px]">arrow_back</span>
          <span>Back to Main Portal (मुख्य पृष्ठ)</span>
        </a>
      </div>
    </div>
  </header>

  <!-- 4. Main Auth Container -->
  <main class="flex-grow py-8 px-4 flex items-center justify-center">
    <div class="w-full max-w-[960px] mx-auto">

      <!-- Breadcrumb & Help Announcement -->
      <div class="mb-5 flex flex-wrap items-center justify-between gap-2 text-xs">
        <div class="flex items-center gap-2 text-on-surface-variant font-medium">
          <a href="index.php" class="hover:underline flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">home</span> Home
          </a>
          <span>/</span>
          <span class="text-primary font-bold">Portal Access Gateway</span>
        </div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-secondary/10 border border-secondary/30 rounded-full text-secondary font-bold text-[11px]">
          <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
          256-Bit SSL Encrypted MySQL Database Active
        </div>
      </div>

      <!-- Main Login / Register Card -->
      <div class="bg-surface-container-lowest border-2 border-outline-variant rounded-2xl shadow-xl overflow-hidden">
        
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-primary to-primary-container text-on-primary p-6 md:p-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-secondary text-on-secondary text-[11px] font-bold uppercase tracking-wider mb-2">
              <span class="material-symbols-outlined text-[14px]">lock</span> Single Sign-On Gateway
            </div>
            <h1 class="font-headline text-2xl md:text-3xl font-extrabold tracking-tight">
              Sign In or Register
            </h1>
            <p class="text-xs md:text-sm text-surface-container-high mt-1 max-w-xl">
              Select your designated stakeholder role below to access your grievance tracking, university solution hub, or administrative scrutiny desk.
            </p>
          </div>
          <!-- Quick Demo Info Pill -->
          <div class="bg-primary/50 border border-outline-variant/30 rounded-xl p-3 text-xs space-y-1.5 shrink-0">
            <span class="text-[11px] text-surface-container-high block font-semibold">⚡ Quick Demonstration:</span>
            <div class="flex flex-wrap gap-1.5">
              <button onclick="quickDemoFill('user')" type="button" class="bg-surface-container-lowest text-primary hover:bg-surface-container px-2 py-1 rounded text-[11px] font-bold transition flex items-center gap-1">
                <span class="material-symbols-outlined text-[13px]">person</span> Citizen
              </button>
              <button onclick="quickDemoFill('university')" type="button" class="bg-surface-container-lowest text-primary hover:bg-surface-container px-2 py-1 rounded text-[11px] font-bold transition flex items-center gap-1">
                <span class="material-symbols-outlined text-[13px]">school</span> University
              </button>
              <button onclick="quickDemoFill('admin')" type="button" class="bg-secondary text-on-secondary hover:bg-secondary/90 px-2 py-1 rounded text-[11px] font-bold transition flex items-center gap-1">
                <span class="material-symbols-outlined text-[13px]">admin_panel_settings</span> Admin
              </button>
            </div>
          </div>
        </div>

        <div class="p-6 md:p-8">

          <!-- 3 Role Options -->
          <div class="mb-8">
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">
              1. Select Your Civic Role (अपनी भूमिका चुनें):
            </label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              
              <!-- Option 1: Resident / User -->
              <button id="role-btn-user" onclick="switchRole('user')" type="button" class="role-card text-left p-4 rounded-xl border-2 border-outline-variant hover:border-primary transition group relative role-card-active">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-lg bg-primary text-on-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[24px]">person</span>
                  </div>
                  <div>
                    <div class="flex items-center gap-1">
                      <span class="font-bold text-sm text-primary">Resident / Citizen</span>
                      <span class="text-[10px] bg-secondary-container text-on-secondary-container px-1.5 py-0.2 rounded font-bold">नागरिक</span>
                    </div>
                    <span class="text-xs text-on-surface-variant block mt-0.5 leading-snug">
                      Login with <strong>Mobile &amp; Password</strong>
                    </span>
                  </div>
                </div>
              </button>

              <!-- Option 2: University -->
              <button id="role-btn-university" onclick="switchRole('university')" type="button" class="role-card text-left p-4 rounded-xl border-2 border-outline-variant hover:border-primary transition group relative">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-lg bg-surface-container text-primary flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-on-primary transition">
                    <span class="material-symbols-outlined text-[24px]">school</span>
                  </div>
                  <div>
                    <div class="flex items-center gap-1">
                      <span class="font-bold text-sm text-primary">University / Hub</span>
                      <span class="text-[10px] bg-surface-container text-primary px-1.5 py-0.2 rounded font-bold">संस्थान</span>
                    </div>
                    <span class="text-xs text-on-surface-variant block mt-0.5 leading-snug">
                      Login with <strong>Email &amp; Password</strong>
                    </span>
                  </div>
                </div>
              </button>

              <!-- Option 3: Admin -->
              <button id="role-btn-admin" onclick="switchRole('admin')" type="button" class="role-card text-left p-4 rounded-xl border-2 border-outline-variant hover:border-primary transition group relative">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-lg bg-surface-container text-primary flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-on-primary transition">
                    <span class="material-symbols-outlined text-[24px]">admin_panel_settings</span>
                  </div>
                  <div>
                    <div class="flex items-center gap-1">
                      <span class="font-bold text-sm text-primary">Admin</span>
                      <span class="text-[10px] bg-tertiary/10 text-tertiary px-1.5 py-0.2 rounded font-bold">एडमिन</span>
                    </div>
                    <span class="text-xs text-on-surface-variant block mt-0.5 leading-snug">
                      Login with <strong>Email &amp; Password</strong>
                    </span>
                  </div>
                </div>
              </button>

            </div>
          </div>

          <!-- Auth Mode Toggle (Login vs Register) -->
          <div class="flex border-b-2 border-outline-variant mb-6">
            <button id="tab-login" onclick="switchAuthMode('login')" type="button" class="tab-btn-active pb-2.5 px-6 text-sm font-bold transition flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px]">login</span>
              <span>Sign In (लॉग इन)</span>
            </button>
            <button id="tab-register" onclick="switchAuthMode('register')" type="button" class="text-on-surface-variant pb-2.5 px-6 text-sm font-medium hover:text-primary transition flex items-center gap-2">
              <span class="material-symbols-outlined text-[18px]">person_add</span>
              <span id="register-tab-title">New Registration (नया पंजीकरण)</span>
            </button>
          </div>

          <!-- Alert / Toast Container -->
          <div id="auth-alert" class="hidden mb-6 p-4 rounded-xl text-xs flex items-start gap-3">
            <span id="alert-icon" class="material-symbols-outlined text-[20px] shrink-0 mt-0.5">info</span>
            <div id="alert-text" class="flex-grow font-medium leading-relaxed"></div>
          </div>

          <!-- ============================================== -->
          <!-- FORM SECTION (CONNECTED TO PHP & MYSQL)        -->
          <!-- ============================================== -->
          <form id="auth-form" onsubmit="handleAuthSubmit(event)" novalidate class="space-y-5">

            <!-- Dynamic Role Description Banner -->
            <div id="role-context-box" class="p-3.5 bg-surface-container-low border border-outline-variant rounded-xl flex items-center justify-between text-xs">
              <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-secondary text-[22px]">info</span>
                <div>
                  <span id="role-context-title" class="font-bold text-primary block">Citizen Portal Access</span>
                  <span id="role-context-desc" class="text-on-surface-variant">Required: Valid 10-digit Indian Mobile Number and Password.</span>
                </div>
              </div>
            </div>

            <!-- REGISTRATION-ONLY FIELDS -->
            <div id="register-only-fields" class="hidden space-y-4">
              
              <!-- Citizen / User Register Fields -->
              <div id="user-register-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-on-surface-variant mb-1" for="reg-user-name">
                    Full Name (पूरा नाम) <span class="text-error">*</span>
                  </label>
                  <input type="text" id="reg-user-name" placeholder="e.g. Rajeshwar Oraon" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary"/>
                </div>
                <div>
                  <label class="block text-xs font-bold text-on-surface-variant mb-1" for="reg-user-district">
                    District (ज़िला) <span class="text-error">*</span>
                  </label>
                  <select id="reg-user-district" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary">
                    <option value="Ranchi">Ranchi (राँची)</option>
                    <option value="Dhanbad">Dhanbad (धनबाद)</option>
                    <option value="East Singhbhum">East Singhbhum (पूर्वी सिंहभूम - जमशेदपुर)</option>
                    <option value="Bokaro">Bokaro (बोकारो)</option>
                    <option value="Hazaribagh">Hazaribagh (हजारीबाग)</option>
                    <option value="Deoghar">Deoghar (देवघर)</option>
                    <option value="Dumka">Dumka (दुमका)</option>
                    <option value="Giridih">Giridih (गिरिडीह)</option>
                    <option value="Palamu">Palamu (पलामू)</option>
                    <option value="Ramgarh">Ramgarh (रामगढ़)</option>
                    <option value="Latehar">Latehar (लातेहार)</option>
                    <option value="Khunti">Khunti (खूंटी)</option>
                    <option value="Gumla">Gumla (गुमला)</option>
                    <option value="West Singhbhum">West Singhbhum (पश्चिमी सिंहभूम)</option>
                  </select>
                </div>
              </div>

              <!-- University Register Fields -->
              <div id="uni-register-fields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-on-surface-variant mb-1" for="reg-uni-name">
                    University / College Name (संस्थान का नाम) <span class="text-error">*</span>
                  </label>
                  <input type="text" id="reg-uni-name" placeholder="e.g. Birla Institute of Technology, Mesra" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary"/>
                </div>
                <div>
                  <label class="block text-xs font-bold text-on-surface-variant mb-1" for="reg-uni-aishe">
                    AISHE Code / College ID <span class="text-error">*</span>
                  </label>
                  <input type="text" id="reg-uni-aishe" placeholder="e.g. U-0268 (MoE Registered)" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary"/>
                </div>
                <div>
                  <label class="block text-xs font-bold text-on-surface-variant mb-1" for="reg-uni-dept">
                    Department / Lead Innovation Cell <span class="text-error">*</span>
                  </label>
                  <input type="text" id="reg-uni-dept" placeholder="e.g. Dept of Civil &amp; Environmental Engg" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary"/>
                </div>
                <div>
                  <label class="block text-xs font-bold text-on-surface-variant mb-1" for="reg-uni-faculty">
                    Faculty Coordinator Name <span class="text-error">*</span>
                  </label>
                  <input type="text" id="reg-uni-faculty" placeholder="e.g. Dr. S. K. Verma" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary"/>
                </div>
              </div>

              <!-- Admin Register Fields (Department & Designation removed as requested!) -->
              <div id="admin-register-fields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-on-surface-variant mb-1" for="reg-admin-name">
                    Admin Full Name (नाम) <span class="text-error">*</span>
                  </label>
                  <input type="text" id="reg-admin-name" placeholder="e.g. Amitesh Kumar" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary"/>
                </div>
                <div>
                  <label class="block text-xs font-bold text-on-surface-variant mb-1" for="reg-admin-badge">
                    Admin / Staff ID (एडमिन आईडी) <span class="text-error">*</span>
                  </label>
                  <input type="text" id="reg-admin-badge" placeholder="e.g. ADM-JH-8821" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary"/>
                </div>
              </div>

            </div>

            <!-- PRIMARY CREDENTIAL FIELD 1: Mobile for User OR Email for Admin/University -->
            <div>
              <!-- User Role: Mobile Number -->
              <div id="credential-mobile-group">
                <label class="block text-xs font-bold text-on-surface-variant mb-1" for="auth-mobile">
                  Mobile Number (मोबाइल नंबर) <span class="text-error">*</span>
                </label>
                <div class="relative flex items-center">
                  <div class="absolute left-3 flex items-center gap-1 text-xs font-bold text-primary select-none border-r border-outline-variant pr-2">
                    <span class="text-secondary">🇮🇳</span> +91
                  </div>
                  <input type="tel" id="auth-mobile" maxlength="10" placeholder="9876543210" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg pl-20 pr-4 py-2.5 text-sm font-medium text-on-surface focus:border-primary focus:ring-1 focus:ring-primary placeholder:text-outline"/>
                </div>
                <p class="text-[11px] text-on-surface-variant mt-1">Enter 10-digit registered mobile number.</p>
              </div>

              <!-- University & Admin Role: Email Address -->
              <div id="credential-email-group" class="hidden">
                <label class="block text-xs font-bold text-on-surface-variant mb-1" for="auth-email">
                  Official Email Address (आधिकारिक ईमेल) <span class="text-error">*</span>
                </label>
                <div class="relative flex items-center">
                  <span class="material-symbols-outlined absolute left-3 text-outline text-[20px]">mail</span>
                  <input type="email" id="auth-email" placeholder="official@jharkhand.gov.in or coordinator@bitmesra.ac.in" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg pl-10 pr-4 py-2.5 text-sm font-medium text-on-surface focus:border-primary focus:ring-1 focus:ring-primary placeholder:text-outline"/>
                </div>
                <p id="email-helper-text" class="text-[11px] text-on-surface-variant mt-1">
                  Use your registered department or university email.
                </p>
              </div>
            </div>

            <!-- PRIMARY CREDENTIAL FIELD 2: Password (Required for all) -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-bold text-on-surface-variant" for="auth-password">
                  Password (पासवर्ड) <span class="text-error">*</span>
                </label>
                <a href="#" onclick="alert('Password reset instructions will be sent via SMS / Official Email.'); return false;" class="text-[11px] text-secondary hover:underline font-semibold">
                  Forgot Password?
                </a>
              </div>
              <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-3 text-outline text-[20px]">key</span>
                <input type="password" id="auth-password" placeholder="Enter your password" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg pl-10 pr-10 py-2.5 text-sm font-medium text-on-surface focus:border-primary focus:ring-1 focus:ring-primary placeholder:text-outline"/>
                <button type="button" onclick="togglePasswordVisibility('auth-password', 'pwd-toggle-icon')" class="absolute right-3 text-outline hover:text-primary p-1">
                  <span id="pwd-toggle-icon" class="material-symbols-outlined text-[18px]">visibility</span>
                </button>
              </div>
            </div>

            <!-- Confirm Password (Registration Only) -->
            <div id="confirm-password-group" class="hidden">
              <label class="block text-xs font-bold text-on-surface-variant mb-1" for="auth-confirm-password">
                Confirm Password (पासवर्ड की पुष्टि करें) <span class="text-error">*</span>
              </label>
              <div class="relative flex items-center">
                <span class="material-symbols-outlined absolute left-3 text-outline text-[20px]">lock_reset</span>
                <input type="password" id="auth-confirm-password" placeholder="Re-enter your password" class="w-full bg-surface-container-lowest border-2 border-outline-variant rounded-lg pl-10 pr-10 py-2.5 text-sm font-medium text-on-surface focus:border-primary focus:ring-1 focus:ring-primary placeholder:text-outline"/>
                <button type="button" onclick="togglePasswordVisibility('auth-confirm-password', 'confirm-pwd-toggle-icon')" class="absolute right-3 text-outline hover:text-primary p-1">
                  <span id="confirm-pwd-toggle-icon" class="material-symbols-outlined text-[18px]">visibility</span>
                </button>
              </div>
            </div>

            <!-- Remember me & Terms -->
            <div class="flex items-center justify-between text-xs pt-1">
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" id="remember-me" checked class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4"/>
                <span class="text-on-surface-variant">Stay signed in on this civic terminal</span>
              </label>
              <span class="text-outline text-[11px]">Protected by JAP-IT &amp; MySQL</span>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
              <button id="auth-submit-btn" type="submit" class="w-full bg-primary hover:bg-primary-container text-on-primary font-bold py-3.5 px-6 rounded-xl transition duration-150 flex items-center justify-center gap-2 shadow-md hover:shadow-lg active:scale-[0.99]">
                <span id="submit-btn-icon" class="material-symbols-outlined text-[20px]">login</span>
                <span id="submit-btn-text">Sign In as Citizen</span>
              </button>
            </div>

          </form>

          <!-- Pre-filled Demo Credentials Helpers -->
          <div class="mt-8 pt-6 border-t border-outline-variant">
            <span class="block text-xs font-bold text-outline uppercase tracking-wider mb-2">
              Try Preset Demonstration Accounts:
            </span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
              <div class="p-2.5 rounded-lg border border-outline-variant bg-surface-container-low flex flex-col justify-between">
                <div>
                  <span class="font-bold text-primary flex items-center gap-1">
                    <span class="material-symbols-outlined text-[15px] text-primary">person</span> Resident Demo
                  </span>
                  <p class="text-[11px] text-on-surface-variant mt-0.5">Mobile: <code class="font-mono font-bold">9876543210</code></p>
                  <p class="text-[11px] text-on-surface-variant">Pass: <code class="font-mono">citizen123</code></p>
                </div>
                <button type="button" onclick="quickDemoFill('user')" class="mt-2 text-[11px] text-secondary font-bold hover:underline text-left">
                  Use Resident Credentials →
                </button>
              </div>

              <div class="p-2.5 rounded-lg border border-outline-variant bg-surface-container-low flex flex-col justify-between">
                <div>
                  <span class="font-bold text-primary flex items-center gap-1">
                    <span class="material-symbols-outlined text-[15px] text-secondary">school</span> University Demo
                  </span>
                  <p class="text-[11px] text-on-surface-variant mt-0.5">Email: <code class="font-mono font-bold">civic.lab@bitmesra.ac.in</code></p>
                  <p class="text-[11px] text-on-surface-variant">Pass: <code class="font-mono">bitmesra123</code></p>
                </div>
                <button type="button" onclick="quickDemoFill('university')" class="mt-2 text-[11px] text-secondary font-bold hover:underline text-left">
                  Use University Credentials →
                </button>
              </div>

              <div class="p-2.5 rounded-lg border-2 border-tertiary/40 bg-tertiary/5 flex flex-col justify-between">
                <div>
                  <span class="font-bold text-tertiary flex items-center gap-1">
                    <span class="material-symbols-outlined text-[15px] text-tertiary">admin_panel_settings</span> Admin Demo
                  </span>
                  <p class="text-[11px] text-on-surface-variant mt-0.5">Email: <code class="font-mono font-bold">admin.scrutiny@jharkhand.gov.in</code></p>
                  <p class="text-[11px] text-on-surface-variant">Pass: <code class="font-mono">admin123</code></p>
                </div>
                <button type="button" onclick="quickDemoFill('admin')" class="mt-2 text-[11px] text-tertiary font-bold hover:underline text-left">
                  Use Admin Credentials →
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </main>

  <!-- 5. Institutional Civic Footer -->
  <footer class="bg-primary text-on-primary py-6 px-4 border-t-4 border-secondary text-xs">
    <div class="max-w-[1200px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-secondary-container text-[24px]">account_balance</span>
        <div>
          <span class="font-bold block">Campus2Community Jharkhand Portal</span>
          <span class="text-surface-container-high text-[11px]">Department of Higher &amp; Technical Education, Government of Jharkhand</span>
        </div>
      </div>
      <div class="flex items-center gap-4 text-surface-container-high text-xs">
        <a href="index.php" class="hover:underline">Home</a>
        <a href="index.php#track-section" class="hover:underline">Track Complaint</a>
        <a href="index.php#how-it-works" class="hover:underline">How It Works</a>
        <span class="text-secondary-container font-mono">v3.0-PHP-MYSQL</span>
      </div>
    </div>
  </footer>

  <!-- ============================================== -->
  <!-- JAVASCRIPT CONNECTING TO PHP AUTH API          -->
  <!-- ============================================== -->
  <script>
    let currentRole = '<?= htmlspecialchars($initialRole) ?>';
    let currentMode = '<?= htmlspecialchars($initialMode) ?>';

    window.addEventListener('DOMContentLoaded', () => {
      switchRole(currentRole);
      switchAuthMode(currentMode);
    });

    function switchRole(role) {
      currentRole = role;

      const roles = ['user', 'university', 'admin'];
      roles.forEach(r => {
        const btn = document.getElementById(`role-btn-${r}`);
        if (r === role) {
          btn.className = "role-card text-left p-4 rounded-xl border-2 border-primary bg-surface-container-low transition group relative shadow-md";
          const iconBox = btn.querySelector('.rounded-lg');
          if (iconBox) iconBox.className = "w-10 h-10 rounded-lg bg-primary text-on-primary flex items-center justify-center shrink-0";
        } else {
          btn.className = "role-card text-left p-4 rounded-xl border-2 border-outline-variant hover:border-primary transition group relative bg-surface-container-lowest";
          const iconBox = btn.querySelector('.rounded-lg');
          if (iconBox) iconBox.className = "w-10 h-10 rounded-lg bg-surface-container text-primary flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-on-primary transition";
        }
      });

      const mobileGroup = document.getElementById('credential-mobile-group');
      const emailGroup = document.getElementById('credential-email-group');
      const emailHelper = document.getElementById('email-helper-text');
      const roleContextTitle = document.getElementById('role-context-title');
      const roleContextDesc = document.getElementById('role-context-desc');
      const registerTabTitle = document.getElementById('register-tab-title');

      if (role === 'user') {
        mobileGroup.classList.remove('hidden');
        emailGroup.classList.add('hidden');
        roleContextTitle.textContent = "Citizen Portal Access (नागरिक)";
        roleContextDesc.textContent = "Required: Valid 10-digit Indian Mobile Number and Password.";
        registerTabTitle.textContent = "Register Citizen (नागरिक पंजीकरण)";
      } else if (role === 'university') {
        mobileGroup.classList.add('hidden');
        emailGroup.classList.remove('hidden');
        emailHelper.textContent = "Use official college email domain (e.g. coordinator@bitmesra.ac.in).";
        roleContextTitle.textContent = "University & Innovation Hub (संस्थान)";
        roleContextDesc.textContent = "Required: Official Institutional Email and Password.";
        registerTabTitle.textContent = "Register University (संस्थान पंजीकरण)";
      } else if (role === 'admin') {
        mobileGroup.classList.add('hidden');
        emailGroup.classList.remove('hidden');
        emailHelper.textContent = "Use registered admin email (e.g. admin.scrutiny@jharkhand.gov.in).";
        roleContextTitle.textContent = "Admin Portal Access (एडमिन)";
        roleContextDesc.textContent = "Required: Admin Email and Password.";
        registerTabTitle.textContent = "Register Admin (एडमिन पंजीकरण)";
      }

      updateRegisterFieldsVisibility();
      updateSubmitButtonText();
    }

    function switchAuthMode(mode) {
      currentMode = mode;
      const tabLogin = document.getElementById('tab-login');
      const tabRegister = document.getElementById('tab-register');
      const regFields = document.getElementById('register-only-fields');
      const confirmPwdGroup = document.getElementById('confirm-password-group');

      if (mode === 'login') {
        tabLogin.className = "tab-btn-active pb-2.5 px-6 text-sm font-bold transition flex items-center gap-2 border-b-2 border-secondary text-primary";
        tabRegister.className = "text-on-surface-variant pb-2.5 px-6 text-sm font-medium hover:text-primary transition flex items-center gap-2 border-b-2 border-transparent";
        regFields.classList.add('hidden');
        confirmPwdGroup.classList.add('hidden');
      } else {
        tabRegister.className = "tab-btn-active pb-2.5 px-6 text-sm font-bold transition flex items-center gap-2 border-b-2 border-secondary text-primary";
        tabLogin.className = "text-on-surface-variant pb-2.5 px-6 text-sm font-medium hover:text-primary transition flex items-center gap-2 border-b-2 border-transparent";
        regFields.classList.remove('hidden');
        confirmPwdGroup.classList.remove('hidden');
      }

      updateRegisterFieldsVisibility();
      updateSubmitButtonText();
    }

    function updateRegisterFieldsVisibility() {
      const userReg = document.getElementById('user-register-fields');
      const uniReg = document.getElementById('uni-register-fields');
      const adminReg = document.getElementById('admin-register-fields');

      if (currentMode === 'register') {
        userReg.classList.toggle('hidden', currentRole !== 'user');
        uniReg.classList.toggle('hidden', currentRole !== 'university');
        adminReg.classList.toggle('hidden', currentRole !== 'admin');
      }
    }

    function updateSubmitButtonText() {
      const btnText = document.getElementById('submit-btn-text');
      const btnIcon = document.getElementById('submit-btn-icon');
      const btn = document.getElementById('auth-submit-btn');

      if (currentMode === 'login') {
        btnIcon.textContent = "login";
        if (currentRole === 'user') {
          btnText.textContent = "Sign In as Citizen";
          btn.className = "w-full bg-primary hover:bg-primary-container text-on-primary font-bold py-3.5 px-6 rounded-xl transition flex items-center justify-center gap-2 shadow-md hover:shadow-lg active:scale-[0.99]";
        } else if (currentRole === 'university') {
          btnText.textContent = "Sign In as University";
          btn.className = "w-full bg-primary hover:bg-primary-container text-on-primary font-bold py-3.5 px-6 rounded-xl transition flex items-center justify-center gap-2 shadow-md hover:shadow-lg active:scale-[0.99]";
        } else {
          btnText.textContent = "Sign In as Admin";
          btn.className = "w-full bg-secondary hover:bg-secondary/90 text-on-secondary font-bold py-3.5 px-6 rounded-xl transition flex items-center justify-center gap-2 shadow-md hover:shadow-lg active:scale-[0.99]";
        }
      } else {
        btnIcon.textContent = "how_to_reg";
        if (currentRole === 'user') {
          btnText.textContent = "Complete Citizen Registration";
          btn.className = "w-full bg-primary hover:bg-primary-container text-on-primary font-bold py-3.5 px-6 rounded-xl transition flex items-center justify-center gap-2 shadow-md hover:shadow-lg active:scale-[0.99]";
        } else if (currentRole === 'university') {
          btnText.textContent = "Register University Account";
          btn.className = "w-full bg-primary hover:bg-primary-container text-on-primary font-bold py-3.5 px-6 rounded-xl transition flex items-center justify-center gap-2 shadow-md hover:shadow-lg active:scale-[0.99]";
        } else {
          btnText.textContent = "Register Admin Account";
          btn.className = "w-full bg-secondary hover:bg-secondary/90 text-on-secondary font-bold py-3.5 px-6 rounded-xl transition flex items-center justify-center gap-2 shadow-md hover:shadow-lg active:scale-[0.99]";
        }
      }
    }

    function togglePasswordVisibility(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
      } else {
        input.type = 'password';
        icon.textContent = 'visibility';
      }
    }

    function quickDemoFill(role) {
      switchRole(role);
      switchAuthMode('login');

      if (role === 'user') {
        document.getElementById('auth-mobile').value = "9876543210";
        document.getElementById('auth-password').value = "citizen123";
        showAlert("Filled Demo Resident credentials (Mobile: 9876543210). Click Sign In to verify with database.", "info");
      } else if (role === 'university') {
        document.getElementById('auth-email').value = "civic.lab@bitmesra.ac.in";
        document.getElementById('auth-password').value = "bitmesra123";
        showAlert("Filled Demo University credentials (Email: civic.lab@bitmesra.ac.in). Click Sign In to verify with database.", "info");
      } else if (role === 'admin') {
        document.getElementById('auth-email').value = "admin.scrutiny@jharkhand.gov.in";
        document.getElementById('auth-password').value = "admin123";
        showAlert("Filled Demo Admin credentials (Email: admin.scrutiny@jharkhand.gov.in). Click Sign In to enter Admin Dashboard.", "info");
      }
    }

    function showAlert(message, type = 'error') {
      const alertBox = document.getElementById('auth-alert');
      const alertIcon = document.getElementById('alert-icon');
      const alertText = document.getElementById('alert-text');

      alertText.innerHTML = message;
      alertBox.classList.remove('hidden');

      if (type === 'error') {
        alertBox.className = "mb-6 p-4 rounded-xl text-xs flex items-start gap-3 bg-error-container text-on-surface border border-error/40";
        alertIcon.className = "material-symbols-outlined text-[20px] shrink-0 text-error";
        alertIcon.textContent = "error";
      } else if (type === 'success') {
        alertBox.className = "mb-6 p-4 rounded-xl text-xs flex items-start gap-3 bg-secondary-container text-on-secondary-container border border-secondary/40";
        alertIcon.className = "material-symbols-outlined text-[20px] shrink-0 text-secondary";
        alertIcon.textContent = "check_circle";
      } else {
        alertBox.className = "mb-6 p-4 rounded-xl text-xs flex items-start gap-3 bg-surface-container-low text-primary border border-outline-variant";
        alertIcon.className = "material-symbols-outlined text-[20px] shrink-0 text-primary";
        alertIcon.textContent = "info";
      }
    }

    // SUBMIT TO PHP BACKEND API
    async function handleAuthSubmit(event) {
      event.preventDefault();

      const password = document.getElementById('auth-password').value.trim();
      if (!password) {
        showAlert("Please enter your password.", "error");
        document.getElementById('auth-password').focus();
        return;
      }

      if (currentMode === 'register') {
        const confirmPassword = document.getElementById('auth-confirm-password').value.trim();
        if (password !== confirmPassword) {
          showAlert("Passwords do not match. Please re-enter.", "error");
          document.getElementById('auth-confirm-password').focus();
          return;
        }
      }

      const submitBtn = document.getElementById('auth-submit-btn');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = `<span class="material-symbols-outlined text-[20px] animate-spin">refresh</span> Processing with Database...`;

      const payload = {
        action: currentMode,
        role: currentRole,
        password: password
      };

      if (currentRole === 'user') {
        payload.mobile = document.getElementById('auth-mobile').value.trim();
        if (currentMode === 'register') {
          payload.name = document.getElementById('reg-user-name').value.trim();
          payload.district = document.getElementById('reg-user-district').value;
        }
      } else {
        payload.email = document.getElementById('auth-email').value.trim();
        if (currentRole === 'university' && currentMode === 'register') {
          payload.name = document.getElementById('reg-uni-name').value.trim();
          payload.aishe_code = document.getElementById('reg-uni-aishe').value.trim();
          payload.department = document.getElementById('reg-uni-dept').value.trim();
          payload.faculty_coordinator = document.getElementById('reg-uni-faculty').value.trim();
        } else if (currentRole === 'admin' && currentMode === 'register') {
          payload.name = document.getElementById('reg-admin-name').value.trim();
          payload.admin_badge = document.getElementById('reg-admin-badge').value.trim();
        }
      }

      try {
        const response = await fetch('api/auth.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await response.json();

        if (result.success) {
          showAlert(`✓ ${result.message}`, 'success');
          // Also set local storage fallback for client consistency
          localStorage.setItem('c2c_auth', JSON.stringify(result.user));
          setTimeout(() => {
            window.location.href = result.redirect || 'index.php';
          }, 1000);
        } else {
          showAlert(result.message || 'Authentication failed. Please verify credentials.', 'error');
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      } catch (err) {
        console.error(err);
        showAlert("Server connection failed. Make sure PHP and MySQL are running.", 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    }
  </script>
</body>
</html>
