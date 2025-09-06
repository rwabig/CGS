<?php
class Mailer {
  public static function send($to, $subject, $body): bool {
    // Extend this with PHPMailer/SMTP later
    return mail($to, $subject, $body);
  }
}
?>
