const http = require("http");
const { Server } = require("socket.io");

const server = http.createServer();

const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

/* ======================================
   MEMORY STORES
====================================== */

// userId => Set(socketIds)
const onlineUsers = new Map();

// socketId => userId
const socketUsers = new Map();

// anti-spam
const messageRateLimit = new Map();

/* ======================================
   HELPERS
====================================== */

function addOnlineUser(userId, socketId) {

    if (!onlineUsers.has(userId)) {
        onlineUsers.set(userId, new Set());
    }

    onlineUsers.get(userId).add(socketId);
}

function removeOnlineUser(userId, socketId) {

    if (!onlineUsers.has(userId)) return;

    const sockets = onlineUsers.get(userId);
    sockets.delete(socketId);

    if (sockets.size === 0) {
        onlineUsers.delete(userId);
        io.emit("presence_update", {
            userId,
            status: "offline"
        });
    }
}

function isRateLimited(userId) {

    const now = Date.now();
    const windowMs = 2000; // 2 sec
    const maxMsgs = 5;

    if (!messageRateLimit.has(userId)) {
        messageRateLimit.set(userId, []);
    }

    const timestamps = messageRateLimit.get(userId)
        .filter(ts => now - ts < windowMs);

    if (timestamps.length >= maxMsgs) {
        return true;
    }

    timestamps.push(now);
    messageRateLimit.set(userId, timestamps);

    return false;
}

/* ======================================
   CONNECTION
====================================== */

io.on("connection", (socket) => {

    console.log("Connected:", socket.id);

    /* ======================================
       AUTH USER
    ====================================== */
    socket.on("join_user", (userId) => {

        userId = parseInt(userId);
        if (!userId) return;

        socketUsers.set(socket.id, userId);
        addOnlineUser(userId, socket.id);

        socket.join("user_" + userId);

        io.emit("presence_update", {
            userId,
            status: "online"
        });
    });

    /* ======================================
       JOIN GROUP
    ====================================== */
    socket.on("join_group", (groupId) => {
        socket.join("group_" + parseInt(groupId));
    });

    /* ======================================
       GROUP POST
    ====================================== */
    socket.on("new_group_post", (data) => {

        io.to("group_" + data.group_id)
            .emit("group_post_created", data);
    });

    /* ======================================
       REACTIONS
    ====================================== */
    socket.on("reaction_update", (data) => {

        io.to(data.room).emit("reaction_update", {
            post_id: data.post_id,
            type: data.type,
            total: data.total
        });
    });

    /* ======================================
       JOIN CONVERSATION
    ====================================== */
    socket.on("join_conversation", (conversationId) => {
        socket.join("conversation_" + parseInt(conversationId));
    });

    /* ======================================
       SEND MESSAGE
    ====================================== */
    socket.on("send_message", (data, callback) => {

        const userId = socketUsers.get(socket.id);
        if (!userId) return;

        if (isRateLimited(userId)) {
            return callback?.({ status: "rate_limited" });
        }

        const room = "conversation_" + data.conversation_id;

        const messagePayload = {
            conversation_id: data.conversation_id,
            sender_id: userId, // secure
            message: data.message,
            created_at: new Date()
        };

        io.to(room).emit("receive_message", messagePayload);

        callback?.({ status: "sent" });
    });

    /* ======================================
       TYPING
    ====================================== */
    socket.on("typing", (data) => {

        const userId = socketUsers.get(socket.id);
        if (!userId) return;

        socket.to("conversation_" + data.conversation_id)
            .emit("typing", {
                userId
            });
    });

    socket.on("stop_typing", (data) => {

        const userId = socketUsers.get(socket.id);
        if (!userId) return;

        socket.to("conversation_" + data.conversation_id)
            .emit("stop_typing", {
                userId
            });
    });

    /* ======================================
       SEEN
    ====================================== */
    socket.on("message_seen", (data) => {

        const userId = socketUsers.get(socket.id);
        if (!userId) return;

        io.to("conversation_" + data.conversation_id)
            .emit("message_seen", {
                message_id: data.message_id,
                seen_by: userId
            });
    });

    /* ======================================
       CALL SYSTEM
    ====================================== */
    socket.on("callUser", (data) => {

        io.to("user_" + data.to)
            .emit("incomingCall", {
                from: socketUsers.get(socket.id),
                signal: data.signal
            });
    });

    socket.on("answerCall", (data) => {

        io.to("user_" + data.to)
            .emit("callAccepted", data.signal);
    });

    /* ======================================
       DISCONNECT
    ====================================== */
    socket.on("disconnect", () => {

        const userId = socketUsers.get(socket.id);

        if (userId) {
            removeOnlineUser(userId, socket.id);
            socketUsers.delete(socket.id);
        }

        console.log("Disconnected:", socket.id);
    });

});

/* ======================================
   START SERVER
====================================== */

server.listen(3000, () => {
    console.log("ZuckBook Socket Server running on port 3000");
});