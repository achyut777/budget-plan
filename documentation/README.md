# Budget Planner 💰

A comprehensive personal finance management system built with PHP, MySQL, and Bootstrap. Track expenses, set goals, manage budgets, and get insights into your financial habits.

![Budget Planner](https://img.shields.io/badge/Version-1.0-blue) ![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple) ![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange) ![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-blue)

## 🌟 Features

### 💳 Financial Management
- **Income & Expense Tracking**: Log all your financial transactions with detailed categorization
- **Budget Planning**: Set monthly/yearly budgets for different expense categories
- **Goal Setting**: Create and track financial goals with progress monitoring
- **Tax Planning**: Manage tax deductions and calculate potential savings

### 📊 Analytics & Insights
- **Visual Dashboard**: Beautiful charts and graphs showing your financial trends
- **Monthly Reports**: Detailed breakdown of income vs expenses
- **Category Analytics**: See where your money goes with expense distribution charts
- **Goal Progress**: Track progress toward your financial objectives

### 🔐 Security & Authentication
- **Email Verification**: Secure account creation with email verification
- **Remember Me**: Persistent login with secure token-based authentication
- **Admin Panel**: Complete user management and system administration
- **Session Management**: Automatic timeout and security features

### 📱 User Experience
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices
- **Dark/Light Themes**: Switch between different visual themes
- **Intuitive Interface**: Clean, modern design with easy navigation
- **Real-time Updates**: Dynamic content updates without page reloads

## 🚀 Quick Start

### Prerequisites
- **XAMPP** (or similar LAMP/WAMP stack)
- **PHP 7.4** or higher
- **MySQL 8.0** or higher
- **Web browser** (Chrome, Firefox, Safari, Edge)

### Installation

1. **Clone or Download**
   ```bash
   git clone https://github.com/yourusername/budget-planner.git
   # OR download and extract the ZIP file
   ```

2. **Setup XAMPP**
   - Start Apache and MySQL services
   - Place the project folder in `htdocs` directory

3. **Database Setup**
   ```bash
   # Navigate to project directory
   cd /path/to/your/htdocs/budget-planner
   
   # Run database initialization (automatically creates tables and sample data)
   php config/init_db.php
   ```

4. **Configure Email (Optional)**
   - Edit `config/email_config.php` for production email settings
   - For development, emails are saved as files in `logs/emails/`

5. **Access the Application**
   ```
   http://localhost/budget-planner
   ```

### Default Accounts

| Account Type | Email | Password | Purpose |
|--------------|-------|----------|---------|
| **Admin** | admin@budgetplanner.com | admin123 | System administration |
| **Demo User** | demo@example.com | demo12345 | Pre-loaded with sample data |

## 📁 Project Structure

```
budget-planner/
├── 📂 admin/                      # Admin panel
│   ├── 📂 api/                    # Admin API endpoints
│   ├── 📂 includes/               # Admin headers/footers
│   ├── dashboard.php              # Admin dashboard
│   ├── users.php                  # User management
│   └── settings.php               # System settings
├── 📂 api/                        # REST API endpoints
│   ├── add_transaction.php        # Add new transaction
│   ├── get_monthly_trend.php      # Monthly analytics
│   └── update_profile.php         # Profile management
├── 📂 assets/                     # Static assets
│   ├── 📂 css/                    # Stylesheets
│   ├── 📂 js/                     # JavaScript files
│   └── 📂 images/                 # Images and icons
├── 📂 config/                     # Configuration files
│   ├── database.php               # Database connection
│   ├── auth_middleware.php        # Authentication system
│   ├── email_verification.php     # Email verification
│   └── init_db.php               # Database initialization
├── 📂 logs/                       # Application logs
│   └── 📂 emails/                 # Development email logs
├── 📂 sql/                        # Database schemas
├── dashboard.php                  # Main dashboard
├── login.php                      # User login
├── register.php                   # User registration
├── transactions.php               # Transaction management
├── goals.php                      # Financial goals
├── profile.php                    # User profile
└── README.md                      # This file
```

## 🔧 Configuration

### Database Settings
Edit `config/database.php`:
```php
class DatabaseConfig {
    private static $host = 'localhost';     // Database host
    private static $username = 'root';      // Database username
    private static $password = '';          // Database password
    private static $database = 'budget_planner'; // Database name
    private static $port = 3306;            // Database port
}
```

### Email Configuration
For production, edit `config/email_config.php`:
```php
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'Your Budget Planner');
define('APP_URL', 'https://yourdomain.com/budget-planner');
```

### Security Settings
- Session timeout: 30 minutes (configurable in `database.php`)
- Remember me tokens: 30 days expiry
- Password hashing: PHP's `password_hash()` with BCRYPT
- CSRF protection on all forms

## 🎯 Usage Guide

### For Users

1. **Getting Started**
   - Register a new account or use demo credentials
   - Verify your email address (check `logs/emails/` in development)
   - Complete your profile setup

2. **Managing Transactions**
   - Add income and expenses with categories
   - Use the search and filter features
   - Edit or delete transactions as needed

3. **Setting Goals**
   - Create financial goals with target amounts and deadlines
   - Track progress on the dashboard
   - Update goal progress manually or automatically

4. **Budget Planning**
   - Set monthly/yearly budgets for expense categories
   - Monitor spending against budgets
   - Receive warnings when approaching limits

### For Administrators

1. **User Management**
   - View all registered users
   - Manually verify user accounts
   - Manage user permissions and status

2. **System Monitoring**
   - Monitor application usage
   - View system logs and errors
   - Manage email verification settings

## 🔌 API Documentation

### Authentication
All API endpoints require user authentication via session or remember token.

### Endpoints

#### Transactions
```http
POST /api/add_transaction.php
Content-Type: application/json

{
    "type": "expense",
    "category_id": 1,
    "amount": 50.00,
    "description": "Grocery shopping",
    "date": "2024-01-15"
}
```

#### Goals
```http
GET /api/get_goals.php
# Returns user's financial goals

POST /api/add_goal.php
Content-Type: application/json

{
    "name": "Emergency Fund",
    "target_amount": 10000.00,
    "deadline": "2024-12-31"
}
```

#### Analytics
```http
GET /api/get_monthly_trend.php
# Returns monthly income/expense trends

GET /api/get_expense_distribution.php
# Returns expense breakdown by category
```

## 🛠️ Development

### Local Development Setup

1. **Enable Debug Mode**
   - Ensure you're running on localhost
   - Debug information automatically enabled for local development

2. **Email Testing**
   - Emails are saved as HTML files in `logs/emails/`
   - View emails at: `http://localhost/budget-planner/email_viewer.php`

3. **Database Management**
   - Reset database: Run `config/init_db.php`
   - View logs: Check `logs/` directory

### Adding New Features

1. **Database Changes**
   - Update `config/init_db.php` with new table structures
   - Add migration scripts if needed

2. **API Endpoints**
   - Create new files in `api/` directory
   - Follow existing patterns for authentication and validation

3. **Frontend Components**
   - Use Bootstrap 5 classes for consistency
   - Follow the existing JavaScript patterns

## 🔒 Security Features

- **SQL Injection Protection**: Prepared statements throughout
- **XSS Prevention**: Input sanitization and output escaping
- **CSRF Protection**: Tokens on all forms
- **Session Security**: Secure session configuration
- **Password Security**: Strong hashing with salt
- **Email Verification**: Prevents unauthorized account access
- **Remember Me Security**: Secure token-based persistent login

## 📊 Database Schema

### Core Tables
- **users**: User account information and verification status
- **categories**: Income/expense categories
- **transactions**: All financial transactions
- **goals**: Financial goals and progress
- **budget_limits**: Budget constraints by category

### Authentication Tables
- **admin_users**: Administrator accounts
- **remember_tokens**: Persistent login tokens

### Feature Tables
- **tax_deductions**: Tax planning data

## 🚀 Deployment

### Production Deployment

1. **Server Requirements**
   - PHP 7.4+ with MySQL extension
   - MySQL 8.0+ or MariaDB 10.3+
   - Apache or Nginx web server
   - SSL certificate (recommended)

2. **Configuration**
   - Update database credentials in `config/database.php`
   - Configure email settings in `config/email_config.php`
   - Set proper file permissions

3. **Security Checklist**
   - [ ] Change default admin password
   - [ ] Remove demo user in production
   - [ ] Configure proper email settings
   - [ ] Enable HTTPS
   - [ ] Set up regular database backups

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Check MySQL service is running
- Verify database credentials
- Ensure database 'budget_planner' exists

**Email Verification Not Working**
- Check `logs/emails/` for development emails
- Verify SMTP settings for production
- Ensure email verification is enabled

**Login Issues**
- Clear browser cookies and cache
- Check user verification status in admin panel
- Verify password requirements

**Permission Errors**
- Ensure web server has write access to `logs/` directory
- Check file ownership and permissions

### Getting Help

1. **Check Logs**
   - Application logs: `logs/` directory
   - PHP error logs: Check your server's error log

2. **Debug Mode**
   - Automatic on localhost
   - Shows detailed error messages

3. **Database Issues**
   - Run `config/init_db.php` to reset/recreate tables
   - Check MySQL error logs

## 🤝 Contributing

We welcome contributions! Please see our contributing guidelines:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Development Guidelines
- Follow PSR-4 autoloading standards
- Use prepared statements for all database queries
- Include error handling for all user inputs
- Write meaningful commit messages
- Update documentation for new features

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Bootstrap** - For the beautiful, responsive UI framework
- **Chart.js** - For interactive charts and graphs
- **Font Awesome** - For the comprehensive icon library
- **PHP Community** - For excellent documentation and support

## 📞 Support

- **Email**: support@budgetplanner.com
- **Documentation**: [Wiki](https://github.com/yourusername/budget-planner/wiki)
- **Issues**: [GitHub Issues](https://github.com/yourusername/budget-planner/issues)

---

## 📈 Version History

### v1.0.0 (Current)
- ✅ Complete user authentication system
- ✅ Email verification functionality
- ✅ Transaction management
- ✅ Goal tracking
- ✅ Budget planning
- ✅ Admin panel
- ✅ Responsive design
- ✅ API endpoints
- ✅ Security features

### Planned Features
- 🔄 Mobile app
- 🔄 Bank integration
- 🔄 Advanced reporting
- 🔄 Multi-currency support
- 🔄 Investment tracking

---

Made with ❤️ by the Budget Planner Team