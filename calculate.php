<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512x512.png">
    <link rel="stylesheet" href="style.css">
    <title>Invoice Automation and EÜR - EÜR</title>
</head>
<body>
<div class="container">
    <h1>
        <img src="/favicon-512x512.png" width="26px" height="26px">
        Invoice Automation and EÜR - EÜR
    </h1>
    <?php
    include 'includes/nav.php';
    include 'includes/db.php';

    // Fetch years from invoices table
    $years_result = $conn->query("SELECT DISTINCT YEAR(invoice_date) AS year FROM invoices ORDER BY year DESC");
    $selected_year = isset($_GET['year']) ? (int)$_GET['year'] : null;

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

    // Handle form submission for adding a new invoice
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['invoice_date'], $_POST['invoice_amount'], $_POST['type'], $_POST['invoice_id'])) {
        $invoice_id = $_POST['invoice_id'];
        $type = $_POST['type'];
        $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null; // Allow NULL for customer_id
        $invoice_date = $_POST['invoice_date'];
        $invoice_amount = $_POST['invoice_amount'];

        // Determine invoice_name
        $invoice_name = $_POST['custom_invoice_name'] ?: $_POST['invoice_file'];
        // Format invoice amount
        $invoice_amount = number_format((float)$invoice_amount, 2, '.', '');

        if (empty($invoice_name)) {
            echo "<div class='message-dialog error'>Please select an invoice file or enter a custom invoice name.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO invoices (invoice_id, type, customer_id, invoice_name, invoice_date, invoice_amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisss", $invoice_id, $type, $customer_id, $invoice_name, $invoice_date, $invoice_amount);

            if ($stmt->execute()) {
                echo "<div class='message-dialog'>Invoice added successfully.</div>";
            } else {
                echo "<div class='message-dialog error'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }

    // Fetch invoices and join with the invoice_cronjobs table to get the send_status
    $query = "
        SELECT 
            invoices.invoice_id,
            invoices.invoice_name,
            invoices.invoice_date,
            COALESCE(latest_cronjobs.send_status, '---') AS invoice_status,
            COALESCE(customers.company, '---') AS customer_name,
            invoices.type,
            invoices.invoice_amount
        FROM 
            invoices
        LEFT JOIN 
            customers ON invoices.customer_id = customers.id
        LEFT JOIN 
            (
                SELECT 
                    filename, 
                    send_status, 
                    MAX(selected_date) AS latest_mailing 
                FROM 
                    invoice_cronjobs 
                GROUP BY 
                    filename
            ) AS latest_cronjobs 
        ON 
            invoices.invoice_name = latest_cronjobs.filename
    ";

    if ($selected_year) {
        $query .= " WHERE YEAR(invoices.invoice_date) = $selected_year";
    }

    $query .= " ORDER BY invoices.invoice_date ASC";
    $result = $conn->query($query);

    // Calculate totals
    $totals_query = "
        SELECT 
            SUM(CASE WHEN type = 'revenue' THEN invoice_amount ELSE 0 END) AS total_revenue,
            SUM(CASE WHEN type = 'expense' THEN invoice_amount ELSE 0 END) AS total_expense
        FROM 
            invoices
    ";

    if ($selected_year) {
        $totals_query .= " WHERE YEAR(invoice_date) = $selected_year";
    }

    $totals_result = $conn->query($totals_query);
    $totals = $totals_result->fetch_assoc();
    $total_revenue = number_format((float)$totals['total_revenue'], 2, ',', '.');
    $total_expense = number_format((float)$totals['total_expense'], 2, ',', '.');
    $net_total = number_format((float)($totals['total_revenue'] - $totals['total_expense']), 2, ',', '.');
    ?>

    <!-- Display Invoice Table -->
    <h2>Invoices</h2>

    <!-- Filter by Year -->
    <form method="get" style="width:25%;margin-bottom: 20px;">
        <label for="year">Filter by Year:</label>
        <select id="year" name="year" onchange="this.form.submit()">
            <option value="">All Years</option>
            <?php
            if ($years_result->num_rows > 0) {
                while ($year_row = $years_result->fetch_assoc()) {
                    $year = $year_row['year'];
                    $selected = ($year == $selected_year) ? 'selected' : '';
                    echo "<option value='$year' $selected>$year</option>";
                }
            }
            ?>
        </select>
    </form>

    <table>
        <tr>
            <th>Invoice ID</th>
            <th>Invoice Name</th>
            <th>Invoice Date</th>
            <th>Invoice Status</th>
            <th>Customer Name</th>
            <th>Type</th>
            <th>Invoice Amount</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $type_label = ($row['type'] === 'revenue') ? 'Revenue (+)' : 'Expense (-)';
                $amount_color = ($row['type'] === 'revenue') ? 'green' : 'red';
                $invoice_amount = number_format((float)$row['invoice_amount'], 2, ',', '.');
                echo "<tr>
                        <td>{$row['invoice_id']}</td>
                        <td>{$row['invoice_name']}</td>
                        <td>{$row['invoice_date']}</td>
                        <td>{$row['invoice_status']}</td>
                        <td>{$row['customer_name']}</td>
                        <td>{$type_label}</td>
                        <td style='color: {$amount_color};'>{$invoice_amount} €</td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No invoices found.</td></tr>";
        }
        ?>
    </table>
    <!-- Display Totals -->
    <div class="totals">
        <h3>Totals</h3>
        <p><strong>Total Revenue:</strong> <span style="color: green;"><?php echo $total_revenue; ?> €</span></p>
        <p><strong>Total Expenses:</strong> <span style="color: red;"><?php echo $total_expense; ?> €</span></p>
        <p><strong>Net Total: <?php echo $net_total; ?> €</strong></p>
    </div>
    <br />
    <br />
    <br />
    <br />
    <br />
    <div class="forms">
        <div class="form-container">
            <h2>Upload Invoice</h2>
            <form action="" method="post" enctype="multipart/form-data">
                <label for="invoice_file">Select Invoice PDF:</label>
                <input type="file" name="invoice_file" id="invoice_file" accept="application/pdf">
                <button type="submit">Upload</button>
            </form>
        </div>
        <div class="form-container">
            <!-- Form to Add a New Invoice -->
            <h2>Add a New Invoice to EÜR</h2>
            <form action="" method="post">
                <label for="type">Invoice Type:</label>
                <select id="type" name="type" required>
                    <option value="revenue">Revenue (+)</option>
                    <option value="expense">Expense (-)</option>
                </select>

                <label for="customer_id">Customer:</label>
                <select id="customer_id" name="customer_id">
                    <option value="">-- No Customer --</option>
                    <?php
                    // Fetch customers from the database
                    $customers_result = $conn->query("SELECT id, company FROM customers ORDER BY company ASC");
                    if ($customers_result->num_rows > 0) {
                        while ($customer = $customers_result->fetch_assoc()) {
                            echo "<option value=\"{$customer['id']}\">{$customer['id']} - {$customer['company']}</option>";
                        }
                    } else {
                        echo "<option value=\"\">No customers found</option>";
                    }
                    ?>
                </select>

                <label for="invoice_file">Select Invoice:</label>
                <select name="invoice_file" id="invoice_file">
                    <?php
                    // Directory containing the invoices
                    $directory = 'invoices/';
                    if (is_dir($directory)) {
                        if ($handle = opendir($directory)) {
                            echo "<option value=\"\">Select invoice</option>";
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

                <label for="custom_invoice_name">Or Enter Custom Invoice Name:</label>
                <input type="text" id="custom_invoice_name" name="custom_invoice_name">

                <label for="invoice_id">Invoice ID:</label>
                <input type="text" id="invoice_id" name="invoice_id" required>

                <label for="invoice_date">Invoice Date:</label>
                <input type="date" id="invoice_date" name="invoice_date" required>

                <label for="invoice_amount">Invoice Amount:</label>
                <input type="number" id="invoice_amount" name="invoice_amount" step="0.01" required>

                <button type="submit">Add Invoice</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
