<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

$userId = (int)$_SESSION["user_id"];
$msg = "";
$isOk = true;

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_request"])) {
  $email = trim((string)($_POST["email"] ?? ""));

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $msg = "Enter a valid email.";
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
    $friend = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$friend) {
      $msg = "User not found.";
      $isOk = false;
    } else {
      $friendId = (int)$friend["ID"];

      if ($friendId === $userId) {
        $msg = "You cannot add yourself.";
        $isOk = false;
      } else {
       
        $stmt = $pdo->prepare("
          SELECT 1 FROM can_add
          WHERE Visitor1_ID = ? AND Visitor2_ID = ?
          LIMIT 1
        ");
        $stmt->execute([$userId, $friendId]);

        if ($stmt->fetch()) {
          $msg = "Already friends.";
          $isOk = false;
        } else {
          
          if ((int)$friend["Privacy"] === 0) {
            $stmt = $pdo->prepare("
              INSERT IGNORE INTO can_add (Visitor1_ID, Visitor2_ID)
              VALUES (?, ?)
            ");
            $stmt->execute([$userId, $friendId]);
            $stmt->execute([$friendId, $userId]);

            $msg = "Friend added instantly (public profile).";
            $isOk = true;
          } else {
            $stmt = $pdo->prepare("
              INSERT IGNORE INTO friend_requests (requester_id, receiver_id, status)
              VALUES (?, ?, 'pending')
            ");
            $stmt->execute([$userId, $friendId]);

            $msg = "📩 Friend request sent.";
            $isOk = true;
          }
        }
      }
    }
  }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_group"])) {
  $groupName = trim((string)($_POST["group_name"] ?? ""));
  $members = $_POST["friends"] ?? [];

  if ($groupName === "") {
    $msg = "Group name cannot be empty.";
    $isOk = false;
  } elseif (empty($members)) {
    $msg = "Select at least one friend.";
    $isOk = false;
  } else {
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare("
        INSERT INTO `groups` (`group_name`, `creator_id`)
        VALUES (?, ?)
      ");
      $stmt->execute([$groupName, $userId]);
      $groupId = (int)$pdo->lastInsertId();

      $ins = $pdo->prepare("
        INSERT IGNORE INTO `group_members` (`group_id`, `visitor_id`)
        VALUES (?, ?)
      ");

      foreach ($members as $fid) {
        $ins->execute([$groupId, (int)$fid]);
      }

     
      $ins->execute([$groupId, $userId]);

      $pdo->commit();
      header("Location: start_party.php?group_id=" . $groupId);
exit;

      $isOk = true;
    } catch (Exception $e) {
      $pdo->rollBack();
      $msg = "Failed to create group: " . $e->getMessage();
      $isOk = false;
    }
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_group"])) {
  $groupId = (int)($_POST["group_id"] ?? 0);

  if ($groupId <= 0) {
    $msg = "Invalid group.";
    $isOk = false;
  } else {
    $stmt = $pdo->prepare("SELECT creator_id FROM `groups` WHERE group_id = ? LIMIT 1");
    $stmt->execute([$groupId]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$g) {
      $msg = "Group not found.";
      $isOk = false;
    } elseif ((int)$g["creator_id"] !== $userId) {
      $msg = "You can only delete groups you created.";
      $isOk = false;
    } else {
      try {
        
        $stmt = $pdo->prepare("DELETE FROM `groups` WHERE group_id = ?");
        $stmt->execute([$groupId]);

        $msg = "🗑️ Group deleted.";
        $isOk = true;
      } catch (Exception $e) {
        $msg = "Failed to delete group: " . $e->getMessage();
        $isOk = false;
      }
    }
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
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
  SELECT u.Name, u.Email, fr.created_at
  FROM friend_requests fr
  JOIN user u ON u.ID = fr.receiver_id
  WHERE fr.requester_id = ? AND fr.status = 'pending'
  ORDER BY fr.created_at DESC
");
$stmt->execute([$userId]);
$sent = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("
  SELECT g.group_id, g.group_name, g.creator_id, g.created_at,
         (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.group_id) AS member_count
  FROM `groups` g
  WHERE g.creator_id = ?
  ORDER BY g.created_at DESC
");
$stmt->execute([$userId]);
$myGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
  SELECT g.group_id, g.group_name, g.creator_id, g.created_at,
         (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.group_id) AS member_count
  FROM group_members gm
  JOIN `groups` g ON g.group_id = gm.group_id
  WHERE gm.visitor_id = ? AND g.creator_id <> ?
  ORDER BY g.created_at DESC
");
$stmt->execute([$userId, $userId]);
$memberGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Friends & Groups</title>
  <link rel="stylesheet" href="style.css?v=999">

  <style>
   
    .tabs { display:flex; gap:12px; margin: 10px 0 14px; flex-wrap:wrap; }
    .tab-btn {
      cursor:pointer;
      padding: 10px 12px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(255,255,255,0.08);
      color: #fff;
      font-weight: 800;
    }
    .tab-btn.active {
      border-color: rgba(255,111,183,0.55);
      box-shadow: 0 10px 25px rgba(230,90,163,0.18);
    }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }

    /* Friend picker */
    .group-friend {
      display:flex; align-items:center; gap:10px;
      margin-bottom: 10px;
      padding: 10px 12px;
      border-radius: 14px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.10);
    }
    .group-friend input { width: 18px; height: 18px; }

    
    .group-link {
      color: #ff6fb7;
      font-weight: 900;
      text-decoration: none;
    }
    .group-link:hover {
      text-decoration: underline;
    }

   
    .btn.small { padding: 8px 10px; font-size: 13px; border-radius: 12px; }
  </style>

  <script>
    function showTab(tabId) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      document.getElementById('btn-' + tabId).classList.add('active');
      document.getElementById(tabId).classList.add('active');
    }
  </script>
</head>

<body class="app-body">
  <div class="app-page">
    <div class="app-card">
      <div class="app-title">Friends & Groups</div>
      <div class="app-sub">Manage friends and create groups.</div>

      <?php if ($msg): ?>
        <div class="notice <?= $isOk ? "ok" : "err" ?>"><?= h($msg) ?></div>
      <?php endif; ?>

      <div class="tabs">
        <button id="btn-friendsTab" class="tab-btn active" type="button" onclick="showTab('friendsTab')">Friends</button>
        <button id="btn-groupsTab" class="tab-btn" type="button" onclick="showTab('groupsTab')">Groups</button>
      </div>

    
      <div id="friendsTab" class="tab-panel active">
        <form method="POST">
          <label class="app-label">Friend Email</label>
          <input class="app-input" type="email" name="email" required placeholder="friend@example.com">
          <button class="btn" type="submit" name="send_request">Add / Request</button>
        </form>

        <hr style="margin:18px 0; opacity:.2">

        <h3>Your Friends</h3>
        <?php if (!$friends): ?>
          <p class="app-sub">No friends yet.</p>
        <?php else: ?>
          <ul>
            <?php foreach ($friends as $f): ?>
              <li><?= h((string)$f["Name"]) ?> (<?= h((string)$f["Email"]) ?>)</li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <hr style="margin:18px 0; opacity:.2">

        <h3>Sent Requests</h3>
        <?php if (!$sent): ?>
          <p class="app-sub">No pending sent requests.</p>
        <?php else: ?>
          <ul>
            <?php foreach ($sent as $s): ?>
              <li>
                <?= h((string)$s["Name"]) ?> (<?= h((string)$s["Email"]) ?>)
                — <span class="badge-pending">Pending</span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

     
      <div id="groupsTab" class="tab-panel">
        <h3 style="margin-bottom:8px;">Create Group</h3>

        <form method="POST">
          <label class="app-label">Group Name</label>
          <input class="app-input" type="text" name="group_name" required placeholder="My Best Friends">

          <h3 style="margin:10px 0;">Select Friends</h3>
          <?php if (!$friends): ?>
            <p class="app-sub">Add friends first, then create a group.</p>
          <?php else: ?>
            <?php foreach ($friends as $f): ?>
              <div class="group-friend">
                <input type="checkbox" name="friends[]" value="<?= (int)$f["ID"] ?>">
                <div>
                  <b><?= h((string)$f["Name"]) ?></b>
                  <div class="app-sub" style="margin:0;"><?= h((string)$f["Email"]) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
            <button class="btn" type="submit" name="create_group">Create Group</button>
          <?php endif; ?>
        </form>

        <hr style="margin:18px 0; opacity:.2">

        <h3>My Groups</h3>

        <?php if (!$myGroups && !$memberGroups): ?>
          <p class="app-sub">No groups yet.</p>
        <?php else: ?>

          <?php if ($myGroups): ?>
            <h4 style="margin:10px 0 8px;">Groups you created</h4>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Group</th>
                    <th>Members</th>
                    <th>Created</th>
                    <th>View</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($myGroups as $g): ?>
                    <tr>
                      <td>
                        <a class="group-link"
                           href="group_view.php?group_id=<?= (int)$g["group_id"] ?>">
                          <?= h((string)$g["group_name"]) ?>
                        </a>
                      </td>
                      <td><?= (int)$g["member_count"] ?></td>
                      <td><?= h((string)$g["created_at"]) ?></td>
                      <td>
                        <a class="btn btn-ghost small"
                           href="group_view.php?group_id=<?= (int)$g["group_id"] ?>">
                          View
                        </a>
                      </td>
                      <td>
                        <form method="POST" style="margin:0;"
                              onsubmit="return confirm('Delete this group? Members will be removed too.');">
                          <input type="hidden" name="group_id" value="<?= (int)$g["group_id"] ?>">
                          <button class="btn btn-ghost small" type="submit" name="delete_group">Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <?php if ($memberGroups): ?>
            <h4 style="margin:14px 0 8px;">Groups you are in</h4>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Group</th>
                    <th>Members</th>
                    <th>Created</th>
                    <th>View</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($memberGroups as $g): ?>
                    <tr>
                      <td>
                        <a class="group-link"
                           href="group_view.php?group_id=<?= (int)$g["group_id"] ?>">
                          <?= h((string)$g["group_name"]) ?>
                        </a>
                      </td>
                      <td><?= (int)$g["member_count"] ?></td>
                      <td><?= h((string)$g["created_at"]) ?></td>
                      <td>
                        <a class="btn btn-ghost small"
                           href="group_view.php?group_id=<?= (int)$g["group_id"] ?>">
                          View
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

        <?php endif; ?>
      </div>

      <div style="margin-top:14px;">
        <a class="btn btn-ghost" href="dashboard.php">Back to Dashboard</a>
      </div>
    </div>
  </div>

  <script>
 
    showTab('friendsTab');
  </script>
</body>
</html>
<?php






