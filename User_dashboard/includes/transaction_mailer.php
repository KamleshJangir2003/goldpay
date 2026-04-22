<?php
date_default_timezone_set('Asia/Kolkata');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

/**
 * Send transaction notification email to user.
 *
 * @param string $toEmail   Recipient email
 * @param string $toName    Recipient name
 * @param string $subject   Email subject
 * @param array  $details   Associative array of label => value for the email body
 */
function sendTransactionEmail(string $toEmail, string $toName, string $subject, array $details): void {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'Sharmagopesh706@gmail.com';
        $mail->Password   = 'auxplhwwkzetzuma';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('Sharmagopesh706@gmail.com', 'MBPAY');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;

        // Build rows
        $rows = '';
        foreach ($details as $label => $value) {
            $rows .= "<tr>
                <td style='padding:8px 12px;font-weight:600;color:#374151;background:#f8fafc;width:40%;'>{$label}</td>
                <td style='padding:8px 12px;color:#1e293b;'>{$value}</td>
            </tr>";
        }

        $mail->Body = "
        <div style='font-family:Poppins,sans-serif;max-width:520px;margin:auto;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;'>
          <div style='background:linear-gradient(135deg,#6366f1,#4f46e5);padding:24px 28px;'>
            <h2 style='color:#fff;margin:0;font-size:1.2rem;'>Transaction Notification</h2>
            <p style='color:#c7d2fe;margin:4px 0 0;font-size:0.85rem;'>Dollario / MBPAY</p>
          </div>
          <div style='padding:24px 28px;'>
            <p style='color:#374151;margin-bottom:16px;'>Hi <strong>{$toName}</strong>, here are your transaction details:</p>
            <table style='width:100%;border-collapse:collapse;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;'>
              {$rows}
            </table>
            <p style='color:#64748b;font-size:0.8rem;margin-top:20px;'>If you did not perform this transaction, please contact support immediately.</p>
          </div>
          <div style='background:#f8fafc;padding:14px 28px;text-align:center;font-size:0.75rem;color:#94a3b8;'>
            &copy; " . date('Y') . " Dollario / MBPAY. All rights reserved.
          </div>
        </div>";

        $mail->send();
    } catch (Exception $e) {
        // Silent fail — transaction already saved, email failure should not break flow
        error_log("Transaction email failed: " . $mail->ErrorInfo);
    }
}
