<popup forwarder>
    <title></title><close></close>
    <content>
        <input name="peopleSearchForwarder" placeholder="Username" />
        <div class="searchList" style="display: none;"></div>
    </content>
</popup>
<popup room-settings>
    <title>New Room</title><close></close>
    <content>
        <div id="roomImg"><input type="file" accept="image/*" /></div>
        <input name="roomName" placeholder="Room Name" />
        <input name="peopleSearchRoomMember" placeholder="Username" />
        <div class="searchList" style="display: none;"></div>
        <div id="roomMembers"></div>
        <btn create-room>Create Room</btn>
    </content>
</popup>
<discussion>
    <title></title><close></close>
    <window>
        <content>
        </content>
    </window>
    <input placeholder="Write something" />
    <send-translate></send-translate>
    <send></send>
</discussion>
<dashboard class="mobile-active-sidebar">
    <menu>
        <div id="account">
            <div id="pic" style="background-image: url(<?php echo urldecode($_SESSION['userData']['photo']); ?>);"></div>
            <p><?php echo urldecode($_SESSION['userData']['first_name']); ?></p>
        </div>
        <div class="btns">
            <btn action="msgs" class="active"><!-- <alert>99+</alert> --></btn>
            <!-- <btn action="contacts"><alert>12</alert></btn> -->
            <!-- <btn action="settings"></btn> -->
            <btn action="signout"></btn>
        </div>
    </menu>
    <sidebar>
        <switcher></switcher>
        <section rooms>
            <h1>Rooms</h1>
            <toggle class="active" listId="1"></toggle>
            <btn class="active" create-room>Create Room</btn>
            <list class="active" listId="1">
                <?php
                    $roomChats = $db->query('SELECT * FROM messages INNER JOIN rooms ON messages.toRoomId=rooms.id WHERE messages.id IN (SELECT MAX(id) FROM messages WHERE messages.toUserId=-1 AND ' . $_SESSION['userData']['id'] . ' IN (SELECT roomMembers.userId FROM roomMembers WHERE roomMembers.roomId=messages.toRoomId) GROUP BY messages.toRoomId) ORDER BY messages.timestamp DESC');
                    foreach ($roomChats as $key => $roomChat) {
                        echo '<item chat-id="' . $roomChat['toRoomId'] . '" type="room">
                                <delete></delete>
                                <photo style="background-image: url(' . $roomChat['photo'] . ');"></photo>
                                <data>
                                    <name>' . urldecode($roomChat['name']) . '</name>
                                    <msg>' . urldecode($roomChat['message']) . '</msg>
                                    <date>' . date('g:ia j M Y', (int)$roomChat['timestamp']) . '</date>
                                </data>
                            </item>';
                    }
                ?>
            </list>
        </section>
        <section people>
            <h1>On hand talk</h1>
            <toggle class="active" listId="2"></toggle>
            <input class="active" type="text" search="people" placeholder="Username" />
            <div user class="searchList" style="display: none;"></div>
            <input class="active" type="text" search="message" placeholder="Message" />
            <div message class="searchList" style="display: none;"></div>
            <list class="active" listId="2">
            <?php
            $chats = $db->query('SELECT * FROM messages WHERE id IN ( SELECT MAX(id) FROM messages WHERE (fromId=' . $_SESSION['userData']['id'] . ' OR toUserId=' . $_SESSION['userData']['id'] . ') AND toRoomId=-1 GROUP BY fromId ) OR id IN ( SELECT MAX(id) FROM messages WHERE (fromId=' . $_SESSION['userData']['id'] . ' OR toUserId=' . $_SESSION['userData']['id'] . ') AND toRoomId=-1 GROUP BY toUserId ) ORDER BY timestamp DESC');
            $newChats = [];
            foreach ($chats as $key => $chat) {
                if ($chat['fromId'] == $_SESSION['userData']['id']) {
                    if (isset($newChats[$chat['toUserId']])) {
                        if ($newChats[$chat['toUserId']]['timestamp'] < $chat['timestamp'])
                            $newChats[$chat['toUserId']] = $chat;
                    } else
                        $newChats[$chat['toUserId']] = $chat;
                } else {
                    if (isset($newChats[$chat['fromId']])) {
                        if ($newChats[$chat['fromId']]['timestamp'] < $chat['timestamp'])
                            $newChats[$chat['fromId']] = $chat;
                    } else
                        $newChats[$chat['fromId']] = $chat;
                }
            }
            $chats = $newChats;
            foreach ($chats as $chat) {
                if ($chat['fromId'] == $_SESSION['userData']['id'])
                    $userData = $db->query('SELECT * FROM users WHERE id=' . $chat['toUserId'])[0];
                else
                    $userData = $db->query('SELECT * FROM users WHERE id=' . $chat['fromId'])[0];
                
                $notSeen = (int)$db->query('SELECT COUNT(id) FROM `messages` WHERE seen=0 AND fromId=' . $userData['id'] . ' AND toUserId=' . $_SESSION['userData']['id'])[0]['COUNT(id)'];
                $notSeen = ($notSeen < 100 ? $notSeen : '99+');
                ?>
                <item class="<?php echo ($userData['websocket'] > 0 ? 'online' : ''); ?>" type="user" chat-id="<?php echo $userData['id']; ?>"><delete></delete><?php echo ($notSeen !=0 ? "<alert>" . $notSeen . "</alert>" : "" ); ?><photo style="background-image: url(<?php echo urldecode($userData['photo']); ?>);"></photo><data><name><?php echo urldecode($userData['first_name']) . ' ' . urldecode($userData['last_name']); ?></name><msg><?php echo urldecode($chat['message']); ?></msg><date><?php echo date('g:ia j M Y', $chat['timestamp']); ?></date></data></item>
                <?
            }
            ?>
            </list>
        </section>
    </sidebar>
    <chat>
        <overflow>
            <banner></banner>
            <p>Tap on the right to start conversation</p>
        </overflow>
        <header>
            <name></name>
            <settings></settings>
        </header>
        <window>
            <content>
            </content>
        </window>
        <input placeholder="Write something" />
        <send-translate></send-translate>
        <send></send>
    </chat>
</dashboard>