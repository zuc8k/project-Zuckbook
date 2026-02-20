# ZuckBook - Complete Setup Guide

## Project Overview
ZuckBook is a full-stack social media application with:
- **Frontend**: PHP-based web interface
- **Backend**: PHP REST API
- **Database**: MySQL 8.0
- **Real-time**: Node.js Socket.io server
- **Containerization**: Docker & Docker Compose

## Prerequisites

### Option 1: Docker (Recommended)
- Docker Desktop installed
- Docker Compose installed
- 4GB RAM minimum
- 2GB free disk space

### Option 2: XAMPP (Local Development)
- XAMPP with PHP 8.2+
- MySQL 8.0
- Node.js 18+

---

## Quick Start (Docker - Recommended)

### Windows
```bash
START.bat
```

### Linux/Mac
```bash
chmod +x START.sh
./START.sh
```

Then open: **http://localhost:8080**

---

## Manual Setup (Docker)

### 1. Start Services
```bash
docker-compose up -d
```

### 2. Verify Services
```bash
docker-compose ps
```

### 3. Access Application
- **Frontend**: http://localhost:8080
- **MySQL**: localhost:3307 (user: root, password: 197520error)
- **Socket Server**: http://localhost:3000

### 4. View Logs
```bash
docker-compose logs -f apache
docker-compose logs -f mysql
docker-compose logs -f node
```

### 5. Stop Services
```bash
docker-compose down
```

---

## XAMPP Setup (Local Development)

### 1. Configure MySQL
```sql
-- Create database
CREATE DATABASE zuckbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'root'@'localhost' IDENTIFIED BY '197520error';
GRANT ALL PRIVILEGES ON zuckbook.* TO 'root'@'localhost';
FLUSH PRIVILEGES;

-- Import schema
SOURCE ZuckBook/database/zuckbook_database.sql;
```

### 2. Configure Apache
Edit `httpd-vhosts.conf`:
```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs/zuckbook"
    
    <Directory "C:/xampp/htdocs/zuckbook">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 3. Start Services
```bash
# Start Apache and MySQL from XAMPP Control Panel
# OR from command line:
cd C:\xampp
apache_start.bat
mysql_start.bat
```

### 4. Start Socket Server
```bash
cd socket-server
npm install
node server.js
```

### 5. Access Application
- **Frontend**: http://localhost:8080 (or http://localhost if using port 80)
- **MySQL**: localhost:3306
- **Socket Server**: http://localhost:3000

---

## Project Structure

```
zuckbook/
├── frontend/              # PHP web interface
│   ├── index.php         # Login/Register page
│   ├── home.php          # Main feed
│   ├── chat.php          # Messaging
│   ├── profile.php       # User profile
│   └── ...
├── backend/              # PHP API endpoints
│   ├── config.php        # Database configuration
│   ├── middleware.php    # Auth middleware
│   ├── login_process.php # Login handler
│   ├── register_process.php # Register handler
│   └── ...
├── socket-server/        # Node.js real-time server
│   └── server.js         # Socket.io server
├── ZuckBook/database/    # Database schema
│   └── zuckbook_database.sql
├── docker-compose.yml    # Docker configuration
├── apache-config.conf    # Apache configuration
├── .env                  # Environment variables
└── START.bat/START.sh    # Quick start scripts
```

---

## Database Configuration

### Connection Details
- **Host**: localhost (XAMPP) or mysql (Docker)
- **Port**: 3306 (XAMPP) or 3307 (Docker)
- **User**: root
- **Password**: 197520error
- **Database**: zuckbook

### Tables
- `users` - User accounts
- `friends` - Friend relationships
- `groups` - Group information
- `group_members` - Group membership
- `posts` - User posts
- `post_comments` - Post comments
- `post_reactions` - Post reactions
- `conversations` - Direct messages
- `messages` - Message content
- `support_tickets` - Support system
- `ticket_messages` - Support messages
- `notifications` - User notifications

---

## API Endpoints

### Authentication
- `POST /backend/login.php` - User login
- `POST /backend/register.php` - User registration
- `POST /backend/logout.php` - User logout

### Posts
- `POST /backend/create_post.php` - Create post
- `GET /backend/get_posts.php` - Get posts
- `POST /backend/like_post.php` - Like post
- `POST /backend/add_comment.php` - Add comment

### Messaging
- `GET /backend/get_conversations.php` - Get conversations
- `GET /backend/get_messages.php` - Get messages
- `POST /backend/send_message.php` - Send message

### Groups
- `POST /backend/create_group_post.php` - Create group post
- `GET /backend/get_group.php` - Get group info
- `POST /backend/join_group.php` - Join group

### Admin
- `POST /backend/admin/ban_user.php` - Ban user
- `POST /backend/admin/promote_admin.php` - Promote admin
- `POST /backend/admin/timeout_user.php` - Timeout user

---

## Socket.io Events

### Connection
- `join_user` - User comes online
- `disconnect` - User goes offline

### Messaging
- `send_message` - Send direct message
- `receive_message` - Receive message
- `message_seen` - Mark message as seen
- `typing` - User is typing
- `stop_typing` - User stopped typing

### Groups
- `join_group` - Join group room
- `new_group_post` - New group post
- `group_post_created` - Broadcast new post

### Reactions
- `reaction_update` - Update post reaction
- `reaction_update` - Broadcast reaction

### Calls
- `callUser` - Initiate call
- `incomingCall` - Incoming call notification
- `answerCall` - Answer call

---

## Troubleshooting

### Docker Issues

**Container won't start**
```bash
docker-compose logs
docker-compose down -v
docker-compose up -d
```

**MySQL connection refused**
```bash
docker-compose restart mysql
docker-compose logs mysql
```

**Port already in use**
```bash
# Change ports in docker-compose.yml
# Or kill existing process:
# Windows: netstat -ano | findstr :8080
# Linux: lsof -i :8080
```

### XAMPP Issues

**MySQL won't start**
- Check if port 3306 is in use
- Verify MySQL service in Services
- Check MySQL error log in xampp/mysql/data/

**Apache won't start**
- Check if port 80 is in use
- Verify Apache configuration syntax
- Check Apache error log in xampp/apache/logs/

**PHP errors**
- Enable error reporting in php.ini
- Check error logs in xampp/apache/logs/
- Verify PHP extensions are loaded

### Database Issues

**"Access denied for user 'root'@'localhost'"**
- Verify credentials in backend/config.php
- Check MySQL user permissions
- Restart MySQL service

**"Unknown database 'zuckbook'"**
- Import schema: `mysql -u root -p < ZuckBook/database/zuckbook_database.sql`
- Verify database exists: `SHOW DATABASES;`

---

## Environment Variables

Create `.env` file in project root:

```env
DB_HOST=mysql
DB_USER=root
DB_PASS=197520error
DB_NAME=zuckbook
DB_PORT=3306

SOCKET_SERVER_URL=http://localhost:3000
SOCKET_SERVER_PORT=3000

APP_URL=http://localhost:8080
APP_ENV=development
```

---

## Development Tips

### Enable Debug Mode
Edit `backend/config.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### View Database Logs
```bash
docker exec zuckbook_mysql mysql -u root -p197520error zuckbook -e "SHOW PROCESSLIST;"
```

### Monitor Socket Server
```bash
docker logs -f zuckbook_socket
```

### Test API Endpoints
```bash
curl -X POST http://localhost:8080/backend/login.php \
  -d "login=user@example.com&password=password"
```

---

## Performance Optimization

### Database
- Indexes are already configured
- Use prepared statements (already implemented)
- Enable query caching in MySQL

### PHP
- Enable OPcache in php.ini
- Use gzip compression
- Minimize database queries

### Socket.io
- Rate limiting is implemented
- Connection pooling configured
- Memory management optimized

---

## Security Notes

- All passwords are hashed with PASSWORD_DEFAULT
- SQL injection prevention via prepared statements
- CSRF protection enabled
- Session security configured
- XSS protection headers set
- Rate limiting on socket messages

---

## Support

For issues or questions:
1. Check logs: `docker-compose logs`
2. Verify database connection
3. Check firewall/port settings
4. Review error messages in browser console
5. Check PHP error logs

---

## License

ZuckBook - Social Media Platform

---

**Last Updated**: February 2026
**Version**: 1.0.0
