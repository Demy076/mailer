<?php

require_once dirname(__FILE__) . '/vendor/autoload.php';

if (!empty($_POST["submitEmail"])) {
    $mailerInstance = (new \Demy\mailer([
        "host" => "",
        "port" => NULL,
        "username" => "",
        "password" => "",
        "isHTML" => false,
        "from" => "",
        "to" => substr_count($_POST["email"], ",") > 0 ? explode(",", trim($_POST["email"])) : $_POST["email"],
        "subject" => $_POST["about"] ?? "",
        "body" => $_POST["message"] ?? ""

    ]))->sendMail();
    if (isset($mailerInstance["status"]) && !empty($mailerInstance["status"])) {
        if ($mailerInstance["status"] == "success") {
            echo "Success {$mailerInstance["message"]}";
        } else {
            echo "Error: {$mailerInstance["message"]}";
        }
    }
}
?>
<html>

<head>
    <title>PHPMailer</title>
</head>

<body>
    <form method="post">
        <input type="text" name="email" placeholder="Email" required>
        <input type="text" name="about" placeholder="About" required>
        <textarea name="message" placeholder="Message" required></textarea>
        <button name="submitEmail" value="Send email">Stuur email</button>
    </form>
</body>

</html>