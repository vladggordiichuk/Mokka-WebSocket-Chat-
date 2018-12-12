<?php
session_start();

require_once '../inc/config.php';
require_once '../inc/db.php';

if ($_POST['action'] == 'register') {
    $first_name = urlencode($_POST['first_name']);
    $last_name = urlencode($_POST['last_name']);
    $username = urlencode($_POST['username']);
    $password = urlencode($_POST['password']);
    $photo = urlencode($_POST['photo']);

    if (strlen($first_name) > 1) {
        if (strlen($last_name) > 1) {
            if (strlen($username) > 1) {
                if (!count($db->query('SELECT * FROM users WHERE username="' . $username . '"'))) {
                    if (strlen($password) > 1) {
                        if (strlen($photo) > 30) {
                            $id = $db->exec('INSERT INTO `users`(`first_name`, `last_name`, `username`, `password`, `photo`, `lastOnline`) VALUES ("' . $first_name . '", "' . $last_name . '", "' . $username . '", "' . md5($password) . '", "' . $photo . '", "' . time() . '")');
                            $_SESSION['userData']['id'] = $id;
                            $_SESSION['userData']['first_name'] = $first_name;
                            $_SESSION['userData']['last_name'] = $last_name;
                            $_SESSION['userData']['username'] = $username;
                            $_SESSION['userData']['photo'] = $photo;
                            exit(json_encode(array('auth' => $id)));
                        }
                        exit(json_encode(array('error' => 'Upload your photo')));
                    }
                    exit(json_encode(array('error' => 'Enter your password')));
                }
                exit(json_encode(array('error' => 'Entered username already exists')));
            }
            exit(json_encode(array('error' => 'Enter your username')));
        }
        exit(json_encode(array('error' => 'Enter your last name')));
    }
    exit(json_encode(array('error' => 'Enter your first name')));
}

if ($_POST['action'] == 'login') {
    $username = urlencode($_POST['username']);
    $password = urlencode($_POST['password']);

    $userData = $db->query('SELECT * FROM users WHERE username="' . $username . '"');
    if ($userData && md5($password) == $userData[0]['password']) {
        $_SESSION['userData']['id'] = $userData[0]['id'];
        $_SESSION['userData']['first_name'] = $userData[0]['first_name'];
        $_SESSION['userData']['last_name'] = $userData[0]['last_name'];
        $_SESSION['userData']['username'] = $userData[0]['username'];
        $_SESSION['userData']['photo'] = $userData[0]['photo'];
        exit(json_encode(array('auth' => $userData[0]['id'])));
    } else {
        exit(json_encode(array('error' => 'Check your login credentials')));
    }
}

if ($_POST['action'] == 'searchUser') {
    if ($_POST['q']) {
        $q = urlencode($_POST['q']);
        $users = $db->query('SELECT id, first_name, last_name, username, websocket, photo FROM users WHERE username LIKE "%' . $q . '%" AND id!=' . urlencode($_SESSION['userData']['id']) . ' LIMIT 0,5');
        foreach ($users as $key => $user) {
            $users[$key]['id'] = urldecode($user['id']);
            $users[$key]['first_name'] = urldecode($user['first_name']);
            $users[$key]['last_name'] = urldecode($user['last_name']);
            $users[$key]['username'] = urldecode($user['username']);
            $users[$key]['websocket'] = urldecode($user['websocket']);
            $users[$key]['photo'] = urldecode($user['photo']);
        }
        exit(json_encode($users));
    }
}

if ($_POST['action'] == 'searchMsg') {
    if ($_POST['q']) {
        $q = urlencode($_POST['q']);
        $messages = $db->query('SELECT message, timestamp, fromId, toUserId FROM messages WHERE (fromId = ' . $_SESSION['userData']['id'] . ' OR toUserId = ' . $_SESSION['userData']['id'] . ') AND message LIKE "%' . $q . '%" ORDER BY timestamp DESC LIMIT 0,5');
        foreach ($messages as $key => $message) {
            $user = $db->query('SELECT id, websocket, photo FROM users WHERE id = ' . ((int)$message['fromId'] == (int)$_SESSION['userData']['id'] ? $message['toUserId'] : $message['fromId']));
            $messages[$key]['id'] = urldecode($user[0]['id']);
            $messages[$key]['websocket'] = urldecode($user[0]['websocket']);
            $messages[$key]['photo'] = urldecode($user[0]['photo']);
            $messages[$key]['message'] = urldecode($message['message']);
            $messages[$key]['date'] = date('g:ia j M Y', urldecode($message['timestamp']));
        }
        exit(json_encode($messages));
    }
}

if ($_POST['action'] == 'setOnline') {
    if ($_SESSION['userData']) {
        $_SESSION['userData']['websocket'] = urlencode($_POST['websocket']);
        $db->exec('UPDATE users SET websocket=' . urlencode($_POST['websocket']) . ' WHERE id=' . $_SESSION['userData']['id']);
        exit(json_encode(array('status' => 'online')));
    } else 
        exit(json_encode(array('error' => 'noUserData')));   
}

if ($_POST['action'] == 'setOffline') {
    if (isset($_POST['websocket'])) {
        $userId = $db->query('SELECT id FROM users WHERE websocket=' . urlencode($_POST['websocket']));
        $userId = $userId ? $userId[0]['id'] : null;
        $db->exec('UPDATE users SET websocket=0 WHERE websocket=' . urlencode($_POST['websocket']));
        exit(json_encode(array('status' => 'offlineByWebsocket', 'action' => 'userLeft', 'userId' => $userId)));
    } else if ($_SESSION['userData']) {
        $userId = $_SESSION['userData']['id'];
        $db->exec('UPDATE users SET websocket=0 WHERE id=' . urlencode($_SESSION['userData']['id']));
        $_SESSION['userData'] = [];
        exit(json_encode(array('status' => 'offlineByUserId', 'action' => 'userLeft', 'userId' => $userId)));
    } else
        exit(json_encode(array('error' => 'You\'re not logged in')));  
}

if ($_POST['action'] == 'loadMsgs') {
    if ($_POST['type'] == 'user') {
        $msgs = $db->query('SELECT *, (SELECT COUNT(*) FROM `discussions` WHERE msgId=messages.id) AS countDiscussion FROM messages WHERE id IN (SELECT id FROM messages WHERE fromId=' . $_POST["chatId"] . ' AND toUserId=' . $_SESSION["userData"]["id"] . ') OR id IN (SELECT id FROM messages WHERE fromId=' . $_SESSION["userData"]["id"] . ' AND toUserId=' . $_POST["chatId"] . ') ORDER BY timestamp ASC');
        $friend = $db->query('SELECT * FROM users WHERE id=' . $_POST["chatId"])[0];
        $db->exec('UPDATE messages SET seen=1 WHERE fromId=' . $_POST["chatId"] . ' AND toUserId=' . $_SESSION["userData"]["id"]);
        
        exit(json_encode(array('msgs' => $msgs, 'friend' => $friend, 'me' => $_SESSION['userData'])));
    } else if ($_POST['type'] == 'room') {
        $msgs = $db->query('SELECT messages.id, messages.message, messages.timestamp, users.websocket, users.first_name, users.photo, IF(STRCMP(' . $_SESSION['userData']['id'] . ', messages.fromId), "", "me") AS me, (SELECT COUNT(*) FROM `discussions` WHERE msgId=messages.id) AS countDiscussion FROM messages INNER JOIN users ON users.id=messages.fromId WHERE toRoomId=' . $_POST['chatId'] . ' ORDER BY timestamp ASC');
        exit(json_encode($msgs));
    }
}

if ($_POST['action'] == 'sendMsg') {
    if ($_POST['type'] == 'user') {
        if (isset($_POST['forwardedMsgId'])) {
            $forwardUser = $db->query('SELECT * FROM users WHERE id=(SELECT fromId FROM messages WHERE id=' . urlencode($_POST['forwardedMsgId']) . ')')[0];
            $msg = '<b>By @' . $forwardUser['username'] . '</b><br>' . $db->query('SELECT message FROM messages WHERE id=' . urlencode($_POST['forwardedMsgId']))[0]['message'];
        }
        $return = $db->exec('INSERT INTO `messages`(`fromId`, `toUserId`, `toRoomId`, `message`, `seen`, `forward`, `timestamp`) VALUES (' . $_SESSION['userData']['id'] . ', ' . urlencode($_POST['chatId']) . ', -1, "' . (isset($_POST['msg']) ? urlencode($_POST['msg']) : $msg) . '", 0, ' . (isset($_POST['forwardedMsgId']) ? urlencode($_POST['forwardedMsgId']) : '-1') . ', ' . time() . ')');
        $lastId = $db->query('SELECT id FROM messages ORDER BY id DESC;')[0]['id'];
        $to = $db->query('SELECT websocket FROM users WHERE websocket!=0 AND id=' . $_POST['chatId']);
        if ($to)
            exit(json_encode(array('action' => 'msg', 'msg' => (isset($_POST['msg']) ? $_POST['msg'] : $msg), 'msgId' => $lastId, 'from' => $_SESSION['userData'], 'to' => array($to[0]['websocket']), 'type' => 'user', 'toUserData' => $db->query('SELECT * FROM users WHERE id=' . $_POST['chatId'])[0])));
        else
            exit(json_encode(array('action' => 'msg', 'msg' => (isset($_POST['msg']) ? $_POST['msg'] : $msg), 'msgId' => $lastId, 'from' => $_SESSION['userData'], 'type' => 'user', 'toUserData' => $db->query('SELECT * FROM users WHERE id=' . $_POST['chatId'])[0])));
    } else if ($_POST['type'] == 'room') {
        $return = $db->exec('INSERT INTO `messages`(`fromId`, `toUserId`, `toRoomId`, `message`, `seen`, `forward`, `timestamp`) VALUES (' . $_SESSION['userData']['id'] . ', -1, ' . urlencode($_POST['chatId']) . ', "' . urlencode($_POST['msg']) . '", 1, -1, ' . time() . ')');
        $lastId = $db->query('SELECT id FROM messages ORDER BY id DESC;')[0]['id'];
        $roomData = $db->query('SELECT * FROM rooms WHERE id=' . $_POST['chatId'])[0];
        $toWebsockets = $db->query('SELECT users.websocket FROM `roomMembers` INNER JOIN `users` ON roomMembers.userId=users.id WHERE users.websocket!=0 AND roomMembers.roomId=' . $_POST['chatId']);
        $to = array();
        foreach ($toWebsockets as $websocket) {
            if ($websocket['websocket'] != $_SESSION['userData']['websocket'])
            array_push($to, $websocket['websocket']);
        }
        exit(json_encode(array('action' => 'msg', 'msg' => $_POST['msg'], 'chatId' => $_POST['chatId'],'to' => $to, 'msgId' => $lastId, 'from' => $_SESSION['userData'], 'type' => 'room', 'title' => $roomData['name'], 'photo' => $roomData['photo'])));
    }
    exit();
}

if ($_POST['action'] == 'removeMsg') {
    if ($_POST['type'] == 'user') {
        $to = $db->query('SELECT websocket FROM users WHERE id=' . $_POST['chatId']);
        $db->exec('DELETE FROM `discussions` WHERE msgId=' . urlencode($_POST['msgId']));
        $return = $db->exec('DELETE FROM `messages` WHERE id=' . urlencode($_POST['msgId']));
        $lastMsg = $db->query('SELECT message, timestamp FROM messages WHERE id IN (SELECT id FROM messages WHERE fromId=' . urlencode($_POST["chatId"]) . ' AND toUserId=' . $_SESSION["userData"]["id"] . ') OR id IN (SELECT id FROM messages WHERE fromId=' . $_SESSION["userData"]["id"] . ' AND toUserId=' . ($_POST["chatId"]) . ') ORDER BY timestamp DESC');
        $unseen = $db->query('SELECT COUNT(*) FROM messages WHERE id IN (SELECT id FROM messages WHERE fromId=' . $_SESSION["userData"]["id"] . ' AND toUserId=' . ($_POST["chatId"]) . ' AND seen=0)')[0]['COUNT(*)'];
        if ($lastMsg)
            exit(json_encode(array('action' => 'removeMsg', 'type' => $_POST['type'], 'msgId' => $_POST['msgId'], 'unseen' => $unseen, 'fromId' => $_SESSION['userData']['id'], 'to' => array($to[0]['websocket']), 'lastMsg' => ($lastMsg ? $lastMsg[0] : null))));
        else
            exit(json_encode(array('action' => 'removeMsg', 'type' => $_POST['type'], 'msgId' => $_POST['msgId'], 'unseen' => $unseen, 'fromId' => $_SESSION['userData']['id'], 'to' => array($to[0]['websocket']))));
    } else if ($_POST['type'] == 'room') {
        $db->exec('DELETE FROM `discussions` WHERE msgId=' . urlencode($_POST['msgId']));
        $return = $db->exec('DELETE FROM `messages` WHERE id=' . urlencode($_POST['msgId']));
        $lastMsg = $db->query('SELECT message, timestamp FROM messages WHERE toRoomId=' . urlencode($_POST["chatId"]) . ' ORDER BY timestamp DESC');
        $toWebsockets = $db->query('SELECT users.websocket FROM `roomMembers` INNER JOIN `users` ON roomMembers.userId=users.id WHERE users.websocket!=0 AND roomMembers.roomId=' . $_POST['chatId']);
        $to = array();
        foreach ($toWebsockets as $websocket) {
            if ($websocket['websocket'] != $_SESSION['userData']['websocket'])
            array_push($to, $websocket['websocket']);
        }
        if ($lastMsg)
            exit(json_encode(array('action' => 'removeMsg', 'type' => $_POST['type'], 'msgId' => $_POST['msgId'], 'fromId' => $_POST['chatId'], 'to' => $to, 'lastMsg' => ($lastMsg ? $lastMsg[0] : null))));
        else {
            $db->exec('DELETE FROM `rooms` WHERE id=' . urlencode($_POST['chatId']));
            $db->exec('DELETE FROM `roomMembers` WHERE roomId=' . urlencode($_POST['chatId']));
            exit(json_encode(array('action' => 'removeMsg', 'type' => $_POST['type'], 'msgId' => $_POST['msgId'], 'fromId' => $_POST['chatId'], 'to' => $to)));
        }
    }
    exit();
}


if ($_POST['action'] == 'setSeen') {
    $db->exec('UPDATE messages SET seen=1 WHERE fromId=' . $_POST["chatId"] . ' AND toUserId=' . $_SESSION["userData"]["id"]);
    exit();
}


// Create room

if ($_POST['action'] == 'createRoom') {
    if (isset($_POST['title']) && $_POST['title'] && strlen(trim($_POST['title'])) >= 2) {
        if (isset($_POST['photo']) && $_POST['photo'] && strlen($_POST['photo']) > 20) {
            $members = json_decode($_POST['members'], true);
            foreach ($members as $key => $member) {
                if ((int)$member == (int)$_SESSION['userData']['id'])
                    unset($members[$key]);
            }
            if ($members && count($members) > 0) {
                $db->exec('INSERT INTO `rooms`(`name`, `photo`) VALUES ("' . trim($_POST['title']) . '", "' . $_POST['photo'] . '")');
                $lastId = $db->query('SELECT id FROM `rooms` ORDER BY id DESC;')[0]['id'];
                $db->exec('INSERT INTO `roomMembers`(`roomId`, `userId`) VALUES (' . $lastId . ', ' . $_SESSION['userData']['id'] . ')');
                $to = array();
                foreach ($members as $key => $member) {
                    $websocket = $db->query('SELECT websocket FROM `users` WHERE id=' . $member . ' AND websocket!=0');
                    if ($websocket) {
                        array_push($to, $websocket[0]['websocket']);
                    }
                    $db->exec('INSERT INTO `roomMembers`(`roomId`, `userId`) VALUES (' . $lastId . ', ' . $member . ')');
                }
                $db->exec('INSERT INTO `messages`(`fromId`, `toUserId`, `toRoomId`, `message`, `seen`, `forward`, `timestamp`) VALUES (' . $_SESSION['userData']['id'] . ', -1, ' . $lastId . ', "Welcome to our new room!", 1, -1, ' . time() . ')');
                $lastMsgId = $db->query('SELECT id FROM `messages` ORDER BY id DESC;')[0]['id'];
                exit(json_encode(array('action' => 'msg', 'type' => 'room', 'to' => $to, 'chatId' => $lastId, 'title' => $_POST['title'], 'photo' => $_POST['photo'], 'msgId' => $lastMsgId, 'msg' => 'Welcome to our new room!', 'from' => array('first_name' => $_SESSION['userData']['first_name'], 'photo' => $_SESSION['userData']['photo']))));
            } else
                exit(json_encode(array('error' => 'Add some room members')));
        } else
            exit(json_encode(array('error' => 'Pick a photo')));
    } else
        exit(json_encode(array('error' => 'Enter title (at least 2 characters)')));
}




// Discussion
if ($_POST['action'] == 'sendDiscussion') {
    $db->exec('INSERT INTO `discussions`(`msgId`, `fromId`, `message`, `timestamp`) VALUES (' . urlencode($_POST['msgId']) . ', ' . $_SESSION['userData']['id'] . ', "' . urlencode($_POST['discussion']) . '", ' . time() . ')');
    $to = $db->query('SELECT toUserId, websocket FROM `messages` INNER JOIN `users` ON messages.toUserId=users.id WHERE messages.id=' . urlencode($_POST['msgId']));
    if ($to && $to[0] && (int)$to[0]['toUserId'] > 0)
        if ((int)$to[0]['toUserId'] != (int)$_SESSION['userData']['id'])
            $to = array((int)$to[0]['websocket']);
        else
            $to = array((int)$db->query('SELECT websocket FROM `messages` INNER JOIN `users` ON messages.fromId=users.id WHERE messages.id=' . urlencode($_POST['msgId']))[0]['websocket']);
    else {
        $members = $db->query('SELECT userId, websocket FROM `roomMembers` INNER JOIN `users` ON roomMembers.userId=users.id WHERE roomId=(SELECT toRoomId FROM messages WHERE messages.id=' . urlencode($_POST['msgId']) . ')');
        $to = array();
        foreach ($members as $member) {
            if ((int)$member['userId'] != (int)$_SESSION['userData']['id'])
                array_push($to, $member['websocket']);
        }
    }
    exit(json_encode(array('action' => 'discussion', 'msgId' => $_POST['msgId'], 'msg' => $_POST['discussion'], 'userImg' => urldecode($_SESSION['userData']['photo']), 'userFirstName' => urldecode($_SESSION['userData']['first_name']), 'to' => $to)));
}

if ($_POST['action'] == 'loadDiscussion') {
    $msgs = $db->query('SELECT * FROM discussions INNER JOIN `users` ON discussions.fromId=users.id WHERE msgId=' . urlencode($_POST['msgId']) . ' ORDER BY timestamp ASC');
    foreach ($msgs as $key => $msg) {
        if ((int)$msg['fromId'] == (int)$_SESSION['userData']['id'])
            $msgs[$key]['isMe'] = true;
        else 
            $msgs[$key]['isMe'] = false;
    }
    exit(json_encode($msgs));
}