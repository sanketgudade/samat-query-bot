<?php
// Database configuration
$db_host = 'localhost';
$db_username = 'root'; // Change to your database username
$db_password = 'rajkumar@123@'; // Change to your database password
$db_name = 'hackathon';

// Create connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $message = $conn->real_escape_string($_POST['message']);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Insert into database
        $sql = "INSERT INTO contact_submissions (name, email, message, ip_address) 
                VALUES ('$name', '$email', '$message', '$ip_address')";
        
        if ($conn->query($sql) === TRUE) {
            $success_message = 'Thank you for reaching out! We will get back to you soon....................💬🌟👍🏼';
        } else {
            $error_message = 'Error: ' . $sql . '<br>' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Global styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #007bff, #ff69b4);
            color: #333;
            min-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            background: white;
            padding: 10px;
            border-radius: 16px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .contact-info {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
            width: 100%;
        }

        .contact-box {
            flex: 1 1 calc(25% - 30px);
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            height: auto;
        }

        .contact-box h3 {
            font-size: 1.5rem;
            margin-top: 0;
            margin-bottom: 10px;
            color: #007bff;
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
        }

        .contact-box i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #007bff;
            align-self: center;
        }

        .contact-box p {
            margin: 5px 0;
            font-size: 1.1rem;
            color: #555;
        }

        .contact-form-section {
            width: 50%;
            padding-right: 40px;
        }

        .contact-form {
            text-align: left;
            margin-bottom: 40px;
        }

        .contact-form label {
            font-weight: bold;
            display: block;
            margin-top: 20px;
            font-size: 1.2rem;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 15px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1.1rem;
        }

        .contact-form textarea {
            resize: vertical;
        }

        .contact-form button {
            width: 100%;
            padding: 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2rem;
            margin-top: 25px;
            cursor: pointer;
        }

        .contact-form button:hover {
            background-color: #0056b3;
        }

        .description-section {
            width: 50%;
            padding-left: 40px;
            text-align: left;
            position: relative;
        }

        .description {
            font-size: 1.2rem;
        }

        .description p {
            margin: 15px 0;
            font-size: 1.1rem;
        }

        .social-icons {
            display: flex;
            justify-content: left;
            margin-top: 30px;
        }

        .social-icons a {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 50px;
            height: 50px;
            margin-right: 20px;
            text-decoration: none;
        }

        .social-icons img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }

        .get-in-touch-header {
            font-family: 'Courier New', Courier, monospace;
            font-size: 2rem;
            margin-bottom: 15px;
            color: #007bff;
        }

        .contact-image {
            width: 100%;
            margin-top: 30px;
            text-align: center;
        }

        .contact-image img {
            width: 80%;
            max-width: 400px;
            height: auto;
            border-radius: 12px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }

        .alert-error {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>

<body>
    <div id="particles-js"></div>
    <div class="container">
        <div class="contact-info">
            <div class="contact-box">
                <i class="fas fa-map-marker-alt"></i>
                <h3 class="contact-box-heading">OUR MAIN OFFICE</h3>
                <p>At: Ridhora TQ: Malegaon</p>
                <p>Dist: Washim 444503</p>
            </div>
            <div class="contact-box">
                <i class="fas fa-phone"></i>
                <h3 class="contact-box-heading">PHONE NUMBER</h3>
                <p>234-9876-5400</p>
                <p>888-0123-4567 (Toll Free)</p>
            </div>
            <div class="contact-box">
                <i class="fas fa-fax"></i>
                <h3 class="contact-box-heading">FAX</h3>
                <p>Fax NO</p>
                <p>1-234-567-8900</p>
            </div>
            <div class="contact-box">
                <i class="fas fa-envelope"></i>
                <h3 class="contact-box-heading">EMAIL</h3>
                <p>Email Address</p>
                <p>SmartQuerybot@gmail.com</p>
            </div>
        </div>

        <div class="contact-form-section">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form class="contact-form" id="contactForm" method="POST" action="contact.php">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter a valid email address" required>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" placeholder="Enter your Name" required>
                <label for="message">Message:</label>
                <textarea id="message" name="message" rows="6" placeholder="Enter your message" required></textarea>
                <button type="submit">SUBMIT</button>
            </form>
        </div>

        <div class="description-section">
            <div class="description">
                <h1 class="get-in-touch-header">GET IN TOUCH </h1>
                <p>We're here to help you with any questions or concerns. Whether you need support, have feedback, or just want to say hello, we'd love to hear from you! 💬✨For technical support requests, submit the form with a comprehensive description of the issue.📈✊</p>
                <p><b>Let's make things happen together! 🚀💡</b></p>
            </div>
            <div class="social-icons">
                <a href="https://www.google.com/" class="icon"><img src="google.png" alt="Google"></a>
                <a href="https://www.facebook.com/" class="icon"><img src="facebook.png" alt="Facebook"></a>
                <a href="https://github.com/" class="icon"><img src="github.png" alt="GitHub"></a>
                <a href="https://www.linkedin.com/" class="icon"><img src="linkedin.png" alt="LinkedIn"></a>
            </div>
            <div class="contact-image">
                <img src="contact.png" alt="Contact Image">
            </div>
        </div>
    </div>

    <script>
        particlesJS("particles-js", {
            "particles": {
                "number": {
                    "value": 25,
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": { "value": "#00aaff" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.8, "random": false },
                "size": { "value": 8, "random": true },
                "move": { "enable": true, "speed": 1.5, "direction": "none", "random": false, "straight": false },
                "line_linked": {
                    "enable": true,
                    "distance": 250,
                    "color": "#00aaff",
                    "opacity": 0.5,
                    "width": 2
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": { "enable": true, "mode": "grab" },
                    "onclick": { "enable": true, "mode": "push" }
                }
            }
        });
    </script>
</body>

</html>

<?php
// Close database connection
$conn->close();
?>