<?php
session_start();
include("config.php");
include_once("system_helpers.php");

ensureComplaintWorkflowSchema($conn);

$role = isset($_SESSION['role']) ? strtolower(trim((string) $_SESSION['role'])) : '';
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$welcomeName = isset($_SESSION['name']) ? trim((string) $_SESSION['name']) : 'Security';
$welcomeShortName = $welcomeName !== '' ? strtok($welcomeName, ' ') : 'Security';

if ($role !== 'security') {
    header("Location: login.html");
    exit;
}

// Fetch only APPROVED gatepasses
$gatepasses = [];
$gatepassResult = $conn->query("SELECT gp.gatepass_id, gp.date_going AS request_date, gp.time_going, gp.date_return, gp.time_return, gp.reason, gp.location, gp.security_status, gp.submitted_at, gp.returned_at, s.name, s.department, s.room_number, s.mobile, s.parent_mobile1
                                 FROM gate_pass gp
                                 JOIN students s ON gp.student_id = s.student_id
                                 WHERE LOWER(TRIM(gp.status)) = 'approved'
                                 ORDER BY gp.gatepass_id DESC");
if ($gatepassResult) {
    while ($row = $gatepassResult->fetch_assoc()) {
        // Format dates correctly from string or null
        $row['formatted_submitted'] = !empty($row['submitted_at']) ? date('h:i A', strtotime($row['submitted_at'])) : '';
        $row['formatted_returned'] = !empty($row['returned_at']) ? date('h:i A', strtotime($row['returned_at'])) : '';
        $gatepasses[] = $row;
    }
}

function getSecurityBadgeClass($status)
{
    $status = strtolower(trim((string) $status));
    if ($status === 'returned')
        return 'badge-success';
    if ($status === 'submitted')
        return 'badge-info';
    return 'badge-warning'; // pending action
}

function getSecurityDisplayStatus($status)
{
    if (empty($status))
        return 'Waiting';
    return ucfirst(strtolower(trim((string) $status)));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Security Dashboard - HostelAssist</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/templatemo-digital-trend.css">
    <style>
        .dashboard-card {
            background: linear-gradient(135deg, #e6f4f7, #d4eef3);
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(5, 122, 141, .08);
            min-height: 500px;
        }

        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .search-bar {
            background: var(--project-bg);
            border: 1px solid #dbe7ec;
            border-radius: 8px;
            color: var(--dark-color);
        }

        .list-container {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 4px;
        }

        /* Custom Scrollbar for list container */
        .list-container::-webkit-scrollbar {
            width: 8px;
        }

        .list-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .list-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .list-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .list-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
            cursor: pointer;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 16px 20px;
            background: var(--project-bg);
            border-radius: 8px;
            transition: .3s;
            border-left: 4px solid transparent;
        }

        .list-item:hover {
            background: #e6f4f7;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* Status borders */
        .status-waiting {
            border-left-color: #ffc107;
        }

        .status-submitted {
            border-left-color: #17a2b8;
        }

        .status-returned {
            border-left-color: #28a745;
        }

        .badge-warning {
            background-color: var(--white-color) !important;
            color: var(--dark-color);
            border: 1px solid #dee2e6;
        }

        .badge-info {
            background-color: #17a2b8 !important;
            color: #fff;
        }

        .badge-success {
            background-color: #28a745 !important;
            color: #fff;
        }

        .welcome-banner {
            margin-bottom: 24px;
            padding: 14px 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #fff, #eef8fb);
            color: #0f5f6b;
            border: 1px solid #cfe6ec;
            box-shadow: 0 12px 28px rgba(5, 122, 141, .10);
        }

        .welcome-banner h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
        }

        .modal-overlay.active {
            display: flex;
        }

        .gp-modal {
            background: #fff;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal-overlay.active .gp-modal {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fcfd;
            border-radius: 16px 16px 0 0;
        }

        .modal-header h4 {
            margin: 0;
            color: #0f5f6b;
            font-weight: 700;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            line-height: 1;
            color: #aaa;
            cursor: pointer;
            transition: 0.2s;
        }

        .close-btn:hover {
            color: #333;
        }

        .modal-body {
            padding: 25px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            background: #f8fcfd;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #eef6f8;
        }

        .detail-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 15px;
            color: #333;
            font-weight: 500;
            word-break: break-word;
        }

        .detail-full {
            grid-column: 1 / -1;
        }

        .action-area {
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            margin-top: 10px;
        }

        .sec-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: 0.2s;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 5px;
        }

        .btn-submit {
            background: #17a2b8;
        }

        .btn-submit:hover {
            background: #138496;
        }

        .btn-return {
            background: #28a745;
        }

        .btn-return:hover {
            background: #218838;
        }

        .time-record {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #666;
            margin-top: 6px;
        }

        .time-record i {
            color: #17a2b8;
        }

        /* Loader */
        .loader {
            border: 3px solid #f3f3f3;
            border-radius: 50%;
            border-top: 3px solid #17a2b8;
            width: 16px;
            height: 16px;
            -webkit-animation: spin 1s linear infinite;
            animation: spin 1s linear infinite;
            display: none;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.html">HostelAssist System</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"><span
                    class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a href="logout.php" class="nav-link contact"
                            onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero hero-bg d-flex align-items-center" style="min-height: 85vh;">
        <div class="container">
            <div class="welcome-banner">
                <h3>
                    <span>🛡️ Welcome, <?php echo htmlspecialchars($welcomeShortName); ?></span>
                    <span
                        style="font-size: 14px; font-weight: normal; background: #fff; padding: 4px 12px; border-radius: 20px;">Security
                        Portal</span>
                </h3>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10 col-md-12 mb-4">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="mb-0 section-title">Gatepass Log</h4>
                            </div>
                            <a class="report-btn sec-btn btn-submit" style="color:white; text-decoration:none;"
                                href="export_report.php?type=gatepasses"><i class="fa fa-download"></i> Report</a>
                        </div>
                        <input type="text" id="gatepassSearch" class="form-control search-bar mb-3"
                            placeholder="Search by ID, Name or Department...">

                        <div class="list-container" id="gpList">
                            <?php if (!empty($gatepasses)) {
                                foreach ($gatepasses as $entry) {
                                    $secStatusHtml = strtolower(trim((string) $entry['security_status']));
                                    $displayStatus = getSecurityDisplayStatus($secStatusHtml);
                                    $badgeClass = getSecurityBadgeClass($secStatusHtml);

                                    $borderClass = 'status-waiting';
                                    if ($secStatusHtml === 'submitted')
                                        $borderClass = 'status-submitted';
                                    if ($secStatusHtml === 'returned')
                                        $borderClass = 'status-returned';

                                    // Encode data for the JS modal
                                    $jsData = htmlspecialchars(json_encode([
                                        'id' => $entry['gatepass_id'],
                                        'name' => $entry['name'],
                                        'dept' => $entry['department'],
                                        'room' => $entry['room_number'],
                                        'mobile' => $entry['mobile'],
                                        'parent_mobile' => $entry['parent_mobile1'],
                                        'reason' => $entry['reason'],
                                        'location' => $entry['location'],
                                        'date_going' => $entry['request_date'],
                                        'time_going' => $entry['time_going'],
                                        'date_return' => $entry['date_return'],
                                        'time_return' => $entry['time_return'],
                                        'sec_status' => $secStatusHtml,
                                        'sub_time' => $entry['formatted_submitted'],
                                        'ret_time' => $entry['formatted_returned']
                                    ]), ENT_QUOTES, 'UTF-8');
                                    ?>

                                    <div class="list-row" onclick="openModal(this)" data-json="<?php echo $jsData; ?>"
                                        data-search="GP#<?php echo $entry['gatepass_id']; ?> <?php echo htmlspecialchars($entry['name'] . ' ' . $entry['department']); ?>">
                                        <div class="list-item <?php echo $borderClass; ?>"
                                            id="row-<?php echo $entry['gatepass_id']; ?>">
                                            <div style="display: flex; gap: 20px; align-items: center; width: 100%;">
                                                <div style="min-width: 60px;">
                                                    <strong>GP#<?php echo $entry['gatepass_id']; ?></strong></div>
                                                <div style="flex: 1;">
                                                    <div style="font-weight: 600; color: #222;">
                                                        <?php echo htmlspecialchars($entry['name']); ?></div>
                                                    <div style="font-size: 13px; color: #666;">
                                                        <?php echo htmlspecialchars($entry['department']); ?> &bull; Room
                                                        <?php echo htmlspecialchars($entry['room_number']); ?></div>
                                                </div>
                                                <div style="text-align: right; margin-right: 15px;">
                                                    <div id="sub-time-<?php echo $entry['gatepass_id']; ?>"
                                                        style="font-size: 12px; color: #17a2b8; <?php echo empty($entry['formatted_submitted']) ? 'display:none;' : ''; ?>">
                                                        Out: <?php echo $entry['formatted_submitted']; ?></div>
                                                    <div id="ret-time-<?php echo $entry['gatepass_id']; ?>"
                                                        style="font-size: 12px; color: #28a745; <?php echo empty($entry['formatted_returned']) ? 'display:none;' : ''; ?>">
                                                        In: <?php echo $entry['formatted_returned']; ?></div>
                                                </div>
                                                <div>
                                                    <span id="badge-<?php echo $entry['gatepass_id']; ?>"
                                                        class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($displayStatus); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php }
                            } else {
                                echo '<div class="text-center p-4 text-muted border rounded bg-light" style="border-style: dashed !important;">No approved gatepasses to show right now.</div>';
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MODAL -->
    <div class="modal-overlay" id="gpModal" onclick="closeOnOutside(event)">
        <div class="gp-modal">
            <div class="modal-header">
                <h4 id="m_title">Gatepass Details</h4>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-grid">
                    <div class="detail-item detail-full"
                        style="background: linear-gradient(to right, #f8fcfd, #fff); border-left: 4px solid #17a2b8;">
                        <div class="detail-label">Student</div>
                        <div class="detail-value" style="font-size: 18px;" id="m_name">--</div>
                        <div style="font-size: 13px; color: #666; margin-top: 4px;" id="m_course">--</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Student Contact</div>
                        <div class="detail-value" id="m_contact">--</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Parent Contact</div>
                        <div class="detail-value" id="m_parent_contact">--</div>
                    </div>

                    <div class="detail-item detail-full text-danger border-danger" style="background:#fffafb;">
                        <div class="detail-label">Reason for leaving</div>
                        <div class="detail-value" id="m_reason">--</div>
                    </div>

                    <div class="detail-item detail-full">
                        <div class="detail-label">Place of Visit</div>
                        <div class="detail-value" id="m_location">--</div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Leaving</div>
                        <div class="detail-value" style="color: #c0392b;" id="m_leaving">--</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Returning</div>
                        <div class="detail-value" style="color: #27ae60;" id="m_returning">--</div>
                    </div>
                </div>

                <div class="action-area" id="m_action_area">
                    <!-- Action buttons injected via JS based on state -->
                </div>
            </div>
        </div>
    </div>

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
                    <p class="mb-1"><i class="fa fa-phone mr-2 footer-icon"></i> +91 97651 71034</p>
                    <p><i class="fa fa-envelope mr-2 footer-icon"></i> thshinge@git-india.edu.in</p>
                </div>
                <div class="col-lg-3 col-md-6 col-12" data-aos="fade-up" data-aos-delay="300">
                    <h4 class="my-4">Our Hostel</h4>
                    <p class="mb-1"><i class="fa fa-home mr-2 footer-icon"></i> Gharda Institute of Technology
                        Ajinkyatara Girls Hostel, Lavel, Khed Maharashtra – 415708</p>
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

    <script>
        // Search
        document.getElementById('gatepassSearch').addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.list-row').forEach(row => {
                const text = row.getAttribute('data-search').toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });

        let currentGpId = null;
        let activeRowElement = null;

        function formatTime(timeStr) {
            if (!timeStr) return '';
            // Basic formatting from html time to 12hr, backend gives H:i:s
            const [h, m] = timeStr.split(':');
            if (!h || !m) return timeStr;
            const hour = parseInt(h);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${m} ${ampm}`;
        }

        function openModal(rowElem) {
            activeRowElement = rowElem;
            const data = JSON.parse(rowElem.getAttribute('data-json'));
            currentGpId = data.id;

            document.getElementById('m_title').innerText = `Gatepass GP#${data.id}`;
            document.getElementById('m_name').innerText = data.name;
            document.getElementById('m_course').innerText = `${data.dept} • Room ${data.room}`;
            document.getElementById('m_contact').innerText = data.mobile;
            document.getElementById('m_parent_contact').innerText = data.parent_mobile;
            document.getElementById('m_reason').innerText = data.reason;
            document.getElementById('m_location').innerText = data.location;
            document.getElementById('m_leaving').innerText = `${data.date_going} ${formatTime(data.time_going)}`;
            document.getElementById('m_returning').innerText = `${data.date_return} ${formatTime(data.time_return)}`;

            renderActions(data.sec_status, data.sub_time, data.ret_time);

            document.getElementById('gpModal').classList.add('active');
            document.body.style.overflow = 'hidden'; // prevent bg scroll
        }

        function renderActions(status, subTime, retTime) {
            const area = document.getElementById('m_action_area');
            area.innerHTML = '';

            if (status === '' || status === null) {
                area.innerHTML = `<button class="sec-btn btn-submit" id="btn-sub" onclick="updateStatus('submitted')">
            Mark as Submitted (Leaving) <div class="loader" id="ldr-sub"></div>
        </button>`;
            } else if (status === 'submitted') {
                area.innerHTML = `
            <div class="time-record mb-3" style="justify-content: center;"><i class="fa fa-check-circle"></i> Student left at ${subTime}</div>
            <button class="sec-btn btn-return" id="btn-ret" onclick="updateStatus('returned')">
                Mark as Returned <div class="loader" id="ldr-ret"></div>
            </button>
        `;
            } else if (status === 'returned') {
                area.innerHTML = `
            <div style="background: #e9f9ee; padding: 15px; border-radius: 8px; border: 1px solid #c8e6c9;">
                <div style="color: #2e7d32; font-weight: 600; margin-bottom: 5px;"><i class="fa fa-check-circle"></i> Gatepass Complete</div>
                <div class="time-record" style="justify-content: center;">Left: ${subTime} &bull; Returned: ${retTime}</div>
            </div>
        `;
            }
        }

        function closeModal() {
            document.getElementById('gpModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function closeOnOutside(e) {
            if (e.target.id === 'gpModal') closeModal();
        }

        function updateStatus(action) {
            if (!currentGpId) return;

            const btnId = action === 'submitted' ? 'btn-sub' : 'btn-ret';
            const ldrId = action === 'submitted' ? 'ldr-sub' : 'ldr-ret';
            const btn = document.getElementById(btnId);
            const ldr = document.getElementById(ldrId);

            // Disable btn and show loader
            if (btn) btn.disabled = true;
            if (ldr) ldr.style.display = 'inline-block';

            const formData = new FormData();
            formData.append('gatepass_id', currentGpId);
            formData.append('action', action);

            fetch('security_gatepass_action.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the stored JSON data on the row
                        const rowData = JSON.parse(activeRowElement.getAttribute('data-json'));
                        rowData.sec_status = action;
                        if (data.submitted_time) rowData.sub_time = data.submitted_time;
                        if (data.returned_time) rowData.ret_time = data.returned_time;
                        activeRowElement.setAttribute('data-json', JSON.stringify(rowData));

                        // Update UI list row
                        const badge = document.getElementById('badge-' + currentGpId);
                        const listItem = document.getElementById('row-' + currentGpId);
                        const subTimeDiv = document.getElementById('sub-time-' + currentGpId);
                        const retTimeDiv = document.getElementById('ret-time-' + currentGpId);

                        badge.className = 'badge';
                        listItem.className = 'list-item';

                        if (action === 'submitted') {
                            badge.classList.add('badge-info');
                            badge.innerText = 'Submitted';
                            listItem.classList.add('status-submitted');
                            subTimeDiv.innerText = `Out: ${data.submitted_time}`;
                            subTimeDiv.style.display = 'block';
                        } else {
                            badge.classList.add('badge-success');
                            badge.innerText = 'Returned';
                            listItem.classList.add('status-returned');
                            retTimeDiv.innerText = `In: ${data.returned_time}`;
                            retTimeDiv.style.display = 'block';
                        }

                        // Update modal UI instantly
                        renderActions(action, rowData.sub_time, rowData.ret_time);
                    } else {
                        alert("Error: " + data.message);
                        if (btn) btn.disabled = false;
                        if (ldr) ldr.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("A network error occurred. Please try again.");
                    if (btn) btn.disabled = false;
                    if (ldr) ldr.style.display = 'none';
                });
        }
    </script>
</body>

</html>