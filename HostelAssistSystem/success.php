<?php
$id = $_GET['id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Success</title>

<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #177e89, #0f5f6b);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .card {
        background: white;
        padding: 40px;
        width: 380px;
        text-align: center;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        animation: popup 0.3s ease;
    }

    @keyframes popup {
        from { transform: scale(0.7); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .check {
        font-size: 60px;
        color: #177e89;
        margin-bottom: 10px;
    }

    h2 {
        margin: 10px 0;
        color: #333;
    }

    .id-box {
        background: #f0fbfc;
        padding: 12px;
        border-radius: 10px;
        margin: 15px 0;
        font-weight: bold;
        color: #177e89;
    }

    button {
        margin-top: 15px;
        padding: 12px 20px;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg, #177e89, #0f5f6b);
        color: white;
        font-size: 14px;
        cursor: pointer;
        transition: 0.3s;
    }

    button:hover {
        transform: translateY(-2px);
    }
</style>
</head>

<body>

<div class="card">
    <div class="check">✔</div>
    <h2>Complaint Submitted</h2>

    <div class="id-box">
        Complaint ID: #<?php echo $id; ?>
    </div>

    <p>Your complaint has been successfully registered.</p>

    <a href="student_home.php">
        <button>Back to Dashboard</button>
    </a>
</div>

</body>
</html>
