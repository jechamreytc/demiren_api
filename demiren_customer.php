<?php

include "headers.php";

class Demiren_customer
{

    function customerProfile($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT a.customers_fname, a.customers_lname, a.customers_email, a.customers_phone_number, a.customers_date_of_birth, b.nationality_name, c.customer_identification_attachment_filename, d.customers_online_username, d.customers_online_profile_image, d.customers_online_authentication_status
                FROM tbl_customers AS a
                INNER JOIN tbl_nationality AS b ON b.nationality_id = a.nationality_id
                INNER JOIN tbl_customer_identification AS c ON c.identification_id = a.identification_id
                INNER JOIN tbl_customers_online AS d ON d.customers_online_id = a.customers_online_id
                WHERE a.customers_id = :customers_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":customers_id", $json["customers_id"]);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return json_encode($result);
    }

    function customerUpdateProfile($json)
    {
        include "connection.php";

        try {
            $json = json_decode($json, true);
            if (empty($json["customers_id"])) {
                return 0;
            }

            $customers_id = $json["customers_id"];

            $conn->beginTransaction();

            $stmt = $conn->prepare("
            SELECT a.customers_fname, a.customers_lname, a.customers_phone_number, 
                   b.nationality_name, d.customers_online_username, 
                   a.nationality_id, a.customers_online_id
            FROM tbl_customers AS a
            INNER JOIN tbl_nationality AS b ON b.nationality_id = a.nationality_id
            INNER JOIN tbl_customers_online AS d ON d.customers_online_id = a.customers_online_id
            WHERE a.customers_id = :customers_id
        ");
            $stmt->execute([":customers_id" => $customers_id]);
            $currentData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$currentData) {
                $conn->rollBack();
                return 0;
            }

            if (isset($json["customers_fname"]) && $json["customers_fname"] !== $currentData["customers_fname"]) {
                $stmt = $conn->prepare("
                UPDATE tbl_customers 
                SET customers_fname = :customers_fname 
                WHERE customers_id = :customers_id
            ");
                $stmt->execute([
                    ":customers_fname" => $json["customers_fname"],
                    ":customers_id" => $customers_id
                ]);
            }

            if (isset($json["customers_lname"]) && $json["customers_lname"] !== $currentData["customers_lname"]) {
                $stmt = $conn->prepare("
                UPDATE tbl_customers 
                SET customers_lname = :customers_lname 
                WHERE customers_id = :customers_id
            ");
                $stmt->execute([
                    ":customers_lname" => $json["customers_lname"],
                    ":customers_id" => $customers_id
                ]);
            }

            if (isset($json["customers_phone_number"]) && $json["customers_phone_number"] !== $currentData["customers_phone_number"]) {
                $stmt = $conn->prepare("
                UPDATE tbl_customers 
                SET customers_phone_number = :customers_phone_number 
                WHERE customers_id = :customers_id
            ");
                $stmt->execute([
                    ":customers_phone_number" => $json["customers_phone_number"],
                    ":customers_id" => $customers_id
                ]);
            }

            if (isset($json["nationality_name"]) && $json["nationality_name"] !== $currentData["nationality_name"]) {
                $stmt = $conn->prepare("SELECT nationality_id FROM tbl_nationality WHERE nationality_name = :nationality_name");
                $stmt->execute([":nationality_name" => $json["nationality_name"]]);
                $nationality = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($nationality) {
                    $stmt = $conn->prepare("
                    UPDATE tbl_customers 
                    SET nationality_id = :nationality_id 
                    WHERE customers_id = :customers_id
                ");
                    $stmt->execute([
                        ":nationality_id" => $nationality["nationality_id"],
                        ":customers_id" => $customers_id
                    ]);
                } else {
                    $conn->rollBack();
                    return 0;
                }
            }

            if (isset($json["customers_online_username"]) && $json["customers_online_username"] !== $currentData["customers_online_username"]) {
                $stmt = $conn->prepare("
                UPDATE tbl_customers_online 
                SET customers_online_username = :customers_online_username 
                WHERE customers_online_id = :customers_online_id
            ");
                $stmt->execute([
                    ":customers_online_username" => $json["customers_online_username"],
                    ":customers_online_id" => $currentData["customers_online_id"]
                ]);
            }

            $conn->commit();
            return 1;
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return 0;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return 0;
        }
    }


    function customerChangePassword($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "UPDATE tbl_customers_online SET customers_online_password = :customers_online_password WHERE customers_online_id = :customers_online_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":customers_online_password", $json["customers_online_password"]);
        $stmt->bindParam(":customers_online_id", $json["customers_online_id"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function customerChangeEmail($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "UPDATE tbl_customers_online SET customers_online_email = :customers_online_email WHERE customers_online_id = :customers_online_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":customers_online_email", $json["customers_online_email"]);
        $stmt->bindParam(":customers_online_id", $json["customers_online_id"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function customerChangeAuthenticationStatus($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "UPDATE tbl_customers_online SET customers_online_authentication_status = :customers_online_authentication_status WHERE customers_online_id = :customers_online_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":customers_online_authentication_status", $json["customers_online_authentication_status"]);
        $stmt->bindParam(":customers_online_id", $json["customers_online_id"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function customerForgotPassword($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "UPDATE tbl_customers_online SET customers_online_password = :customers_online_password WHERE customers_online_email = :customers_online_email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":customers_online_password", $json["customers_online_password"]);
        $stmt->bindParam(":customers_online_email", $json["customers_online_email"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function customerBookingNoAccount($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        try {
            $conn->beginTransaction();

            // Insert walk-in customer
            $stmt = $conn->prepare("
                INSERT INTO tbl_customers_walk_in 
                    (customers_walk_in_fname, customers_walk_in_lname, customers_walk_in_email, customers_walk_in_phone_number) 
                VALUES 
                    (:customers_walk_in_fname, :customers_walk_in_lname, :customers_walk_in_email, :customers_walk_in_phone_number)
            ");
            $stmt->bindParam(":customers_walk_in_fname", $json["customers_walk_in_fname"]);
            $stmt->bindParam(":customers_walk_in_lname", $json["customers_walk_in_lname"]);
            $stmt->bindParam(":customers_walk_in_email", $json["customers_walk_in_email"]);
            $stmt->bindParam(":customers_walk_in_phone_number", $json["customers_walk_in_phone_number"]);
            $stmt->execute();
            $walkInCustomerId = $conn->lastInsertId();

            // Insert booking
            $stmt = $conn->prepare("
                INSERT INTO tbl_booking 
                    (customers_id, customers_walk_in_id, booking_status_id, booking_downpayment, booking_checkin_dateandtime, booking_checkout_dateandtime, booking_created_at) 
                VALUES 
                    (NULL, :customers_walk_in_id, 2, :booking_downpayment, :booking_checkin_dateandtime, :booking_checkout_dateandtime, NOW())
            ");
            $stmt->bindParam(":customers_walk_in_id", $walkInCustomerId);
            $stmt->bindParam(":booking_downpayment", $json["booking_downpayment"]);
            $stmt->bindParam(":booking_checkin_dateandtime", $json["booking_checkin_dateandtime"]);
            $stmt->bindParam(":booking_checkout_dateandtime", $json["booking_checkout_dateandtime"]);
            $stmt->execute();
            $bookingId = $conn->lastInsertId();

            // Insert into tbl_booking_room based on room quantity
            $roomtype_id = $json["roomtype_id"];
            $room_count = intval($json["room_count"]);

            for ($i = 0; $i < $room_count; $i++) {
                $stmt = $conn->prepare("
                    INSERT INTO tbl_booking_room 
                        (booking_id, roomtype_id, roomnumber_id) 
                    VALUES 
                        (:booking_id, :roomtype_id, NULL)
                ");
                $stmt->bindParam(":booking_id", $bookingId);
                $stmt->bindParam(":roomtype_id", $roomtype_id);
                $stmt->execute();
            }

            $conn->commit();
            return 1;
        } catch (PDOException $e) {
            $conn->rollBack();
            return 0;
        }
    }

    function customerBookingWithAccount($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        try {
            $conn->beginTransaction();

            // Use existing customer ID
            $existingCustomerId = $json["customers_id"];

            // Insert booking
            $stmt = $conn->prepare("
                INSERT INTO tbl_booking 
                    (customers_id, customers_walk_in_id, booking_status_id, booking_downpayment, booking_checkin_dateandtime, booking_checkout_dateandtime, booking_created_at) 
                VALUES 
                    (:customers_id, NULL, 2, :booking_downpayment, :booking_checkin_dateandtime, :booking_checkout_dateandtime, NOW())
            ");
            $stmt->bindParam(":customers_id", $existingCustomerId);
            $stmt->bindParam(":booking_downpayment", $json["booking_downpayment"]);
            $stmt->bindParam(":booking_checkin_dateandtime", $json["booking_checkin_dateandtime"]);
            $stmt->bindParam(":booking_checkout_dateandtime", $json["booking_checkout_dateandtime"]);
            $stmt->execute();
            $bookingId = $conn->lastInsertId();

            // Insert rooms for the booking
            $roomtype_id = $json["roomtype_id"];
            $room_count = intval($json["room_count"]);

            for ($i = 0; $i < $room_count; $i++) {
                $stmt = $conn->prepare("
                    INSERT INTO tbl_booking_room 
                        (booking_id, roomtype_id, roomnumber_id) 
                    VALUES 
                        (:booking_id, :roomtype_id, NULL)
                ");
                $stmt->bindParam(":booking_id", $bookingId);
                $stmt->bindParam(":roomtype_id", $roomtype_id);
                $stmt->execute();
            }

            $conn->commit();
            return 1;
        } catch (PDOException $e) {
            $conn->rollBack();
            return $e->getMessage();
        }
    }

    // booking history ni customer

    function customerViewBookings($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $bookingCustomerId = $json['booking_customer_id'] ?? 0;

        $sql = "SELECT 
                    a.roomtype_name,
                    e.roomnumber_id,
                    c.booking_downpayment,
                    e.room_beds,
                    e.room_sizes,
                    c.booking_created_at,
                    d.booking_status_name,
                    c.booking_checkin_dateandtime,
                    c.booking_checkout_dateandtime
                FROM tbl_roomtype AS a
                INNER JOIN tbl_booking_room AS b ON b.roomtype_id = a.roomtype_id
                INNER JOIN tbl_booking AS c ON c.booking_id = b.booking_id
                INNER JOIN tbl_booking_status AS d ON d.booking_status_id = c.booking_status_id
                INNER JOIN tbl_rooms AS e ON e.roomtype_id = a.roomtype_id
                WHERE c.customers_id = :bookingCustomerId OR c.customers_walk_in_id = :bookingCustomerId";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':bookingCustomerId', $bookingCustomerId);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return json_encode($result);
    }

    // booking history ni customer

    function customerFeedBack($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "INSERT INTO tbl_customersreviews (customers_id, customersreviews, customersreviews_hospitality_rate,	customersreviews_behavior_rate, customersreviews_facilities_rate, customersreviews_cleanliness_rate, customersreviews_foods_rate ) VALUES (:customers_id, :customersreviews, :customersreviews_hospitality_rate, :customersreviews_behavior_rate, :customersreviews_facilities_rate, :customersreviews_cleanliness_rate, :customersreviews_foods_rate)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":customersreviews", $json["customersreviews"]);
        $stmt->bindParam(":customersreviews_hospitality_rate", $json["customersreviews_hospitality_rate"]);
        $stmt->bindParam(":customersreviews_behavior_rate", $json["customersreviews_behavior_rate"]);
        $stmt->bindParam(":customersreviews_facilities_rate", $json["customersreviews_facilities_rate"]);
        $stmt->bindParam(":customersreviews_cleanliness_rate", $json["customersreviews_cleanliness_rate"]);
        $stmt->bindParam(":customersreviews_foods_rate", $json["customersreviews_foods_rate"]);
        $stmt->bindParam(":customers_id", $json["customers_id"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function customerCancelBooking($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "UPDATE tbl_booking SET booking_status_id = 3 WHERE booking_id = :booking_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":booking_id", $json["booking_id"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function sendEmail($json)
    {
        include "send_email.php";
        $json = json_decode($json, true);


        $emailTo = $json['emailToSent'] ?? null;
        $emailSubject = $json['emailSubject'] ?? "Demiren Hotel & Restaurant";
        $confirmationNumber = $json['confirmationNumber'] ?? "N/A";


        $emailBody = '
            <html>
            <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; background-color: #f9f9f9; padding: 20px; }
                .container { background-color: #fff; border-radius: 10px; padding: 20px; max-width: 600px; margin: auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                h2 { color: #1a73e8; }
                .details { margin-top: 20px; }
                .label { font-weight: bold; color: #555; }
                .value { margin-bottom: 10px; }
                .code { font-size: 20px; font-weight: bold; color: #444; background: #f0f0f0; padding: 10px; border-radius: 5px; text-align: center; margin: 20px 0; }
                .footer { font-size: 12px; color: #777; margin-top: 30px; text-align: center; }
            </style>
            </head>
            <body>
            <div class="container">
                <h2>Booking Confirmation</h2>
                <p>Thank you for choosing <strong>Demiren Hotel & Restaurant</strong>.</p>
                <p>Your booking has been confirmed successfully.</p>
                
                <div class="code">Confirmation #: ABC1234</div>

                <div class="details">
                <p><span class="label">Room Number:</span> Room 205</p>
                <p><span class="label">Check-in Date:</span> May 5, 2025</p>
                <p><span class="label">Check-out Date:</span> May 7, 2025</p>
                </div>

                <p>We look forward to welcoming you!</p>

                <div class="footer">
                This is an automated message. Please do not reply to this email.
                </div>
            </div>
            </body>
            </html>';


        $sendEmail = new SendEmail();
        return $sendEmail->sendEmail($emailTo, $emailSubject, $emailBody);
    }

    function getBookingAllCharges($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        try {
            $stmt = $conn->prepare("
            SELECT 
                CONCAT(e.customers_fname, ' ', e.customers_lname) AS customers_fullname,
                MAX(g.invoice_date) AS invoice_date,
                CONCAT(h.employee_fname, ' ', h.employee_lname) AS employee_fullname,
                SUM(CASE WHEN c.charges_category_id = 1 THEN a.booking_charges_price * a.booking_charges_quantity ELSE 0 END) AS room_charges,
                SUM(CASE WHEN c.charges_category_id = 2 THEN a.booking_charges_price * a.booking_charges_quantity ELSE 0 END) AS food_charges,
                SUM(CASE WHEN c.charges_category_id = 3 THEN a.booking_charges_price * a.booking_charges_quantity ELSE 0 END) AS extra_charges,
                SUM(a.booking_charges_price * a.booking_charges_quantity) AS total_charges
            FROM tbl_booking_charges AS a
            INNER JOIN tbl_charges_master AS b ON b.charges_master_id = a.charges_master_id
            INNER JOIN tbl_charges_category AS c ON c.charges_category_id = b.charges_category_id
            INNER JOIN tbl_booking_room AS d ON d.booking_room_id = a.booking_room_id
            INNER JOIN tbl_booking AS f ON f.booking_id = d.booking_id
            INNER JOIN tbl_customers AS e ON e.customers_id = f.customers_id
            LEFT JOIN tbl_billing AS i ON i.booking_id = f.booking_id
            LEFT JOIN tbl_invoice AS g ON g.billing_id = i.billing_id
            LEFT JOIN tbl_employee AS h ON h.employee_id = g.employee_id
            WHERE a.booking_room_id = :booking_room_id
        ");

            $stmt->bindParam(":booking_room_id", $json["booking_room_id"]);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return json_encode($result);
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }


    function customerDisplayRooms($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $sql = "SELECT 
                a.room_amenities_master_name, 
                c.roomtype_name, 
                c.roomtype_description, 
                c.roomtype_price, 
                b.roomfloor, 
                b.room_capacity, 
                b.room_beds, 
                b.room_sizes, 
                d.status_name
                FROM tbl_room_amenities AS e
                INNER JOIN tbl_room_amenities_master AS a ON a.room_amenities_master_id = e.amenities_room_amenities_master_id
                INNER JOIN tbl_rooms AS b ON b.roomnumber_id = e.amenities_roomnumber_id
                INNER JOIN tbl_roomtype AS c ON c.roomtype_id = b.roomtype_id
                INNER JOIN tbl_status_types AS d ON d.status_id = b.room_status_id
                WHERE e.amenities_roomnumber_id = :amenities_roomnumber_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":amenities_roomnumber_id", $json["amenities_roomnumber_id"]);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($result);
    }

    function addRoomsAmenities($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "INSERT INTO tbl_room_amenities (amenities_roomnumber_id, amenities_room_amenities_master_id) VALUES (:amenities_roomnumber_id, :amenities_room_amenities_master_id)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":amenities_roomnumber_id", $json["amenities_roomnumber_id"]);
        $stmt->bindParam(":amenities_room_amenities_master_id", $json["amenities_room_amenities_master_id"]);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return json_encode($result);
    }

    //New Updates Starting this line 05/06/2025

    function customerRegistration($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        try {
            $conn->beginTransaction();


            $stmt = $conn->prepare("
                INSERT INTO tbl_customers_online (
                    customers_online_username, 
                    customers_online_password, 
                    customers_online_profile_image,
                    customers_online_authentication_status
                ) VALUES (
                    :customers_online_username, 
                    :customers_online_password, 
                    :customers_online_profile_image,
                    0
                )
            ");
            $stmt->bindParam(':customers_online_username', $json['customers_online_username']);
            $stmt->bindParam(':customers_online_password', $json['customers_online_password']);
            $stmt->bindParam(':customers_online_profile_image', $json['customers_online_profile_image']);
            $stmt->execute();
            $customers_online_id = $conn->lastInsertId();


            $stmt = $conn->prepare("
                INSERT INTO tbl_customer_identification (
                    customer_identification_attachment_filename
                ) VALUES (
                    :customer_identification_attachment_filename
                )
            ");
            $stmt->bindParam(':customer_identification_attachment_filename', $json['customer_identification_attachment_filename']);
            $stmt->execute();
            $identification_id = $conn->lastInsertId();


            $stmt = $conn->prepare("
                INSERT INTO tbl_customers (
                    nationality_id,
                    identification_id,
                    customers_online_id,
                    customers_fname,
                    customers_lname,
                    customers_email,
                    customers_phone_number,
                    customers_date_of_birth
                ) VALUES (
                    :nationality_id,
                    :identification_id,
                    :customers_online_id,
                    :customers_fname,
                    :customers_lname,
                    :customers_email,
                    :customers_phone_number,
                    :customers_date_of_birth
                )
            ");
            $stmt->bindParam(':nationality_id', $json['nationality_id']);
            $stmt->bindParam(':identification_id', $identification_id);
            $stmt->bindParam(':customers_online_id', $customers_online_id);
            $stmt->bindParam(':customers_fname', $json['customers_fname']);
            $stmt->bindParam(':customers_lname', $json['customers_lname']);
            $stmt->bindParam(':customers_email', $json['customers_email']);
            $stmt->bindParam(':customers_phone_number', $json['customers_phone_number']);
            $stmt->bindParam(':customers_date_of_birth', $json['customers_date_of_birth']);
            $stmt->execute();

            $conn->commit();
            return 1;
        } catch (PDOException $e) {
            $conn->rollBack();
            return $e->getMessage();
        }
    }

    function customerCurrentBookingsWithAccount($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $bookingCustomerId = $json['booking_customer_id'] ?? 0;

        $sql = "SELECT 
                a.roomtype_name,
                b.roomnumber_id,
                c.booking_downpayment,
                e.room_beds,
                e.room_sizes,
                c.booking_created_at,
                d.booking_status_name,
                c.booking_checkin_dateandtime,
                c.booking_checkout_dateandtime
            FROM tbl_roomtype AS a
            INNER JOIN tbl_booking_room AS b ON b.roomtype_id = a.roomtype_id
            INNER JOIN tbl_booking AS c ON c.booking_id = b.booking_id
            INNER JOIN tbl_booking_status AS d ON d.booking_status_id = c.booking_status_id
            INNER JOIN tbl_rooms AS e ON e.roomtype_id = a.roomtype_id
            WHERE c.customers_id = :bookingCustomerId AND c.booking_status_id IN (1, 2) LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':bookingCustomerId', $bookingCustomerId);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return json_encode($result);
    }

    function customerCurrentBookingsWithoutAccount($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $bookingReferenceNumber = $json['booking_reference_number'] ?? 0;

        $sql = "SELECT 
                a.roomtype_name,
                b.roomnumber_id,
                c.booking_downpayment,
                e.room_beds,
                e.room_sizes,
                c.booking_created_at,
                d.booking_status_name,
                c.booking_checkin_dateandtime,
                c.booking_checkout_dateandtime
            FROM tbl_roomtype AS a
            INNER JOIN tbl_booking_room AS b ON b.roomtype_id = a.roomtype_id
            INNER JOIN tbl_booking AS c ON c.booking_id = b.booking_id
            INNER JOIN tbl_booking_status AS d ON d.booking_status_id = c.booking_status_id
            INNER JOIN tbl_rooms AS e ON e.roomtype_id = a.roomtype_id
            WHERE c.booking_reference_number = :bookingReferenceNumber AND c.booking_status_id IN (1, 2) LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':bookingReferenceNumber', $bookingReferenceNumber);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return json_encode($result);
    }

    function getCurrentBillings($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        try {
            $stmt = $conn->prepare("
            SELECT 
                CONCAT(a.customers_fname, ' ', a.customers_lname) AS customers_fullname,
                MAX(f.invoice_date) AS invoice_date,
                CONCAT(g.employee_fname, ' ', g.employee_lname) AS employee_fullname,
                SUM(CASE WHEN e.charges_category_id = 1 THEN b.booking_charges_price * b.booking_charges_quantity ELSE 0 END) AS room_charges,
                SUM(CASE WHEN e.charges_category_id = 2 THEN b.booking_charges_price * b.booking_charges_quantity ELSE 0 END) AS food_charges,
                SUM(CASE WHEN e.charges_category_id = 3 THEN b.booking_charges_price * b.booking_charges_quantity ELSE 0 END) AS extra_charges,
                SUM(b.booking_charges_price * b.booking_charges_quantity) AS total_charges
            FROM tbl_customers AS a
            INNER JOIN tbl_booking AS j ON j.customers_id = a.customers_id
            INNER JOIN tbl_booking_room AS c ON c.booking_id = j.booking_id
            INNER JOIN tbl_booking_charges AS b ON b.booking_room_id = c.booking_room_id
            INNER JOIN tbl_charges_master AS d ON d.charges_master_id = b.charges_master_id
            INNER JOIN tbl_charges_category AS e ON e.charges_category_id = d.charges_category_id
            LEFT JOIN tbl_billing AS h ON h.booking_id = j.booking_id
            LEFT JOIN tbl_invoice AS f ON f.billing_id = h.billing_id
            LEFT JOIN tbl_employee AS g ON g.employee_id = f.employee_id
            WHERE b.booking_room_id = :booking_room_id
              AND j.booking_status_id IN (1, 2)
        ");

            $stmt->bindParam(":booking_room_id", $json["booking_room_id"]);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return json_encode($result);
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }
}



$json = isset($_POST["json"]) ? $_POST["json"] : "0";
$operation = isset($_POST["operation"]) ? $_POST["operation"] : "0";
$demiren_customer = new Demiren_customer();
switch ($operation) {
    case "customerProfile":
        echo $demiren_customer->customerProfile($json);
        break;
    case "customerUpdateProfile":
        echo $demiren_customer->customerUpdateProfile($json);
        break;
    case "customerChangePassword":
        echo $demiren_customer->customerChangePassword($json);
        break;
    case "customerChangeEmail":
        echo $demiren_customer->customerChangeEmail($json);
        break;
    case "customerForgotPassword":
        echo $demiren_customer->customerForgotPassword($json);
        break;
    case "customerChangeAuthenticationStatus":
        echo $demiren_customer->customerChangeAuthenticationStatus($json);
        break;
    case "customerBookingNoAccount":
        echo $demiren_customer->customerBookingNoAccount($json);
        break;
    case "customerBookingWithAccount":
        echo $demiren_customer->customerBookingWithAccount($json);
        break;
    case "customerFeedBack":
        echo $demiren_customer->customerFeedBack($json);
        break;
    case "customerCancelBooking":
        echo $demiren_customer->customerCancelBooking($json);
        break;
    case "customerViewBookings":
        echo $demiren_customer->customerViewBookings($json);
        break;
    case "sendEmail":
        echo $demiren_customer->sendEmail($json);
        break;
    case "getBookingAllCharges":
        echo $demiren_customer->getBookingAllCharges($json);
        break;
    case "customerDisplayRooms":
        echo $demiren_customer->customerDisplayRooms($json);
        break;
    case "addRoomsAmenities":
        echo $demiren_customer->addRoomsAmenities($json);
        break;
    case "customerRegistration":
        echo $demiren_customer->customerRegistration($json);
        break;
    case "customerCurrentBookingsWithAccount":
        echo $demiren_customer->customerCurrentBookingsWithAccount($json);
        break;
    case "customerCurrentBookingsWithoutAccount":
        echo $demiren_customer->customerCurrentBookingsWithoutAccount($json);
        break;
    case "getCurrentBillings":
        echo $demiren_customer->getCurrentBillings($json);
        break;
    default:
        echo json_encode(["error" => "Invalid operation"]);
        break;
}
