<?php
session_start();
include("config.php");
include("notification_helpers.php");
include_once("system_helpers.php");

ensureComplaintWorkflowSchema($conn);

$role = isset($_SESSION['role']) ? strtolower(trim((string) $_SESSION['role'])) : '';
$hodDepartment = isset($_SESSION['department']) ? trim((string) $_SESSION['department']) : '';
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$welcomeName = isset($_SESSION['name']) ? trim((string) $_SESSION['name']) : 'HOD';
$welcomeShortName = $welcomeName !== '' ? strtok($welcomeName, ' ') : 'HOD';

if ($role !== 'hod') {
    header("Location: login.html");
    exit;
}

function hodDashboardBadgeClass($status)
{
    $status = strtolower(trim((string) $status));
    if (in_array($status, ['approved', 'resolved'], true)) {
        return 'badge-success';
    }
    if (in_array($status, ['rejected', 'rejected by hod'], true)) {
        return 'badge-danger';
    }
    if (in_array($status, ['assigned', 'in progress'], true)) {
        return 'badge-info';
    }
    return 'badge-warning';
}

$notificationCount = getNotificationCount($conn, $role, $userId);
$flashMessage = isset($_SESSION['gatepass_flash']) ? $_SESSION['gatepass_flash'] : '';
unset($_SESSION['gatepass_flash']);

$gatepasses = [];
// HOD sees all statuses for gatepasses from their department that were created during
// working hours (Mon–Sat, 10:00–17:00) — i.e., requests that go through the HOD approval path.
$gatepassStmt = $conn->prepare(
    "SELECT gp.gatepass_id, gp.date_going AS request_date, gp.status, s.name
     FROM gate_pass gp
     JOIN students s ON gp.student_id = s.student_id
     WHERE LOWER(TRIM(s.department)) = LOWER(TRIM(?))
       AND DAYOFWEEK(gp.issue_date) BETWEEN 2 AND 7
       AND TIME(gp.issue_time) >= '10:00:00'
       AND TIME(gp.issue_time) <= '17:00:00'
       AND gp.deleted_by_hod = 0
     ORDER BY gp.gatepass_id DESC"
);
if ($gatepassStmt) {
    $gatepassStmt->bind_param("s", $hodDepartment);
    $gatepassStmt->execute();
    $gatepassResult = $gatepassStmt->get_result();
    if ($gatepassResult) {
        $gatepasses = $gatepassResult->fetch_all(MYSQLI_ASSOC);
    }
    $gatepassStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>HostelAssist System</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/font-awesome.min.css">
<link rel="stylesheet" href="css/aos.css">
<link rel="stylesheet" href="css/owl.carousel.min.css">
<link rel="stylesheet" href="css/owl.theme.default.min.css">
<link rel="stylesheet" href="css/templatemo-digital-trend.css">
<style>
.dashboard-card{background:linear-gradient(135deg,#e6f4f7,#d4eef3);border-radius:14px;padding:25px;box-shadow:0 8px 25px rgba(5,122,141,.08);height:100%}
.section-title{color:var(--primary-color);font-weight:600;text-transform:uppercase;letter-spacing:1px}
.search-bar{background:var(--project-bg);border:1px solid #dbe7ec;border-radius:8px;color:var(--dark-color)}
.toolbar{display:flex;justify-content:flex-end;gap:10px;margin-bottom:12px}
.tool-btn,.report-btn{height:40px;border:none;border-radius:999px;background:#fff;color:#0f5f6b;box-shadow:0 8px 18px rgba(5,122,141,.12);cursor:pointer;padding:0 16px;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;font-size:13px;text-decoration:none!important;white-space:nowrap}
.tool-btn{width:40px;padding:0}
.delete-submit{display:none;width:100%;margin-bottom:14px;border:none;border-radius:12px;background:linear-gradient(135deg,#c0392b,#922b21);color:#fff;padding:11px 18px;font-weight:700;font-size:14px;cursor:pointer;gap:8px;letter-spacing:0.3px;box-shadow:0 4px 14px rgba(192,57,43,.30)}
.delete-mode .delete-submit{display:flex;align-items:center;justify-content:center}
.list-container{max-height:320px;overflow-y:auto;padding-right:4px}
.list-row{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.delete-checkbox{display:none;width:18px;height:18px;min-width:18px;accent-color:#c0392b;cursor:pointer;border-radius:4px;transition:.2s}
.delete-mode .delete-checkbox{display:block}
.item-link{flex:1;text-decoration:none!important;color:#1d3338}
.item-link:hover,.item-link:focus{color:#1d3338!important;text-decoration:none!important}
.list-item{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;background:var(--project-bg);border-radius:8px;transition:.3s}
.list-item:hover{background:#e6f4f7}
.delete-mode .item-link{pointer-events:none}
.badge-warning{background-color:var(--white-color)!important;color:var(--dark-color)}
.badge-info{background-color:#17a2b8!important;color:#fff}
.badge-success{background-color:#28a745!important;color:#fff}
.badge-danger{background-color:#dc3545!important;color:#fff}
.notification-badge{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 7px;margin-left:6px;border-radius:999px;background:#dc3545;color:#fff;font-size:12px;font-weight:700}
.welcome-banner{margin-bottom:24px;padding:14px 18px;border-radius:18px;background:linear-gradient(135deg,#fff,#eef8fb);color:#0f5f6b;border:1px solid #cfe6ec;box-shadow:0 12px 28px rgba(5,122,141,.10)}
.welcome-banner h3{margin:0;font-size:22px;font-weight:700}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.html">HostelAssist System</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a href="notification.php" class="nav-link smoothScroll">Notification<?php if ($notificationCount > 0) { ?><span class="notification-badge"><?php echo $notificationCount; ?></span><?php } ?></a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link contact" onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
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

<section class="hero hero-bg d-flex align-items-center">
<div class="container">
    <div class="welcome-banner"><h3>Welcome, <?php echo htmlspecialchars($welcomeShortName); ?></h3></div>
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-12 mb-4">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0 section-title">Gatepass Requests</h4>
                    <div class="d-flex align-items-center" style="gap:8px;">
                        <a class="report-btn" href="export_report.php?type=gatepasses"><i class="fa fa-download"></i> Report</a>
                        <button type="button" class="tool-btn" data-toggle-delete="hodGatepasses"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
                <input type="text" id="hodGatepassSearch" class="form-control search-bar mb-3" placeholder="Search Gatepass ID...">
                <form method="POST" action="bulk_delete_records.php" id="hodGatepasses">
                    <input type="hidden" name="record_type" value="gatepass">
                    <button type="submit" class="delete-submit" onclick="return confirm('Delete selected gatepasses?');"><i class="fa fa-trash"></i> Delete Selected</button>
                    <div class="list-container">
                        <?php if (!empty($gatepasses)) { foreach ($gatepasses as $entry) {
                            $displayStatus = ucfirst(strtolower(trim((string) $entry['status'])));
                            $badgeClass = hodDashboardBadgeClass($displayStatus);
                            $canDelete = in_array(strtolower(trim((string) $entry['status'])), ['approved', 'rejected'], true);
                            echo '<div class="list-row" data-search="GP#'.$entry['gatepass_id'].' '.$entry['request_date'].' '.$entry['name'].'">';
                            if ($canDelete) {
                                echo '<input class="delete-checkbox" type="checkbox" name="selected_ids[]" value="'.(int) $entry['gatepass_id'].'">';
                            } else {
                                echo '<span class="delete-checkbox" style="visibility:hidden;"></span>';
                            }
                            echo '<a href="view_gatepass.php?id='.(int) $entry['gatepass_id'].'" class="item-link"><div class="list-item"><div><strong>GP#'.(int) $entry['gatepass_id'].'</strong> - '.htmlspecialchars((string) $entry['request_date']).'<br><small>'.htmlspecialchars((string) $entry['name']).'</small></div><span class="badge '.$badgeClass.'">'.htmlspecialchars($displayStatus).'</span></div></a>';
                            echo '</div>';
                        }} else { echo '<p>No gatepass requests found.</p>'; } ?>
                    </div>
                </form>
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
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/aos.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/smoothscroll.js"></script>
<script src="js/custom.js"></script>
<script>
function setupSearch(inputId, formId) {
    const input = document.getElementById(inputId);
    const form = document.getElementById(formId);
    if (!input || !form) return;
    input.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        form.querySelectorAll('.list-row').forEach(function (row) {
            const text = (row.getAttribute('data-search') || '').toLowerCase();
            row.style.display = query === '' || text.includes(query) ? '' : 'none';
        });
    });
}

document.querySelectorAll('[data-toggle-delete]').forEach(function (button) {
    button.addEventListener('click', function () {
        const form = document.getElementById(this.getAttribute('data-toggle-delete'));
        if (form) form.classList.toggle('delete-mode');
    });
});

setupSearch('hodGatepassSearch', 'hodGatepasses');
</script>
<?php if ($flashMessage !== ''): ?><script>alert(<?php echo json_encode($flashMessage); ?>);</script><?php endif; ?>
</body>
</html>
