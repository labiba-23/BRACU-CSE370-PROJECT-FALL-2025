<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$message = "";
$isOk = true;

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}


if ($_SERVER["RE=QUEST_METHOD"] === "POST" && isset($_POST["send_request"])) {
    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Enter a valid email.";
        $isOk = false;
    } else {
        $stmt = $pdo->prepare("
            SELECT u.ID, v.Privacy
            FROM user u
            JOIN visitor v ON v.ID = u.ID
            WHERE u.Email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $friend = $stmt->fetch();

        if (!$friend) {
            $message = "User not found.";
            $isOk = false;
        } else {
            $friendId = (int)$friend["ID"];

            if ($friendId === $userId) {
                $message = "You cannot add yourself.";
                $isOk = false;
            } else {
                $stmt = $pdo->prepare("
                    SELECT 1 FROM can_add
                    WHERE Visitor1_ID = ? AND Visitor2_ID = ?
                    LIMIT 1
                ");
                $stmt->execute([$userId, $friendId]);

                if ($stmt->fetch()) {
                    $message = "Already friends.";
                    $isOk = false;
                } else {
                    if ((int)$friend["Privacy"] === 0) {
                        $stmt = $pdo->prepare("
                            INSERT IGNORE INTO can_add (Visitor1_ID, Visitor2_ID)
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$userId, $friendId]);
                        $stmt->execute([$friendId, $userId]);

                        $message = "Friend added successfully.";
                        $isOk = true;
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT IGNORE INTO friend_requests (requester_id, receiver_id, status)
                            VALUES (?, ?, 'pending')
                        ");
                        $stmt->execute([$userId, $friendId]);

                        $message = "Friend request sent.";
                        $isOk = true;
                    }
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_group"])) {
    $group_name = trim($_POST["group_name"] ?? "");
    $members = $_POST["friends"] ?? [];

    if ($group_name === "") {
        $message = "Group name cannot be empty.";
        $isOk = false;
    } elseif (empty($members)) {
        $message = "Select at least one friend.";
        $isOk = false;
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO `groups` (`add-members`, `creator_id`)
            VALUES (?, ?)
        ");
        $stmt->execute([$group_name, $userId]);
        $groupId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO group_members (group_id, visitor_id)
            VALUES (?, ?)
        ");

        foreach ($members as $fid) {
            $stmt->execute([$groupId, (int)$fid]);
        }

        $message = "Group created successfully.";
        $isOk = true;
    }
}


$stmt = $pdo->prepare("
    SELECT u.ID, u.Name, u.Email
    FROM can_add c
    JOIN user u ON u.ID = c.Visitor2_ID
    WHERE c.Visitor1_ID = ?
    ORDER BY u.Name
");
$stmt->execute([$userId]);
$friends = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Friends & Groups</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">

<style>
.tab { display:inline-block; margin-right:12px; cursor:pointer; font-weight:bold; }
.tab-content { display:none; margin-top:12px; }
.tab-active { display:block; }
.tab-selected { color:#0066ff; }

.group-friend {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}

.group-friend input[type="checkbox"] {
    flex: 0 0 20px;
    width: 20px;
    margin: 0;
}

.group-friend label {
    flex: 1 1 auto;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<script>
function showTab(id, el) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('tab-active'));
    document.getElementById(id).classList.add('tab-active');

    document.querySelectorAll('.tab').forEach(t => t.classList.remove('tab-selected'));
    el.classList.add('tab-selected');
}
</script>
</head>

<body>
<div class="container">
<div class="card">

<h1>Friends & Groups</h1>

<?php if ($message): ?>
<div class="<?= $isOk ? 'ok' : 'error' ?>"><?= h($message) ?></div>
<?php endif; ?>

<div>
    <span class="tab tab-selected" onclick="showTab('addFriendTab', this)">Add Friend</span>
    <span class="tab" onclick="showTab('createGroupTab', this)">Create Group</span>
</div>

<div id="addFriendTab" class="tab-content tab-active">
<form method="POST">
    <label>Friend Email</label>
    <input type="email" name="email" required>
    <button type="submit" name="send_request">Add / Request</button>
</form>

<h3>Your Friends</h3>
<ul>
<?php foreach ($friends as $f): ?>
<li><?= h($f["Name"]) ?> — <?= h($f["Email"]) ?></li>
<?php endforeach; ?>
</ul>
</div>

<div id="createGroupTab" class="tab-content">
<form method="POST">
    <label>Group Name</label>
    <input type="text" name="group_name" required>

    <h4>Select Friends</h4>
    <?php foreach ($friends as $f): ?>
    <div class="group-friend">
        <input type="checkbox" name="friends[]" value="<?= (int)$f["ID"] ?>">
        <label><?= h($f["Name"]) ?> — <?= h($f["Email"]) ?></label>
    </div>
    <?php endforeach; ?>

    <button type="submit" name="create_group">Create Group</button>
</form>
</div>

<a href="dashboard.php">Back to Dashboard</a>

</div>
</div>
</body>
</html>
