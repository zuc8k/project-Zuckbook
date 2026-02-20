# 🚀 ZuckBook - Complete Social Network Platform

A fully functional, production-ready social network application built with PHP, MySQL, and Socket.IO for real-time features.

## ✨ Features

- **User Authentication** - Secure login/registration with password hashing
- **Posts & Reactions** - Create posts, like, love, and react with emojis
- **Real-time Messaging** - Socket.IO powered instant messaging
- **Groups & Communities** - Create and manage groups
- **Friend System** - Add friends, manage friend requests
- **Admin Dashboard** - Comprehensive admin panel for user/group management
- **Support Tickets** - Customer support system
- **Coins System** - Virtual currency for rewards
- **User Profiles** - Customizable profiles with cover images
- **Notifications** - Real-time notifications
- **Dark/Light Theme** - User preference support

## 🛠️ Tech Stack

- **Backend**: PHP 8.2
- **Database**: MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript
- **Real-time**: Socket.IO (Node.js)
- **Server**: Apache 2.4
- **Containerization**: Docker & Docker Compose

## 📋 Prerequisites

### Option 1: Docker (Recommended)
- Docker Desktop installed
- Docker Compose installed

### Option 2: XAMPP (Local Development)
- XAMPP with PHP 7.4+
- MySQL 5.7+
- Node.js 14+ (for Socket.IO)

## 🚀 Quick Start

### Using Docker (Recommended)

**Windows:**
```bash
# Double-click START.bat
# Or run in terminal:
START.bat
```

**Linux/Mac:**
```bash
bash START.sh
```

**Manual:**
```bash
docker-compose up -d
```

Then open: **http://localhost:8080**

### Using XAMPP

**Windows:**
```bash
# Double-click RUN_XAMPP.bat
# Or run in terminal:
RUN_XAMPP.bat
```

Then open: **http://localhost:8080**

## 🔐 Database Credentials

```
Username: root
Password: 197520error
Database: zuckbook
Port: 3307 (Docker) or 3307 (XAMPP)
```

## 🌐 Services & Ports

| Service | URL | Port |
|---------|-----|------|
| Application | http://localhost:8080 | 8080 |
| MySQL | localhost:3307 | 3307 |
| Socket.IO | http://localhost:3000 | 3000 |

## 📁 Project Structure

```
ZuckBook/
├── frontend/              # Frontend PHP files
│   ├── index.php         # Login page
│   ├── home.php          # Main feed
│   ├── profile.php       # User profiles
│   ├── chat.php          # Messaging
│   ├── admin.php         # Admin panel
│   └── ...
├── backend/              # Backend API endpoints
│   ├── config.php        # Database configuration
│   ├── login_process.php # Authentication
│   ├── get_posts.php     # Fetch posts
│   ├── send_message.php  # Messaging API
│   └── ...
├── socket-server/        # Real-time server
│   ├── server.js         # Socket.IO server
│   └── package.json
├── ZuckBook/database/    # Database schema
│   └── zuckbook_database.sql
├── docker-compose.yml    # Docker configuration
├── START.bat            # Windows startup script
├── START.sh             # Linux/Mac startup script
└── RUN_XAMPP.bat        # XAMPP startup script
```

## 🔧 Configuration

### Environment Variables (.env)

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=197520error
DB_NAME=zuckbook
DB_PORT=3307

SOCKET_SERVER_URL=http://localhost:3000
SOCKET_SERVER_PORT=3000

APP_URL=http://localhost:8080
APP_ENV=development
```

### Apache Configuration

The application uses URL rewriting to serve the frontend as the document root:
- DocumentRoot: `/var/www/html/frontend`
- Backend API: `/backend/`
- Uploads: `/uploads/`

## 🔒 Security Features

- ✅ Prepared statements (SQL injection prevention)
- ✅ Password hashing (bcrypt)
- ✅ Session security (HTTPOnly, SameSite)
- ✅ CSRF protection
- ✅ Input validation
- ✅ Rate limiting on Socket.IO
- ✅ Security headers

## 📊 Database Schema

### Main Tables

- **users** - User accounts and profiles
- **posts** - User posts
- **post_reactions** - Likes and reactions
- **post_comments** - Comments on posts
- **messages** - Direct messages
- **conversations** - Message conversations
- **groups** - Group information
- **group_members** - Group membership
- **friends** - Friend relationships
- **notifications** - User notifications
- **support_tickets** - Support system

## 🧪 Testing

### Health Check
Visit: **http://localhost/setup-db.php**

This will:
- Check database connection
- Import database schema
- Redirect to application

### Manual Database Import
```bash
# Using MySQL CLI
mysql -h localhost -P 3307 -u root -p197520error < ZuckBook/database/zuckbook_database.sql

# Or use phpMyAdmin (XAMPP only)
http://localhost/phpmyadmin
```

## 📝 API Endpoints

### Authentication
- `POST /backend/login_process.php` - Login
- `POST /backend/register_process.php` - Register
- `GET /backend/logout.php` - Logout

### Posts
- `GET /backend/get_posts.php` - Fetch posts
- `POST /backend/create_post.php` - Create post
- `POST /backend/reaction_post.php` - React to post

### Messaging
- `GET /backend/get_messages.php` - Fetch messages
- `POST /backend/send_message.php` - Send message
- `GET /backend/get_conversations.php` - Fetch conversations

### Users
- `GET /backend/get_user_status.php` - User status
- `POST /backend/add_friend.php` - Add friend
- `POST /backend/accept_friend.php` - Accept friend request

## 🐛 Troubleshooting

### "Connection refused" error
**Solution**: Make sure MySQL is running
- Docker: Services should start automatically
- XAMPP: Start MySQL in XAMPP Control Panel

### "Port 8080 already in use"
**Solution**: Change port in docker-compose.yml or close the app using port 8080

### Database not importing
**Solution**: Visit http://localhost/setup-db.php to manually import

### Socket.IO not connecting
**Solution**: Make sure port 3000 is not blocked by firewall

## 📚 Documentation

- `START_HERE.txt` - Quick start guide
- `SETUP_GUIDE.md` - Detailed setup instructions
- `docker-compose.yml` - Docker configuration
- `.env.example` - Environment variables template

## 🚀 Deployment

### Docker Production
```bash
# Build and push to registry
docker build -t zuckbook:latest .
docker push your-registry/zuckbook:latest

# Deploy
docker-compose -f docker-compose.yml up -d
```

### XAMPP Production
1. Copy project to `C:\xampp\htdocs\zuckbook`
2. Configure Apache virtual host
3. Import database
4. Update `.env` with production credentials
5. Start Apache and MySQL

## 📞 Support

For issues or questions:
1. Check `START_HERE.txt` for quick solutions
2. Review `SETUP_GUIDE.md` for detailed instructions
3. Check Docker logs: `docker-compose logs -f`
4. Check PHP error logs in Apache

## 📄 License

This project is provided as-is for educational and development purposes.

## ✅ What's Been Fixed

- ✅ All PHP errors resolved
- ✅ SQL injection vulnerabilities patched
- ✅ All paths corrected (absolute URLs)
- ✅ Socket.IO integrated for real-time features
- ✅ Database connection configured
- ✅ Session security hardened
- ✅ CSRF protection added
- ✅ Docker setup complete
- ✅ XAMPP configuration ready
- ✅ Apache rewrite rules configured

## 🎯 Next Steps

1. Start the application using one of the startup scripts
2. Create a user account
3. Explore the features
4. Check the admin panel (if you have admin role)
5. Customize as needed

---

**ZuckBook - Ready t