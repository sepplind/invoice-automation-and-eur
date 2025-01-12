<?php
$servername = "localhost";
$username = "d040cec2";
$password = "DCbZJQzxFsppFxrPAMy3";
$dbname = "d040cec2";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch past records with pending send status
$sql = "SELECT * FROM invoice_cronjobs WHERE send_status = 'pending'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recordDateTime = new DateTime($row["selected_date"] . ' ' . $row["selected_time"]);
        $currentDateTime = new DateTime();

        if ($recordDateTime < $currentDateTime) {
            // Send email
            $to = $row["recipient_email"];
            $cc = $row["cc_email"];
            $subject = "Ihre Rechnung von Sebastian Lind - Web-Entwicklung";
            $body = "Sehr geehrte Damen und Herren,\n\n";
            $body .= "vielen Dank für Ihr Vertrauen in Sebastian Lind – Web-Entwicklung. Für meine Leistungen sende ich Ihnen die Rechnung \"" . $row["filename"] . "\" die Sie im Anhang finden.";
            $body .= "\n\nMit freundlichen Grüßen\nSebastian Lind";
            $body .= "\n\n- - -\n■ Sebastian Lind – Web-Entwicklung\nSebastian Lind | Ringstraße 29a | 64380 Roßdorf";
            $body .= "\n\nMail: mail@sebastian-lind.de\nWeb: https://www.sebastian-lind.de";
            $headers = "From: Sebastian Lind <mail@sebastian-lind.de>";

            // Add CC email if provided
            if (!empty($cc)) {
                $headers .= "\r\nCc: $cc";
            }

            // Boundary for marking the different sections of the email
            $separator = md5(time());

            // Headers for attachment
            $headers .= "\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"".$separator."\"";

            // Message
            $message = "--".$separator."\r\n";
            $message .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
            $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $message .= $body."\r\n";

            // Attachment
            $attachment = chunk_split(base64_encode(file_get_contents('invoices/' . $row["filename"])));
            $message .= "--".$separator."\r\n";
            $message .= "Content-Type: application/octet-stream; name=\"".$row["filename"]."\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment\r\n\r\n";
            $message .= $attachment."\r\n";
            $message .= "--".$separator."--";

            // Send the email
            if (mail($to, $subject, $message, $headers)) {
                // Update send status to 'sent'
                $update_sql = "UPDATE invoice_cronjobs SET send_status='sent' WHERE id=?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("i", $row["id"]);
                $stmt->execute();
                $stmt->close();

                // Return HTTP 200 status if email sent successfully
                http_response_code(200);
            } else {
                // Return HTTP 400 status if email is not sent successfully
                http_response_code(400);
            }
        }
    }
}

$conn->close();
