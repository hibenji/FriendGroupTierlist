# ChillGC Friend Tierlist

A Discord OAuth-authenticated tierlist application where users can rank their friends into tiers (S, A, B, C, D, F). Features aggregate results showing how everyone voted.

## Features

- 🔐 **Discord OAuth Login** – Users authenticate with their Discord account
- 🎯 **Drag & Drop Tierlist** – Intuitive interface for ranking friends
- 📊 **Aggregate Results** – View combined rankings from all users
- 🚫 **Self-Vote Prevention** – Users cannot rank themselves
- 👑 **Admin Controls** – Admins can add/remove people from the tierlist
- 🎨 **Modern UI** – Clean, responsive design with smooth animations

## Requirements

- PHP 7.4+
- MySQL/MariaDB
- A Discord Application with OAuth2 enabled

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/hibenji/FriendGroupTierlist.git
cd FriendGroupTierlist
```

### 2. Set Up the Database

Import the database schema:

```bash
mysql -u your_user -p your_database < schema.sql
```

### 3. Configure the Application

Copy the example config and update with your credentials:

```bash
cp config.example.php config.php
```

Edit `config.php` and fill in:
- Database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`)
- Discord OAuth settings (`DISCORD_CLIENT_ID`, `DISCORD_CLIENT_SECRET`, `DISCORD_BOT_TOKEN`)
- Your domain URL (`DISCORD_REDIRECT_URI`, `APP_URL`)

### 4. Discord Application Setup

1. Go to the [Discord Developer Portal](https://discord.com/developers/applications)
2. Create a new application (or use an existing one)
3. Navigate to **OAuth2** settings
4. Add your redirect URI: `https://your-domain.com/callback.php`
5. Copy the **Client ID** and **Client Secret** to your `config.php`
6. If using bot features, go to **Bot** and copy the bot token

### 5. Web Server Configuration

Ensure your web server points to this directory. The included `.htaccess` handles routing for Apache.

For Nginx, add appropriate rewrite rules to your server block.

## Project Structure

```
├── api/                  # API endpoints for AJAX calls
│   ├── people.php        # Manage people (add/remove)
│   ├── rankings.php      # Save/load rankings
│   └── results.php       # Get aggregate results
├── assets/
│   ├── css/              # Stylesheets
│   └── js/
│       └── tierlist.js   # Main JavaScript functionality
├── includes/
│   ├── auth.php          # Discord OAuth handling
│   └── db.php            # Database connection & queries
├── callback.php          # Discord OAuth callback
├── config.example.php    # Example configuration
├── config.php            # Your configuration (gitignored)
├── index.php             # Main tierlist page
├── login.php             # Initiates Discord login
├── logout.php            # Clears session
├── results.php           # View aggregate results
└── schema.sql            # Database schema
```

## Usage

1. Visit the site and click **Login with Discord**
2. Authorize the application
3. Drag people from the sidebar into tier rows
4. Your rankings are saved automatically
5. View **Results** to see aggregate rankings from all users

## Admin Features

Admins can:
- Add new people to the tierlist
- Remove people from the tierlist
- View all user rankings

To make a user an admin, update their record in the database:

```sql
UPDATE users SET is_admin = 1 WHERE id = 'discord_user_id';
```

## License

MIT License
