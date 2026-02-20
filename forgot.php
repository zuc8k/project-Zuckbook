<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en" id="htmlTag">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ZuckBook | Forgot Password</title>

<style>
body{
    margin:0;
    font-family:system-ui,-apple-system,sans-serif;
    background:linear-gradient(135deg,#0f0f0f,#18191a);
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
    color:#fff;
}

.container{
    width:380px;
    background:#1c1e21;
    padding:35px;
    border-radius:16px;
    box-shadow:0 20px 40px rgba(0,0,0,0.6);
    position:relative;
    animation:fadeIn .4s ease;
}

@keyframes fadeIn{
    from{opacity:0;transform:translateY(10px)}
    to{opacity:1;transform:translateY(0)}
}

.lang-switch{
    position:absolute;
    top:15px;
    right:15px;
    color:#1877f2;
    font-weight:bold;
    cursor:pointer;
    font-size:14px;
}

h2{
    text-align:center;
    margin-bottom:20px;
}

input{
    width:100%;
    padding:13px;
    margin-bottom:14px;
    border:none;
    border-radius:10px;
    background:#2a2b2f;
    color:white;
    font-size:14px;
    transition:.2s;
}

input:focus{
    outline:none;
    background:#33343a;
    box-shadow:0 0 0 2px #1877f2;
}

button{
    width:100%;
    padding:13px;
    border:none;
    border-radius:10px;
    background:#1877f2;
    color:white;
    font-weight:600;
    cursor:pointer;
    transition:.2s;
    position:relative;
}

button:hover{
    background:#166fe5;
}

button.loading{
    opacity:.7;
    pointer-events:none;
}

.spinner{
    width:16px;
    height:16px;
    border:2px solid rgba(255,255,255,.4);
    border-top:2px solid #fff;
    border-radius:50%;
    animation:spin .8s linear infinite;
    display:inline-block;
    vertical-align:middle;
}

@keyframes spin{
    to{transform:rotate(360deg);}
}

.back{
    text-align:center;
    margin-top:18px;
    font-size:14px;
}

.back a{
    color:#42b72a;
    text-decoration:none;
    font-weight:bold;
}

.error{
    background:#ff4d4d;
    padding:10px;
    border-radius:8px;
    font-size:13px;
    margin-bottom:12px;
    display:none;
}

.success{
    background:#16a34a;
    padding:10px;
    border-radius:8px;
    font-size:13px;
    margin-bottom:12px;
    display:none;
}
</style>
</head>
<body>

<div class="container">

<div class="lang-switch" onclick="toggleLang()">AR</div>

<h2 data-en="Forgot Your Password"
    data-ar="نسيت كلمة المرور؟">Forgot Your Password</h2>

<div class="error" id="errorBox"></div>
<div class="success" id="successBox"></div>

<form id="forgotForm" action="../backend/create_ticket.php" method="POST" onsubmit="return validateForm()">

    <input type="text" name="username"
           id="username"
           placeholder="Username"
           data-en-placeholder="Username"
           data-ar-placeholder="اسم المستخدم"
           required>

    <input type="email" name="email"
           id="email"
           placeholder="Gmail"
           data-en-placeholder="Gmail"
           data-ar-placeholder="البريد الإلكتروني"
           required>

    <button type="submit" id="submitBtn"
            data-en="Open Support Ticket"
            data-ar="فتح تذكرة دعم">
            Open Support Ticket
    </button>

</form>

<div class="back">
    <a href="index.php"
       data-en="Back to Login"
       data-ar="العودة لتسجيل الدخول">Back to Login</a>
</div>

</div>

<script>

let currentLang = "en";

function toggleLang(){
    currentLang = currentLang === "en" ? "ar" : "en";

    document.querySelector(".lang-switch").innerText =
        currentLang === "en" ? "AR" : "EN";

    document.querySelectorAll("[data-en]").forEach(el=>{
        el.innerText = el.getAttribute("data-"+currentLang);
    });

    document.querySelectorAll("[data-en-placeholder]").forEach(el=>{
        el.placeholder = el.getAttribute("data-"+currentLang+"-placeholder");
    });

    document.getElementById("htmlTag").dir =
        currentLang === "ar" ? "rtl" : "ltr";
}

function validateForm(){

    let username = document.getElementById("username").value.trim();
    let email = document.getElementById("email").value.trim();
    let errorBox = document.getElementById("errorBox");
    let successBox = document.getElementById("successBox");
    let button = document.getElementById("submitBtn");

    errorBox.style.display="none";
    successBox.style.display="none";

    let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if(username.length < 3){
        showError(currentLang === "en"
            ? "Username must be at least 3 characters"
            : "اسم المستخدم يجب أن يكون 3 أحرف على الأقل");
        return false;
    }

    if(!emailRegex.test(email)){
        showError(currentLang === "en"
            ? "Please enter a valid email address"
            : "يرجى إدخال بريد إلكتروني صحيح");
        return false;
    }

    button.classList.add("loading");
    button.innerHTML = '<span class="spinner"></span>';

    successBox.style.display="block";
    successBox.innerText = currentLang === "en"
        ? "Submitting your request..."
        : "جاري إرسال الطلب...";

    return true;
}

function showError(message){
    let errorBox = document.getElementById("errorBox");
    errorBox.style.display="block";
    errorBox.innerText = message;
}

</script>

</body>
</html>