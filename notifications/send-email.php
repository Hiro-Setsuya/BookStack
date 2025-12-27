<?php
// Email notification functions for BookStack
require_once __DIR__ . '/../config/mail.php';

// Global IP address for email links
$GLOBALS['ip'] = "192.168.1.32";

/**
 * Send OTP verification email
 * @param string $email User's email address
 * @param string $otp 6-digit OTP code
 * @param string $userName Optional user name
 * @return bool Success status
 */
function sendOTPEmail($email, $otp, $userName = '')
{
    $greeting = !empty($userName) ? "Hi $userName," : "Hi,";

    $subject = "Your BookStack Verification Code";

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .otp-box { background: white; border: 2px dashed #667eea; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
            .otp-code { font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #667eea; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>BookStack</h1>
                <p style='margin: 10px 0 0 0;'>Password Reset Verification</p>
            </div>
            <div class='content'>
                <p>$greeting</p>
                <p>We received a request to reset your password. Use the verification code below to continue:</p>
                
                <div class='otp-box'>
                    <div class='otp-code'>$otp</div>
                    <p style='margin: 10px 0 0 0; color: #666;'>Valid for 10 minutes</p>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Security Notice:</strong> Never share this code with anyone. BookStack staff will never ask for your verification code.
                </div>
                
                <p>If you didn't request a password reset, please ignore this email or contact support if you have concerns.</p>
                
                <p>Best regards,<br><strong>BookStack Team</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated message, please do not reply.</p>
                <p>&copy; " . date('Y') . " BookStack. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    return sendEmail($email, $subject, $message);
}

/**
 * Send password reset confirmation email
 * @param string $email User's email address
 * @param string $userName Optional user name
 * @return bool Success status
 */
function sendPasswordResetConfirmation($email, $userName = '')
{
    global $ip;
    $greeting = !empty($userName) ? "Hi $userName," : "Hi,";

    $subject = "Password Successfully Changed - BookStack";

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .success-icon { font-size: 48px; text-align: center; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>BookStack</h1>
                <p style='margin: 10px 0 0 0;'>Password Reset Successful</p>
            </div>
            <div class='content'>
                <div class='success-icon'>✅</div>
                
                <p>$greeting</p>
                <p>Your password has been successfully changed. You can now log in with your new password.</p>
                
                <div style='text-align: center;'>
                    <a href='http://$ip/BookStack/login.php' class='button'>Login to BookStack</a>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Security Alert:</strong> If you didn't make this change, please contact our support team immediately.
                </div>
                
                <p><strong>Security Tips:</strong></p>
                <ul>
                    <li>Never share your password with anyone</li>
                    <li>Use a unique password for each account</li>
                </ul>
                
                <p>Best regards,<br><strong>BookStack Team</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated message, please do not reply.</p>
                <p>&copy; " . date('Y') . " BookStack. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    return sendEmail($email, $subject, $message);
}

/**
 * Send welcome email to new users
 * @param string $email User's email address
 * @param string $userName User name
 * @return bool Success status
 */
function sendWelcomeEmail($email, $userName)
{
    global $ip;
    $subject = "Welcome to BookStack!";

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>Welcome to BookStack! 📚</h1>
            </div>
            <div class='content'>
                <p>Hi $userName,</p>
                <p>Thank you for joining BookStack! We're excited to have you as part of our reading community.</p>
                
                <p><strong>Get started with BookStack:</strong></p>
                <ul>
                    <li>Browse our extensive collection of ebooks</li>
                    <li>Add books to your cart and checkout securely</li>
                    <li>Download your purchased ebooks anytime</li>
                    <li>Track your orders in your profile</li>
                </ul>
                
                <div style='text-align: center;'>
                    <a href='http://$ip/BookStack/ebooks.php' class='button'>Start Browsing</a>
                </div>
                
                <p>If you have any questions, feel free to contact our support team.</p>
                
                <p>Happy reading!<br><strong>BookStack Team</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated message, please do not reply.</p>
                <p>&copy; " . date('Y') . " BookStack. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    return sendEmail($email, $subject, $message);
}
