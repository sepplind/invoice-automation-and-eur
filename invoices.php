<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512x512.png">
    <link rel="stylesheet" href="style.css">
    <title>Invoice automation and EÜR - Invoices mailing</title>
</head>
<body>
    <div class="container">
        <h1>
            <img src="/favicon-512x512.png" width="26px" height="26px">
            Invoice automation and EÜR - Invoices mailing
        </h1>
        <?php include 'includes/nav.php';
        include 'includes/db.php';

        // Handle form submission for adding a new record
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['date']) && isset($_POST['time']) && isset($_POST['filename']) && isset($_POST['email'])) {
            $selected_date = $_POST['date'];
            $selected_time = $_POST['time'];
            $filename = $_POST['filename'];
            $recipient_email = $_POST['email'];
            $cc_email = $_POST['cc_email'];

            // Prepare and bind
            $stmt = $conn->prepare("INSERT INTO invoice_cronjobs (selected_date, selected_time, filename, recipient_email, cc_email, send_status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("sssss", $selected_date, $selected_time, $filename, $recipient_email, $cc_email);

            if ($stmt->execute()) {
                echo "<div class='message-dialog'>New record created successfully.</div>";
            } else {
                echo "<div class='message-dialog error'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }

        // Process file upload
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['invoice_file'])) {
            $uploadDir = 'invoices/';
            $uploadFile = $uploadDir . basename($_FILES['invoice_file']['name']);

            // Check if file is a PDF
            $fileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
            if ($fileType != 'pdf') {
                echo "<div class='message-dialog' style='background-color: red;'>Error: Only PDF files are allowed.</div>";
            } else {
                if (move_uploaded_file($_FILES['invoice_file']['tmp_name'], $uploadFile)) {
                    echo "<div class='message-dialog'>File uploaded successfully.</div>";
                } else {
                    echo "<div class='message-dialog' style='background-color: red;'>Error: Failed to upload file.</div>";
                }
            }
        }

        // Delete record if delete is requested
        if (isset($_POST['delete'])) {
            $id = $_POST['id'];
            $delete_sql = "DELETE FROM invoice_cronjobs WHERE id=?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->execute()) {
                echo "<div class='message-dialog'>Record deleted successfully.</div>";
            } else {
                echo "<div class='message-dialog error'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }

        // Fetch data from the invoice_cronjobs table
        $sql = "SELECT * FROM invoice_cronjobs";
        $result = $conn->query($sql);

        $upcoming_records = [];
        $past_records = [];

        if ($result->num_rows > 0) {
            $currentDateTime = new DateTime();

            while ($row = $result->fetch_assoc()) {
                $recordDateTime = new DateTime($row["selected_date"] . ' ' . $row["selected_time"]);
                if ($recordDateTime >= $currentDateTime) {
                    $upcoming_records[] = $row;
                } else {
                    $past_records[] = $row;
                }
            }
        }

        echo "<br><br><h2>Upcoming Records</h2>";
        if (count($upcoming_records) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Date</th><th>Time</th><th>Filename</th><th>Recipient Email</th><th>CC Email</th><th>Send Status</th><th>Actions</th></tr>";
            foreach ($upcoming_records as $row) {
                echo "<tr><td>" . $row["id"] . "</td><td>" . $row["selected_date"] . "</td><td>" . $row["selected_time"] . "</td><td>" . $row["filename"] . "</td><td>" . $row["recipient_email"] . "</td><td>" . $row["cc_email"] . "</td><td>" . $row["send_status"] . "</td><td><form method='post' style='display:inline;'><input type='hidden' name='id' value='" . $row["id"] . "'><button type='submit' name='delete' class='delete-button'>Delete</button></form></td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No upcoming records found.</p>";
        }

        echo "<br><br><h2>Past Records</h2>";
        if (count($past_records) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Date</th><th>Time</th><th>Filename</th><th>Recipient Email</th><th>CC Email</th><th>Send Status</th><th>Actions</th></tr>";
            foreach ($past_records as $row) {
                echo "<tr><td>" . $row["id"] . "</td><td>" . $row["selected_date"] . "</td><td>" . $row["selected_time"] . "</td><td>" . $row["filename"] . "</td><td>" . $row["recipient_email"] . "</td><td>" . $row["cc_email"] . "</td><td>" . $row["send_status"] . "</td><td><form method='post' style='display:inline;'><input type='hidden' name='id' value='" . $row["id"] . "'><button type='submit' name='delete' class='delete-button'>Delete</button></form></td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No past records found.</p>";
        }

        $conn->close();
        ?>
        <br>
        <br>
        <div class="forms">
            <div class="form-container">
                <h2>Upload Invoice</h2>
                <form action="" method="post" enctype="multipart/form-data">
                    <label for="invoice_file">Select Invoice PDF:</label>
                    <input type="file" name="invoice_file" id="invoice_file" accept="application/pdf" required>
                    <button type="submit">Upload</button>
                </form>
            </div>
            <div class="form-container">
                <h2>Submit New Record</h2>
                <form action="" method="post">
                    <!-- Date Select -->
                    <label for="date">Select Date:</label>
                    <select name="date" id="date">
                        <?php
                        // Get today's date
                        $today = new DateTime();
                        // Get the date two years from today
                        $maxDate = (new DateTime())->modify('+2 years');

                        // Generate date options from today to two years in the future
                        for ($date = clone $today; $date <= $maxDate; $date->modify('+1 day')) {
                            echo "<option value=\"" . $date->format('Y-m-d') . "\">" . $date->format('d. m. Y') . "</option>";
                        }
                        ?>
                    </select>

                    <!-- Time Select -->
                    <label for="time">Select Time:</label>
                    <select name="time" id="time">
                        <?php
                        // Generate time options in HH:MM format at 15-minute intervals
                        for ($hour = 0; $hour < 24; $hour++) {
                            for ($minute = 0; $minute < 60; $minute += 15) {
                                $time = sprintf('%02d:%02d:00', $hour, $minute);
                                echo "<option value=\"$time\">$time</option>";
                            }
                        }
                        ?>
                    </select>

                    <!-- Filename Select -->
                    <label for="filename">Select Invoice:</label>
                    <select name="filename" id="filename">
                        <?php
                        // Directory containing the invoices
                        $directory = 'invoices/';
                        if (is_dir($directory)) {
                            if ($handle = opendir($directory)) {
                                while (false !== ($file = readdir($handle))) {
                                    if ($file != "." && $file != "..") {
                                        echo "<option value=\"$file\">$file</option>";
                                    }
                                }
                                closedir($handle);
                            } else {
                                echo "<option value=\"\">Unable to open directory</option>";
                            }
                        } else {
                            echo "<option value=\"\">Directory not found</option>";
                        }
                        ?>
                    </select>

                    <!-- Email Input -->
                    <label for="email">Recipient Email:</label>
                    <input type="email" name="email" id="email" required>

                    <!-- CC Email Input -->
                    <label for="cc_email">CC Email:</label>
                    <input type="email" name="cc_email" id="cc_email">

                    <button type="submit">Submit</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
