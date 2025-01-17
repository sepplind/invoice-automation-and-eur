<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512x512.png">
    <link rel="stylesheet" href="style.css">
    <title>Invoice automation  and EÜR - Customers</title>
</head>
<body>
    <div class="container">
        <h1>
            <img src="/favicon-512x512.png" width="26px" height="26px">
            Invoice automation  and EÜR - Customers
        </h1>
        <?php include 'includes/nav.php';
        include 'includes/db.php';
        // Handle adding a new customer
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['company']) && isset($_POST['email'])) {
            $company = $_POST['company'];
            $contact_person = $_POST['contact_person'];
            $street = $_POST['street'];
            $zip = $_POST['zip'];
            $city = $_POST['city'];
            $country = $_POST['country'];
            $mobile = $_POST['mobile'];
            $phone = $_POST['phone'];
            $email = $_POST['email'];
            $url = $_POST['url'];
            $note = $_POST['note'];

            $stmt = $conn->prepare("INSERT INTO customers (company, contact_person, street, zip, city, country, mobile, phone, email, url, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", $company, $contact_person, $street, $zip, $city, $country, $mobile, $phone, $email, $url, $note);

            if ($stmt->execute()) {
                echo "<div class='message-dialog'>Customer added successfully.</div>";
            } else {
                echo "<div class='message-dialog error'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
        // Fetch customers and their total invoice amounts
        $result = $conn->query(
            "SELECT c.*, 
                    COALESCE(SUM(CASE WHEN i.type = 'revenue' THEN i.invoice_amount WHEN i.type = 'expense' THEN -i.invoice_amount ELSE 0 END), 0) AS total_invoices 
             FROM customers c
             LEFT JOIN invoices i ON c.id = i.customer_id
             GROUP BY c.id"
        );
        ?>
        <h2>Customer list</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Company</th>
                <th>Contact Person</th>
                <th>Email</th>
                <th>Total Invoices</th>
                <th>Actions</th>
            </tr>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $total_invoices = number_format((float)$row['total_invoices'], 2, ',', '.');
                    $color = ($row['total_invoices'] >= 0) ? 'green' : 'firebrick';
                    echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['company']}</td>
                        <td>{$row['contact_person']}</td>
                        <td>{$row['email']}</td>
                        <td style='color: {$color};'>{$total_invoices} €</td>
                        <td>
                            <button onclick='viewDetails(" . json_encode($row) . ")'>View Details</button>
                            <form action='' method='post' style='display:inline;'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <button type='submit' name='delete'>Delete</button>
                            </form>
                        </td>
                      </tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No customers found.</td></tr>";
            }
            ?>
        </table>
        <div class="modal-overlay" id="modalOverlay"></div>
        <div class="details-modal" id="detailsModal">
            <button class="close-button" onclick="closeModal()">Close</button>
            <h2>Customer Details</h2>
            <table>
                <tr><th>Field</th><th>Value</th></tr>
                <tbody id="modalContent"></tbody>
            </table>
        </div>
        <br />
        <br />
        <br />
        <br />
        <br />
        <div class="forms">
            <div class="form-container">
                <h2>Add customer</h2>
                <p>(*) = required field</p>
                <form action="" method="post">
                    <label for="company">Company (*):</label>
                    <input type="text" id="company" name="company" required>
                    <label for="contact_person">Contact Person:</label>
                    <input type="text" id="contact_person" name="contact_person">
                    <label for="street">Street:</label>
                    <input type="text" id="street" name="street">
                    <label for="zip">Zip:</label>
                    <input type="text" id="zip" name="zip">
                    <label for="city">City:</label>
                    <input type="text" id="city" name="city">
                    <label for="country">Country:</label>
                    <input type="text" id="country" name="country">
                    <label for="mobile">Mobile:</label>
                    <input type="text" id="mobile" name="mobile">
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone">
                    <label for="email">Email (*):</label>
                    <input type="email" id="email" name="email" required>
                    <label for="url">URL:</label>
                    <input type="text" id="url" name="url">
                    <label for="note">Note:</label>
                    <input type="text" id="note" name="note">
                    <button type="submit">Add customer</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Function to format the key (capitalize the first letter and replace underscores with spaces)
        function formatKey(key) {
            return key
                .replace(/_/g, ' ') // Replace underscores with spaces
                .replace(/(^\w|\s\w)/g, match => match.toUpperCase()); // Capitalize the first letter of each word
        }

        // Function to display customer details in the modal
        function viewDetails(customer) {
            const modalContent = document.getElementById('modalContent');
            modalContent.innerHTML = '';

            // Iterate through the customer's keys and values
            for (let key in customer) {
                const formattedKey = formatKey(key);
                const row = `<tr><td>${formattedKey}</td><td>${customer[key]}</td></tr>`;
                modalContent.innerHTML += row;
            }

            // Show the modal and overlay
            document.getElementById('modalOverlay').style.display = 'block';
            document.getElementById('detailsModal').style.display = 'block';
        }

        // Function to close the modal
        function closeModal() {
            document.getElementById('modalOverlay').style.display = 'none';
            document.getElementById('detailsModal').style.display = 'none';
        }
    </script>
</body>
</html>
