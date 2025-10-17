# Email Notification System Setup Guide

## Overview
The Email Notification System provides automated alerts and reports for your Budget Planner application. This system includes:

- **Budget Alerts**: Notifications when spending approaches limits
- **Goal Achievement**: Milestone celebrations and progress updates
- **Bill Reminders**: Automated payment reminders for recurring bills
- **Monthly Reports**: Comprehensive financial summaries
- **Custom Alerts**: Low balance and large transaction notifications

## Features

### 1. Notification Types
- **Budget Alerts**: Configurable thresholds (70%, 80%, 90%, 100%)
- **Goal Milestones**: 25%, 50%, 75%, and achievement notifications
- **Bill Reminders**: 1-14 days advance notice
- **Monthly Reports**: Start/end of month comprehensive summaries
- **Low Balance**: Customizable minimum balance alerts
- **Large Transactions**: Alerts for transactions above threshold

### 2. Email Features
- **HTML Email Templates**: Professional, mobile-optimized designs
- **Digest Mode**: Combine multiple notifications into one email
- **Timezone Support**: Proper time scheduling for user's timezone
- **Unsubscribe Links**: Easy opt-out for users
- **Test Email Function**: Verify email configuration

### 3. Management Dashboard
- **Real-time Statistics**: Sent, pending, and failed email counts
- **Notification Log**: Complete history with timestamps
- **Settings Management**: Easy configuration of all notification types
- **Email Settings**: Custom email preferences and delivery options

## Setup Instructions

### 1. Database Setup
The system automatically creates the following tables:
- `notification_settings`: User notification preferences
- `notification_log`: Email delivery history
- `notification_schedule`: Scheduled notification management
- `user_email_settings`: Email configuration per user

### 2. Windows Task Scheduler Setup

#### Option A: Using the Batch File
1. Open Windows Task Scheduler (`taskschd.msc`)
2. Click "Create Basic Task"
3. Name: "Budget Planner Email Notifications"
4. Trigger: Daily
5. Time: Every 15 minutes
6. Action: Start a program
7. Program: `C:\xampp\htdocs\new 2\Cursor Web\api\run_notification_processor.bat`

#### Option B: Direct PHP Execution
1. Program: `C:\xampp\php\php.exe`
2. Arguments: `"C:\xampp\htdocs\new 2\Cursor Web\api\notification_processor.php"`
3. Start in: `C:\xampp\htdocs\new 2\Cursor Web\api`

### 3. Email Configuration

#### PHP Mail Configuration
1. Ensure your server has `sendmail` or similar mail function enabled
2. Configure PHP mail settings in `php.ini`:
   ```ini
   [mail function]
   SMTP = localhost
   smtp_port = 25
   sendmail_from = noreply@budgetplanner.com
   ```

#### Production Email Service
For production use, consider integrating with:
- **SendGrid**: Professional email delivery service
- **Mailgun**: Reliable email API
- **Amazon SES**: AWS email service
- **PHPMailer**: Advanced PHP email library

### 4. Testing the System

1. Navigate to `email_notifications.php`
2. Configure your notification preferences
3. Click "Test Email" to verify delivery
4. Check the notification log for status updates

## Usage Guide

### For Users
1. **Access Notifications**: Click "Notifications" in the main navigation
2. **Configure Alerts**: Toggle notification types on/off
3. **Set Thresholds**: Customize when you want to be notified
4. **Test Setup**: Send test emails to verify configuration
5. **Review History**: Check notification log for delivery status

### For Administrators
1. **Monitor Health**: Check notification statistics regularly
2. **Review Logs**: Monitor failed notifications and errors
3. **Manage Schedules**: Ensure cron jobs are running properly
4. **Update Settings**: Maintain email server configuration

## File Structure

```
email_notifications.php          # Main notification dashboard
api/
├── notification_settings.php    # Settings management API
├── send_test_email.php          # Test email functionality
├── notification_stats.php       # Statistics and metrics
├── notification_log.php         # Email delivery history
├── email_settings.php           # Email configuration
├── notification_processor.php   # Automated processing script
└── run_notification_processor.bat # Windows batch file
```

## Customization

### Email Templates
Edit the HTML generation functions in `notification_processor.php`:
- `generateBudgetAlertHTML()`
- `generateBillReminderHTML()`
- `generateMonthlyReportHTML()`
- `generateGoalAlertHTML()`

### Notification Logic
Modify the trigger conditions in:
- `processScheduledNotifications()`
- `processEventBasedNotifications()`
- `checkBudgetLimitNotifications()`

### Scheduling
Adjust timing in:
- `updateNextTrigger()`
- `updateNotificationSchedule()`

## Troubleshooting

### Common Issues

1. **Emails Not Sending**
   - Check PHP mail configuration
   - Verify SMTP settings
   - Review notification log for errors
   - Test with simple PHP mail() function

2. **Notifications Not Triggering**
   - Ensure cron job is active
   - Check notification_schedule table
   - Verify trigger conditions are met
   - Review processor logs

3. **Wrong Timing**
   - Check user timezone settings
   - Verify server timezone configuration
   - Review next_trigger calculations

### Log Files
- `notification_processor.log`: Cron job execution log
- PHP error logs: Check for runtime errors
- Database logs: Monitor query performance

## Security Considerations

1. **Email Privacy**: All emails include unsubscribe links
2. **Data Protection**: No sensitive financial data in email subjects
3. **Token Security**: Unsubscribe tokens are cryptographically secure
4. **Input Validation**: All user inputs are sanitized and validated
5. **Database Security**: Prepared statements prevent SQL injection

## Performance Notes

- The processor is optimized to handle large user bases
- Memory limit set to 256MB for batch processing
- Execution time limited to 5 minutes
- Efficient database queries with proper indexing
- Digest mode reduces email volume

## Maintenance

### Regular Tasks
- Monitor failed notifications weekly
- Clean old notification logs monthly
- Review email templates quarterly
- Update email service configuration as needed

### Updates
- Check for PHP mail function updates
- Monitor email deliverability rates
- Update HTML templates for better mobile support
- Review and optimize database queries

## Integration

The notification system integrates seamlessly with:
- Budget tracking system
- Goal management
- Recurring transactions
- Financial reporting
- User profile management

## Support

For technical issues:
1. Check the notification log for specific error messages
2. Review processor execution logs
3. Test email configuration with simple scripts
4. Verify database table integrity
5. Contact system administrator for server-level issues