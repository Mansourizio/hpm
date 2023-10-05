<?php

//start the session
session_start();

require_once "../config/db.php";
require_once "../utils/helpers/functions.php";
require_once "../model/Email.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $send_email = new Email();

    $email = sanitizeInput($_POST["email"]);

    // Use PDO to query the database for a user with the provided credentials
    $db = new db();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id, email, username FROM `users` WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // User exists, generate and store a token
        $token = bin2hex(random_bytes(16));
        $id = $user['id'];
        $username = $user['username'];

        $sql = "UPDATE `users` SET `reset_token`= ? ,`reset_token_expires_at`= NOW() WHERE  `id`= ?;";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$token, $id]);

        // Send an email to the user with a link to the password reset form
        $recipientEmail = $user['email'];
        $subject = "Password reset request";
        $link = "http://localhost/HPM/src/controller/email2.php?token=" . $token;
        $message = "<!DOCTYPE html>
        <html>
          <head>
            <title>Password Reset Request</title>
            <style>
              /* Button styles */
              .button {
                background-color: #4CAF50;
                border: none;
                color: white;
                padding: 15px 32px;
                text-align: center;
                text-decoration: none;
                display: inline-block;
                font-size: 16px;
                margin: 4px 2px;
                cursor: pointer;
              }
        
              /* Container styles */
              .container {
                margin: 50px auto;
                max-width: 600px;
                padding: 20px;
                background-color: #f2f2f2;
                border-radius: 5px;
                font-family: Arial, sans-serif;
                font-size: 16px;
              }
        
              /* Link styles */
              .link {
                color: #4CAF50;
                text-decoration: none;
                font-weight: bold;
              }
            </style>
          </head>
          <body>
            <div class='container'>
              <p>Dear $username</p>
              <p>We have received a request to reset your password. Please click the button below to verify your identity and create a new password:</p>
              <a href='$link' target='_blank' class='button'>Reset Password</a>
              <p>If you did not request a password reset, please ignore this message and contact us immediately if you believe your account has been compromised.</p>
              <p>Thank you for using our service.</p>
              <p>Best regards,</p>
              <p>Reliquio</p>
              <p>From: noreplyReliquio@gmail.com</p>
            </div>
          </body>
        </html>";


        $res =  $send_email->sendEmail($recipientEmail, $username, $subject, $message);

        echo $res . "<br />";

        if ($res)
            if ($res == "Email sent successfully") {
                echo 'Please follow the link in your email to verify your identity and create a new password';
                exit;
            } else {
                echo 'please try again after 5 min';
                exit;
            }
    } else {
        // User does not exist, display an error message
        $error_message = "Sorry, that email address does not exist. Please try again.";
        header("Location: ../views/email1.php?message=" . $error_message . '&type=danger');
    }
}
