# Invoice automation and EÜR

## Installation

* Download the latest release and unzip all files.
* Take the database dump mysql_dump.sql and import it into a database
* Copy the file includes/db-sample.php and rename the file to db.php. Add the database connection data to this file.
* Upload all files to a web server
* A cronjob is required for automated mail dispatch. I recommend setting the interval to 5 minutes. The cronjob must point to the file cronjob.php.

Note: There is currently no login, so all information is accessible to everyone. I recommend to implement a basic auth password protection.

## Functionality

* Upload invoices and have them sent automatically at a specific time.
* Create customers and display how much turnover this customer has made.
* Create a simple income statement and see how much revenue you have made in a year.

## Documentation

* tbd.