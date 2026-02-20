<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$user_id = intval($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messenger</title>

<style>
body{
    margin:0;
    font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto;
    background:#000;
    color:#fff;
}

/* HEADER */
.header{
    padding:18px 16px;
    font-size:22px;
    font-weight:600;
    background:#000;
    position:sticky;
    top:0;
    z-index:10;
    border-bottom:1px solid #111;
}

/* SEARCH */
.search-box{
    padding:12px 15px;
}

.search-box input{
    width:100%;
    padding:12px 16px;
    border-radius:25px;
    border:none;
    background:#1c1c1e;
    color:#fff;
    outline:none;
    font-size:14px;
}

/* CHAT ITEM */
.chat-item{
    display:flex;
    align-items:center;
    padding:14px 15px;
    border-bottom:1px solid #111;
    position:relative;
    transition:transform .25s ease, background .2s ease;
    background:#000;
}

.chat-item.active{
    background:#111;
}

.avatar-wrapper{
    position:relative;
    margin-right:14px;
}

.avatar{
    width:55px;
    height:55px;
    border-radius:50%;
    object-fit:cover;
}

.online-dot{
    position:absolute;
    width:11px;
    height:11px;
    background:#31d158;
    border-radius:50%;
    bottom:3px;
    right:3px;
    border:2px solid #000;
}

.chat-info{
    flex:1;
    overflow:hidden;
}

.chat-name{
    font-weight:600;
    font-size:16px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.chat-last{
    font-size:14px;
    color:#aaa;
    margin-top:3px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.unread{
    background:#1877f2;
    font-size:12px;
    padding:5px 9px;
    border-radius:20px;
    min-width:22px;
    text-align:center;
}

/* POPUP */
.popup{
    position:fixed;
    bottom:-100%;
    left:0;
    right:0;
    background:#1c1c1e;
    padding:20px;
    border-radius:20px 20px 0 0;
    transition:.3s ease;
}

.popup.show{
    bottom:0;
}

.popup button{
    width:100%;
    padding:14px;
    margin:8px 0;
    border:none;
    border-radius:12px;
    background:#2c2c2e;
    color:white;
    font-size:15px;
}

.popup button:hover{
    background:#3a3a3c;
}
</style>
</head>
<body>

<div class="header">Chats</div>

<div class="search-box">
    <input type="text" id="searchInput" placeholder="Search chats...">
</div>

<div id="chat-list"></div>

<div id="popup" class="popup">
    <button onclick="muteChat()">Mute Conversation</button>
    <button onclick="blockUser()">Block User</button>
    <button onclick="closePopup()">Cancel</button>
</div>

<script>

let currentUser = <?php echo $user_id; ?>;
let selectedConversation = null;
let startX = 0;
let currentEl = null;
let pressTimer = null;
let isSwiping = false;

/* ================= LOAD ================= */

function loadConversations(){

fetch("../backend/get_conversations.php")
.then(res=>res.json())
.then(data=>{

let container=document.getElementById("chat-list");
container.innerHTML="";

data.forEach(chat=>{

let unread = chat.unread>0 ?
`<div class="unread">${chat.unread}</div>`:"";

let avatar = chat.profile_image ?
"../uploads/"+chat.profile_image :
"../assets/zuckuser.png";

container.innerHTML += `
<div class="chat-item"
ontouchstart="touchStart(event,this,${chat.conversation_id})"
ontouchmove="touchMove(event)"
ontouchend="touchEnd(${chat.conversation_id})"
onclick="handleClick(${chat.conversation_id})">

<div class="avatar-wrapper">
<img src="${avatar}" class="avatar">
${chat.is_online?`<div class="online-dot"></div>`:""}
</div>

<div class="chat-info">
<div class="chat-name">${chat.name}</div>
<div class="chat-last">${chat.last_message || ''}</div>
</div>

${unread}

</div>
`;
});

});
}

/* ================= TOUCH ================= */

function touchStart(e,el,id){
startX=e.touches[0].clientX;
currentEl=el;
selectedConversation=id;
isSwiping=false;

pressTimer=setTimeout(()=>{
openPopup();
},600);
}

function touchMove(e){
let diff=e.touches[0].clientX-startX;

if(Math.abs(diff)>10){
isSwiping=true;
clearTimeout(pressTimer);
}

currentEl.style.transform="translateX("+diff+"px)";
}

function touchEnd(id){

clearTimeout(pressTimer);

let transformValue=parseInt(currentEl.style.transform.replace(/[^\-0-9]/g,''))||0;

if(transformValue>100){
pinChat(id);
}
else if(transformValue<-100){
archiveChat(id);
}

currentEl.style.transform="translateX(0px)";
}

/* ================= CLICK ================= */

function handleClick(id){
if(isSwiping) return;
window.location="chat.php?conversation_id="+id;
}

/* ================= ACTIONS ================= */

function pinChat(id){
fetch("../backend/pin_chat.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"conversation_id="+id
}).then(()=>loadConversations());
}

function archiveChat(id){
fetch("../backend/archive_chat.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"conversation_id="+id
}).then(()=>loadConversations());
}

function muteChat(){
fetch("../backend/mute_chat.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"conversation_id="+selectedConversation
}).then(()=>{
closePopup();
loadConversations();
});
}

function blockUser(){
fetch("../backend/block_user.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"conversation_id="+selectedConversation
}).then(()=>{
closePopup();
loadConversations();
});
}

/* ================= POPUP ================= */

function openPopup(){
document.getElementById("popup").classList.add("show");
}

function closePopup(){
document.getElementById("popup").classList.remove("show");
}

/* ================= SEARCH ================= */

document.getElementById("searchInput")
.addEventListener("input",function(){

let filter=this.value.toLowerCase();
document.querySelectorAll(".chat-item").forEach(item=>{
let name=item.querySelector(".chat-name").innerText.toLowerCase();
item.style.display=name.includes(filter)?"flex":"none";
});
});

/* ================= AUTO REFRESH ================= */

loadConversations();
setInterval(loadConversations,5000);

</script>
</body>
</html>