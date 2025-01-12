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
        // Fetch customers
        $result = $conn->query("SELECT * FROM customers");
        ?>
        <h2>Customer list</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>company</th>
                <th>contact_person</th>
                <th>email</th>
                <th>actions</th>
            </tr>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['company']}</td>
                        <td>{$row['contact_person']}</td>
                        <td>{$row['email']}</td>
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
                <form action="" method="post">
                    <label for="company">company:</label>
                    <input type="text" id="company" name="company" required>
                    <label for="contact_person">contact_person:</label>
                    <input type="text" id="contact_person" name="contact_person">
                    <label for="street">street:</label>
                    <input type="text" id="street" name="street">
                    <label for="zip">zip:</label>
                    <input type="text" id="zip" name="zip">
                    <label for="city">city:</label>
                    <input type="text" id="city" name="city">
                    <label for="country">country:</label>
                    <input type="text" id="country" name="country">
                    <label for="mobile">mobile:</label>
                    <input type="text" id="mobile" name="mobile">
                    <label for="phone">phone:</label>
                    <input type="text" id="phone" name="phone">
                    <label for="email">email:</label>
                    <input type="email" id="email" name="email" required>
                    <label for="url">url:</label>
                    <input type="text" id="url" name="url">
                    <label for="note">note:</label>
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
