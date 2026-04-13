<?php
session_start();
include("config.php");
include_once("system_helpers.php");
ensureComplaintWorkflowSchema($conn);
include("notification_helpers.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: login.html');
    exit;
}

$studentId = $_SESSION['user_id'];
$notificationCount = getNotificationCount($conn, 'student', $studentId);
$welcomeName = isset($_SESSION['name']) ? trim((string) $_SESSION['name']) : 'Student';
$welcomeShortName = $welcomeName !== '' ? strtok($welcomeName, ' ') : 'Student';
$complaintSql = "SELECT complaint_id, title, status, completion_date FROM complaints WHERE student_id = ? AND deleted_by_student = 0 ORDER BY complaint_id DESC";
$complaintStmt = mysqli_prepare($conn, $complaintSql);
$complaints = [];
if ($complaintStmt) {
    mysqli_stmt_bind_param($complaintStmt, 'i', $studentId);
    mysqli_stmt_execute($complaintStmt);
    $complaintResult = mysqli_stmt_get_result($complaintStmt);
    if ($complaintResult) {
        $complaints = mysqli_fetch_all($complaintResult, MYSQLI_ASSOC);
    }
    mysqli_stmt_close($complaintStmt);
}

$gatepassSql = "SELECT gatepass_id, date_going AS request_date, status FROM gate_pass WHERE student_id = ? AND deleted_by_student = 0 ORDER BY gatepass_id DESC";
$gatepassStmt = mysqli_prepare($conn, $gatepassSql);
$gatepasses = [];
if ($gatepassStmt) {
    mysqli_stmt_bind_param($gatepassStmt, 'i', $studentId);
    mysqli_stmt_execute($gatepassStmt);
    $gatepassResult = mysqli_stmt_get_result($gatepassStmt);
    if ($gatepassResult) {
        $gatepasses = mysqli_fetch_all($gatepassResult, MYSQLI_ASSOC);
    }
    mysqli_stmt_close($gatepassStmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
     <style>
/* ===== Dashboard Card - Matching Digital Trend Theme ===== */

.dashboard-card {
    background: linear-gradient(135deg, #e6f4f7, #d4eef3);
    border-radius: 14px;
    padding: 25px;
    border: none;
    color: var(--title-color);
    transition: 0.3s ease;
    box-shadow: 0 8px 25px rgba(5, 122, 141, 0.08);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.dashboard-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 35px rgba(5, 122, 141, 0.15);
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.08);
}

/* Section Titles */
.section-title {
    color: var(--primary-color);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Button Matching Theme */
.theme-btn {
    background: var(--primary-color);
    color: var(--white-color);
    border-radius: var(--border-radius-large);
    padding: 6px 16px;
    font-size: 14px;
    transition: 0.3s ease;
}

.theme-btn:hover {
    background: var(--white-color);
    color: var(--dark-color);
}

/* Search Bar */
.search-bar {
    background: var(--project-bg);
    border: 1px solid #dbe7ec;
    border-radius: 8px;
    color: var(--dark-color);
}

.search-bar::placeholder {
    color: var(--gray-color);
}


/* List Items */
.list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    margin-bottom: 14px;
    background: var(--project-bg);
    border-radius: 8px;
    transition: 0.3s;
}

.list-item:hover {
    background: #e6f4f7;
}

/* Badge Colors - Theme Based */

.badge-warning {
    background-color: var(--white-color) !important;
    color: var(--dark-color);
}

.badge-info {
    background-color: #17a2b8 !important;
    color: #fff;
}

.badge-success {
    background-color: #28a745 !important;
}

.badge-danger {
    background-color: #dc3545 !important;
}

/* Global scrollbar styling */
html, body {
    scrollbar-width: thin;
    scrollbar-color: rgba(23, 162, 184, 0.75) rgba(0,0,0,0.08);
}

html, body, .hero {
    min-height: 100%;
    overflow-y: auto;
}

/* Scrollbar for complaints and gatepass lists */
#complaintsContainer, #gatepassContainer {
    max-height: 320px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 4px;
    /* Force scrollbar presence when needed */
    scrollbar-width: thin;
}

#complaintsContainer::-webkit-scrollbar, #gatepassContainer::-webkit-scrollbar,
html::-webkit-scrollbar, body::-webkit-scrollbar {
    width: 10px;
}

#complaintsContainer::-webkit-scrollbar-thumb, #gatepassContainer::-webkit-scrollbar-thumb,
html::-webkit-scrollbar-thumb, body::-webkit-scrollbar-thumb {
    background: rgba(23, 162, 184, 0.75);
    border-radius: 10px;
    border: 2px solid rgba(255,255,255,0.25);
}

#complaintsContainer::-webkit-scrollbar-track, #gatepassContainer::-webkit-scrollbar-track,
html::-webkit-scrollbar-track, body::-webkit-scrollbar-track {
    background: rgba(0,0,0,0.05);
}

.notification-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 7px;
    margin-left: 6px;
    border-radius: 999px;
    background: #dc3545;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
}

.welcome-banner {
    margin-bottom: 24px;
    padding: 14px 18px;
    border-radius: 18px;
    background: linear-gradient(135deg, #ffffff, #eef8fb);
    color: #0f5f6b;
    border: 1px solid #cfe6ec;
    box-shadow: 0 12px 28px rgba(5, 122, 141, 0.10);
}

.welcome-banner h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
}

.welcome-banner p {
    display: none;
}

</style>
     <title>HostelAssist System</title>
<!--

DIGITAL TREND

https://templatemo.com/tm-538-digital-trend

-->
     <meta charset="UTF-8">
     <meta http-equiv="X-UA-Compatible" content="IE=Edge">
     <meta name="description" content="">
     <meta name="keywords" content="">
     <meta name="author" content="">
     <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

     <link rel="stylesheet" href="css/bootstrap.min.css">
     <link rel="stylesheet" href="css/font-awesome.min.css">
     <link rel="stylesheet" href="css/aos.css">
     <link rel="stylesheet" href="css/owl.carousel.min.css">
     <link rel="stylesheet" href="css/owl.theme.default.min.css">

     <!-- MAIN CSS -->
     <link rel="stylesheet" href="css/templatemo-digital-trend.css">

</head>
<body>
     <!-- MENU BAR -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.html">
              <!-- <i class="fa fa-line-chart"></i> -->
              HostelAssist System
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a href="notification.php" class="nav-link smoothScroll">Notification<?php if ($notificationCount > 0) { ?><span class="notification-badge"><?php echo $notificationCount; ?></span><?php } ?></a>
                    </li>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link contact" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<?php if (isset($_SESSION['login_message'])): ?>
<div id="loginMessage" class="alert alert-success" style="margin: 15px auto; max-width: 1200px; border-radius: 8px; animation: slideDown 0.3s ease-in;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <span><?php echo htmlspecialchars($_SESSION['login_message']); ?></span>
        <button type="button" style="background: none; border: none; font-size: 18px; cursor: pointer; color: #155724;" onclick="document.getElementById('loginMessage').remove();">&times;</button>
    </div>
</div>
<style>
@keyframes slideDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    padding: 12px 20px;
}
</style>
<script>
    setTimeout(function() {
        const msg = document.getElementById('loginMessage');
        if (msg) {
            msg.style.animation = 'slideUp 0.3s ease-out forwards';
            setTimeout(function() { msg.remove(); }, 300);
        }
    }, 10000);
    const style = document.createElement('style');
    style.textContent = '@keyframes slideUp { from { transform: translateY(0); opacity: 1; } to { transform: translateY(-20px); opacity: 0; } }';
    document.head.appendChild(style);
</script>
<?php unset($_SESSION['login_message']); endif; ?>

<!-- HERO -->
<section class="hero hero-bg d-flex align-items-center">
<div class="container">
<div class="welcome-banner">
    <h3>Welcome, <?php echo htmlspecialchars($welcomeShortName); ?></h3>
</div>
<div class="row">

<?php // Student-specific data is loaded from prepared queries at top ?>
  <!-- Complaints Card --> 
   <div class="col-lg-6 col-md-12 mb-4">
     <div class="dashboard-card p-4 rounded"> 
      <div class="d-flex justify-content-between align-items-center mb-4"> 
        <h4 class="mb-0 section-title">Complaints</h4> 
        <a href="new-complaint.html" class="btn theme-btn btn-sm">+ New Complaint</a> </div> 
        <input type="text" id="complaintSearch" class="form-control search-bar mb-4" placeholder="Search Complaint ID...">
        <div id="complaintsContainer">
 <?php if (!empty($complaints)) {
   foreach ($complaints as $row) { $status = $row['status'];
  $badgeClass = "badge-warning";
  $statusLower = strtolower(trim($status));
  if ($statusLower === "assigned") {
    $badgeClass = "badge-info text-white";
  } elseif (in_array($statusLower, ["resolved"])) {
    $badgeClass = "badge-success";
  } elseif ($statusLower === "pending") {
    $badgeClass = "badge-warning";
  }
    $completionDateText = '';
    if (in_array($statusLower, ["resolved"]) && !empty($row['completion_date'])) {
      $completionDateText = '<br><small>Completion Date: '.htmlspecialchars(date('d/m/Y', strtotime($row['completion_date']))).'</small>';
    }
    echo ' <a href="complaint_info.php?id='.$row['complaint_id'].'" class="text-decoration-none text-dark"> <div class="list-item" data-search="#'.$row['complaint_id'].' '.$row['title'].'"> <div> <strong>#'.$row['complaint_id'].'</strong> - '.$row['title'].$completionDateText.' </div> <span class="badge '.$badgeClass.'">'.$status.'</span> </div> </a> '; } } 
    else { echo "<p>No complaints found</p>"; } ?>
        </div>
     </div>
     </div>

<!-- Gatepass Card -->
<div class="col-lg-6 col-md-12 mb-4">
          <div class="dashboard-card p-4 rounded">

               <div class="d-flex justify-content-between align-items-center mb-4">
               <h4 class="mb-0 section-title">Gatepass Requests</h4>
               <a href="apply_gatepass.php" class="btn theme-btn btn-sm">+ Apply Gatepass</a>
               </div>

               <!-- Added Search Bar -->
               <input type="text" id="gatepassSearch" class="form-control search-bar mb-4" placeholder="Search Gatepass ID...">
               <div id="gatepassContainer">
               <?php if (!empty($gatepasses)) {
                   foreach ($gatepasses as $entry) {
                       $status = $entry['status'];
                       $badgeClass = 'badge-warning';
                       $statusLower = strtolower(trim($status));
                       if ($statusLower === 'approved') {
                           $badgeClass = 'badge-success';
                       } elseif ($statusLower === 'rejected') {
                           $badgeClass = 'badge-danger';
                       }
                       echo '<a href="view_gatepass.php?id='.$entry['gatepass_id'].'" class="text-decoration-none text-dark">';
                       echo '<div class="list-item" data-search="GP#'.$entry['gatepass_id'].' '.$entry['request_date'].' '.$welcomeName.'">';
                       echo '<div><strong>GP#'.$entry['gatepass_id'].'</strong> - '.$entry['request_date'].'<br><small>'.htmlspecialchars($welcomeName).'</small></div>';
                       echo '<span class="badge '.$badgeClass.'">'.ucfirst($status).'</span>';
                       echo '</div>';
                       echo '</a>';
                   }
               } else {
                   echo '<p>No gatepass requests found</p>';
               } ?>
               </div>

          </div>
          </div>
     </div>
     </div>
     </section>

      <footer class="site-footer">
        <div class="container">
          <div class="row">

            <div class="col-lg-5 mx-lg-auto col-md-8 col-10">
              <h1 class="text-white" data-aos="fade-up" data-aos-delay="100">
                Digital Complaint & Gatepass Management Portal.
              </h1>
            </div>

            <div class="col-lg-3 col-md-6 col-12" data-aos="fade-up" data-aos-delay="200">
              <h4 class="my-4">Contact Info</h4>

              <p class="mb-1">
                <i class="fa fa-phone mr-2 footer-icon"></i> 
                +91 97651 71034
              </p>

              <p>
                <i class="fa fa-envelope mr-2 footer-icon"></i>
                thshinge@git-india.edu.in
              </p>
            </div>

            <div class="col-lg-3 col-md-6 col-12" data-aos="fade-up" data-aos-delay="300">
              <h4 class="my-4">Our Hostel</h4>

              <p class="mb-1">
                <i class="fa fa-home mr-2 footer-icon"></i> 
                Gharda Institute of Technology Ajinkyatara Girls Hostel, Lavel, Khed
                Maharashtra – 415708
              </p>
            </div>

            <div class="col-lg-4 mx-lg-auto col-md-6 col-12" data-aos="fade-up" data-aos-delay="500">
              <ul class="footer-link">
                <li><a href="#">help</a></li>
                <li><a href="#">profile</a></li>
                <li><a href="#">notification</a></li>
              </ul>
            </div>

            <div class="col-lg-3 mx-lg-auto col-md-6 col-12" data-aos="fade-up" data-aos-delay="600">
              <ul class="social-icon">
                <li><a href="#" class="fa fa-instagram"></a></li>
                <li><a href="https://x.com/minthu" class="fa fa-twitter" target="_blank"></a></li>
                <li><a href="#" class="fa fa-dribbble"></a></li>
                <li><a href="#" class="fa fa-behance"></a></li>
              </ul>
            </div>

          </div>
        </div>
      </footer>


     <!-- SCRIPTS -->
     <script>
       // Search functionality for complaints
       const complaintSearch = document.getElementById('complaintSearch');
       if (complaintSearch) {
         complaintSearch.addEventListener('input', function() {
           const query = this.value.toLowerCase();
           const container = document.getElementById('complaintsContainer');
           if (container) {
             const items = container.querySelectorAll('.list-item');
             items.forEach(item => {
               const searchText = item.getAttribute('data-search') || item.innerText;
               if (query === '' || searchText.toLowerCase().includes(query)) {
                 item.parentElement.style.display = '';
               } else {
                 item.parentElement.style.display = 'none';
               }
             });
           }
         });
       }

       // Search functionality for gatepass
       const gatepassSearch = document.getElementById('gatepassSearch');
       if (gatepassSearch) {
         gatepassSearch.addEventListener('input', function() {
           const query = this.value.toLowerCase();
           const container = document.getElementById('gatepassContainer');
           if (container) {
             const items = container.querySelectorAll('.list-item');
             items.forEach(item => {
               const searchText = item.getAttribute('data-search') || item.innerText;
               if (query === '' || searchText.toLowerCase().includes(query)) {
                 item.style.display = '';
               } else {
                 item.style.display = 'none';
               }
             });
           }
         });
       }
     </script>
     <script src="js/jquery.min.js"></script>
     <script src="js/bootstrap.min.js"></script>
     <script src="js/aos.js"></script>
     <script src="js/owl.carousel.min.js"></script>
     <script src="js/smoothscroll.js"></script>
     <script src="js/custom.js"></script>

</body>
</html>
