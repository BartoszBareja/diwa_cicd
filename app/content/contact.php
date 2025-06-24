<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = array();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    }

    $name = trim($_POST['name'] ?? '');
    if (strlen($name) < 3 || strlen($name) > 100) {
        $errors[] = 'Your name must be between 3 and 100 characters.';
    }

    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) {
        $errors[] = 'Please enter a valid E-Mail address.';
    }

    $recipients = $_POST['recipients'] ?? [];
    if (!is_array($recipients) || count($recipients) === 0) {
        $errors[] = 'Please select at least one recipient.';
    }

    $message = trim($_POST['message'] ?? '');
    if (strlen($message) < 3 || strlen($message) > 1000) {
        $errors[] = 'Your message must be between 3 and 1000 characters.';
    }

    if (empty($errors)) {
        $useMailFunction = ($config['site']['use_mail_function'] ?? false);
        $logCount = 0;
        $mailCount = 0;

        foreach ($recipients as $recipient) {
            $recipient = trim($recipient);

            @file_put_contents(
                ROOT_PATH . '/logs/mail.log',
                '[=== ' . date('Y-m-d H:i:s') . ' ===]' . PHP_EOL .
                'TO: ' . $recipient . PHP_EOL .
                'FROM: ' . $name . ' <' . $email . '>' . PHP_EOL .
                'MESSAGE:' . PHP_EOL . $message . PHP_EOL . PHP_EOL,
                FILE_APPEND
            );

            if ($useMailFunction) {
                $headers = 'From: ' . $name . ' <' . $email . '>';
                if (@mail($recipient, 'New Message from DIWA', $message, $headers)) {
                    $mailCount++;
                }
            }
        }

        if ($useMailFunction && $mailCount === 0) {
            $errors[] = 'Your message could not be sent to any recipient.';
        } elseif ($useMailFunction && $mailCount !== count($recipients)) {
            redirect('?page=messagesent&message=' . urlencode("Message sent to $mailCount of " . count($recipients) . " recipients."));
        } else {
            redirect('?page=messagesent');
        }
    }
}

try {
    $resultAdmins = $model->getAllAdmins();
    if (!$resultAdmins || count($resultAdmins) === 0) {
        error(500, 'Could not determine all admin accounts');
    }
} catch (Exception $ex) {
    error(500, 'Could not query admins from Database', $ex);
}
?>

<div class="row">
    <div class="col-lg-12">
        <h1>Contact an Admin</h1>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php echo implode('<br/>', array_map('htmlspecialchars', $errors)); ?></div>
        <?php endif; ?>
        <form action="?page=contact" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label for="name">Your Name:</label>
                <input type="text" class="form-control" name="name" id="name"
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" maxlength="100">
            </div>
            <div class="form-group">
                <label for="email">Your E-Mail Address:</label>
                <input type="email" class="form-control" name="email" id="email"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" maxlength="100">
            </div>
            <div class="form-group">
                <p><strong>Recipients:</strong></p>
                <div class="btn-group">
                    <button type="button" class="btn btn-default select-all-admins">Select all</button>
                    <button type="button" class="btn btn-default unselect-all-admins">Unselect all</button>
                </div>
                <?php foreach ($resultAdmins as $admin): ?>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" class="select-admin"
                                   name="recipients[]" value="<?php echo htmlspecialchars($admin['email']); ?>"
                                <?php echo (isset($_POST['recipients']) && in_array($admin['email'], $_POST['recipients'])) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($admin['username']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-group">
                <label for="message">Your Message:</label>
                <textarea class="form-control" rows="5" name="message" id="message"
                          maxlength="1000"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo icon('envelope'); ?> Send Message</button>
        </form>
    </div>
</div>
