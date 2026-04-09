<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Forgot Password - HostelAssist</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/templatemo-digital-trend.css">
    <style>
        .login-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 18px;
            padding: 35px 30px;
            box-shadow: 0 15px 40px rgba(5, 122, 141, .15);
            max-width: 480px;
            margin: auto;
            border: 1px solid rgba(15, 95, 107, 0.1);
        }
        .user-select {
            display: flex;
            gap: 8px;
            margin-bottom: 25px;
        }
        .user-box {
            cursor: pointer;
            padding: 10px 4px;
            border-radius: 10px;
            text-align: center;
            width: 25%;
            border: 1px solid #ccc;
            transition: 0.3s;
            font-size: 13px;
        }
        .user-box.active {
            background: linear-gradient(135deg, #17a2b8, #0f5f6b);
            color: #fff;
            border-color: #0f5f6b;
            box-shadow: 0 8px 15px rgba(23, 162, 184, 0.3);
            font-weight: bold;
        }
        .user-box .icon {
            display: block;
            font-size: 20px;
            margin-bottom: 3px;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f7fbfc, #d5edf1); min-height: 100vh; display: flex; align-items: center; justify-content: center;">

<div class="container">
    <div class="row align-items-center">
        <div class="col-12">
            <div class="login-card">
                <div class="text-center mb-4">
                    <h3 style="color: #0f5f6b; font-weight: 700; margin-bottom: 5px;">Forgot Password</h3>
                    <p style="color: #666; font-size: 14px;">Select your account type and enter your registered email address to receive an OTP on that email.</p>
                </div>

                <div class="user-select">
                    <div id="student" class="user-box active" onclick="selectUser('student')"><span class="icon">&#127891;</span>Student</div>
                    <div id="faculty" class="user-box" onclick="selectUser('faculty')"><span class="icon">&#128105;&#8205;&#127979;</span>Faculty</div>
                    <div id="admin" class="user-box" onclick="selectUser('admin')"><span class="icon">&#128737;</span>Admin</div>
                    <div id="security" class="user-box" onclick="selectUser('security')"><span class="icon">&#128110;&#8205;&#9794;&#65039;</span>Security</div>
                </div>

                <form method="post" id="forgotForm">
                    <input type="hidden" name="user_type" id="userType" value="student">
                    
                    <div id="statusMessage" style="display:none; padding:10px; border-radius:8px; margin-bottom:15px; font-size:14px; text-align:center;"></div>

                    <div class="form-group mb-4">
                        <label>Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" style="border-radius: 10px; height: 48px;" placeholder="Enter your registered email" required>
                    </div>

                    <button type="submit" id="submitBtn" class="btn btn-block" style="background: linear-gradient(135deg, #17a2b8, #0f5f6b); color: white; height: 50px; border-radius: 12px; font-weight: 600; font-size: 16px;">
                        Send OTP Code
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="login.html" style="color: #17a2b8; text-decoration: none; font-weight: 500;">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectUser(type) {
    document.getElementById('student').classList.remove('active');
    document.getElementById('faculty').classList.remove('active');
    document.getElementById('admin').classList.remove('active');
    document.getElementById('security').classList.remove('active');
    document.getElementById(type).classList.add('active');

    document.getElementById('userType').value = type;
}

document.getElementById('forgotForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    const msg = document.getElementById('statusMessage');
    
    btn.disabled = true;
    btn.innerText = 'Sending... Please wait';
    msg.style.display = 'none';

    const formData = new FormData(this);

    fetch('send_otp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            window.location.href = 'verify_otp.php';
        } else {
            msg.style.display = 'block';
            msg.style.background = '#fff1f1';
            msg.style.color = '#9d2b2b';
            msg.innerText = data.message;
            btn.disabled = false;
            btn.innerText = 'Send OTP Code';
        }
    })
    .catch(err => {
        msg.style.display = 'block';
        msg.style.background = '#fff1f1';
        msg.style.color = '#9d2b2b';
        msg.innerText = 'Network error. Try again.';
        btn.disabled = false;
        btn.innerText = 'Send OTP Code';
    });
});
</script>

</body>
</html>
